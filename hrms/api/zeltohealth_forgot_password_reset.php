<?php
/**
 * Forgot password – reset with OTP.
 * Body: Email, OTP, NewPassword. Updates users.Password (MD5 to match login).
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/common-api-header.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true) ?: [];
$email = isset($data['Email']) ? trim($data['Email']) : '';
$otp = isset($data['OTP']) ? trim($data['OTP']) : '';
$newPassword = isset($data['NewPassword']) ? $data['NewPassword'] : '';

if ($email === '' || $otp === '' || $newPassword === '') {
    echo json_encode(['error' => true, 'message' => 'Email, OTP and NewPassword are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => true, 'message' => 'Invalid email format.']);
    exit;
}

if (strlen($newPassword) < 4) {
    echo json_encode(['error' => true, 'message' => 'Password must be at least 4 characters.']);
    exit;
}

$stmt = $conn->prepare("SELECT ID, OTP, CreatedAt FROM otp_verification WHERE Email = ? AND Purpose = 'forgot' AND IsUsed = 0 ORDER BY ID DESC LIMIT 1");
if (!$stmt) {
    echo json_encode(['error' => true, 'message' => 'Service error.']);
    exit;
}
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$otpRow = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$otpRow || $otpRow['OTP'] !== $otp) {
    echo json_encode(['error' => true, 'message' => 'Invalid or expired OTP. Request a new one.']);
    exit;
}

$created = strtotime($otpRow['CreatedAt']);
if (time() - $created > 600) {
    echo json_encode(['error' => true, 'message' => 'OTP expired. Request a new one.']);
    exit;
}

$passwordHash = md5($newPassword);
$upd = $conn->prepare("UPDATE users SET Password = ? WHERE Email = ?");
if (!$upd) {
    echo json_encode(['error' => true, 'message' => 'Database error.']);
    exit;
}
$upd->bind_param('ss', $passwordHash, $email);
$upd->execute();
$upd->close();

$mark = $conn->prepare("UPDATE otp_verification SET IsUsed = 1 WHERE Email = ? AND Purpose = 'forgot'");
if ($mark) {
    $mark->bind_param('s', $email);
    $mark->execute();
    $mark->close();
}

echo json_encode([
    'error' => false,
    'message' => 'Password updated successfully. You can now login with your new password.',
]);
