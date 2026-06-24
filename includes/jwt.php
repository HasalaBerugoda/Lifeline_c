<?php
// Custom JWT implementation using HMAC-SHA256 (HS256)

define('JWT_SECRET', 'LifeLineSuperSecretJWTKey2026!');

function base64UrlEncode($data) {
    $b64 = base64_encode($data);
    if ($b64 === false) {
        return false;
    }
    return str_replace(['+', '/', '='], ['-', '_', ''], $b64);
}

function base64UrlDecode($data) {
    $b64 = str_replace(['-', '_'], ['+', '/'], $data);
    $padding = strlen($b64) % 4;
    if ($padding) {
        $b64 .= str_repeat('=', 4 - $padding);
    }
    return base64_decode($b64);
}

// Generate JWT token
function generateJWT($payload) {
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    
    // Add default claims if not set
    if (!isset($payload['iat'])) {
        $payload['iat'] = time();
    }
    if (!isset($payload['exp'])) {
        $payload['exp'] = time() + (24 * 60 * 60); // 1 day expiry
    }

    $payloadStr = json_encode($payload);

    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payloadStr);

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = base64UrlEncode($signature);

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

// Verify JWT token and return payload
function verifyJWT($jwt) {
    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) !== 3) {
        return false;
    }

    $header = base64UrlDecode($tokenParts[0]);
    $payload = base64UrlDecode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];

    // Rebuild signature
    $signatureCheck = hash_hmac('sha256', $tokenParts[0] . "." . $tokenParts[1], JWT_SECRET, true);
    $base64UrlSignatureCheck = base64UrlEncode($signatureCheck);

    if ($base64UrlSignatureCheck !== $signatureProvided) {
        return false; // Signature mismatch
    }

    $payloadArr = json_decode($payload, true);
    if (!$payloadArr) {
        return false;
    }

    // Check expiration
    if (isset($payloadArr['exp']) && $payloadArr['exp'] < time()) {
        return false; // Token expired
    }

    return $payloadArr;
}

// Helper to get bearer token from request headers
function getBearerToken() {
    $headers = null;
    
    // 1. Try $_SERVER['HTTP_AUTHORIZATION']
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    // 2. Try $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    // 3. Try apache_request_headers()
    elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server keys are sometimes lowercase
        $requestHeaders = array_change_key_case($requestHeaders, CASE_LOWER);
        if (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    // 4. Try getallheaders()
    elseif (function_exists('getallheaders')) {
        $requestHeaders = getallheaders();
        $requestHeaders = array_change_key_case($requestHeaders, CASE_LOWER);
        if (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }

    // Extract token
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/i', $headers, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

// Helper to check if token exists, is valid and return payload or terminate with 401
function requireAuth($allowedRoles = []) {
    $token = getBearerToken();
    if (!$token) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Authorization token required.']);
        exit;
    }

    $payload = verifyJWT($token);
    if (!$payload) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token.']);
        exit;
    }

    // Check role access
    if (!empty($allowedRoles) && !in_repeat_role($payload['role'], $allowedRoles)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access forbidden: Insufficient privileges.']);
        exit;
    }

    return $payload;
}

function in_repeat_role($role, $allowedRoles) {
    return in_array($role, $allowedRoles);
}
