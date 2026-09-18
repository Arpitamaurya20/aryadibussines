<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
include('../controller/state_manager_verification_controller.php');

header('Content-Type: application/json');

$response = array(
    'error' => true,
    'message' => 'Invalid request.',
);

$conn = _connectodb();
setTimeZone();
SessionCheck();

$ticketPK = isset($_POST['TicketPK']) ? (int) $_POST['TicketPK'] : 0;
if ($ticketPK <= 0) {
    $response['message'] = 'Ticket is required.';
    echo json_encode($response);
    exit;
}

$ticket = psmv_getTicketForVerification($conn, $ticketPK);
if (!is_array($ticket) || empty($ticket['ID'])) {
    $response['message'] = 'Ticket not found.';
    echo json_encode($response);
    exit;
}

$status = psmv_getVerificationStatusForTicket($conn, $ticketPK);
$response = array(
    'error' => false,
    'message' => 'Verification details loaded.',
    'ticket_pk' => $ticketPK,
    'ticket_id' => $ticket['TicketID'],
    'can_verify' => psmv_canUserVerifyTicket($_SESSION) && psmv_isTicketStatusClosed($ticket['Status']),
    'is_verified' => $status['is_verified'],
    'checks' => $status['checks'],
    'labels' => $status['labels'],
    'required_keys' => $status['required_keys'],
    'verified_by' => $status['verified_by'],
    'verified_date' => $status['verified_date'],
    'verified_time' => $status['verified_time'],
    'customer_po_available' => $status['customer_po_available'],
    'customer_po_remarks' => $status['customer_po_remarks'],
);

echo json_encode($response);
