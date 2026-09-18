<?php
@session_start();
// Include necessary files and initialize database connection
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporateticket_obj = new Corporateticket($conn);
$core = new Core();
$core->setTimeZone();
if(isset($_POST['TicketQuotationID']) && isset($_POST['LineItemID']) && isset($_POST['quantity']) && isset($_POST['TicketID']))
{
    $data = $_POST;
    $data['CreatedBy'] = $_SESSION['pb_username'];
    $data['CreatedDate'] = date('Y-m-d');
    $data['CreatedTime'] =  date('H:i:s');
    $TicketQuotationID = $_POST['TicketQuotationID'];
    if($TicketQuotationID == -1)
    {
       
        $data['QuotationStatus'] = "Draft";
        $data['Remarks'] = "";
        $response = $corporateticket_obj->UpdateTicketQuotation($data);
        if($response['error'] == false)
        {
            $data['QuotationID'] = $response['last_insert_id'];
            $corporateticket_obj->UpdateTicketQuotationHistory($data);
        }
        $response['QuotationID'] = $data['QuotationID'];
    }
    else
    {
        $data['QuotationID'] = $TicketQuotationID;
    }

    if($data['QuotationID'] != -1)
    {
        $response_line_item = $corporateticket_obj->UpdateQuotationLineItem($data);
        if($response_line_item['error'] == false)
        {
            $response['error'] = false;
            $response['message'] = "Line Item Added to Quotation";
        }
        else
        {
            $response['error'] = true;
            $response['message'] = "Technical Problem";
        }
    } 
}
else
{
    $response['message'] = "Technical Problem!";
    $response['error'] = true;
}
echo json_encode($response);
?>