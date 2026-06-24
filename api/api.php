<?php
// API Multi-Endpoint Router: Handles Profile, Contacts, Users, and Admin Dashboard
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/jwt.php';
require_once __DIR__ . '/../includes/mail_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

// Helper to parse input
function getRequestData() {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    return $_POST;
}

// ----------------------------------------------------
// Endpoint: PROFILE (?endpoint=profile&id=X)
// ----------------------------------------------------
if ($endpoint === 'profile') {
    $userPayload = requireAuth();
    $targetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($targetId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Profile ID is required.']);
        exit;
    }

    // Auth check: owner or admin
    if ((int)$userPayload['id'] !== $targetId && $userPayload['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: You cannot access this profile.']);
        exit;
    }

    if ($method === 'GET') {
        try {
            $stmt = $db->prepare("SELECT id, donor_number, fullName, email, phone, province, district, town, bloodType, role, facility_name, created_at FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $profile = $stmt->fetch();
            if (!$profile) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Profile not found.']);
            } else {
                echo json_encode(['status' => 'success', 'data' => $profile]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'PUT') {
        $data = getRequestData();
        $fullName = trim($data['fullName'] ?? '');
        $phone    = trim($data['phone'] ?? '');
        $province = trim($data['province'] ?? '');
        $district = trim($data['district'] ?? '');
        $town     = trim($data['town'] ?? '');

        if (empty($fullName) || empty($phone) || empty($province) || empty($district) || empty($town)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'All profile fields are required.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE users SET fullName = ?, phone = ?, province = ?, district = ?, town = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone, $province, $district, $town, $targetId]);

            // Audit
            $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Update Profile', ?)");
            $audit->execute([$targetId, "User updated profile details."]);

            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed for profile.']);
    }
}

// ----------------------------------------------------
// Endpoint: CONTACT (?endpoint=contact)
// ----------------------------------------------------
elseif ($endpoint === 'contact') {
    if ($method === 'POST') {
        // Public contact form submission or Admin reply
        $data = getRequestData();
        $action = trim($data['action'] ?? '');

        if ($action === 'reply') {
            // Require Admin Authentication
            requireAuth(['admin']);

            $msgId   = isset($data['id']) ? (int)$data['id'] : 0;
            $to      = trim($data['to'] ?? '');
            $subject = trim($data['subject'] ?? '');
            $body    = trim($data['message'] ?? '');

            if ($msgId <= 0 || empty($to) || empty($subject) || empty($body)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid message ID, recipient, subject, or reply content.']);
                exit;
            }

            // Email headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: LifeLine Blood Bank <no-reply@lifeline.org>\r\n";

            $htmlBody = "
            <html>
            <head>
                <style>
                    body { font-family: sans-serif; line-height: 1.5; color: #333; }
                    .header { background-color: #e63946; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; }
                    .footer { font-size: 11px; color: #777; margin-top: 20px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>LifeLine Blood Bank Support</h2>
                </div>
                <div class='content'>
                    <p>Dear Customer,</p>
                    <p>" . nl2br(htmlspecialchars($body)) . "</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #666;'>LifeLine Blood Bank Core Team</p>
                </div>
                <div class='footer'>
                    This is an automated support response. Please do not reply directly to this email.
                </div>
            </body>
            </html>
            ";

            $emailLogStmt = $db->prepare("INSERT INTO email_log (recipient, subject, status, error_msg) VALUES (?, ?, ?, ?)");
            $result = sendEmailPHPMailer($to, $subject, $htmlBody);

            if ($result['status']) {
                $emailLogStmt->execute([$to, $subject, 'sent', null]);
            } else {
                $emailLogStmt->execute([$to, $subject, 'failed', $result['error']]);
            }

            // Update contact message status to 'Replied'
            $updateStmt = $db->prepare("UPDATE contact_messages SET status = 'Replied' WHERE id = ?");
            $updateStmt->execute([$msgId]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Reply sent successfully. ' . ($result['status'] ? 'Email dispatched via SMTP.' : 'Logged in outbox (SMTP mail delivery failed: ' . $result['error'] . ')')
            ]);
            exit;
        }

        $name    = trim($data['name'] ?? '');
        $email   = trim($data['email'] ?? '');
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'All message fields are required.']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            echo json_encode(['status' => 'success', 'message' => 'Message submitted successfully. We will get back to you soon.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to send message: ' . $e->getMessage()]);
        }
    } elseif ($method === 'GET') {
        // Requires admin only
        requireAuth(['admin']);
        try {
            $stmt = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'PATCH') {
        // Update contact status (admin only)
        requireAuth(['admin']);
        $data = getRequestData();
        $msgId  = isset($data['id']) ? (int)$data['id'] : 0;
        $status = trim($data['status'] ?? '');

        if ($msgId <= 0 || !in_array($status, ['Unread', 'Read', 'Replied'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID or status value.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
            $stmt->execute([$status, $msgId]);
            echo json_encode(['status' => 'success', 'message' => 'Message status updated to ' . $status]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed for contact.']);
    }
}

// ----------------------------------------------------
// Endpoint: ADMIN DASHBOARD (?endpoint=admin_dashboard)
// ----------------------------------------------------
elseif ($endpoint === 'admin_dashboard') {
    $userPayload = requireAuth(['admin']);
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        exit;
    }

    try {
        // 1. Core aggregations
        $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalDonors = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'donor'")->fetchColumn();
        $totalUpdaters = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'updater'")->fetchColumn();
        $totalCamps = (int)$db->query("SELECT COUNT(*) FROM camps")->fetchColumn();
        $totalMessages = (int)$db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
        
        $donationStats = $db->query("SELECT COUNT(*) as cnt, SUM(volume_ml) as vol FROM donations")->fetch();
        $totalDonations = (int)($donationStats['cnt'] ?? 0);
        $totalVolume = (int)($donationStats['vol'] ?? 0);

        // 2. Recent users (last 5)
        $recentUsers = $db->query("SELECT id, donor_number, fullName, email, role, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

        // 3. Recent contact messages (last 5)
        $recentMessages = $db->query("SELECT * FROM contact_messages ORDER BY id DESC LIMIT 5")->fetchAll();

        // 4. Recent audit logs (last 10)
        $recentAudits = $db->query("SELECT a.*, u.fullName as user_name FROM audit_log a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 10")->fetchAll();

        echo json_encode([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_users' => $totalUsers,
                    'total_donors' => $totalDonors,
                    'total_updaters' => $totalUpdaters,
                    'total_camps' => $totalCamps,
                    'total_messages' => $totalMessages,
                    'total_donations' => $totalDonations,
                    'total_volume_ml' => $totalVolume
                ],
                'recent_users' => $recentUsers,
                'recent_messages' => $recentMessages,
                'recent_audits' => $recentAudits
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Dashboard load failed: ' . $e->getMessage()]);
    }
}

// ----------------------------------------------------
// Endpoint: USERS MANAGEMENT (?endpoint=users)
// ----------------------------------------------------
elseif ($endpoint === 'users') {
    $userPayload = requireAuth(['admin']);
    
    if ($method === 'GET') {
        // Fetch all users
        try {
            $stmt = $db->query("SELECT id, donor_number, fullName, email, phone, province, district, town, bloodType, role, facility_name, created_at FROM users ORDER BY fullName ASC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'PUT') {
        // Change user role/facility name
        $data = getRequestData();
        $targetId     = isset($data['id']) ? (int)$data['id'] : 0;
        $role         = trim($data['role'] ?? '');
        $facilityName = trim($data['facility_name'] ?? '');

        if ($targetId <= 0 || !in_array($role, ['admin', 'updater', 'donor', 'revoked'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid User ID or role name.']);
            exit;
        }

        try {
            // Get current details for audit
            $curStmt = $db->prepare("SELECT fullName, role FROM users WHERE id = ?");
            $curStmt->execute([$targetId]);
            $targetUser = $curStmt->fetch();

            if (!$targetUser) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'User not found.']);
                exit;
            }

            // Perform update
            // Facility name is only saved for updaters
            $finalFacilityName = ($role === 'updater') ? $facilityName : null;
            
            $stmt = $db->prepare("UPDATE users SET role = ?, facility_name = ? WHERE id = ?");
            $stmt->execute([$role, $finalFacilityName, $targetId]);

            // Audit
            $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Modify User', ?)");
            $audit->execute([
                (int)$userPayload['id'], 
                "Changed user " . $targetUser['fullName'] . " (ID: $targetId) role from " . $targetUser['role'] . " to $role." . ($role === 'updater' ? " Facility: $finalFacilityName." : "")
            ]);

            echo json_encode(['status' => 'success', 'message' => 'User role updated successfully.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
        }
    } elseif ($method === 'DELETE') {
        // Delete a user
        $targetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($targetId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
            exit;
        }

        // Block self-deletion
        if ((int)$userPayload['id'] === $targetId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action blocked: You cannot delete your own admin account.']);
            exit;
        }

        try {
            // Get user details for audit
            $curStmt = $db->prepare("SELECT fullName FROM users WHERE id = ?");
            $curStmt->execute([$targetId]);
            $targetUser = $curStmt->fetch();

            if (!$targetUser) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'User not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$targetId]);

            // Audit
            $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Delete User', ?)");
            $audit->execute([
                (int)$userPayload['id'], 
                "Deleted user account: " . $targetUser['fullName'] . " (ID: $targetId)"
            ]);

            echo json_encode(['status' => 'success', 'message' => 'User account deleted successfully.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed for users endpoint.']);
    }
}

// ----------------------------------------------------
// Endpoint: URGENT REQUESTS (?endpoint=urgent_requests)
// ----------------------------------------------------
elseif ($endpoint === 'urgent_requests') {
    if ($method === 'GET') {
        try {
            $stmt = $db->query("SELECT * FROM urgent_requests ORDER BY created_at DESC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'POST') {
        $userPayload = requireAuth(['admin', 'updater']);
        $data = getRequestData();
        
        $blood_type    = trim($data['blood_type'] ?? '');
        $hospital_name = trim($data['hospital_name'] ?? '');
        $status_level  = trim($data['status_level'] ?? '');

        if (empty($blood_type) || empty($hospital_name) || empty($status_level)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'All fields (blood_type, hospital_name, status_level) are required.']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO urgent_requests (blood_type, hospital_name, status_level) VALUES (?, ?, ?)");
            $stmt->execute([$blood_type, $hospital_name, $status_level]);
            $newId = $db->lastInsertId();

            // Audit Log
            $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Create Urgent Request', ?)");
            $audit->execute([
                (int)$userPayload['id'],
                "Added urgent request ID $newId for $blood_type at $hospital_name with status '$status_level'."
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Urgent request added successfully.', 'data' => ['id' => $newId]]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to add urgent request: ' . $e->getMessage()]);
        }
    } elseif ($method === 'PUT') {
        $userPayload = requireAuth(['admin', 'updater']);
        $data = getRequestData();
        
        $id            = isset($data['id']) ? (int)$data['id'] : 0;
        $blood_type    = trim($data['blood_type'] ?? '');
        $hospital_name = trim($data['hospital_name'] ?? '');
        $status_level  = trim($data['status_level'] ?? '');

        if ($id <= 0 || empty($blood_type) || empty($hospital_name) || empty($status_level)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'All fields (id, blood_type, hospital_name, status_level) are required.']);
            exit;
        }

        try {
            // Check if exists
            $check = $db->prepare("SELECT * FROM urgent_requests WHERE id = ?");
            $check->execute([$id]);
            $req = $check->fetch();
            if (!$req) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Urgent request not found.']);
                exit;
            }

            $stmt = $db->prepare("UPDATE urgent_requests SET blood_type = ?, hospital_name = ?, status_level = ? WHERE id = ?");
            $stmt->execute([$blood_type, $hospital_name, $status_level, $id]);

            // Audit Log
            $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Update Urgent Request', ?)");
            $audit->execute([
                (int)$userPayload['id'],
                "Updated urgent request ID $id: changed to $blood_type at $hospital_name ($status_level) (was: " . $req['blood_type'] . " at " . $req['hospital_name'] . ")."
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Urgent request updated successfully.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update urgent request: ' . $e->getMessage()]);
        }
    } elseif ($method === 'DELETE') {
        $userPayload = requireAuth(['admin', 'updater']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Urgent request ID is required.']);
            exit;
        }

        try {
            // Check if exists
            $check = $db->prepare("SELECT * FROM urgent_requests WHERE id = ?");
            $check->execute([$id]);
            $req = $check->fetch();
            if (!$req) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Urgent request not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM urgent_requests WHERE id = ?");
            $stmt->execute([$id]);

            // Audit Log
            $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Delete Urgent Request', ?)");
            $audit->execute([
                (int)$userPayload['id'],
                "Deleted urgent request for " . $req['blood_type'] . " at " . $req['hospital_name'] . " (ID: $id)."
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Urgent request deleted successfully.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete urgent request: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed for urgent requests endpoint.']);
    }
}

// ----------------------------------------------------
// Fallback
// ----------------------------------------------------
else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Endpoint parameter is missing or invalid.']);
}
