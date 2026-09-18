<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
include('../controller/hr_tickets_controller.php');

$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['error' => true, 'message' => 'Invalid request.']);
	exit;
}

if (!hasHrTicketManageAccess($_SESSION['Roles'] ?? [])) {
	echo json_encode(['error' => true, 'message' => 'Access denied.']);
	exit;
}

$ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
if ($ticketId < 1) {
	echo json_encode(['error' => true, 'message' => 'Invalid ticket.']);
	exit;
}

$conn = _connectodb();
$hr = new Hrticket($conn);
$ticket = $hr->getTicketById($ticketId);
if (!$ticket) {
	echo json_encode(['error' => true, 'message' => 'Ticket not found.']);
	exit;
}

$oldStatus = (string) ($ticket['status'] ?? '');
$oldAssigned = (int) ($ticket['assigned_to'] ?? 0);

$result = $hr->updateAssignmentAndStatus($ticketId, $_POST, hr_ticket_session_username());

if (empty($result['error'])) {
	$newStatus = (string) ($result['new_status'] ?? $oldStatus);
	$newAssigned = (int) ($result['new_assigned'] ?? $oldAssigned);
	$updatedTicket = $result['ticket'] ?? $hr->getTicketById($ticketId);
	hr_ticket_notify_on_update($conn, $updatedTicket, $oldStatus, $newStatus, $oldAssigned, $newAssigned, hr_ticket_session_username());
	unset($result['ticket'], $result['old_status'], $result['new_status'], $result['old_assigned'], $result['new_assigned']);
}

echo json_encode($result);
