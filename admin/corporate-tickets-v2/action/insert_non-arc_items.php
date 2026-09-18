<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
require_once('../../includes/autoloader.inc.php');
$response = array();
if(isset($_SESSION['pb_username']))
{
    $CreatedBy = $_SESSION['pb_username'];
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) 
    {
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();

        $core = new Core();
        $core->setTimeZone();

        $corporateticket_obj = new Corporateticket($conn);

        $CreatedDate = date("Y-m-d");
        $CreatedTime = date("H:i:s");

        $data_q = array();
        $response_json = array();
        $data_q['CreatedBy'] = $_SESSION['pb_username'];
        $data_q['CreatedDate'] = $CreatedDate;
        $data_q['CreatedTime'] =  $CreatedTime;

        foreach ($data as $row) 
        {
            $rowData = [
                'CompanyID' => $row['CorporateID'],
                'Type' => $row['type'],
                'Category' => $row['category'],
                'SubCategory' => $row['subcategory'],
                'LineItemName' => $row['lineItemName'],
                'Make' => $row['make'],
                'HSN' => $row['hsn'],
                'ARCCode' => 'N.A.',
                'UoM' => $row['uom'],
                'Price' => $row['price'],
                'Tax' => $row['tax'],
                'CreatedDate' => $CreatedDate,
                'CreatedTime' => $CreatedTime,
                'CreatedBy' => $CreatedBy,
                'ARCItem' => 0
            ];
            $response = $core->_InsertTableRecords_prepare($conn, 'corporate_rate_card', $rowData);
            if($response['error'] == false)
            {
                $LineItemID = $response['last_insert_id'];
                $QuotationID = $row['QuotationID'];
                if(isset($data_q['QuotationID']))
                {
                    $QuotationID = $data_q['QuotationID'];

                }
                $response['QuotationID'] = $QuotationID;
                if($QuotationID == -1)
                {
                   $data_q['QuotationStatus'] = "Draft"; 
                   $data_q['Remarks'] = "";
                   $data_q['TicketID'] = $row['TicketID'];
                   $data_q['TicketQuotationID'] = $QuotationID;
                   $response_update_quotation = $corporateticket_obj->UpdateTicketQuotation($data_q);
                   if($response_update_quotation['error'] == false)
                    {
                        $data_q['QuotationID'] = $response_update_quotation['last_insert_id'];
                        $corporateticket_obj->UpdateTicketQuotationHistory($data_q);
                        $response['QuotationID'] = $data_q['QuotationID'];
                    }
                }
                else
                {
                    $data_q['QuotationID'] = $QuotationID;
                }


                if($data_q['QuotationID'] != -1)
                {
                    $data_q['LineItemID'] = $LineItemID;
                    $data_q['quantity'] = $row['qty'];
                    $response_line_item = $corporateticket_obj->UpdateQuotationLineItem($data_q);
                    
                }

            }

            
        }
        $conn->close();
    }
}
else
{
    $response['message'] = "Technical Error ! Please login and try again.";
    $response['error'] = true;
}
echo json_encode($response);


?>
