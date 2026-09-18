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
    'BillingStatus' => isset($_POST['BillingStatus']) ? $_POST['BillingStatus'] : 'Unbilled',
    'PaymentStatus' => isset($_POST['PaymentStatus']) ? $_POST['PaymentStatus'] : 'Pending',
    'BilledAmount' => isset($_POST['BilledAmount']) ? floatval($_POST['BilledAmount']) : 0,
    'BillingNumber' => isset($_POST['BillingNumber']) ? $_POST['BillingNumber'] : '',
    'BilledDate' => isset($_POST['BilledDate']) ? $_POST['BilledDate'] : date('Y-m-d'),
    'BillingPeriod' => isset($_POST['BillingPeriod']) ? $_POST['BillingPeriod'] : 'Monthly',
    'BillingStartDate' => isset($_POST['BillingStartDate']) ? $_POST['BillingStartDate'] : date('Y-m-01'),
    'BillingEndDate' => isset($_POST['BillingEndDate']) ? $_POST['BillingEndDate'] : date('Y-m-t'),
    'Remarks' => isset($_POST['Remarks']) ? $_POST['Remarks'] : '',
    'CreatedBy' => isset($_SESSION['Username']) ? $_SESSION['Username'] : 'System',
    'SendZoho' => isset($_POST['SendZoho']) ? $_POST['SendZoho'] : false
);

if ($data['TicketID'] == 0) {
    $response['error'] = true;
    $response['message'] = "Ticket ID is required";
} else {
    $result = CreateUpdateBillingTracking($conn, $data);
    $response = $result;
    
    // If billing is successful and SendZoho is checked, prepare for Zoho PDF
    if ($response['error'] == false && isset($data['SendZoho']) && $data['SendZoho'] && !empty($response['BillingNumber'])) {
        // TODO: Integrate with Zoho PDF API here
        // For now, just add a note
        $response['zoho_note'] = "Billing number " . $response['BillingNumber'] . " is ready for Zoho PDF generation";
        $response['message'] .= " Billing number generated: " . $response['BillingNumber'];
    }
}

echo json_encode($response);
?>
