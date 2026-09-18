<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
require_once('../controller/ticket_billing_controller.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

$filters = array(
    'CorporateID' => isset($_GET['CorporateID']) ? intval($_GET['CorporateID']) : -1,
    'BranchID' => isset($_GET['BranchID']) ? intval($_GET['BranchID']) : -1,
    'RegionID' => isset($_GET['RegionID']) ? intval($_GET['RegionID']) : -1,
    'StateID' => isset($_GET['StateID']) ? intval($_GET['StateID']) : -1,
    'StartDate' => isset($_GET['StartDate']) ? $_GET['StartDate'] : date('Y-m-01', strtotime('-11 months')),
    'EndDate' => isset($_GET['EndDate']) ? $_GET['EndDate'] : date('Y-m-t'),
    'BillingNumber' => isset($_GET['BillingNumber']) ? trim($_GET['BillingNumber']) : ''
);

$result = GetBillingInvoicesList($conn, $filters);
if ($result['error'] == false) {
    echo json_encode(array(
        'error' => false,
        'message' => 'Billing invoices fetched successfully',
        'data' => $result['data'],
        'count' => count($result['data'])
    ));
} else {
    echo json_encode(array(
        'error' => true,
        'message' => isset($result['message']) ? $result['message'] : 'Unable to fetch billing invoices',
        'data' => array()
    ));
}
?>
