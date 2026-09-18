<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
$UserType = SessionCheck();

header('Content-Type: application/json');

$response = array();
$response['error'] = false;

require_once('../controller/ppm_billing_controller.php');

$data = array(
    'TicketID' => isset($_POST['TicketID']) ? intval($_POST['TicketID']) : 0,
    'CurrentBillingStatus' => isset($_POST['CurrentBillingStatus']) ? $_POST['CurrentBillingStatus'] : 'Unbilled',
    'BillingStartDate' => isset($_POST['BillingStartDate']) ? $_POST['BillingStartDate'] : date('Y-m-01'),
    'BillingEndDate' => isset($_POST['BillingEndDate']) ? $_POST['BillingEndDate'] : date('Y-m-t'),
    'CreatedBy' => isset($_SESSION['Username']) ? $_SESSION['Username'] : 'System'
);

if ($data['TicketID'] == 0) {
    $response['error'] = true;
    $response['message'] = "Ticket ID is required";
} else {
    $result = ToggleBillingStatus($conn, $data);
    $response = $result;
}

echo json_encode($response);
?>
