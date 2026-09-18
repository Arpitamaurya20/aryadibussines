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

$employeeId = hr_ticket_session_employee_id();
if ($employeeId < 1) {
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
$result = $hr->closeByEmployee($ticketId, $employeeId, hr_ticket_session_username());

if (empty($result['error'])) {
	$ticket = $hr->getTicketById($ticketId);
	if ($ticket) {
		hr_ticket_notify_on_update($conn, $ticket, 'resolved', 'closed', (int) ($ticket['assigned_to'] ?? 0), (int) ($ticket['assigned_to'] ?? 0), hr_ticket_session_username());
	}
}

echo json_encode($result);
