<?php
require_once('../../includes/autoloader.inc.php');

$dbh  = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();

$lineItemId   = $_POST['line_item_id'];
$quotationId  = $_POST['quotation_id'];

$sql = "SELECT q.ID as QuotationItemID, q.QuotationID, q.Qty, q.PerItemPrice, q.TotalPrice,
               r.ID as RateCardID, r.Type, r.Category, r.SubCategory, r.LineItemName, 
               r.Make, r.HSN, r.UoM, r.Price, r.Tax
        FROM corporate_ticket_quotation_items q
        JOIN corporate_rate_card r ON q.LineItemID = r.ID
        WHERE q.ID = '$lineItemId' AND q.QuotationID = '$quotationId'";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode($row);
} else {
    echo json_encode([]);
}
?>
