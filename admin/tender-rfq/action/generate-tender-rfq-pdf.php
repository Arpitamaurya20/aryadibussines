<?php
session_start();
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php';
include('../../controllers/common_controllers.php');
include('../controller/tender_rfq_controller.php');

$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);
if (!isset($_Nav_Tender_RFQ) || !$_Nav_Tender_RFQ) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied.');
}
$conn = _connectodb();
$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$roles = tender_rfq_session_roles();
$trfq = new TenderRfq($conn);
$ticket = $trfq->getTicketById($ticketId, $roles, $UserType);
if (!$ticket) {
    header('HTTP/1.0 404 Not Found');
    exit('Not found.');
}

$headers = $trfq->decodeHeaders($ticket);
$importRows = $trfq->getImportRows($ticketId);
$publicId = $trfq->ticketPublicId($ticketId);
$qref = $ticket['quotation_ref'] ? $ticket['quotation_ref'] : $trfq->defaultQuotationRef($ticketId);
$qdate = $ticket['quotation_date'] ? $ticket['quotation_date'] : date('Y-m-d');
$exp = $ticket['expiry_date'] ? $ticket['expiry_date'] : '';

$h = function ($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$br = function ($s) use ($h) {
    return str_replace(["\r\n", "\n", "\r"], '<br>', $h($s));
};

$tableHead = '';
foreach ($headers as $col) {
    $tableHead .= '<th style="border:1px solid #000;padding:6px;background:#08475e;color:#fff;font-size:10px;">' . $h($col) . '</th>';
}
$tableBody = '';
$ri = 1;
foreach ($importRows as $r) {
    $obj = json_decode($r['dynamic_data'], true);
    if (!is_array($obj)) {
        $obj = [];
    }
    $tableBody .= '<tr><td style="border:1px solid #000;padding:5px;">' . $ri . '</td>';
    foreach ($headers as $col) {
        $tableBody .= '<td style="border:1px solid #000;padding:5px;font-size:10px;">' . $h($obj[$col] ?? '') . '</td>';
    }
    $tableBody .= '</tr>';
    $ri++;
}
if ($tableBody === '') {
    if ($tableHead === '') {
        $tableHead = '<th style="border:1px solid #000;padding:6px;background:#08475e;color:#fff;">—</th>';
    }
    $colspan = max(2, count($headers) + 1);
    $tableBody = '<tr><td style="border:1px solid #000;padding:8px;" colspan="' . (int) $colspan . '">No CSV rows imported yet.</td></tr>';
}

$media_asset = 'techx-quotation.png';
$header_company_name = 'Techxpert Facilities India Private Limited';
$header_company_address = '451 - 452, First Floor, Leela Ram Market, <br>Masjid Moth, South Extension Part - 2,<br>New Delhi, Delhi - 110049, India';
$gst_block = '<tr><td style="width:40%;"> GSTIN - 07AAICT0561A1ZR</td></tr>
<tr><td style="width:40%;"> PAN - AAICT0561A</td></tr>
<tr><td style="width:40%;">Phone - 9873669227</td></tr>';

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:10pt;margin:0;padding:0;}
table{border-collapse:collapse;width:100%;table-layout:fixed;}
.tbl{border:1px solid #000;}
.row_hdr td{background:#08475e;color:#fff;font-weight:bold;text-align:center;padding:7px 6px;font-size:11px;border:1px solid #000;}
.row_hdr td:first-child{border-right:1px solid #fff;}
.cell{padding:8px 10px;vertical-align:top;font-size:10px;}
.cell_l{border-right:1px solid #000;}
.lbl{font-weight:bold;width:42%;vertical-align:top;padding:3px 6px 3px 0;font-size:10px;}
.val{vertical-align:top;padding:3px 0;font-size:10px;}
.cert{font-size:8pt;color:#333;padding:4px 0 8px 0;}
</style></head><body>
<table style="width:100%;margin:0;padding:0;"><tr><td style="padding:0;margin:0;">
<img src="../../media/pdf-assets/' . $media_asset . '" style="width:100%;display:block;margin:0;padding:0;">
</td></tr></table>
<div class="cert">( An ISO 9001:2015, ISO 14001:2015 &amp; ISO 45001:2018 Certified Company )</div>

<table class="tbl" style="margin-top:6px;">
<tr>
<td class="cell cell_l" style="width:50%;">
  <div style="font-weight:bold;font-size:11px;margin-bottom:4px;">' . $h($header_company_name) . '</div>
  <div>' . $header_company_address . '</div>
</td>
<td class="cell" style="width:50%;">
  <table style="width:100%;">' . $gst_block . '</table>
</td>
</tr>
</table>

<table style="margin-top:10px;" class="tbl">
<tr class="row_hdr">
<td style="width:50%;">Quotation Details</td>
<td style="width:50%;">Customer Details</td>
</tr>
<tr>
<td class="cell cell_l" style="width:50%;border-top:1px solid #000;">
  <table style="width:100%;">
    <tr><td class="lbl">Quotation</td><td class="val">: ' . $h($qref) . '</td></tr>
    <tr><td class="lbl">Quotation Date</td><td class="val">: ' . $h($qdate) . '</td></tr>
    <tr><td class="lbl">Expiry Date</td><td class="val">: ' . $h($exp) . '</td></tr>
    <tr><td class="lbl">Ticket Number</td><td class="val">: ' . $h($publicId) . '</td></tr>
    <tr><td class="lbl">Client Reference Ticket</td><td class="val">: ' . $h($ticket['client_reference'] ?? '') . '</td></tr>
  </table>
</td>
<td class="cell" style="width:50%;border-top:1px solid #000;">
  <table style="width:100%;">
    <tr><td class="lbl">Place Of Supply</td><td class="val">: ' . $h($ticket['place_of_supply'] ?? '') . '</td></tr>
    <tr><td class="lbl">Sales Person</td><td class="val">: ' . $h($ticket['sales_person'] ?? '') . '</td></tr>
    <tr><td class="lbl">Customer Name</td><td class="val">: ' . $h($ticket['customer_name'] ?? '') . '</td></tr>
    <tr><td class="lbl">Cust Contact NO</td><td class="val">: ' . $h($ticket['customer_contact'] ?? '') . '</td></tr>
  </table>
</td>
</tr>
</table>

<table style="margin-top:10px;" class="tbl">
<tr class="row_hdr">
<td style="width:50%;">Bill To</td>
<td style="width:50%;">Ship To</td>
</tr>
<tr>
<td class="cell cell_l" style="width:50%;border-top:1px solid #000;">
  <div style="font-weight:bold;font-size:10px;margin-bottom:4px;">' . $h($ticket['bill_to_entity'] ?? '') . '</div>
  <div style="margin-bottom:6px;">' . $br($ticket['bill_to_address'] ?? '') . '</div>
  <div style="font-weight:bold;font-size:10px;">GSTIN - ' . $h($ticket['bill_to_gstin'] ?? '') . '</div>
</td>
<td class="cell" style="width:50%;border-top:1px solid #000;">
  <div>' . $br($ticket['ship_to'] ?? '') . '</div>
</td>
</tr>
</table>

<div style="margin-top:12px;font-weight:bold;background:#08475e;color:#fff;padding:7px 8px;border:1px solid #000;font-size:11px;">Line items (from uploaded CSV)</div>
<table style="border-collapse:collapse;margin-top:0;width:100%;"><thead><tr>
<th style="border:1px solid #000;padding:6px;background:#08475e;color:#fff;width:36px;">#</th>' . $tableHead . '</tr></thead><tbody>' . $tableBody . '</tbody></table>
</body></html>';

$mpdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_top' => 10, 'margin_bottom' => 15]);
$mpdf->WriteHTML($html);
$fn = 'Tender-RFQ-' . $ticketId . '.pdf';
$mpdf->Output($fn, 'I');
