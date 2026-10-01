<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
ob_end_clean();

$response = array('error' => true, 'message' => 'Technical Problem. Please try again');
$conn = _connectodb();
if (!$conn) {
    echo json_encode($response);
    exit;
}

$TicketID = isset($_POST['TicketID']) ? (int) $_POST['TicketID'] : 0;
$TicketType = isset($_POST['service_type']) ? trim((string) $_POST['service_type']) : '';
$allowed = array('R&M', 'Projects', 'Supply', 'AMC');

if ($TicketID <= 0 || $TicketType === '' || !in_array($TicketType, $allowed, true)) {
    $response['message'] = 'Invalid ticket type';
    echo json_encode($response);
    exit;
}

$ticketTypeEsc = mysqli_real_escape_string($conn, $TicketType);
$sql = "UPDATE corporate_tickets SET Type = '$ticketTypeEsc' WHERE ID = $TicketID";
if (mysqli_query($conn, $sql)) {
    $response['error'] = false;
    $response['message'] = 'Ticket Type has been Updated';
} else {
    $response['message'] = 'Could not update ticket type';
}

echo json_encode($response);
exit;
