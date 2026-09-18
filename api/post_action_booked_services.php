<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once("../admin/booking/controller/booking_controller.php");

setTimeZone();
$conn = _connectodb();
$data = json_decode(file_get_contents("php://input"), true);

// ==========================
// Validation
// ==========================
if (empty($data['BookingCode']) || empty($data['LeadID']) || empty($data['Action'])) {
    echo json_encode([
        "error" => true,
        "message" => "BookingCode, LeadID and Action are required"
    ]);
    exit;
}

$BookingCode = $data['BookingCode'];
$LeadID     = $data['LeadID'];
$Action     = strtoupper($data['Action']); // ACCEPT or REJECT
$Reason     = $data['Reason'] ?? ''; // Only for REJECT

$response = [];

switch ($Action) {
    case 'ACCEPT':
        $response = acceptBooking($conn, $BookingCode, $LeadID);
        // Queue notifications
        queueActionNotifications($conn, $BookingCode, $LeadID, 'ACCEPTED', $Reason);
        break;

    case 'REJECT':
        if (empty($Reason)) {
            echo json_encode([
                "error" => true,
                "message" => "Rejection reason required"
            ]);
            exit;
        }
        $response = rejectBooking($conn, $BookingCode, $LeadID, $Reason);
        // Queue notifications
        queueActionNotifications($conn, $BookingCode, $LeadID, 'REJECTED', $Reason);
        break;

    default:
        $response = [
            "error" => true,
            "message" => "Invalid Action. Use ACCEPT or REJECT"
        ];
        break;
}

echo json_encode($response);
exit;

// ==========================
// Notifications after ACCEPT/REJECT
// ==========================
function queueActionNotifications($conn, $BookingCode, $LeadID, $Action, $Reason = '')
{
    $now = date("Y-m-d H:i:s");

    // Get Booking Info
    $booking = _getTableDetails($conn, 'service_bookings', " WHERE BookingCode='$BookingCode'");
    if (!$booking) return;

    // Customer Notification
    $payloadCustomer = [
        "type"         => "BOOKING_" . $Action,
        "booking_code" => $BookingCode,
        "screen"       => "booking_details",
        "phone"        => $booking['Phone'] ?? '',
        "email"        => $booking['Email'] ?? '',
        "reason"       => $Reason,
        "channel"      => ["WHATSAPP","EMAIL"]
    ];
    _InsertTableRecords_prepare($conn, 'push_notifications_log', [
        "user_id"    => $booking['CustomerID'] ?? $booking['Phone'],
        "title"      => "Booking $Action",
        "body"       => "Your booking $BookingCode has been $Action." . ($Reason ? " Reason: $Reason" : ""),
        "payload"    => json_encode($payloadCustomer),
        "status"     => "PENDING",
        "created_at" => $now
    ]);

    // City Lead Notification (optional)
    _InsertTableRecords_prepare($conn, 'push_notifications_log', [
        "user_id"    => $LeadID,
        "title"      => "Booking $Action",
        "body"       => "Booking $BookingCode has been $Action by you",
        "payload"    => json_encode([
            "type"         => "CITY_LEAD_" . $Action,
            "booking_code" => $BookingCode,
            "screen"       => "booking_list",
            "channel"      => ["WHATSAPP","EMAIL"]
        ]),
        "status"     => "PENDING",
        "created_at" => $now
    ]);
}
