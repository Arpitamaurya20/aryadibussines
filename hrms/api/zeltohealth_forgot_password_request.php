<?php
/**
 * Forgot password – request OTP.
 * Sends OTP to user's email and mobile (from user_details). Call zeltohealth_forgot_password_reset with OTP to set new password.
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

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => true, 'message' => 'Valid Email is required.']);
    exit;
}

$stmt = $conn->prepare("SELECT ID FROM users WHERE Email = ? AND IsActive = 1 LIMIT 1");
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

$userId = $user['ID'];
$mobile = '';
$stmt_m = $conn->prepare("SELECT Mobile FROM user_details WHERE UserID = ? LIMIT 1");
$stmt_m->bind_param('i', $userId);
$stmt_m->execute();
$rm = $stmt_m->get_result();
if ($rm && ($row = $rm->fetch_assoc()) && !empty($row['Mobile'])) {
    $mobile = trim($row['Mobile']);
}
$stmt_m->close();

$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$createdAt = date('Y-m-d H:i:s');

$ins = $conn->prepare("INSERT INTO otp_verification (Email, Mobile, OTP, Purpose, CreatedAt, IsUsed) VALUES (?, ?, ?, 'forgot', ?, 0)");
if (!$ins) {
    echo json_encode(['error' => true, 'message' => 'Database error.']);
    exit;
}
$ins->bind_param('ssss', $email, $mobile, $otp, $createdAt);
$ins->execute();
$ins->close();

$subject = 'Reset your password - OTP';
$body = "Your OTP to reset password is: $otp. Valid for 10 minutes. Do not share.";
$mailError = null;
if (!sendMailViaSMTP($email, $subject, $body, null, '', $mailError)) {
    if (function_exists('WriteLog')) {
        WriteLog('forgot_password_mail_fail', ['email' => $email, 'error' => $mailError]);
    }
}

if ($mobile !== '') {
    $smsFields = [
        'route' => 'q',
        'message' => "Your password reset OTP is $otp. Valid for 10 minutes.",
        'numbers' => preg_replace('/\D/', '', $mobile),
    ];
    sendSmsOtp($smsFields);
}

echo json_encode([
    'error' => false,
    'message' => 'OTP sent to your email' . ($mobile !== '' ? ' and mobile' : '') . '. Use it in forgot password reset.',
]);
