<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
require_once('../controller/ticket_billing_controller.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

$data = array(
    'TicketPK' => isset($_POST['TicketPK']) ? intval($_POST['TicketPK']) : 0,
    'CurrentBillingStatus' => isset($_POST['CurrentBillingStatus']) ? $_POST['CurrentBillingStatus'] : 'Unbilled',
    'CreatedBy' => isset($_SESSION['Username']) ? $_SESSION['Username'] : 'System'
);

if ($data['TicketPK'] <= 0) {
    echo json_encode(array('error' => true, 'message' => 'Ticket ID is required'));
    exit;
}

echo json_encode(ToggleTicketBillingStatus($conn, $data));
?>
