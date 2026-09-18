<?php
/**
 * Send OTP to email and mobile.
 * Used for: register (Email + Mobile), forgot password (Email – mobile from user_details).
 * Table: otp_verification (ID, Email, Mobile, OTP, Purpose, CreatedAt, IsUsed)
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/common-api-header.php';
require_once __DIR__ . '/../include/send_mail_phpmailer.php';
require_once __DIR__ . '/../controllers/common_controller.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true) ?: [];
$email = isset($data['Email']) ? trim($data['Email']) : '';
$mobile = isset($data['Mobile']) ? trim($data['Mobile']) : '';
$purpose = isset($data['Purpose']) ? trim($data['Purpose']) : 'register'; // register | forgot

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => true, 'message' => 'Valid Email is required.']);
    exit;
}

if ($purpose !== 'forgot' && $purpose !== 'register') {
    $purpose = 'register';
}

if ($purpose === 'forgot') {
    $stmt = $conn->prepare("SELECT ID, Email FROM users WHERE Email = ? AND IsActive = 1 LIMIT 1");
    if (!$stmt) {
        echo json_encode(['error' => true, 'message' => 'Service error.']);
        exit;
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$user) {
        echo json_encode(['error' => true, 'message' => 'No account found with this email.']);
        exit;
    }
    $stmt_m = $conn->prepare("SELECT Mobile FROM user_details WHERE UserID = ? AND IsActive = 1 LIMIT 1");
    $uid = $user['ID'];
    $stmt_m->bind_param('i', $uid);
    $stmt_m->execute();
    $rm = $stmt_m->get_result();
    $row_m = $rm ? $rm->fetch_assoc() : null;
    $stmt_m->close();
    $mobile = ($row_m && !empty($row_m['Mobile'])) ? trim($row_m['Mobile']) : '';
}

if ($purpose === 'register' && $mobile === '') {
    echo json_encode(['error' => true, 'message' => 'Mobile is required for registration OTP.']);
    exit;
}

$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$createdAt = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO otp_verification (Email, Mobile, OTP, Purpose, CreatedAt, IsUsed) VALUES (?, ?, ?, ?, ?, 0)");
if (!$stmt) {
    echo json_encode(['error' => true, 'message' => 'Database error. Ensure otp_verification table exists.']);
    exit;
}
$stmt->bind_param('sssss', $email, $mobile, $otp, $purpose, $createdAt);
$stmt->execute();
$stmt->close();

$subject = $purpose === 'forgot' ? 'Reset your password - OTP' : 'Verify your account - OTP';
$body = "Your OTP is: $otp. Valid for 10 minutes. Do not share.";
$mailError = null;
if (!sendMailViaSMTP($email, $subject, $body, null, '', $mailError)) {
    // Log if needed; still return success so we don't leak email delivery status
    if (function_exists('WriteLog')) {
        WriteLog('send_otp_mail_fail', ['email' => $email, 'error' => $mailError]);
    }
}

if ($mobile !== '') {
    $smsFields = [
        'route' => 'q',
        'message' => "Your OTP is $otp. Valid for 10 minutes.",
        'numbers' => preg_replace('/\D/', '', $mobile),
    ];
    sendSmsOtp($smsFields);
}

echo json_encode([
    'error' => false,
    'message' => 'OTP sent to your email' . ($mobile !== '' ? ' and mobile' : '') . '.',
]);
