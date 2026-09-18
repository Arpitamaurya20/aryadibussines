<?php
/**
 * Mobile Logout API (Zeltohealth)
 * Invalidates refresh token in users table so the token can no longer be used.
 * Use after zeltohealth_login / zeltohealth_refresh_token flow.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/common-api-header.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($content_type, 'application/json') !== false) {
    $data_raw = file_get_contents('php://input');
    $data = json_decode($data_raw, true) ?: [];
} else {
    $data = [];
}

// Accept refresh_token from JSON body or cookie (e.g. web client)
$refresh_token = '';
if (!empty($data['refresh_token'])) {
    $refresh_token = trim($data['refresh_token']);
} elseif (isset($_COOKIE['refresh_token'])) {
    $refresh_token = trim($_COOKIE['refresh_token']);
}

$response = ['error' => false, 'message' => 'Logout successful.'];

if ($refresh_token !== '' && strlen($refresh_token) <= 256) {
    try {
        $stmt = $conn->prepare("UPDATE users SET refresh_token = NULL, refresh_expiry = NULL WHERE refresh_token = ?");
        if ($stmt) {
            $stmt->bind_param('s', $refresh_token);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Still return success so client can clear local token
    }
}

// If request was with cookie, clear it
if (isset($_COOKIE['refresh_token'])) {
    setcookie('refresh_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'secure'   => false,
        'samesite' => 'Strict',
    ]);
}

echo json_encode($response);
