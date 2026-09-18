<?php
/**
 * Mobile Refresh Token API
 * Exchange refresh_token for a new JWT access token.
 * Security: prepared statements, no password in request.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once __DIR__ . '/common-api-header.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

require_once __DIR__ . '/../include/autoloader.inc.php';

$conf = new Configuration();
$secret_key = $conf->getJWTKey();
$logs = new Logs();
$logs->SetCurrentAPILogFile();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($content_type, 'application/json') === false) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Content-Type must be application/json']);
    exit;
}

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$refresh_token = isset($data['refresh_token']) ? trim($data['refresh_token']) : '';

$response = ['error' => true, 'message' => ''];

if ($refresh_token === '' || strlen($refresh_token) > 256) {
    $response['message'] = 'Invalid request.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();

    $stmt = $conn->prepare(
        "SELECT `ID`, `Email`, `UserType`, `refresh_token`, `refresh_expiry` FROM `users` WHERE `refresh_token` = ? AND `IsActive` = 1 LIMIT 1"
    );
    if (!$stmt) {
        $response['message'] = 'Service temporarily unavailable.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param('s', $refresh_token);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$user || empty($user['refresh_expiry']) || strtotime($user['refresh_expiry']) < time()) {
        $response['message'] = 'Invalid or expired refresh token. Please login again.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $user_id = (int) $user['ID'];
    $email = $user['Email'];
    $user_type = $user['UserType'];

    // Get display name from user_details
    $stmt_detail = $conn->prepare(
        "SELECT `Name`, `Mobile`, `UserType` FROM `user_details` WHERE `UserID` = ? LIMIT 1"
    );
    $display_name = $email;
    $mobile = '';
    if ($stmt_detail) {
        $stmt_detail->bind_param('i', $user_id);
        $stmt_detail->execute();
        $res_detail = $stmt_detail->get_result();
        if ($res_detail && ($row = $res_detail->fetch_assoc())) {
            if (!empty($row['Name'])) $display_name = $row['Name'];
            if (isset($row['Mobile'])) $mobile = $row['Mobile'];
            if (!empty($row['UserType'])) $user_type = $row['UserType'];
        }
        $stmt_detail->close();
    }

    $access_expiry_seconds = 3600;
    $expiration_time = time() + $access_expiry_seconds;
    $token_payload = [
        'iss' => 'zeltohealth.api',
        'aud' => 'zeltohealth',
        'iat' => time(),
        'exp' => $expiration_time,
        'data' => [
            'user_id'  => $user_id,
            'username' => $display_name,
            'email'    => $email,
            'role'     => $user_type,
        ],
    ];
    $jwt = JWT::encode($token_payload, $secret_key, 'HS512');

    $response = [
        'error'        => false,
        'message'      => 'Token refreshed.',
        'token'        => $jwt,
        'expires_in'   => $access_expiry_seconds,
        'role'         => $user_type,
        'user'         => [
            'user_id'   => $user_id,
            'name'      => $display_name,
            'email'     => $email,
            'mobile'    => $mobile,
            'user_type' => $user_type,
        ],
    ];
} catch (Exception $e) {
    $response = ['error' => true, 'message' => 'An error occurred. Please try again.'];
    $logs->WriteLog('Refresh token exception: ' . $e->getMessage(), __FILE__, __LINE__);
}

header('Content-Type: application/json');
echo json_encode($response);
