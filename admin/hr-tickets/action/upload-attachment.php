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

$commentId = isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0;
$result = $hr->uploadAttachment($ticketId, $_FILES, hr_ticket_session_username(), $commentId);

if (empty($result['error']) && !hasHrTicketManageAccess($_SESSION['Roles'] ?? [])) {
	$title = 'Attachment on HR Ticket — ' . ($ticket['ticket_code'] ?? '');
	$body = ($ticket['employee_name'] ?? 'Employee') . ' uploaded an attachment.';
	$targets = (int) ($ticket['assigned_to'] ?? 0) > 0
		? [(int) $ticket['assigned_to']]
		: hr_ticket_hr_employee_ids_for_notification($conn);
	hr_ticket_notify_employees($conn, $targets, $title, $body, [
		'module' => 'hr_ticket',
		'screen' => 'hr_ticket_manage',
		'ticket_id' => $ticketId,
		'ticket_code' => $ticket['ticket_code'] ?? '',
	], hr_ticket_session_username());
} elseif (empty($result['error']) && hasHrTicketManageAccess($_SESSION['Roles'] ?? [])) {
	$title = 'HR Attachment — ' . ($ticket['ticket_code'] ?? '');
	hr_ticket_notify_employees($conn, [(int) ($ticket['employee_id'] ?? 0)], $title, 'HR uploaded an attachment to your ticket.', [
		'module' => 'hr_ticket',
		'screen' => 'hr_ticket',
		'ticket_id' => $ticketId,
		'ticket_code' => $ticket['ticket_code'] ?? '',
	], hr_ticket_session_username());
}

echo json_encode($result);
