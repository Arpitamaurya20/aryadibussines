<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();
require_once('../../includes/autoloader.inc.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'error' => true,
    'message' => 'Unable to update quotation dates.'
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
$revisedDate = trim((string) ($_POST['QuotationDate'] ?? ''));
$expiryDate = trim((string) ($_POST['QuotationExpiryDate'] ?? ''));

if ($revisedDate === '' || $expiryDate === '') {
    $response['message'] = 'Revised date and expiry date are required.';
    echo json_encode($response);
    exit;
}

$revisedDateObj = DateTime::createFromFormat('Y-m-d', $revisedDate);
$expiryDateObj = DateTime::createFromFormat('Y-m-d', $expiryDate);

if (!$revisedDateObj || $revisedDateObj->format('Y-m-d') !== $revisedDate) {
    $response['message'] = 'Invalid revised date format. Use YYYY-MM-DD.';
    echo json_encode($response);
    exit;
}

if (!$expiryDateObj || $expiryDateObj->format('Y-m-d') !== $expiryDate) {
    $response['message'] = 'Invalid expiry date format. Use YYYY-MM-DD.';
    echo json_encode($response);
    exit;
}

if ($expiryDateObj < $revisedDateObj) {
    $response['message'] = 'Expiry date cannot be before revised date.';
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
    $response['message'] = 'Revised date can only be updated when quotation status is Quote Sent Approval Pending.';
    echo json_encode($response);
    exit;
}

$createdBy = $_SESSION['pb_username'];
$createdDate = date('Y-m-d');
$createdTime = date('H:i:s');

$revisedDateEsc = mysqli_real_escape_string($conn, $revisedDate);
$expiryDateEsc = mysqli_real_escape_string($conn, $expiryDate);

$sqlUpdate = " QuotationDate = '$revisedDateEsc',
               QuotationExpiryDate = '$expiryDateEsc',
               UpdatedDate = '$createdDate'
               WHERE ID = $quotationId
               AND QuotationStatus = 'Quote Sent Approval Pending'
               AND IsActive = 1";

$updateResult = $core->_UpdateTableRecords($conn, 'corporate_ticket_quotation', $sqlUpdate);

if (!empty($updateResult['error'])) {
    $response['message'] = 'Failed to update quotation dates.';
    echo json_encode($response);
    exit;
}

$historyRemarks = 'Quotation dates revised. Revised Date: ' . $revisedDate . ', Expiry Date: ' . $expiryDate;

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
    'message' => 'Quotation revised and expiry dates updated successfully.',
    'QuotationDate' => $revisedDate,
    'QuotationExpiryDate' => $expiryDate
));
exit;
