<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();
require_once('../../includes/autoloader.inc.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'error' => true,
    'message' => 'Unable to update expected budget.'
);

if (!isset($_SESSION['pb_username'])) {
    $response['message'] = 'Please login first!';
    echo json_encode($response);
    exit;
}

if (empty($_POST['QuotationID'])) {
    $response['message'] = 'QuotationID is required.';
    echo json_encode($response);
    exit;
}

$quotationId = (int) $_POST['QuotationID'];
$expectedBudgetRaw = trim((string) ($_POST['expectedbudget'] ?? ''));

if ($expectedBudgetRaw === '') {
    $response['message'] = 'Expected budget is required.';
    echo json_encode($response);
    exit;
}

if (!is_numeric($expectedBudgetRaw)) {
    $response['message'] = 'Expected budget must be a valid number.';
    echo json_encode($response);
    exit;
}

$expectedBudget = round((float) $expectedBudgetRaw, 2);
if ($expectedBudget < 0) {
    $response['message'] = 'Expected budget cannot be negative.';
    echo json_encode($response);
    exit;
}

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$core->setTimeZone();
$corporateticket_obj = new Corporateticket($conn);

$quotationDetail = $corporateticket_obj->GetQuotationDetailbyID($quotationId);

if (empty($quotationDetail) || !is_array($quotationDetail)) {
    $response['message'] = 'Quotation not found.';
    echo json_encode($response);
    exit;
}

if ((int) ($quotationDetail['IsActive'] ?? 0) !== 1) {
    $response['message'] = 'Quotation is not active.';
    echo json_encode($response);
    exit;
}

if (($quotationDetail['QuotationStatus'] ?? '') !== 'Quote Sent Approval Pending') {
    $response['message'] = 'Expected budget can only be updated before quote approval.';
    echo json_encode($response);
    exit;
}

$createdBy = $_SESSION['pb_username'];
$createdDate = date('Y-m-d');
$createdTime = date('H:i:s');
$expectedBudgetEsc = mysqli_real_escape_string($conn, number_format($expectedBudget, 2, '.', ''));

$sqlUpdate = " expectedbudget = '$expectedBudgetEsc',
               UpdatedDate = '$createdDate'
               WHERE ID = $quotationId
               AND QuotationStatus = 'Quote Sent Approval Pending'
               AND IsActive = 1";

$updateResult = $core->_UpdateTableRecords($conn, 'corporate_ticket_quotation', $sqlUpdate);

if (!empty($updateResult['error'])) {
    $response['message'] = 'Failed to update expected budget.';
    echo json_encode($response);
    exit;
}

$historyRemarks = 'Expected budget updated to ' . number_format($expectedBudget, 2, '.', '');

$corporateticket_obj->UpdateTicketQuotationHistory(array(
    'QuotationID' => $quotationId,
    'QuotationStatus' => 'Quote Sent Approval Pending',
    'Remarks' => $historyRemarks,
    'CreatedBy' => $createdBy,
    'CreatedDate' => $createdDate,
    'CreatedTime' => $createdTime
));

echo json_encode(array(
    'error' => false,
    'message' => 'Expected budget updated successfully.',
    'expectedbudget' => number_format($expectedBudget, 2, '.', '')
));
exit;
