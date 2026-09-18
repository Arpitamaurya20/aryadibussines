<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
$UserType = SessionCheck();

header('Content-Type: application/json');

$response = array();
$response['error'] = false;
$response['success_count'] = 0;
$response['failed_count'] = 0;
$response['messages'] = array();

require_once('../controller/ppm_billing_controller.php');

$TicketIDs = isset($_POST['TicketIDs']) ? $_POST['TicketIDs'] : array();
$BillingStatus = isset($_POST['BillingStatus']) ? $_POST['BillingStatus'] : 'Billed';
$BillingStartDate = isset($_POST['BillingStartDate']) ? $_POST['BillingStartDate'] : date('Y-m-01');
$BillingEndDate = isset($_POST['BillingEndDate']) ? $_POST['BillingEndDate'] : date('Y-m-t');
$CreatedBy = isset($_SESSION['Username']) ? $_SESSION['Username'] : 'System';

if (empty($TicketIDs) || !is_array($TicketIDs)) {
    $response['error'] = true;
    $response['message'] = "No tickets selected";
    echo json_encode($response);
    exit;
}

foreach ($TicketIDs as $TicketID) {
    $TicketID = intval($TicketID);
    if ($TicketID <= 0) {
        $response['failed_count']++;
        continue;
    }
    
    // Get ticket details
    $where = " where ID = $TicketID";
    $ticket = _getTableDetails($conn, 'ppm_tickets', $where);
    
    if (!$ticket) {
        $response['failed_count']++;
        $response['messages'][] = "Ticket ID $TicketID not found";
        continue;
    }
    
    // Get existing billing tracking
    $where_tracking = " where TicketID = $TicketID AND IsActive = 1";
    $existing = _getTableDetails($conn, 'ppm_billing_tracking', $where_tracking);
    
    // Get branch asset details
    $where_asset = " where ID = " . $ticket['BranchAssetID'];
    $asset = _getTableDetails($conn, 'branch_assets', $where_asset);
    
    if (!$asset) {
        $response['failed_count']++;
        $response['messages'][] = "Asset not found for Ticket ID $TicketID";
        continue;
    }
    
    // Get unit rate
    $unitRate = !empty($asset['UnitRate']) ? floatval($asset['UnitRate']) : floatval(0);
    
    // Get AMC dates from asset
    $amcStartDate = !empty($asset['AMCStartDate']) ? $asset['AMCStartDate'] : '';
    $amcEndDate = !empty($asset['AMCEndDate']) ? $asset['AMCEndDate'] : '';
    $PPMInterval=!empty($asset['PPMInterval']) ? $asset['PPMInterval'] : 'Monthly';
    
    // Determine billing period (for display purposes)
    $BillingPeriod = DetermineBillingPeriod($BillingStartDate, $BillingEndDate);
    
    // Calculate amount based on AMC date interval
    $CalculatedAmount = CalculateBillingAmount($unitRate, $amcStartDate, $amcEndDate,$PPMInterval);
    
    // Set BilledAmount based on status
    $BilledAmount = 0;
    if ($BillingStatus == 'Billed') {
        // Use existing billed amount if available, otherwise use calculated amount
        if ($existing && !empty($existing['BilledAmount']) && $existing['BilledAmount'] > 0) {
            $BilledAmount = floatval($existing['BilledAmount']);
        } else {
            $BilledAmount = $CalculatedAmount;
        }
    }
    
    // Prepare billing data
    $billing_data = array(
        'TicketID' => $TicketID,
        'BillingStatus' => $BillingStatus,
        'PaymentStatus' => ($BillingStatus == 'Billed') ? 'Billed' : 'Pending',
        'BilledAmount' => $BilledAmount,
        'BillingNumber' => ($existing && !empty($existing['BillingNumber'])) ? $existing['BillingNumber'] : '',
        'BilledDate' => ($BillingStatus == 'Billed') ? date('Y-m-d') : null,
        'BilledBy' => ($BillingStatus == 'Billed') ? $CreatedBy : '',
        'BillingStartDate' => $BillingStartDate,
        'BillingEndDate' => $BillingEndDate,
        'CreatedBy' => $CreatedBy
    );
    
    $result = CreateUpdateBillingTracking($conn, $billing_data);
    
    if ($result['error'] == false) {
        $response['success_count']++;
    } else {
        $response['failed_count']++;
        $response['messages'][] = "Failed to update Ticket ID $TicketID: " . (isset($result['message']) ? $result['message'] : 'Unknown error');
    }
}

if ($response['success_count'] > 0) {
    $response['message'] = "Successfully updated " . $response['success_count'] . " ticket(s) to " . $BillingStatus;
    if ($response['failed_count'] > 0) {
        $response['message'] .= ". " . $response['failed_count'] . " ticket(s) failed to update.";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Failed to update any tickets. " . implode('; ', $response['messages']);
}

echo json_encode($response);
?>
