<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();
require_once('../../includes/autoloader.inc.php');

$response = array();

if (!isset($_SESSION['pb_username'])) {
    echo json_encode([
        "error" => true,
        "message" => "Please login first!"
    ]);
    exit;
}

if (empty($_POST)) {
    echo json_encode([
        "error" => true,
        "message" => "No data received!"
    ]);
    exit;
}

$UpdatedBy = $_SESSION['pb_username'];
$data = $_POST;

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$core->setTimeZone();
$corporateticket_obj = new Corporateticket($conn);

mysqli_begin_transaction($conn);

try {

    $rateCardID       = $data['RateCardID'];
    $quotationItemID  = $data['QuotationItemID'];

    $type         = mysqli_real_escape_string($conn, $data['type']);
    $category     = mysqli_real_escape_string($conn, $data['category']);
    $subcategory  = mysqli_real_escape_string($conn, $data['subcategory']);
    $lineItemName = mysqli_real_escape_string($conn, $data['lineItemName']);
    $make         = mysqli_real_escape_string($conn, $data['make']);
    $hsn          = mysqli_real_escape_string($conn, $data['hsn']);
    $uom          = mysqli_real_escape_string($conn, $data['uom']);
    $price        = mysqli_real_escape_string($conn, $data['price']);
    $tax          = mysqli_real_escape_string($conn, $data['tax']);
    $qty          = mysqli_real_escape_string($conn, $data['qty']);

    $totalPrice = $price * $qty;

    // 1️⃣ Update Rate Card
    $updateRateCard = "
        UPDATE corporate_rate_card SET
            Type = '$type',
            Category = '$category',
            SubCategory = '$subcategory',
            LineItemName = '$lineItemName',
            Make = '$make',
            HSN = '$hsn',
            UoM = '$uom',
            Price = '$price',
            Tax = '$tax'
        WHERE ID = '$rateCardID'
    ";

    if (!mysqli_query($conn, $updateRateCard)) {
        throw new Exception("Rate card update failed: " . mysqli_error($conn));
    }

    // 2️⃣ Update Quotation Item
    $updateQuotationItem = "
        UPDATE corporate_ticket_quotation_items SET
            Qty = '$qty',
            PerItemPrice = '$price',
            TotalPrice = '$totalPrice'
        WHERE ID = '$quotationItemID'
    ";

    if (!mysqli_query($conn, $updateQuotationItem)) {
        throw new Exception("Quotation item update failed: " . mysqli_error($conn));
    }

    // 3️⃣ Get QuotationID
    $getQuotation = mysqli_query($conn, "
        SELECT QuotationID 
        FROM corporate_ticket_quotation_items 
        WHERE ID = '$quotationItemID'
    ");

    if (!$getQuotation || mysqli_num_rows($getQuotation) == 0) {
        throw new Exception("Quotation not found!");
    }

    $quotationRow = mysqli_fetch_assoc($getQuotation);
    $quotationID = $quotationRow['QuotationID'];

    // 4️⃣ Get TicketID
    $getTicket = mysqli_query($conn, "
        SELECT TicketID 
        FROM corporate_ticket_quotation 
        WHERE ID = '$quotationID'
    ");

    if (!$getTicket || mysqli_num_rows($getTicket) == 0) {
        throw new Exception("Ticket not found!");
    }

    $ticketRow = mysqli_fetch_assoc($getTicket);
    $ticketID = $ticketRow['TicketID'];

    // 5️⃣ 🔥 Update Full Finance Using Your Proper Function
    $corporateticket_obj->UpdateTicketFinancesfromQuotation($quotationID, $ticketID);

    // Commit Transaction
    mysqli_commit($conn);

    $response['error'] = false;
    $response['message'] = "Line item and finance updated successfully!";

} catch (Exception $e) {

    mysqli_rollback($conn);

    $response['error'] = true;
    $response['message'] = $e->getMessage();
}

$conn->close();

echo json_encode($response);
?>
