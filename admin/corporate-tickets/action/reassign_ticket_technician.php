<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/corporate_tickets_controller.php');
include('../controller/ticket_escalation_controller.php');

header('Content-Type: application/json; charset=utf-8');

$conn = _connectodb();
setTimeZone();
SessionCheck();

$response = array('error' => true, 'message' => 'Invalid request');
$ticketPK = isset($_POST['TicketPK']) ? (int) $_POST['TicketPK'] : 0;
$technicianId = isset($_POST['AssignedTo']) ? (int) $_POST['AssignedTo'] : 0;
$remarks = isset($_POST['Remarks']) ? trim((string) $_POST['Remarks']) : '';

if ($ticketPK <= 0 || $technicianId <= 0) {
    $response['message'] = 'Ticket and technician are required';
    echo json_encode($response);
    exit;
}

if (!te_userCanReassignTechnician($conn, $_SESSION, $ticketPK)) {
    $response['message'] = 'You are not authorized to reassign this ticket';
    echo json_encode($response);
    exit;
}

$ticket = _getTableDetails($conn, 'corporate_tickets', " WHERE ID = $ticketPK");
$reassignCheck = ct_validateTechnicianReassignTarget($conn, is_array($ticket) ? $ticket : array(), $technicianId);
if ($reassignCheck['error']) {
    echo json_encode($reassignCheck);
    exit;
}

$result = ct_reassignTicketToTechnician($conn, array(
    'TicketID' => $ticketPK,
    'AssignedTo' => $technicianId,
    'UpdatedBy' => $_SESSION['pb_username'] ?? 'system',
    'Remarks' => $remarks !== '' ? $remarks : 'Ticket reassigned to technician',
));

echo json_encode($result);
exit;
