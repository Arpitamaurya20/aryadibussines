<?php
/**
 * New user registration with OTP verification.
 * Tables: users (ID, Email, Password, UserType, CreatedDate, CreatedTime, IsActive, ...)
 *         user_details (ID, UserID, Name, Mobile, Email, UserType, CreatedDate, CreatedBy, IsActive)
 * Flow: Call zeltohealth_send_otp first with Purpose=register, then call this with OTP.
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
$name = isset($data['Name']) ? trim($data['Name']) : '';
$email = isset($data['Email']) ? trim($data['Email']) : '';
$mobile = isset($data['Mobile']) ? trim($data['Mobile']) : '';
$password = isset($data['Password']) ? $data['Password'] : '';
$otp = isset($data['OTP']) ? trim($data['OTP']) : '';
$userType = 'User';

if ($name === '' || $email === '' || $mobile === '' || $password === '' || $otp === '') {
    echo json_encode(['error' => true, 'message' => 'Name, Email, Mobile, Password and OTP are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => true, 'message' => 'Invalid email format.']);
    exit;
}

if (strlen($password) < 4) {
    echo json_encode(['error' => true, 'message' => 'Password must be at least 4 characters.']);
    exit;
}

$stmt = $conn->prepare("SELECT ID, OTP, CreatedAt FROM otp_verification WHERE Email = ? AND Purpose = 'register' AND IsUsed = 0 ORDER BY ID DESC LIMIT 1");
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

$check = $conn->prepare("SELECT ID FROM users WHERE Email = ? LIMIT 1");
$check->bind_param('s', $email);
$check->execute();
$r = $check->get_result();
if ($r && $r->num_rows > 0) {
    $check->close();
    echo json_encode(['error' => true, 'message' => 'This email is already registered.']);
    exit;
}
$check->close();

$checkM = $conn->prepare("SELECT ID FROM user_details WHERE Mobile = ? LIMIT 1");
$checkM->bind_param('s', $mobile);
$checkM->execute();
$rM = $checkM->get_result();
if ($rM && $rM->num_rows > 0) {
    $checkM->close();
    echo json_encode(['error' => true, 'message' => 'This mobile number is already registered.']);
    exit;
}
$checkM->close();

$passwordHash = md5($password);
$createdDate = date('Y-m-d');
$createdTime = date('H:i:s');

$ins = $conn->prepare("INSERT INTO users (Email, Password, UserType, CreatedDate, CreatedTime, IsActive) VALUES (?, ?, ?, ?, ?, 1)");
if (!$ins) {
    echo json_encode(['error' => true, 'message' => 'Database error.']);
    exit;
}
$ins->bind_param('sssss', $email, $passwordHash, $userType, $createdDate, $createdTime);
$ins->execute();
$userId = $conn->insert_id;
$ins->close();

if ($userId <= 0) {
    echo json_encode(['error' => true, 'message' => 'Registration failed.']);
    exit;
}

$ins2 = $conn->prepare("INSERT INTO user_details (UserID, Name, Mobile, Email, UserType, CreatedDate, CreatedBy, IsActive) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
if (!$ins2) {
    $conn->query("DELETE FROM users WHERE ID = " . (int) $userId);
    echo json_encode(['error' => true, 'message' => 'Database error.']);
    exit;
}
$ins2->bind_param('isssssi', $userId, $name, $mobile, $email, $userType, $createdDate, $userId);
$ins2->execute();
$ins2->close();

$upd = $conn->prepare("UPDATE otp_verification SET IsUsed = 1 WHERE Email = ? AND Purpose = 'register'");
if ($upd) {
    $upd->bind_param('s', $email);
    $upd->execute();
    $upd->close();
}

echo json_encode([
    'error' => false,
    'message' => 'Account created successfully. You can now login.',
    'user_id' => (int) $userId,
]);
