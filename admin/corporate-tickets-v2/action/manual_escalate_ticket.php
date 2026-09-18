<?php
include('../../controllers/common_controllers.php');
include('../../includes/autoloader.inc.php');
include('../controller/ticket_escalation_controller.php');
$conn = _connectodb();
setTimeZone();
SessionCheck();
$session = $_SESSION;
header('Content-Type: application/json');

$response = array('error' => true, 'message' => 'Invalid request');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = te_manuallyEscalateTicket($conn, $_POST, $session);
}
echo json_encode($response);
