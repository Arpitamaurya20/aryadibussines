<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
require_once(__DIR__ . '/quotation_pending_line_item_helper.php');

$response = array(
    'error' => true,
    'message' => 'Unable to add non-ARC items.'
);

if (!isset($_SESSION['pb_username'])) {
    $response['message'] = 'Please login first!';
    echo json_encode($response);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (empty($payload) || !is_array($payload)) {
    $response['message'] = 'Invalid request data.';
    echo json_encode($response);
    exit;
}

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$core->setTimeZone();
$corporateticket_obj = new Corporateticket($conn);

$CreatedBy = $_SESSION['pb_username'];
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');

$quotationId = 0;

foreach ($payload as $row) {
    $quotationId = (int) ($row['QuotationID'] ?? 0);
    if ($quotationId <= 0) {
        $response['message'] = 'Quotation not found.';
        echo json_encode($response);
        exit;
    }

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
        $response['message'] = 'Non-ARC items can only be added when quotation status is Quote Sent Approval Pending.';
        echo json_encode($response);
        exit;
    }

    $rowData = array(
        'CompanyID' => (int) ($row['CorporateID'] ?? 0),
        'Type' => $row['type'] ?? '',
        'Category' => $row['category'] ?? '',
        'SubCategory' => $row['subcategory'] ?? '',
        'LineItemName' => $row['lineItemName'] ?? '',
        'Make' => trim((string) ($row['make'] ?? '')) !== '' ? $row['make'] : 'N.A.',
        'HSN' => trim((string) ($row['hsn'] ?? '')) !== '' ? $row['hsn'] : '0',
        'ARCCode' => 'N.A.',
        'UoM' => trim((string) ($row['uom'] ?? '')) !== '' ? $row['uom'] : 'EA',
        'Price' => $row['price'] ?? 0,
        'Tax' => trim((string) ($row['tax'] ?? '')) !== '' ? $row['tax'] : '0',
        'CreatedDate' => $CreatedDate,
        'CreatedTime' => $CreatedTime,
        'CreatedBy' => $CreatedBy,
        'ARCItem' => 0
    );

    $insertResult = $core->_InsertTableRecords_prepare($conn, 'corporate_rate_card', $rowData);
    if (!empty($insertResult['error'])) {
        $response['message'] = !empty($insertResult['message'])
            ? 'Failed to save non-ARC item: ' . $insertResult['message']
            : 'Failed to save non-ARC item.';
        echo json_encode($response);
        exit;
    }

    $lineItemResult = addPendingQuotationLineItem($conn, $core, array(
        'QuotationID' => $quotationId,
        'LineItemID' => (int) $insertResult['last_insert_id'],
        'quantity' => (int) ($row['qty'] ?? 0),
        'CreatedBy' => $CreatedBy,
        'CreatedDate' => $CreatedDate,
        'CreatedTime' => $CreatedTime
    ));

    if (!empty($lineItemResult['error'])) {
        $response['message'] = !empty($lineItemResult['message'])
            ? $lineItemResult['message']
            : 'Failed to add line item to quotation.';
        echo json_encode($response);
        exit;
    }
}

if ($quotationId > 0) {
    $quotationDetail = $corporateticket_obj->GetQuotationDetailbyID($quotationId);
    $finalQuotationStatus = 'Quote Sent Approval Pending';
    $finalMessage = 'Non-ARC items added successfully.';

    if (
        quotationRequiresCompanyAdminApproval($conn, $core, $quotationDetail)
        && !quotationCurrentUserCanApproveCompanyAdmin($conn, $core, $quotationDetail)
    ) {
        $finalQuotationStatus = 'Quote Pending Company Admin Approval';
        $finalMessage = 'Non-ARC items added. Quotation sent for company admin approval.';
        moveQuotationBackToCompanyAdminApproval(
            $conn,
            $core,
            $corporateticket_obj,
            $quotationDetail,
            $CreatedBy,
            $CreatedDate,
            $CreatedTime,
            'Non-ARC line item(s) added. Company admin approval required before resubmitting quote.'
        );
    } else {
        $corporateticket_obj->UpdateTicketQuotationHistory(array(
            'QuotationID' => $quotationId,
            'QuotationStatus' => $finalQuotationStatus,
            'Remarks' => 'Non-ARC line item(s) added while quote sent approval pending.',
            'CreatedBy' => $CreatedBy,
            'CreatedDate' => $CreatedDate,
            'CreatedTime' => $CreatedTime
        ));
    }
} else {
    $finalQuotationStatus = 'Quote Sent Approval Pending';
    $finalMessage = 'Non-ARC items added successfully.';
}

echo json_encode(array(
    'error' => false,
    'message' => $finalMessage,
    'QuotationID' => $quotationId,
    'QuotationStatus' => $finalQuotationStatus
));
exit;
