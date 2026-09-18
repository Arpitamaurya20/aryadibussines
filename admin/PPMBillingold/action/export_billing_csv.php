<?php
// Prevent any output before headers
ob_start();

session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
$UserType = SessionCheck();

require_once('../controller/ppm_billing_controller.php');

// Clear any output buffer before sending headers
ob_end_clean();

// Get and validate dates
$StartDate = isset($_GET['StartDate']) ? trim($_GET['StartDate']) : date('Y-m-01');
$EndDate = isset($_GET['EndDate']) ? trim($_GET['EndDate']) : date('Y-m-t');

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $StartDate)) {
    $StartDate = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $EndDate)) {
    $EndDate = date('Y-m-t');
}

// Get and validate CorporateID and BranchID
$CorporateID = isset($_GET['CorporateID']) ? $_GET['CorporateID'] : -1;
$BranchID = isset($_GET['BranchID']) ? $_GET['BranchID'] : -1;

// Convert to integer, handling empty strings and '0'
if ($CorporateID === '' || $CorporateID === null || $CorporateID === '0') {
    $CorporateID = -1;
} else {
    $CorporateID = intval($CorporateID);
}

if ($BranchID === '' || $BranchID === null || $BranchID === '0') {
    $BranchID = -1;
} else {
    $BranchID = intval($BranchID);
}

$filters = array(
    'CorporateID' => $CorporateID,
    'BranchID' => $BranchID,
    'StartDate' => $StartDate,
    'EndDate' => $EndDate,
    'TicketID' => '' // Export all tickets, not just one
);

// Get billing tickets data
$result = GetPPMTicketsForBilling($conn, $filters);

// Set headers for CSV download (must be before any output)
$filename = 'PPM_Billing_Export_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 to ensure Excel displays correctly
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers (matching table columns)
$headers = array(
    'Ticket ID',
    'Company',
    'Branch',
    'Equipment Number',
    'Equipment',
    'PPM Date',
    'Ticket Status',
    'Unit Rate',
    'Calculated Amount',
    'Billing Status',
    'Payment Status',
    'Billed Amount',
    'Billing Number',
    'Billed Date'
);

fputcsv($output, $headers);

// Export data rows
if (isset($result['error']) && $result['error'] == false && isset($result['data']) && is_array($result['data']) && count($result['data']) > 0) {
    foreach ($result['data'] as $row) {
        // Handle billed amount display (same logic as table)
        $billedAmount = 0;
        $billingStatus = isset($row['BillingStatus']) ? trim($row['BillingStatus']) : 'Unbilled';
        
        if ($billingStatus == 'Billed') {
            $billedAmount = isset($row['BilledAmount']) && $row['BilledAmount'] !== null ? floatval($row['BilledAmount']) : 0;
            // If billed amount is 0, use calculated amount
            if ($billedAmount == 0) {
                $billedAmount = isset($row['CalculatedAmount']) && $row['CalculatedAmount'] !== null ? floatval($row['CalculatedAmount']) : 0;
            }
        } else {
            // For unbilled, use calculated amount
            $billedAmount = isset($row['CalculatedAmount']) && $row['CalculatedAmount'] !== null ? floatval($row['CalculatedAmount']) : 0;
        }
        
        // Get values safely with proper defaults
        $ticketNumber = '';
        if (isset($row['TicketNumber']) && !empty($row['TicketNumber'])) {
            $ticketNumber = $row['TicketNumber'];
        } elseif (isset($row['TicketID'])) {
            $ticketNumber = $row['TicketID'];
        }
        
        $companyName = isset($row['CompanyName']) ? trim($row['CompanyName']) : '';
        $branchSite = isset($row['BranchSite']) ? trim($row['BranchSite']) : '';
        $EquipmentID = isset($row['EquipmentID']) ? trim($row['EquipmentID']) : '';
        $equipmentName = isset($row['EquipmentName']) ? trim($row['EquipmentName']) : '';
        $ppmDate = isset($row['PPMDate']) ? trim($row['PPMDate']) : '';
        $ticketStatus = isset($row['TicketStatus']) ? trim($row['TicketStatus']) : '';
        $unitRate = isset($row['AssetUnitRate']) && $row['AssetUnitRate'] !== null ? floatval($row['AssetUnitRate']) : 0;
        $calculatedAmount = isset($row['CalculatedAmount']) && $row['CalculatedAmount'] !== null ? floatval($row['CalculatedAmount']) : 0;
        $paymentStatus = isset($row['PaymentStatus']) ? trim($row['PaymentStatus']) : 'Pending';
        $billingNumber = isset($row['BillingNumber']) && $row['BillingNumber'] !== null ? trim($row['BillingNumber']) : '';
        $billedDate = isset($row['BilledDate']) && $row['BilledDate'] !== null ? trim($row['BilledDate']) : '';
        
        $csvRow = array(
            $ticketNumber,
            $companyName,
            $branchSite,
            $EquipmentID,
            $equipmentName,
            $ppmDate,
            $ticketStatus,
            number_format($unitRate, 2, '.', ''),
            number_format($calculatedAmount, 2, '.', ''),
            $billingStatus,
            $paymentStatus,
            number_format($billedAmount, 2, '.', ''),
            $billingNumber,
            $billedDate
        );
        
        fputcsv($output, $csvRow);
    }
} else {
    // If no data or error, write error message
    if (isset($result['error']) && $result['error'] == true) {
        $errorMsg = isset($result['message']) ? $result['message'] : 'Unknown error';
        $errorRow = array('Error: ' . $errorMsg);
        fputcsv($output, $errorRow);
    } else {
        $noDataRow = array('No data found for the selected filters');
        fputcsv($output, $noDataRow);
    }
}

fclose($output);
exit;
?>
