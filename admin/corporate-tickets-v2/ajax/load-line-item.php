<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('../api/common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/corporate-tickets/controller/corporate_tickets_controller.php');
require_once('../../admin/includes/autoloader.inc.php');

$response = [];

if (isset($_POST['TicketID'])) {
    $TicketID = $_POST['TicketID'];
    $conn = _connectodb();	
    $corporateticket = new Corporateticket($conn);
    $Quotation = $corporateticket->GetQuotationDetail($TicketID);
    $QuotationID = $Quotation['ID'];

    $q_line_items = $corporateticket->GetQuotationLineItems($QuotationID);
    $filtered_data = [];

    foreach ($q_line_items as $item) {
        $filtered_data[] = [
            "LineItemID" => $item['LineItemID'],
            "LineItemName" => $item['LineItemName'],
            "Make" => $item['Make'],
            "Category" => $item['Category'],
            "SubCategory" => $item['SubCategory']
        ];
    }

    $response['data'] = $filtered_data;
} else {
    $response["error"] = true;
    $response["message"] = "Missing TicketID";
}

echo json_encode($response);
?>
