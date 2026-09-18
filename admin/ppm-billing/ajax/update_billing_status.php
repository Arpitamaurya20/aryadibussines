<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once('../../includes/autoloader.inc.php');
include '../../controllers/common_controllers.php';

$conn = _connectodb();
setTimeZone();

$response = array(
    'error' => true,
    'message' => 'Invalid request'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $TicketID      = isset($_POST['TicketID']) ? intval($_POST['TicketID']) : 0;
    $Status        = isset($_POST['Status']) ? $_POST['Status'] : 'Pending';
    $BilledAmount  = isset($_POST['BilledAmount']) ? $_POST['BilledAmount'] : '';
    $InvoiceNumber = isset($_POST['InvoiceNumber']) ? $_POST['InvoiceNumber'] : '';
    $Notes         = isset($_POST['Notes']) ? $_POST['Notes'] : '';
    $PeriodFrom    = isset($_POST['BillingPeriodStart']) ? $_POST['BillingPeriodStart'] : date('Y-m-01');
    $PeriodTo      = isset($_POST['BillingPeriodEnd']) ? $_POST['BillingPeriodEnd'] : date('Y-m-t');
    $CreatedBy     = isset($_SESSION['Name']) ? $_SESSION['Name'] : 'System';

    if ($TicketID > 0) {
        $billing = new Ppmbilling($conn);
        $save_data = array(
            'TicketID'           => $TicketID,
            'Status'             => $Status,
            'BilledAmount'       => $BilledAmount,
            'InvoiceNumber'      => $InvoiceNumber,
            'Notes'              => $Notes,
            'BillingPeriodStart' => $PeriodFrom,
            'BillingPeriodEnd'   => $PeriodTo,
            'CreatedBy'          => $CreatedBy
        );

        $result = $billing->saveBillingStatus($save_data);
        $response = $result;
    } else {
        $response['message'] = 'TicketID is required';
    }
}

echo json_encode($response);
exit;


