<?php
/**
 * Mobile Login API (Zeltohealth)
 * Tables: users (ID, Email, Password, UserType, IsActive, refresh_token, refresh_expiry)
 *         user_details (ID, UserID, Name, Mobile, Email, UserType, IsActive)
 * Security: prepared statements, input validation, JWT + refresh token, activity logging.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/common-api-header.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

require_once __DIR__ . '/../include/autoloader.inc.php';
require_once __DIR__ . '/../controllers/common_controller.php';

$conf = new Configuration();
$secret_key = $conf->getJWTKey();
$logs = new Logs();
$logs->SetCurrentAPILogFile();

// ---------- Security: Only POST with JSON body ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

$data_raw = file_get_contents('php://input');
$logs->WriteLog($data_raw, __FILE__, __LINE__);

$data = json_decode($data_raw, true);

$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($content_type, 'application/json') === false && !is_array($data)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Content-Type must be application/json or send a valid JSON body.']);
    exit;
}
$response = ['error' => true, 'message' => ''];

// ---------- Input validation ----------
$email = isset($data['Email']) ? trim($data['Email']) : '';
$password = isset($data['Password']) ? $data['Password'] : '';

if ($email === '' || $password === '') {
    $response['message'] = 'Email and Password are required.';
    $logs->WriteLog(json_encode($response), __FILE__, __LINE__);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Basic email format check
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format.';
    $logs->WriteLog(json_encode($response), __FILE__, __LINE__);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Limit length to avoid abuse
if (strlen($email) > 255 || strlen($password) > 500) {
    $response['message'] = 'Invalid request.';
    $logs->WriteLog(json_encode($response), __FILE__, __LINE__);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// ---------- Login log (no password) ----------
$login_log_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($login_log_dir)) {
    @mkdir($login_log_dir, 0755, true);
}
$login_log_file = $login_log_dir . DIRECTORY_SEPARATOR . 'login_activity.log';

// ---------- Database: fetch user with prepared statements ----------
try {
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();

    // 1. Get user by email (prepared statement – no SQL injection)
    $stmt = $conn->prepare(
        "SELECT `ID`, `Email`, `Password`, `UserType`, `IsActive` FROM `users` WHERE `Email` = ? AND `IsActive` = 1 LIMIT 1"
    );
    if (!$stmt) {
        $log_line = date('Y-m-d H:i:s') . " - LOGIN ERROR - DB prepare failed - Email: " . substr($email, 0, 3) . "***\n";
        @file_put_contents($login_log_file, $log_line, FILE_APPEND);
        $response['message'] = 'Service temporarily unavailable.';
        $logs->WriteLog(json_encode($response), __FILE__, __LINE__);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user_row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$user_row) {
        $log_line = date('Y-m-d H:i:s') . " - LOGIN FAIL - Invalid credentials - Email: " . substr($email, 0, 3) . "***\n";
        @file_put_contents($login_log_file, $log_line, FILE_APPEND);
        $response['message'] = 'Invalid email or password.';
        $logs->WriteLog(json_encode($response), __FILE__, __LINE__);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // 2. Password check (existing system uses MD5; use password_verify if you migrate to bcrypt)
    $password_hash_stored = $user_row['Password'];
    $password_valid = false;

    if (strlen($password_hash_stored) === 32 && ctype_xdigit($password_hash_stored)) {
        $password_valid = (md5($password) === $password_hash_stored);
    } else {
        $password_valid = password_verify($password, $password_hash_stored);
    }

    if (!$password_valid) {
        $log_line = date('Y-m-d H:i:s') . " - LOGIN FAIL - Invalid credentials - UserID: " . $user_row['ID'] . "\n";
        @file_put_contents($login_log_file, $log_line, FILE_APPEND);
        $response['message'] = 'Invalid email or password.';
        $logs->WriteLog(json_encode($response), __FILE__, __LINE__);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $user_id = (int) $user_row['ID'];
    $user_type = $user_row['UserType'];

    // 3. Get user_details by UserID (prepared statement)
    $stmt_detail = $conn->prepare(
        "SELECT `ID`, `UserID`, `Name`, `Mobile`, `Email`, `UserType`, `IsActive` FROM `user_details` WHERE `UserID` = ? AND (`IsActive` = 1 OR `IsActive` IS NULL) LIMIT 1"
    );
    $detail_row = null;
    if ($stmt_detail) {
        $stmt_detail->bind_param('i', $user_id);
        $stmt_detail->execute();
        $res_detail = $stmt_detail->get_result();
        $detail_row = $res_detail ? $res_detail->fetch_assoc() : null;
        $stmt_detail->close();
    }

    $display_name = $email;
    $mobile = '';
    if ($detail_row && !empty($detail_row['Name'])) {
        $display_name = $detail_row['Name'];
    }
    if ($detail_row && isset($detail_row['Mobile'])) {
        $mobile = $detail_row['Mobile'];
    }
    if ($detail_row && isset($detail_row['UserType']) && $detail_row['UserType'] !== '') {
        $user_type = $detail_row['UserType'];
    }

    // ---------- JWT (access token) ----------
    $access_expiry_seconds = 3600; // 1 hour
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

    // ---------- Refresh token (optional – store in users table) ----------
    $refresh_token = bin2hex(random_bytes(32));
    $refresh_expiry = date('Y-m-d H:i:s', time() + (7 * 24 * 3600)); // 7 days

    $stmt_refresh = $conn->prepare("UPDATE `users` SET `refresh_token` = ?, `refresh_expiry` = ? WHERE `ID` = ?");
    if ($stmt_refresh) {
        $stmt_refresh->bind_param('ssi', $refresh_token, $refresh_expiry, $user_id);
        $stmt_refresh->execute();
        $stmt_refresh->close();
    }

    // ---------- Success response ----------
    $response = [
        'error'   => false,
        'message' => 'Login successful.',
        'token'   => $jwt,
        'refresh_token' => $refresh_token,
        'expires_in'    => $access_expiry_seconds,
        'role'   => $user_type,
        'user'   => [
            'user_id'   => $user_id,
            'name'      => $display_name,
            'email'     => $email,
            'mobile'    => $mobile,
            'user_type' => $user_type,
        ],
    ];

    $log_line = date('Y-m-d H:i:s') . " - LOGIN SUCCESS - UserID: $user_id - Email: " . substr($email, 0, 3) . "***\n";
    @file_put_contents($login_log_file, $log_line, FILE_APPEND);

} catch (Exception $e) {
    $response = ['error' => true, 'message' => 'An error occurred. Please try again later.'];
    $logs->WriteLog('Login exception: ' . $e->getMessage(), __FILE__, __LINE__);
}

$log_safe = $response;
if (isset($log_safe['token'])) $log_safe['token'] = '(redacted)';
if (isset($log_safe['refresh_token'])) $log_safe['refresh_token'] = '(redacted)';
$logs->WriteLog(json_encode($log_safe), __FILE__, __LINE__);
header('Content-Type: application/json');
echo json_encode($response);
