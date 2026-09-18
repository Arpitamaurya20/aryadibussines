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

$ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
if ($ticketId < 1) {
	echo json_encode(['error' => true, 'message' => 'Invalid ticket.']);
	exit;
}

$conn = _connectodb();
$hr = new Hrticket($conn);
$ticket = $hr->getTicketById($ticketId);
if (!$ticket || !hr_ticket_user_can_view($_SESSION, $ticket)) {
	echo json_encode(['error' => true, 'message' => 'Access denied.']);
	exit;
}

$isHr = hasHrTicketManageAccess($_SESSION['Roles'] ?? []);
$authorRole = $isHr ? 'hr' : 'employee';
$employeeId = hr_ticket_session_employee_id();

if (!$isHr && (int) ($ticket['employee_id'] ?? 0) !== $employeeId) {
	echo json_encode(['error' => true, 'message' => 'Access denied.']);
	exit;
}

$result = $hr->addComment(
	$ticketId,
	$_POST,
	$employeeId,
	hr_ticket_session_username(),
	$authorRole
);

if (empty($result['error'])) {
	$preview = substr(preg_replace('/\s+/', ' ', trim($_POST['comment_text'] ?? '')), 0, 120);
	$updatedTicket = $result['ticket'] ?? $hr->getTicketById($ticketId);
	hr_ticket_notify_on_comment($conn, $updatedTicket, $authorRole, $preview, hr_ticket_session_username());
	unset($result['ticket']);
}

echo json_encode($result);
