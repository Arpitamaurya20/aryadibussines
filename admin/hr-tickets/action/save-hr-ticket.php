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

if (!hr_ticket_user_can_raise($_SESSION)) {
	echo json_encode(['error' => true, 'message' => 'Employee profile required to raise HR tickets.']);
	exit;
}

$conn = _connectodb();
$hr = new Hrticket($conn);
$employeeId = hr_ticket_session_employee_id();
$result = $hr->createTicket($_POST, $employeeId, hr_ticket_session_username());

if (empty($result['error']) && !empty($result['id'])) {
	hr_ticket_notify_on_created($conn, (int) $result['id'], hr_ticket_session_username());
}

echo json_encode($result);
