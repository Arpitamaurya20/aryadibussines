<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents("php://input");
$data = json_decode($data_raw, true);

$response = array();
$response['data'] = array();

if (!isset($data['QuotationID'])) {
    $response['error'] = true;
    $response['message'] = "Missing QuotationID";
    echo json_encode($response);
    exit;
}

$dbh=new Dbh();
$conn = $dbh->_connectodb();
$corporateticket = new Corporateticket($conn);

$QuotationID = intval($data['QuotationID']);
$line_items = $corporateticket->GetQuotationLineItems($QuotationID);

if ($line_items != null && count($line_items) > 0) {
    foreach ($line_items as $item) {
        $response['data'][] = array(
            "LineItemID" => $item['LineItemID'],
            "LineItemName" => $item['LineItemName'],
            "Quantity" => $item['Qty'],
            "Price" => $item['PerItemPrice'],
            "ARCCode"=>$item['ARCCode'],
            "UoM" => $item['UoM'],
            "Tax" => $item['Tax'],
            "TotalAmount" => $item['TotalPrice']
        );
    }
}

$response['error'] = false;
echo json_encode($response);
?>
