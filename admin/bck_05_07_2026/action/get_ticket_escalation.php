<?php
include('../../controllers/common_controllers.php');
include('../../includes/autoloader.inc.php');
include('../controller/ticket_escalation_controller.php');
$conn = _connectodb();
setTimeZone();
SessionCheck();
$session = $_SESSION;
header('Content-Type: application/json');

$ticketPK = isset($_GET['TicketPK']) ? (int) $_GET['TicketPK'] : 0;
if ($ticketPK <= 0) {
    echo json_encode(array('error' => true, 'message' => 'Invalid ticket'));
    exit;
}

te_processTicketEscalation($conn, $ticketPK, isset($session['pb_username']) ? $session['pb_username'] : 'system');
$summary = te_getEscalationSummary($conn, $ticketPK, $session);
echo json_encode(array('error' => false, 'data' => $summary));
