<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents("php://input");
$data = json_decode($data_raw, true);

$response = array();
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporateticket_obj = new Corporateticket($conn);

/* ================= REQUIRED VALIDATION ================= */
$required = ['TicketQuotationID', 'LineItemID', 'quantity', 'TicketID', 'CreatedBy'];

foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        $response['error'] = true;
        $response['message'] = "Missing parameter: $field";
        echo json_encode($response);
        exit;
    }
}

/* ================= COMMON DATA ================= */
$data['CreatedDate'] = date('Y-m-d');
$data['CreatedTime'] = date('H:i:s');

$TicketQuotationID = intval($data['TicketQuotationID']);

/* ================= CREATE / UPDATE QUOTATION ================= */
if ($TicketQuotationID == -1) {

    $data['QuotationStatus'] = "Draft";
    $data['Remarks'] = "";

    $quotation_response = $corporateticket_obj->UpdateTicketQuotation($data);

    if ($quotation_response['error'] == false) {

        $data['QuotationID'] = $quotation_response['last_insert_id'];

        // Insert quotation history
        $corporateticket_obj->UpdateTicketQuotationHistory($data);

    } else {
        $response['error'] = true;
        $response['message'] = "Unable to create quotation";
        echo json_encode($response);
        exit;
    }

} else {
    $data['QuotationID'] = $TicketQuotationID;
}

/* ================= ADD LINE ITEM ================= */
$line_item_response = $corporateticket_obj->UpdateQuotationLineItem($data);

if ($line_item_response['error'] == false) {

    $response['error'] = false;
    $response['message'] = "Line item added to quotation successfully";
    $response['QuotationID'] = $data['QuotationID'];

} else {

    $response['error'] = true;
    $response['message'] = "Failed to add line item";
}

echo json_encode($response);
?>
