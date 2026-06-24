<?php
// Database and CSV Setup Script
// Run this file to initialize the database tables and seed test data.

require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>LifeLine Setup</title>";
echo "<link rel='icon' type='image/svg+xml' href='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><path fill=%22%23e63946%22 d=%22M50,5 C50,5 90,45 90,65 C90,85 70,95 50,95 C30,95 10,85 10,65 C10,45 50,5 50,5 Z%22/></svg>'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>body{background-color:#111827;color:#f9fafb;font-family:sans-serif;} .card{background-color:#1f2937;border:1px solid #374151;color:#f9fafb;}</style>";
echo "</head><body><div class='container my-5'><h1 class='mb-4 text-danger'>LifeLine System Setup</h1>";

try {
    // 1. Connect to MySQL (without selecting database to allow creating it)
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 2. Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS blood_bank_test");
    $pdo->exec("USE blood_bank_test");
    echo "<div class='alert alert-success'>Database 'blood_bank_test' created or verified.</div>";

    // 3. Create tables
    $queries = [
        "DROP TABLE IF EXISTS urgent_requests",
        "DROP TABLE IF EXISTS audit_log",
        "DROP TABLE IF EXISTS email_log",
        "DROP TABLE IF EXISTS contact_messages",
        "DROP TABLE IF EXISTS camp_registrations",
        "DROP TABLE IF EXISTS camps",
        "DROP TABLE IF EXISTS donations",
        "DROP TABLE IF EXISTS blood_inventory",
        "DROP TABLE IF EXISTS users",

        "CREATE TABLE users (
          id            INT AUTO_INCREMENT PRIMARY KEY,
          donor_number  VARCHAR(10) UNIQUE,
          fullName      VARCHAR(100) NOT NULL,
          email         VARCHAR(100) UNIQUE NOT NULL,
          phone         VARCHAR(20) NOT NULL,
          province      VARCHAR(50) NOT NULL,
          district      VARCHAR(50) NOT NULL,
          town          VARCHAR(50) NOT NULL,
          bloodType     VARCHAR(10) NOT NULL,
          password      VARCHAR(255) NOT NULL,
          role          ENUM('superadmin','admin','updater','donor','revoked') DEFAULT 'donor',
          facility_name VARCHAR(150),
          created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        "CREATE TABLE blood_inventory (
          id INT AUTO_INCREMENT PRIMARY KEY,
          aPos INT DEFAULT 0, aNeg INT DEFAULT 0,
          bPos INT DEFAULT 0, bNeg INT DEFAULT 0,
          oPos INT DEFAULT 0, oNeg INT DEFAULT 0,
          abPos INT DEFAULT 0, abNeg INT DEFAULT 0,
          platelets INT DEFAULT 0,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        "CREATE TABLE donations (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NOT NULL,
          blood_type VARCHAR(10) NOT NULL,
          volume_ml INT NOT NULL,
          location VARCHAR(150),
          hemoglobin DECIMAL(5,2),
          blood_pressure VARCHAR(20),
          weight DECIMAL(5,2),
          donation_date DATE NOT NULL,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        "CREATE TABLE camps (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(150) NOT NULL,
          date DATE NOT NULL,
          time TIME NOT NULL,
          location VARCHAR(200) NOT NULL,
          organizer VARCHAR(150) NOT NULL,
          description TEXT,
          created_by INT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )",

        "CREATE TABLE camp_registrations (
          id INT AUTO_INCREMENT PRIMARY KEY,
          camp_id INT NOT NULL,
          user_id INT NOT NULL,
          registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          attended BOOLEAN DEFAULT FALSE,
          UNIQUE KEY uq_camp_donor (camp_id, user_id),
          FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE CASCADE,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        "CREATE TABLE contact_messages (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(100) NOT NULL,
          email VARCHAR(150) NOT NULL,
          subject VARCHAR(200) NOT NULL,
          message TEXT NOT NULL,
          status ENUM('Unread','Read','Replied') DEFAULT 'Unread',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE email_log (
          id INT AUTO_INCREMENT PRIMARY KEY,
          recipient VARCHAR(150) NOT NULL,
          subject VARCHAR(200),
          status ENUM('sent','failed') NOT NULL,
          error_msg TEXT,
          sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE audit_log (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT,
          action VARCHAR(255),
          details TEXT,
          timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )",

        "CREATE TABLE urgent_requests (
          id INT AUTO_INCREMENT PRIMARY KEY,
          blood_type VARCHAR(10) NOT NULL,
          hospital_name VARCHAR(150) NOT NULL,
          status_level VARCHAR(50) NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($queries as $q) {
        $pdo->exec($q);
    }
    echo "<div class='alert alert-success'>All tables created successfully.</div>";

    // 4. Seed Users
    $usersToSeed = [
        [
            'fullName' => 'Super Administrator',
            'email' => 'superadmin@lifeline.com',
            'phone' => '+94770000000',
            'province' => 'Western',
            'district' => 'Colombo',
            'town' => 'Colombo 1',
            'bloodType' => 'O+',
            'password' => password_hash('SuperadminPassword123', PASSWORD_BCRYPT),
            'role' => 'superadmin',
            'facility_name' => null
        ],
        [
            'fullName' => 'System Administrator',
            'email' => 'admin@lifeline.com',
            'phone' => '+94771234567',
            'province' => 'Western',
            'district' => 'Colombo',
            'town' => 'Colombo 3',
            'bloodType' => 'O+',
            'password' => password_hash('AdminPassword123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'facility_name' => null
        ],
        [
            'fullName' => 'Central Hospital Updater',
            'email' => 'updater@lifeline.com',
            'phone' => '+94777654321',
            'province' => 'Central',
            'district' => 'Kandy',
            'town' => 'Peradeniya',
            'bloodType' => 'A+',
            'password' => password_hash('UpdaterPassword123', PASSWORD_BCRYPT),
            'role' => 'updater',
            'facility_name' => 'Central General Hospital'
        ],
        [
            'fullName' => 'John Doe',
            'email' => 'donor@lifeline.com',
            'phone' => '+94711112222',
            'province' => 'Western',
            'district' => 'Colombo',
            'town' => 'Nugegoda',
            'bloodType' => 'O+',
            'password' => password_hash('DonorPassword123', PASSWORD_BCRYPT),
            'role' => 'donor',
            'facility_name' => null
        ],
        [
            'fullName' => 'Jane Smith',
            'email' => 'jane@lifeline.com',
            'phone' => '+94722223333',
            'province' => 'Southern',
            'district' => 'Galle',
            'town' => 'Unawatuna',
            'bloodType' => 'B-',
            'password' => password_hash('DonorPassword123', PASSWORD_BCRYPT),
            'role' => 'donor',
            'facility_name' => null
        ],
        [
            'fullName' => 'Revoked Donor',
            'email' => 'revoked@lifeline.com',
            'phone' => '+94755554444',
            'province' => 'Northern',
            'district' => 'Jaffna',
            'town' => 'Jaffna',
            'bloodType' => 'AB+',
            'password' => password_hash('RevokedPassword123', PASSWORD_BCRYPT),
            'role' => 'revoked',
            'facility_name' => null
        ],
        [
            'fullName' => 'Saman Perera',
            'email' => 'saman@lifeline.com',
            'phone' => '+94779998888',
            'province' => 'Western',
            'district' => 'Gampaha',
            'town' => 'Negombo',
            'bloodType' => 'A-',
            'password' => password_hash('DonorPassword123', PASSWORD_BCRYPT),
            'role' => 'donor',
            'facility_name' => null
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO users (donor_number, fullName, email, phone, province, district, town, bloodType, password, role, facility_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $donorIdx = 1;
    foreach ($usersToSeed as $u) {
        $donor_number = 'LL-' . str_pad($donorIdx++, 4, '0', STR_PAD_LEFT);
        $stmt->execute([
            $donor_number,
            $u['fullName'],
            $u['email'],
            $u['phone'],
            $u['province'],
            $u['district'],
            $u['town'],
            $u['bloodType'],
            $u['password'],
            $u['role'],
            $u['facility_name']
        ]);
    }
    echo "<div class='alert alert-success'>Seeded 6 initial users successfully.</div>";

    // 5. Generate CSV data
    $csvDir = __DIR__ . '/data';
    if (!is_dir($csvDir)) {
        mkdir($csvDir, 0777, true);
    }
    $csvFile = $csvDir . '/lifeline_blood_inventory.csv';

    // Header columns
    $headers = ['Bag_ID', 'Donor_Name', 'Blood_Group', 'Volume_ml', 'Store_Date', 'Expiry_Date', 'Storage_Location', 'Status'];
    $fp = fopen($csvFile, 'w');
    fputcsv($fp, $headers);

    // Let's generate synthetic rows spanning 5 years
    // Starting 5 years ago up to today (2026-06-23)
    $startDate = strtotime('-5 years');
    $endDate = strtotime('now');
    
    $names = ['Liam', 'Olivia', 'Noah', 'Emma', 'Oliver', 'Ava', 'Elijah', 'Charlotte', 'William', 'Sophia', 'James', 'Amelia', 'Benjamin', 'Isabella', 'Lucas', 'Mia', 'Henry', 'Evelyn', 'Alexander', 'Harper'];
    $surnames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'];
    $bloodGroups = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
    $bloodGroupWeights = [38, 30, 20, 5, 3, 2, 1.5, 0.5]; // Must sum to 100
    
    $locations = ['Colombo General Hospital', 'Kandy National Hospital', 'Galle Karapitiya Hospital', 'Jaffna Teaching Hospital', 'Kurunegala Hospital', 'National Blood Center Colombo'];
    
    // Function to pick weighted random
    function getWeightedRandom($values, $weights) {
        $rand = rand(1, array_sum($weights));
        $sum = 0;
        foreach ($values as $i => $v) {
            $sum += $weights[$i];
            if ($rand <= $sum) {
                return $v;
            }
        }
        return $values[0];
    }

    $totalRows = 2500;
    $availableCounts = [
        'O+' => 0, 'A+' => 0, 'B+' => 0, 'AB+' => 0,
        'O-' => 0, 'A-' => 0, 'B-' => 0, 'AB-' => 0
    ];

    echo "<div class='text-light my-2'>Generating $totalRows synthetic blood bags spanning 5 years...</div>";

    for ($i = 1; $i <= $totalRows; $i++) {
        // Random date within 5 years
        $storeTimestamp = rand($startDate, $endDate);
        $storeDateStr = date('Y-m-d', $storeTimestamp);
        // Expiry is 35 days after store
        $expiryTimestamp = $storeTimestamp + (35 * 24 * 60 * 60);
        $expiryDateStr = date('Y-m-d', $expiryTimestamp);

        $donorName = $names[array_rand($names)] . ' ' . $surnames[array_rand($surnames)];
        $bg = getWeightedRandom($bloodGroups, $bloodGroupWeights);
        $vol = rand(0, 1) ? 350 : 450;
        $loc = $locations[array_rand($locations)];
        
        // Decide status
        // If expired based on current date (2026-06-23)
        if ($expiryTimestamp < $endDate) {
            // Either Used (e.g. 88%) or Expired (e.g. 12%)
            $status = (rand(1, 100) <= 88) ? 'Used' : 'Expired';
        } else {
            // Still within shelf life: either Available (e.g. 75%) or Used (e.g. 25%)
            $status = (rand(1, 100) <= 75) ? 'Available' : 'Used';
        }

        if ($status === 'Available') {
            $availableCounts[$bg]++;
        }

        $bagId = 'BAG-' . date('Ym', $storeTimestamp) . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
        fputcsv($fp, [$bagId, $donorName, $bg, $vol, $storeDateStr, $expiryDateStr, $loc, $status]);
    }
    fclose($fp);
    echo "<div class='alert alert-success'>CSV file created at 'data/lifeline_blood_inventory.csv' with $totalRows rows.</div>";

    // 6. Seed blood_inventory table with counts matching the Available CSV rows
    $invStmt = $pdo->prepare("INSERT INTO blood_inventory (oPos, aPos, bPos, abPos, oNeg, aNeg, bNeg, abNeg, platelets) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $invStmt->execute([
        $availableCounts['O+'],
        $availableCounts['A+'],
        $availableCounts['B+'],
        $availableCounts['AB+'],
        $availableCounts['O-'],
        $availableCounts['A-'],
        $availableCounts['B-'],
        $availableCounts['AB-'],
        rand(15, 30) // Random seed for platelets
    ]);
    echo "<div class='alert alert-success'>Seeded 'blood_inventory' matching CSV available stock.</div>";

    // 7. Seed some past donations for our seeded donors
    // Find the donor IDs
    $donorIds = $pdo->query("SELECT id, bloodType FROM users WHERE role = 'donor'")->fetchAll();
    $donationStmt = $pdo->prepare("INSERT INTO donations (user_id, blood_type, volume_ml, location, hemoglobin, blood_pressure, weight, donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($donorIds as $d) {
        // Create 2-3 donations for each donor
        $numDonations = rand(2, 4);
        for ($k = 0; $k < $numDonations; $k++) {
            $dDate = date('Y-m-d', strtotime('-' . (($k + 1) * 3) . ' months'));
            $donationStmt->execute([
                $d['id'],
                $d['bloodType'],
                rand(0, 1) ? 350 : 450,
                $locations[array_rand($locations)],
                rand(125, 165) / 10, // Hemoglobin e.g. 12.5 - 16.5
                rand(110, 130) . '/' . rand(70, 85), // Blood pressure
                rand(55, 85), // Weight
                $dDate
            ]);
        }
    }
    echo "<div class='alert alert-success'>Seeded test donation history for donors.</div>";

    // 8. Seed some upcoming and past camps
    $campStmt = $pdo->prepare("INSERT INTO camps (name, date, time, location, organizer, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $adminUser = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
    
    $campStmt->execute([
        'National Hero Blood Drive',
        date('Y-m-d', strtotime('+5 days')),
        '09:00:00',
        'National Blood Center, Colombo 05',
        'Ministry of Health',
        'A grand blood donation camp organized to support the national pediatric cancer ward requirements. All blood groups needed.',
        $adminUser['id']
    ]);

    $campStmt->execute([
        'Kandy Youth Donation Campaign',
        date('Y-m-d', strtotime('+12 days')),
        '08:30:00',
        'Kandy City Centre, Kandy',
        'Rotaract Club of Kandy',
        'Annual youth blood donation campaign to support Central Province blood reserves.',
        $adminUser['id']
    ]);

    $campStmt->execute([
        'Galle Beach Blood Drive',
        date('Y-m-d', strtotime('-10 days')), // Past camp
        '10:00:00',
        'Karapitiya Medical College Grounds, Galle',
        'Galle Lions Club',
        'Community blood donation campaign to support post-monsoon medical emergencies.',
        $adminUser['id']
    ]);
    echo "<div class='alert alert-success'>Seeded 3 blood donation camps.</div>";

    // 8.5. Seed Urgent Requests
    $urgentRequestsToSeed = [
        [
            'blood_type' => 'AB-',
            'hospital_name' => 'Badulla General Hospital',
            'status_level' => 'Critical Level'
        ],
        [
            'blood_type' => 'AB+',
            'hospital_name' => 'Balangoda Base',
            'status_level' => 'Critical Level'
        ],
        [
            'blood_type' => 'B+',
            'hospital_name' => 'Kandy National Hospital',
            'status_level' => 'Critical Level'
        ]
    ];
    $urgentStmt = $pdo->prepare("INSERT INTO urgent_requests (blood_type, hospital_name, status_level) VALUES (?, ?, ?)");
    foreach ($urgentRequestsToSeed as $ur) {
        $urgentStmt->execute([
            $ur['blood_type'],
            $ur['hospital_name'],
            $ur['status_level']
        ]);
    }
    echo "<div class='alert alert-success'>Seeded 3 urgent requests.</div>";

    // 9. Add audit log record
    $auditStmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)");
    $auditStmt->execute([$adminUser['id'], 'System Setup', 'Database created, tables initialized, seeded users, CSV file generated, and dummy data inserted.']);
    echo "<div class='alert alert-success'>Audit logs populated.</div>";

    echo "<h3 class='text-success mt-4'>Setup Completed Successfully!</h3>";
    echo "<p class='lead'>You can now log in using the following test credentials:</p>";
    echo "<table class='table table-dark table-striped table-bordered mt-2' style='max-width: 600px;'>";
    echo "<thead><tr><th>Role</th><th>Email</th><th>Password</th></tr></thead>";
    echo "<tbody>";
    echo "<tr><td>Super Admin</td><td><code>superadmin@lifeline.com</code></td><td><code>SuperadminPassword123</code></td></tr>";
    echo "<tr><td>Admin</td><td><code>admin@lifeline.com</code></td><td><code>AdminPassword123</code></td></tr>";
    echo "<tr><td>Updater</td><td><code>updater@lifeline.com</code></td><td><code>UpdaterPassword123</code></td></tr>";
    echo "<tr><td>Donor (O+)</td><td><code>donor@lifeline.com</code></td><td><code>DonorPassword123</code></td></tr>";
    echo "<tr><td>Donor (B-)</td><td><code>jane@lifeline.com</code></td><td><code>DonorPassword123</code></td></tr>";
    echo "<tr><td>Revoked</td><td><code>revoked@lifeline.com</code></td><td><code>RevokedPassword123</code></td></tr>";
    echo "</tbody></table>";
    echo "<a href='index.php' class='btn btn-danger mt-3'>Proceed to Login Page</a>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger mt-4'><h4>Setup Failed!</h4>" . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
