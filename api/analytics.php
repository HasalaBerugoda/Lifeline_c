<?php
// API Analytics Handler: Returns CSV-based blood stock and usage analytics
require_once __DIR__ . '/../includes/jwt.php';
require_once __DIR__ . '/../includes/csv_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Retrieve analytics data publicly without auth restriction

try {
    $analytics = getAnalyticsData();
    echo json_encode([
        'status' => 'success',
        'data' => $analytics
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve analytics: ' . $e->getMessage()
    ]);
}
