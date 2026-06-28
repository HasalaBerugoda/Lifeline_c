<?php
// API Donations Handler: GET (fetch history) and POST (log new donation)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/jwt.php';
require_once __DIR__ . '/../includes/csv_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tokenPayload = requireAuth();
    
    // Determine target user
    $targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$tokenPayload['id'];
    
    // Authorization check: owner or admin only
    if ((int)$tokenPayload['id'] !== $targetUserId && $tokenPayload['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: You cannot access another user\'s donations.']);
        exit;
    }

    try {
        // Fetch donations
        $stmt = $db->prepare("SELECT * FROM donations WHERE user_id = ? ORDER BY donation_date DESC");
        $stmt->execute([$targetUserId]);
        $donations = $stmt->fetchAll();

        // Calculate summary stats
        $totalDonations = count($donations);
        $totalVolume = 0;
        $lastDonationDate = null;
        
        if ($totalDonations > 0) {
            foreach ($donations as $d) {
                $totalVolume += (int)$d['volume_ml'];
            }
            $lastDonationDate = $donations[0]['donation_date'];
        }

        $livesSavedEstimate = $totalDonations * 3; // Standard blood bank heuristic: 1 donation can save up to 3 lives

        echo json_encode([
            'status' => 'success',
            'data' => $donations,
            'summary' => [
                'total_donations' => $totalDonations,
                'total_volume_ml' => $totalVolume,
                'lives_saved_estimate' => $livesSavedEstimate,
                'last_donation_date' => $lastDonationDate
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve donations: ' . $e->getMessage()]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Requires updater or admin
    $tokenPayload = requireAuth(['updater', 'admin']);

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    // Parameters
    $donorInput   = trim($data['donor_id'] ?? ''); // Can be ID or Donor Number e.g. LL-0001
    $bloodType    = trim($data['blood_type'] ?? '');
    $volumeMl     = isset($data['volume_ml']) ? (int)$data['volume_ml'] : 0;
    $location     = trim($data['location'] ?? 'Central Clinic');
    $hemoglobin   = isset($data['hemoglobin']) ? (float)$data['hemoglobin'] : null;
    $bloodPressure= trim($data['blood_pressure'] ?? '');
    $weight       = isset($data['weight']) ? (float)$data['weight'] : null;
    $donationDate = trim($data['donation_date'] ?? date('Y-m-d'));

    if (empty($donorInput) || empty($bloodType) || $volumeMl <= 0 || empty($donationDate)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Donor ID/Number, Blood Type, Volume, and Date are required.']);
        exit;
    }

    try {
        // 1. Look up donor by ID or Donor Number
        $donor = null;
        if (is_numeric($donorInput)) {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([(int)$donorInput]);
            $donor = $stmt->fetch();
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE donor_number = ?");
            $stmt->execute([$donorInput]);
            $donor = $stmt->fetch();
        }

        if (!$donor) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Donor not found. Please verify the Donor ID/Number.']);
            exit;
        }

        if ($donor['role'] === 'revoked') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Cannot log donation: This donor\'s status is revoked.']);
            exit;
        }

        // 2. Insert into donations database table
        $insStmt = $db->prepare("INSERT INTO donations (user_id, blood_type, volume_ml, location, hemoglobin, blood_pressure, weight, donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insStmt->execute([
            (int)$donor['id'],
            $bloodType,
            $volumeMl,
            $location,
            $hemoglobin,
            $bloodPressure,
            $weight,
            $donationDate
        ]);
        
        $donationId = $db->lastInsertId();

        // 3. Append to CSV file
        $bagId = appendDonationToCSV($donor['fullName'], $bloodType, $volumeMl, $location, $donationDate);

        // 4. Sync: update the blood_inventory table
        // Map blood type to database column name
        $columnMap = [
            'A+'  => 'aPos', 'A-'  => 'aNeg',
            'B+'  => 'bPos', 'B-'  => 'bNeg',
            'O+'  => 'oPos', 'O-'  => 'oNeg',
            'AB+' => 'abPos', 'AB-' => 'abNeg'
        ];

        if (array_key_exists($bloodType, $columnMap)) {
            $colName = $columnMap[$bloodType];
            
            // Check if there is an existing inventory record
            $check = $db->query("SELECT id, $colName FROM blood_inventory LIMIT 1")->fetch();
            if ($check) {
                $newCount = (int)$check[$colName] + 1;
                $upStmt = $db->prepare("UPDATE blood_inventory SET $colName = ? WHERE id = ?");
                $upStmt->execute([$newCount, $check['id']]);
            } else {
                $upStmt = $db->prepare("INSERT INTO blood_inventory ($colName) VALUES (1)");
                $upStmt->execute();
            }
        }

        // 5. Add audit log
        $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Log Donation', ?)");
        $audit->execute([
            $tokenPayload['id'], 
            "Donation logged for donor " . $donor['fullName'] . " ($donorInput). Bag ID: $bagId. Volume: {$volumeMl}ml. Blood Type: $bloodType."
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Donation logged successfully and CSV synchronized.',
            'data' => [
                'donation_id' => $donationId,
                'bag_id' => $bagId,
                'donor_name' => $donor['fullName'],
                'blood_type' => $bloodType,
                'volume_ml' => $volumeMl
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to log donation: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
