<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$conn = _connectodb();
setTimeZone();

$data = json_decode(file_get_contents("php://input"), true);

/* =====================
   VALIDATION
===================== */
if (empty($data['BookingCode'])) {
    echo json_encode([
        "error" => true,
        "message" => "BookingCode required"
    ]);
    exit;
}

$BookingCode = cleantext($data['BookingCode']);

/* =====================
   BOOKING DETAILS
===================== */
$booking = _getTableDetails(
    $conn,
    "service_bookings",
    "WHERE BookingCode = '$BookingCode'"
);

if (empty($booking)) {
    echo json_encode([
        "error" => true,
        "message" => "Booking not found"
    ]);
    exit;
}

/* =====================
   BOOKING REVIEWS TIMELINE
===================== */
$reviews = _getTableRecords(
    $conn,
    "booking_reviews",
    "WHERE booking_id = '$BookingCode' ORDER BY reviewed_at ASC"
);

/* =====================
   RESPONSE
===================== */
echo json_encode([
    "error"   => false,
    "booking" => $booking,
    "timeline"=> $reviews
]);
exit;
