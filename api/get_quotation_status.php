<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents("php://input");
$data = json_decode($data_raw, true);

$response = array();

/* ================= VALIDATION ================= */
if (!isset($data['QuotationID'])) {
    $response['error'] = true;
    $response['message'] = "Missing QuotationID";
    echo json_encode($response);
    exit;
}

/* ================= DB INIT ================= */
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporateticket = new Corporateticket($conn);

$QuotationID = intval($data['QuotationID']);
$quotation_detail = $corporateticket->GetQuotationDetailbyID($QuotationID);

/* ================= DEFAULT RESPONSE ================= */
$response['TicketQuotationID'] = -1;
$response['QuotationStatus'] = "";

/* ================= DATA ================= */
if ($quotation_detail != null) {
    $response['TicketQuotationID'] = $quotation_detail['ID'];
    $response['QuotationStatus'] = $quotation_detail['QuotationStatus'];
}

$response['error'] = false;
echo json_encode($response);
?>
