<?php
// API Inventory Handler: GET and POST for current blood stock counts
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/jwt.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Requires any authenticated user
    requireAuth();

    try {
        $stmt = $db->query("SELECT * FROM blood_inventory ORDER BY id DESC LIMIT 1");
        $inventory = $stmt->fetch();
        
        if (!$inventory) {
            $inventory = [
                'aPos' => 0, 'aNeg' => 0,
                'bPos' => 0, 'bNeg' => 0,
                'oPos' => 0, 'oNeg' => 0,
                'abPos' => 0, 'abNeg' => 0,
                'platelets' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $inventory
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to load inventory: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Requires updater or admin
    $user = requireAuth(['updater', 'admin']);

    // Parse input data
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    // Validate inputs (cast to int)
    $aPos      = isset($data['aPos']) ? (int)$data['aPos'] : null;
    $aNeg      = isset($data['aNeg']) ? (int)$data['aNeg'] : null;
    $bPos      = isset($data['bPos']) ? (int)$data['bPos'] : null;
    $bNeg      = isset($data['bNeg']) ? (int)$data['bNeg'] : null;
    $oPos      = isset($data['oPos']) ? (int)$data['oPos'] : null;
    $oNeg      = isset($data['oNeg']) ? (int)$data['oNeg'] : null;
    $abPos     = isset($data['abPos']) ? (int)$data['abPos'] : null;
    $abNeg     = isset($data['abNeg']) ? (int)$data['abNeg'] : null;
    $platelets = isset($data['platelets']) ? (int)$data['platelets'] : null;

    if ($aPos === null || $aNeg === null || $bPos === null || $bNeg === null || $oPos === null || $oNeg === null || $abPos === null || $abNeg === null || $platelets === null) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'All inventory parameters are required.']);
        exit;
    }

    try {
        // Check if there is an existing inventory record
        $check = $db->query("SELECT id FROM blood_inventory LIMIT 1")->fetch();
        
        if ($check) {
            // Update
            $stmt = $db->prepare("UPDATE blood_inventory SET aPos = ?, aNeg = ?, bPos = ?, bNeg = ?, oPos = ?, oNeg = ?, abPos = ?, abNeg = ?, platelets = ? WHERE id = ?");
            $stmt->execute([$aPos, $aNeg, $bPos, $bNeg, $oPos, $oNeg, $abPos, $abNeg, $platelets, $check['id']]);
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO blood_inventory (aPos, aNeg, bPos, bNeg, oPos, oNeg, abPos, abNeg, platelets) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$aPos, $aNeg, $bPos, $bNeg, $oPos, $oNeg, $abPos, $abNeg, $platelets]);
        }

        // Log the change
        $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Update Inventory', ?)");
        $audit->execute([
            $user['id'], 
            "Blood inventory manually updated. A+: $aPos, A-: $aNeg, B+: $bPos, B-: $bNeg, O+: $oPos, O-: $oNeg, AB+: $abPos, AB-: $abNeg, Platelets: $platelets"
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Inventory updated successfully.'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update inventory: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
