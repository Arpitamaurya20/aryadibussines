<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/booking/controller/booking_controller.php');

$conn = _connectodb();
setTimeZone();

$data = json_decode(file_get_contents("php://input"), true);

/* =====================
   VALIDATION
===================== */
if (
    empty($data['BookingCode']) ||
    empty($data['LeadID']) ||
    empty($data['Reason'])
) {
    echo json_encode([
        "error"   => true,
        "message" => "BookingCode, LeadID and Reason required"
    ]);
    exit;
}

$BookingCode = cleantext($data['BookingCode']);
$LeadID      = (int)$data['LeadID'];
$Reason      = cleantext($data['Reason']);

/* =====================
   REJECT BOOKING
===================== */
$result = rejectBooking($conn, $BookingCode, $LeadID, $Reason);

if (!empty($result['error'])) {
    echo json_encode($result);
    exit;
}

/* =====================
   NOTIFICATION QUEUE
===================== */
logNotification($conn, [
    "user_id" => $LeadID,
    "title"   => "Booking Rejected",
    "body"    => "You rejected booking $BookingCode",
    "payload" => json_encode([
        "type"        => "BOOKING_REJECTED",
        "bookingCode" => $BookingCode,
        "reason"      => $Reason
    ])
]);

echo json_encode([
    "error"   => false,
    "message" => "Booking rejected successfully"
]);
exit;
