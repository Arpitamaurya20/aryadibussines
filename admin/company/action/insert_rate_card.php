<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
require_once('../../includes/autoloader.inc.php');
$response = array();
if(isset($_SESSION['CompanyID']))
{
    $CompanyID = $_SESSION['CompanyID'];
    $CreatedBy = $_SESSION['pb_username'];
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) 
    {
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();

        $core = new Core();
        $core->setTimeZone();

        $CreatedDate = date("Y-m-d");
        $CreatedTime = date("H:i:s");

        foreach ($data as $row) 
        {
            $rowData = [
                'CompanyID' => $CompanyID,
                'Type' => $row['type'],
                'Category' => $row['category'],
                'SubCategory' => $row['subcategory'],
                'LineItemName' => $row['lineItemName'],
                'Make' => $row['make'],
                'HSN' => $row['hsn'],
                'ARCCode' => $row['arccode'],
                'UoM' => $row['uom'],
                'Price' => $row['price'],
                'Tax' => $row['tax'],
                'CreatedDate' => $CreatedDate,
                'CreatedTime' => $CreatedTime,
                'CreatedBy' => $CreatedBy
            ];
            $response = $core->_InsertTableRecords_prepare($conn, 'corporate_rate_card', $rowData);
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
