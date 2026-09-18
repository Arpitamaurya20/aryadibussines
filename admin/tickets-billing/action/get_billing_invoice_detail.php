<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
require_once('../controller/ticket_billing_controller.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

$billingNumber = isset($_GET['BillingNumber']) ? trim($_GET['BillingNumber']) : '';
$result = GetBillingInvoiceDetail($conn, $billingNumber);

if ($result['error'] == false) {
    echo json_encode(array(
        'error' => false,
        'message' => 'Billing invoice detail fetched successfully',
        'data' => $result['data']
    ));
} else {
    echo json_encode(array(
        'error' => true,
        'message' => isset($result['message']) ? $result['message'] : 'Unable to fetch billing invoice detail'
    ));
}
?>
