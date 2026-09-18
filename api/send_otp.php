<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents('php://input'), true);
$response = [];

if (!isset($data['phonenumber'])) {
    echo json_encode(["error" => true, "message" => "Phone number required"]);
    exit;
}

$phone = preg_replace('/\D/', '', $data['phonenumber']);
$phone = substr($phone, -10);

// Generate OTP
$otp = rand(100000, 999999);

// // Store OTP in DB
// $conn = _connectodb();
// $expiry_time = time() + 300; // 5 min expiry
// $stmt = $conn->prepare("INSERT INTO temp_otp (phone, otp, expiry) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp=?, expiry=?");
// $stmt->bind_param("iiiii", $phone, $otp, $expiry_time, $otp, $expiry_time);
// $stmt->execute();
// $stmt->close();

// Send WhatsApp OTP
$whatsapp = new Whatsapp();

// 1️⃣ Opt-in number
$optIn = $whatsapp->optInUser($phone);

// 2️⃣ Send OTP
$result = $whatsapp->sendWhatsappOtp($phone, $otp);

// Response
if (isset($result['result']) && $result['result'] === true) {
    $response = ["error" => false, "message" => "OTP sent successfully on WhatsApp"];
} else {
    $response = [
        "error" => true,
        "message" => "Failed to send OTP",
        "debug" => $result
    ];
}

echo json_encode($response);
?>
