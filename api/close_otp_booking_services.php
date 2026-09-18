<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
require_once('../admin/controllers/common_controllers.php');

$conn = _connectodb();
setTimeZone();

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['BookingCode'])) {
    echo json_encode(["error" => true, "message" => "BookingCode required"]);
    exit;
}

$BookingCode = cleantext($data['BookingCode']);

/* =========================
   1️⃣ FETCH BOOKING
========================= */
$booking = _getTableDetails(
    $conn,
    "service_bookings",
    "WHERE BookingCode = '$BookingCode'
     AND BookingStatus = 'WIP'"
);

if (empty($booking)) {
    echo json_encode([
        "error" => true,
        "message" => "Service not in progress or invalid booking"
    ]);
    exit;
}

/* =========================
   2️⃣ PREVENT RE-GENERATION
========================= */
if (!empty($booking['CloseOtp'])) {
    echo json_encode([
        "error" => true,
        "message" => "Close OTP already generated"
    ]);
    exit;
}

/* =========================
   3️⃣ GENERATE OTP
========================= */
$otp = rand(1000, 9999);
$now = date("Y-m-d H:i:s");

/* =========================
   4️⃣ UPDATE BOOKING
========================= */
_UpdateTableRecords_prepare($conn, "service_bookings", [
    "CloseOtp"      => $otp,
    "UpdatedDate"   => date("Y-m-d"),
    "UpdatedTime"   => date("H:i:s")
], ["BookingCode" => $BookingCode]);

/* =========================
   5️⃣ BOOKING HISTORY
========================= */
_InsertTableRecords_prepare($conn, "booking_reviews", [
    "booking_id"    => $BookingCode,
    "action"        => "CLOSE_OTP_GENERATED",
    "reviewed_by"   => $booking['TechnicianID'],
    "reviewed_role" => "TECHNICIAN",
    "reviewed_at"   => $now
]);

/* =========================
   6️⃣ CUSTOMER NOTIFICATION
========================= */
_InsertTableRecords_prepare($conn, "push_notifications_log", [
    "user_id"    => $booking['CustomerID'],
    "title"      => "Service Completion OTP",
    "body"       => "Your service completion OTP is $otp",
    "payload"    => json_encode([
        "type"         => "SERVICE_CLOSE_OTP",
        "booking_code" => $BookingCode
    ]),
    "status"     => "PENDING",
    "created_at" => $now
]);

/* =========================
   7️⃣ TECHNICIAN NOTIFICATION
========================= */
_InsertTableRecords_prepare($conn, "push_notifications_log", [
    "user_id"    => $booking['TechnicianID'],
    "title"      => "Close Service OTP",
    "body"       => "Use OTP to close service",
    "payload"    => json_encode([
        "type"         => "TECH_SERVICE_CLOSE",
        "booking_code" => $BookingCode,
        "otp"          => $otp
    ]),
    "status"     => "PENDING",
    "created_at" => $now
]);

echo json_encode([
    "error"   => false,
    "message" => "Close OTP generated, booking ready to close"
]);
