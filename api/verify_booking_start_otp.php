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
if ($booking['BookingStatus'] === 'WIP') {
    echo json_encode([
        "error" => true,
        "message" => "Service already started"
    ]);
    exit;
}

/* =========================
   4️⃣ VERIFY OTP
========================= */
if ($booking['StartOtp'] != $Otp) {
    echo json_encode([
        "error" => true,
        "message" => "Invalid OTP"
    ]);
    exit;
}

/* =========================
   5️⃣ UPDATE BOOKING → WIP
========================= */
_UpdateTableRecords_prepare($conn, "service_bookings", [
    "BookingStatus" => "WIP",
    "ServiceStartAt"=> $now,
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
    "action"        => "WIP",
    "reviewed_by"   => $booking['TechnicianID'],
    "reviewed_role" => "TECHNICIAN",
    "reviewed_at"   => $now
]);

/* =========================
   7️⃣ CUSTOMER NOTIFICATION
========================= */
$customerPayload = [
    "type"         => "SERVICE_STARTED",
    "booking_code" => $BookingCode,
    "message"      => "Your service has started"
];

_InsertTableRecords_prepare($conn, "push_notifications_log", [
    "user_id"    => $booking['Phone'],
    "title"      => "Service Started",
    "body"       => "Your service has started successfully",
    "payload"    => json_encode($customerPayload),
    "status"     => "PENDING",
    "created_at" => $now
]);

/* =========================
   8️⃣ TECHNICIAN NOTIFICATION (OPTIONAL)
========================= */
_InsertTableRecords_prepare($conn, "push_notifications_log", [
    "user_id"    => $booking['TechnicianID'],
    "title"      => "Service Started",
    "body"       => "You have successfully started the service",
    "payload"    => json_encode([
        "type" => "TECH_SERVICE_STARTED",
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
    "message" => "OTP verified. Service started successfully"
]);
