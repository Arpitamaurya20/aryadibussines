<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$CartID   = isset($data['CartID'])   ? $data['CartID']   : null;
$TicketID = isset($data['TicketID']) ? intval($data['TicketID']) : null;

if (!$CartID && !$TicketID) {
    echo json_encode([
        'error' => true,
        'message' => 'Provide CartID or TicketID'
    ]);
    exit;
}

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

//---------------------------------------------------
// 1. FETCH CART HEADER
//---------------------------------------------------
if ($CartID) {
    $CartID = $conn->real_escape_string($CartID);
    $sql_cart = "SELECT * FROM sparepart_cart 
                 WHERE CartID = '$CartID' AND IsActive = 1";
} else {
    $sql_cart = "SELECT * FROM sparepart_cart 
                 WHERE TicketID = $TicketID AND IsActive = 1 
                 ORDER BY ID DESC LIMIT 1";
}

$cart = $core->_getSQLDetails($conn, $sql_cart);

if (empty($cart)) {
    echo json_encode([
        'error' => true,
        'message' => 'Cart not found'
    ]);
    exit;
}

$cid = $cart['CartID'];

//---------------------------------------------------
// 2. FETCH CART ITEMS
//---------------------------------------------------
$sql_items = "
    SELECT i.*, 
           s.SparePartCode, 
           s.SparePart, 
           s.Categories, 
           s.UOM
    FROM sparepart_cart_items i
    LEFT JOIN sparepartlist s ON s.ID = i.SparePartID
    WHERE i.CartID = '$cid' AND i.IsActive = 1
";

$items = $core->_getSQLRecords($conn, $sql_items);

//---------------------------------------------------
// 3. SUMMARY CALCULATION
//---------------------------------------------------
$totalQty  = 0;
$subTotal  = 0;

foreach ($items as $it) {
    $totalQty  += floatval($it['Qty']);
    $subTotal  += floatval($it['TotalAmount']);
}

//---------------------------------------------------
// 4. FINAL RESPONSE
//---------------------------------------------------
echo json_encode([
    'error' => false,
    'Cart' => $cart,
    'Items' => $items,
    'Summary' => [
        'TotalQty' => $totalQty,
        'SubTotal' => $subTotal
    ]
]);
?>
