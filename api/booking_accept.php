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
    empty($data['LeadID'])
) {
    echo json_encode([
        "error" => true,
        "message" => "BookingCode and LeadID required"
    ]);
    exit;
}

$BookingCode = cleantext($data['BookingCode']);
$LeadID      = (int)$data['LeadID'];

/* =====================
   ACCEPT BOOKING
===================== */
$result = acceptBooking($conn, $BookingCode, $LeadID);

/* =====================
   HANDLE FAILURE
===================== */
if ($result['error']) {
    echo json_encode($result);
    exit;
}

/* =====================
   NOTIFICATION QUEUE
===================== */
logNotification($conn, [
    "user_id" => $LeadID,
    "title"   => "Booking Accepted",
    "body"    => "You have accepted booking $BookingCode",
    "payload" => json_encode([
        "type"        => "BOOKING_ACCEPTED",
        "bookingCode" => $BookingCode
    ])
]);

/* =====================
   SUCCESS RESPONSE
===================== */
echo json_encode([
    "error"   => false,
    "message" => "Booking accepted successfully",
    "data"    => [
        "BookingCode" => $BookingCode
    ]
]);
exit;
