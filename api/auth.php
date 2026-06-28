<?php
// API Authentication Handler: Login & Register
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/jwt.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$action = $_GET['action'] ?? '';

// Helper to parse input (both JSON and form POST)
function getRequestData() {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    return $_POST;
}

$db = getDBConnection();

if ($action === 'register') {
    $data = getRequestData();
    
    $fullName  = trim($data['fullName'] ?? '');
    $email     = trim($data['email'] ?? '');
    $phone     = trim($data['phone'] ?? '');
    $province  = trim($data['province'] ?? '');
    $district  = trim($data['district'] ?? '');
    $town      = trim($data['town'] ?? '');
    $bloodType = trim($data['bloodType'] ?? '');
    $password  = trim($data['password'] ?? '');

    // Validation
    if (empty($fullName) || empty($email) || empty($phone) || empty($province) || empty($district) || empty($town) || empty($bloodType) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'All registration fields are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }

    // Check if email already exists
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists.']);
        exit;
    }

    // Generate donor number
    // We get the next auto-increment id or max id
    $idStmt = $db->query("SELECT MAX(id) as max_id FROM users");
    $row = $idStmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;
    $donorNumber = 'LL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $insertStmt = $db->prepare("INSERT INTO users (donor_number, fullName, email, phone, province, district, town, bloodType, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'donor')");
        $insertStmt->execute([
            $donorNumber,
            $fullName,
            $email,
            $phone,
            $province,
            $district,
            $town,
            $bloodType,
            $passwordHash
        ]);

        $userId = $db->lastInsertId();

        // Create JWT
        $payload = [
            'id' => (int)$userId,
            'email' => $email,
            'role' => 'donor'
        ];
        $token = generateJWT($payload);

        // Audit Log
        $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, 'Register', ?)");
        $audit->execute([$userId, "User registered as a donor. Assigned Donor Number: $donorNumber"]);

        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => [
                'id' => (int)$userId,
                'donor_number' => $donorNumber,
                'fullName' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'province' => $province,
                'district' => $district,
                'town' => $town,
                'bloodType' => $bloodType,
                'role' => 'donor'
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
    }

} elseif ($action === 'login') {
    $data = getRequestData();
    $email    = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit;
    }

    if ($user['role'] === 'revoked') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Your account has been suspended. Please contact administration.']);
        exit;
    }

    // Create JWT
    $payload = [
        'id' => (int)$user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    $token = generateJWT($payload);

    // Audit Log
    $audit = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)");
    $audit->execute([$user['id'], 'Login', 'User logged in.']);

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful.',
        'token' => $token,
        'user' => [
            'id' => (int)$user['id'],
            'donor_number' => $user['donor_number'],
            'fullName' => $user['fullName'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'province' => $user['province'],
            'district' => $user['district'],
            'town' => $user['town'],
            'bloodType' => $user['bloodType'],
            'role' => $user['role'],
            'facility_name' => $user['facility_name']
        ]
    ]);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid auth action.']);
}
