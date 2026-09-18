<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
require_once('../../includes/autoloader.inc.php');

$response = array();

if(isset($_SESSION['pb_username'])) {
    $UpdatedBy = $_SESSION['pb_username'];
    $data = $_POST;
    if($data) {
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();
        $core = new Core();
        $core->setTimeZone();
        $corporateticket_obj = new Corporateticket($conn);
        $row = $data;
        $rateCardID = $row['RateCardID'];
        $updateRateCard = "UPDATE corporate_rate_card SET
                            Type = '".mysqli_real_escape_string($conn, $row['type'])."',
                            Category = '".mysqli_real_escape_string($conn, $row['category'])."',
                            SubCategory = '".mysqli_real_escape_string($conn, $row['subcategory'])."',
                            LineItemName = '".mysqli_real_escape_string($conn, $row['lineItemName'])."',
                            Make = '".mysqli_real_escape_string($conn, $row['make'])."',
                            HSN = '".mysqli_real_escape_string($conn, $row['hsn'])."',
                            UoM = '".mysqli_real_escape_string($conn, $row['uom'])."',
                            Price = '".mysqli_real_escape_string($conn, $row['price'])."',
                            Tax = '".mysqli_real_escape_string($conn, $row['tax'])."'
                            WHERE ID = '$rateCardID'";

        if(mysqli_query($conn, $updateRateCard)) {
            $quotationItemID = $row['QuotationItemID'];
            $updateQuotationItem = "UPDATE corporate_ticket_quotation_items SET
                                    Qty = '".mysqli_real_escape_string($conn, $row['qty'])."',
                                    PerItemPrice = '".mysqli_real_escape_string($conn, $row['price'])."',
                                    TotalPrice = '".($row['price'] * $row['qty'])."'
                                    WHERE ID = '$quotationItemID'";
            if(mysqli_query($conn, $updateQuotationItem)) {
                $response['message'] = "Line item updated successfully!";
                $response['error'] = false;
            } else {
                $response['message'] = "Failed to update quotation item: ".mysqli_error($conn);
                $response['error'] = true;
            }
        } else {
            $response['message'] = "Failed to update rate card: ".mysqli_error($conn);
            $response['error'] = true;
        }

        $conn->close();
    } else {
        $response['message'] = "No data received!";
        $response['error'] = true;
    }
} else {
    $response['message'] = "Please login first!";
    $response['error'] = true;
}

echo json_encode($response);
?>
