<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
include('../controller/hc_tickets_controller.php');

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
$hc = new Hcticket($conn);
$result = $hc->updateAssignment($ticketId, $_POST, hc_ticket_created_by());
echo json_encode($result);
