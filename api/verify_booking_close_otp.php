<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
require_once('../admin/controllers/common_controllers.php');

$conn = _connectodb();
setTimeZone();

$data = json_decode(file_get_contents('php://input'), true);

/* =========================
   1️⃣ VALIDATION
========================= */
if (empty($data['BookingCode']) || empty($data['Otp'])) {
    echo json_encode([
        "error" => true,
        "message" => "BookingCode and OTP are required"
    ]);
    exit;
}

$BookingCode = cleantext($data['BookingCode']);
$Otp         = cleantext($data['Otp']);
$now         = date("Y-m-d H:i:s");

/* =========================
   2️⃣ FETCH BOOKING
========================= */
$booking = _getTableDetails(
    $conn,
    "service_bookings",
    "WHERE BookingCode = '$BookingCode'"
);

if (empty($booking)) {
    echo json_encode([
        "error" => true,
        "message" => "Invalid BookingCode"
    ]);
    exit;
}

/* =========================
   3️⃣ CHECK STATUS
========================= */
if ($booking['BookingStatus'] !== 'WIP') {
    echo json_encode([
        "error" => true,
        "message" => "Service not in progress"
    ]);
    exit;
}

/* =========================
   4️⃣ VERIFY CLOSE OTP
========================= */
if ($booking['CloseOtp'] != $Otp) {
    echo json_encode([
        "error" => true,
        "message" => "Invalid Close OTP"
    ]);
    exit;
}

/* =========================
   5️⃣ UPDATE BOOKING → CLOSED
========================= */
_UpdateTableRecords_prepare($conn, "service_bookings", [
    "BookingStatus" => "CLOSED",
    "ServiceEndAt"  => $now,
    "UpdatedDate"   => date("Y-m-d"),
    "UpdatedTime"   => date("H:i:s")
], [
    "BookingCode" => $BookingCode
]);

/* =========================
   6️⃣ BOOKING HISTORY
========================= */
_InsertTableRecords_prepare($conn, "booking_reviews", [
    "booking_id"    => $BookingCode,
    "action"        => "CLOSED",
    "reviewed_by"   => $booking['TechnicianID'],
    "reviewed_role" => "TECHNICIAN",
    "reviewed_at"   => $now
]);

/* =========================
   7️⃣ CUSTOMER NOTIFICATION
========================= */
_InsertTableRecords_prepare($conn, "push_notifications_log", [
    "user_id"    => $booking['Phone'],
    "title"      => "Service Completed",
    "body"       => "Your service has been completed successfully",
    "payload"    => json_encode([
        "booking_code" => $BookingCode
    ]),
    "status"     => "PENDING",
    "created_at" => $now
]);

/* =========================
   8️⃣ TECHNICIAN NOTIFICATION
========================= */
_InsertTableRecords_prepare($conn, "push_notifications_log", [
    "user_id"    => $booking['TechnicianID'],
    "title"      => "Service Closed",
    "body"       => "You have successfully closed the service",
    "payload"    => json_encode([
        "booking_code" => $BookingCode
    ]),
    "status"     => "PENDING",
    "created_at" => $now
]);

/* =========================
   9️⃣ RESPONSE
========================= */
echo json_encode([
    "error"   => false,
    "message" => "OTP verified. Service closed successfully"
]);
