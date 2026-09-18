<?php
/**
 * JWT authentication for protected APIs.
 * Include this after common-api-header.php. Sends 401 and exits if token missing/invalid.
 * On success sets $auth_user = [ 'user_id', 'username', 'email' ] from token payload.
 */
if (!isset($conn)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Auth: common-api-header required first']);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$auth_user = null;

// Apache often strips Authorization; try multiple sources
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if ($auth_header === '' && function_exists('getallheaders')) {
    $headers = getallheaders();
    foreach ($headers ?: [] as $name => $value) {
        if (strtolower($name) === 'authorization') {
            $auth_header = $value;
            break;
        }
    }
}
// Fallback: custom header (e.g. when Apache strips Authorization)
if ($auth_header === '' && !empty($_SERVER['HTTP_X_ACCESS_TOKEN'])) {
    $auth_header = 'Bearer ' . trim($_SERVER['HTTP_X_ACCESS_TOKEN']);
}

if (preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $auth_header, $m)) {
    $token = $m[1];
} else {
    $token = '';
}

if ($token === '') {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Authorization required. Send Bearer token in Authorization header.']);
    exit;
}

try {
    $conf = new Configuration();
    $secret_key = $conf->getJWTKey();
    $decoded = JWT::decode($token, new Key($secret_key, 'HS512'));
    $decoded = (array) $decoded;
    $data = isset($decoded['data']) ? (array) $decoded['data'] : [];
    $auth_user = [
        'user_id'  => $data['user_id'] ?? null,
        'username' => $data['username'] ?? '',
        'email'    => $data['email'] ?? '',
        'role'     => $data['role'] ?? '',
    ];
} catch (Exception $e) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Invalid or expired token. Please login again.']);
    exit;
}
