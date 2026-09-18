<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
require_once('../controller/ticket_billing_controller.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

$ticketPKs = isset($_POST['TicketPKs']) ? $_POST['TicketPKs'] : array();
$newStage = isset($_POST['QueueStage']) ? trim($_POST['QueueStage']) : '';
$verified = isset($_POST['Verified']) ? intval($_POST['Verified']) : 0;
$billRemaining = isset($_POST['BillRemaining']) ? intval($_POST['BillRemaining']) : 0;
$actor = isset($_SESSION['Username']) ? $_SESSION['Username'] : 'System';

if ($billRemaining !== 1 && $newStage === '') {
    echo json_encode(array('error' => true, 'message' => 'Queue stage is required'));
    exit;
}

echo json_encode(UpdateTicketsBillingQueueStage($conn, $ticketPKs, $newStage, $actor, array(
    'Verified' => $verified === 1,
    'BillRemaining' => $billRemaining === 1
)));
