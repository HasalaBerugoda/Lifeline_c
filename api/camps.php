<?php
// API Camps Handler: CRUD, Registrations, and Participants
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/jwt.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Helper to parse input
function getRequestData() {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    return $_POST;
}

if ($method === 'GET') {
    if ($action === 'all') {
        // Requires updater/admin
        requireAuth(['updater', 'admin']);
        try {
            $stmt = $db->query("SELECT c.*, u.fullName as creator_name, (SELECT COUNT(*) FROM camp_registrations cr WHERE cr.camp_id = c.id) as registered_count FROM camps c LEFT JOIN users u ON c.created_by = u.id ORDER BY c.date DESC, c.time DESC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'my_registrations') {
        // Requires any authenticated user
        $user = requireAuth();
        try {
            $stmt = $db->prepare("SELECT c.*, cr.registered_at, cr.attended FROM camp_registrations cr JOIN camps c ON cr.camp_id = c.id WHERE cr.user_id = ? ORDER BY c.date ASC");
            $stmt->execute([(int)$user['id']]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'participants') {
        // Requires updater/admin
        requireAuth(['updater', 'admin']);
        $campId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($campId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Camp ID is required.']);
            exit;
        }
        try {
            $stmt = $db->prepare("SELECT cr.id as registration_id, cr.registered_at, cr.attended, u.id as user_id, u.donor_number, u.fullName, u.email, u.phone, u.bloodType FROM camp_registrations cr JOIN users u ON cr.user_id = u.id WHERE cr.camp_id = ? ORDER BY cr.registered_at DESC");
            $stmt->execute([$campId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        // Public upcoming camps list
        try {
            $stmt = $db->query("SELECT c.*, COALESCE(COUNT(cr.id), 0) as registered_count FROM camps c LEFT JOIN camp_registrations cr ON c.id = cr.camp_id WHERE c.date >= CURDATE() GROUP BY c.id ORDER BY c.date ASC, c.time ASC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
} elseif ($method === 'POST') {
    if ($action === 'register') {
        // Register logged-in user for a camp
        $user = requireAuth();
        $campId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($campId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Camp ID is required.']);
            exit;
        }

        try {
            // Verify camp exists
            $campCheck = $db->prepare("SELECT id FROM camps WHERE id = ?");
            $campCheck->execute([$campId]);
            if (!$campCheck->fetch()) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Camp not found.']);
                exit;
            }

            // Register
            $stmt = $db->prepare("INSERT INTO camp_registrations (camp_id, user_id) VALUES (?, ?)");
            $stmt->execute([$campId, (int)$user['id']]);

            // Audit
            $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Register Camp', ?)");
            $audit->execute([(int)$user['id'], "Registered for camp ID: $campId"]);

            echo json_encode(['status' => 'success', 'message' => 'Successfully registered for this donation camp!']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate entry)
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'You are already registered for this camp.']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
            }
        }
    } elseif ($action === 'toggle_attendance') {
        // Toggle participant attendance (updater/admin)
        requireAuth(['updater', 'admin']);
        $data = getRequestData();
        $registrationId = isset($data['registration_id']) ? (int)$data['registration_id'] : 0;
        $attended = isset($data['attended']) ? (bool)$data['attended'] : false;

        if ($registrationId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Registration ID is required.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE camp_registrations SET attended = ? WHERE id = ?");
            $stmt->execute([$attended ? 1 : 0, $registrationId]);
            echo json_encode(['status' => 'success', 'message' => 'Attendance status updated.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        // Create camp (updater/admin)
        $user = requireAuth(['updater', 'admin']);
        $data = getRequestData();

        $name        = trim($data['name'] ?? '');
        $date        = trim($data['date'] ?? '');
        $time        = trim($data['time'] ?? '');
        $location    = trim($data['location'] ?? '');
        $organizer   = trim($data['organizer'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($name) || empty($date) || empty($time) || empty($location) || empty($organizer)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Camp Name, Date, Time, Location, and Organizer are required.']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO camps (name, date, time, location, organizer, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $date, $time, $location, $organizer, $description, (int)$user['id']]);
            $campId = $db->lastInsertId();

            // Log camp creation
            $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Create Camp', ?)");
            $audit->execute([(int)$user['id'], "Created camp: $name at $location on $date"]);

            // Send automatic emails to all donors
            $donorStmt = $db->query("SELECT email, fullName FROM users WHERE role = 'donor'");
            $donors = $donorStmt->fetchAll();

            $successCount = 0;
            $failCount = 0;

            if (count($donors) > 0) {
                // Email template details
                $formattedDate = date('F j, Y', strtotime($date));
                $formattedTime = date('g:i A', strtotime($time));
                
                $subject = "New Blood Donation Camp: " . $name;
                
                // Formulate HTML Email
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: LifeLine Blood Bank <no-reply@lifelinebloodbank.com>\r\n";

                $emailLogStmt = $db->prepare("INSERT INTO email_log (recipient, subject, status, error_msg) VALUES (?, ?, ?, ?)");

                foreach ($donors as $d) {
                    $to = $d['email'];
                    $donorName = $d['fullName'];

                    $body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; background-color: #f3f4f6; padding: 20px; color: #111827;'>
                        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);'>
                            <div style='background-color: #e63946; padding: 30px; text-align: center; color: #ffffff;'>
                                <h1 style='margin: 0; font-size: 28px; font-weight: bold;'>LifeLine</h1>
                                <p style='margin: 5px 0 0 0; font-size: 16px; opacity: 0.9;'>Every Drop Saves a Life</p>
                            </div>
                            <div style='padding: 30px;'>
                                <h2 style='color: #111827; margin-top: 0;'>Hello $donorName,</h2>
                                <p style='font-size: 16px; line-height: 1.6; color: #4b5563;'>A new blood donation camp has been scheduled near you! Your contribution can make a massive impact. Here are the details:</p>
                                
                                <div style='background-color: #f9fafb; border-left: 4px solid #e63946; padding: 20px; margin: 20px 0; border-radius: 4px;'>
                                    <p style='margin: 0 0 10px 0; font-size: 16px;'><strong>Camp:</strong> $name</p>
                                    <p style='margin: 0 0 10px 0; font-size: 16px;'><strong>Date:</strong> $formattedDate</p>
                                    <p style='margin: 0 0 10px 0; font-size: 16px;'><strong>Time:</strong> $formattedTime</p>
                                    <p style='margin: 0 0 10px 0; font-size: 16px;'><strong>Location:</strong> $location</p>
                                    <p style='margin: 0; font-size: 16px;'><strong>Organizer:</strong> $organizer</p>
                                </div>

                                " . (!empty($description) ? "<p style='font-size: 15px; font-style: italic; color: #6b7280; margin-bottom: 20px;'>\"$description\"</p>" : "") . "
                                
                                <div style='text-align: center; margin-top: 30px;'>
                                    <a href='http://localhost/donation-camps.php' style='background-color: #e63946; color: #ffffff; text-decoration: none; padding: 12px 30px; font-size: 16px; font-weight: bold; border-radius: 30px; display: inline-block; box-shadow: 0 4px 6px rgba(230, 57, 70, 0.2);'>Register for Camp</a>
                                </div>
                            </div>
                            <div style='background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb;'>
                                &copy; " . date('Y') . " LifeLine Blood Bank. This is an automated notification.
                            </div>
                        </div>
                    </body>
                    </html>";

                    // Send email using PHP mail()
                    // Since local environment might not support real sending, we check return value and log errors gracefully
                    $mailSent = @mail($to, $subject, $body, $headers);
                    
                    if ($mailSent) {
                        $successCount++;
                        $emailLogStmt->execute([$to, $subject, 'sent', null]);
                    } else {
                        $failCount++;
                        $errorMsg = "PHP mail() returned false. Check local SMTP server configuration.";
                        $emailLogStmt->execute([$to, $subject, 'failed', $errorMsg]);
                    }
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => "Camp created. " . ($successCount + $failCount) . " donors notified by email ($successCount succeeded, $failCount failed to deliver due to local server limitations)."
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create camp: ' . $e->getMessage()]);
        }
    }
} elseif ($method === 'PUT') {
    // Edit camp details
    requireAuth(['updater', 'admin']);
    $campId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($campId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Camp ID is required.']);
        exit;
    }

    $data = getRequestData();
    $name        = trim($data['name'] ?? '');
    $date        = trim($data['date'] ?? '');
    $time        = trim($data['time'] ?? '');
    $location    = trim($data['location'] ?? '');
    $organizer   = trim($data['organizer'] ?? '');
    $description = trim($data['description'] ?? '');

    if (empty($name) || empty($date) || empty($time) || empty($location) || empty($organizer)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Fields cannot be empty.']);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE camps SET name = ?, date = ?, time = ?, location = ?, organizer = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $date, $time, $location, $organizer, $description, $campId]);

        echo json_encode(['status' => 'success', 'message' => 'Camp updated successfully.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
    }
} elseif ($method === 'DELETE') {
    // Delete camp (updater/admin)
    $user = requireAuth(['updater', 'admin']);
    $campId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($campId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Camp ID is required.']);
        exit;
    }

    try {
        // Fetch camp name for audit
        $fetchName = $db->prepare("SELECT name FROM camps WHERE id = ?");
        $fetchName->execute([$campId]);
        $camp = $fetchName->fetch();
        $campName = $camp['name'] ?? "ID $campId";

        $stmt = $db->prepare("DELETE FROM camps WHERE id = ?");
        $stmt->execute([$campId]);

        // Audit
        $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Delete Camp', ?)");
        $audit->execute([(int)$user['id'], "Deleted camp: $campName (ID: $campId)"]);

        echo json_encode(['status' => 'success', 'message' => 'Camp deleted successfully.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
