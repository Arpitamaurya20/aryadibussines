<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
require_once(__DIR__ . '/quotation_pending_line_item_helper.php');

$response = array(
    'error' => true,
    'message' => 'Unable to add line item.'
);

if (!isset($_SESSION['pb_username'])) {
    $response['message'] = 'Please login first!';
    echo json_encode($response);
    exit;
}

if (
    !isset($_POST['TicketQuotationID'], $_POST['LineItemID'], $_POST['quantity'], $_POST['TicketID'])
) {
    $response['message'] = 'Technical Problem!';
    echo json_encode($response);
    exit;
}

$quotationId = (int) $_POST['TicketQuotationID'];
if ($quotationId <= 0) {
    $response['message'] = 'Quotation not found.';
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
    $response['message'] = 'Line items can only be added when quotation status is Quote Sent Approval Pending.';
    echo json_encode($response);
    exit;
}

$data = array(
    'QuotationID' => $quotationId,
    'LineItemID' => (int) $_POST['LineItemID'],
    'quantity' => (int) $_POST['quantity'],
    'CreatedBy' => $_SESSION['pb_username'],
    'CreatedDate' => date('Y-m-d'),
    'CreatedTime' => date('H:i:s')
);

$response_line_item = addPendingQuotationLineItem($conn, $core, $data);

if (!empty($response_line_item['error'])) {
    $response['message'] = !empty($response_line_item['message'])
        ? $response_line_item['message']
        : 'Technical Problem';
    echo json_encode($response);
    exit;
}

$corporateticket_obj->UpdateTicketQuotationHistory(array(
    'QuotationID' => $quotationId,
    'QuotationStatus' => 'Quote Sent Approval Pending',
    'Remarks' => 'Line item added while quote sent approval pending.',
    'CreatedBy' => $data['CreatedBy'],
    'CreatedDate' => $data['CreatedDate'],
    'CreatedTime' => $data['CreatedTime']
));

echo json_encode(array(
    'error' => false,
    'message' => 'Line Item Added to Quotation'
));
exit;
