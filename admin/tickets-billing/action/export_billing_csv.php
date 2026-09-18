<?php
ob_start();
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
require_once('../controller/ticket_billing_controller.php');

$conn = _connectodb();
SessionCheck();
ob_end_clean();

$filters = array(
    'CorporateID' => isset($_GET['CorporateID']) ? intval($_GET['CorporateID']) : -1,
    'BranchID' => isset($_GET['BranchID']) ? intval($_GET['BranchID']) : -1,
    'RegionID' => isset($_GET['RegionID']) ? intval($_GET['RegionID']) : -1,
    'StateID' => isset($_GET['StateID']) ? intval($_GET['StateID']) : -1,
    'TicketStatus' => isset($_GET['TicketStatus']) ? trim($_GET['TicketStatus']) : '',
    'TicketType' => isset($_GET['TicketType']) ? trim($_GET['TicketType']) : '',
    'StartDate' => isset($_GET['StartDate']) ? $_GET['StartDate'] : date('Y-m-01'),
    'EndDate' => isset($_GET['EndDate']) ? $_GET['EndDate'] : date('Y-m-t'),
    'TicketID' => ''
);

$result = GetTicketsForBilling($conn, $filters);

$filename = 'Ticket_Billing_Export_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, array(
    'Ticket ID',
    'Company',
    'Branch',
    'Created Date',
    'Ticket Status',
    'Quotation ID',
    'Quotation Status',
    'Quotation Amount (No GST)',
    'Billing Status',
    'Payment Status',
    'Billed Amount',
    'Billing Number',
    'Billed Date',
    'Remarks'
));

if ($result['error'] == false && is_array($result['data'])) {
    foreach ($result['data'] as $row) {
        $billingStatus = ($row['BillingStatus'] === 'Billed') ? 'Billed' : 'Unbilled';
        $calc = (float)$row['CalculatedAmount'];
        $billed = (float)$row['BilledAmount'];
        if ($billingStatus !== 'Billed' || $billed <= 0) {
            $billed = $calc;
        }
        fputcsv($output, array(
            $row['TicketNumber'],
            $row['CompanyName'],
            $row['BranchSite'],
            $row['CreatedDate'],
            $row['TicketStatus'],
            $row['QuotationID'],
            $row['QuotationStatus'],
            number_format($calc, 2, '.', ''),
            $billingStatus,
            $row['PaymentStatus'],
            number_format($billed, 2, '.', ''),
            $row['BillingNumber'],
            $row['BilledDate'],
            $row['Remarks']
        ));
    }
}

fclose($output);
exit;
?>
