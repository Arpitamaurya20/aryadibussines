<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$conn = _connectodb();


/* ================= READ INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

$response = [
    "error" => true,
    "message" => "",
    "data" => [],
    "summary" => []
];

/* ================= VALIDATION ================= */
if (empty($data['CartID']) || !is_numeric($data['CartID'])) {
    $response['message'] = "CartID is required";
    echo json_encode($response);
    exit;
}

$CartID = (int)$data['CartID'];

/* ================= FETCH CART ITEMS ================= */
$sql = "
    SELECT 
        ci.ID AS CartItemID,
        ci.CartID,
        ci.LineItemID,
        ci.Qty,
        ci.PerItemPrice,
        ci.TotalPrice
    FROM corporate_quotation_cart_item ci
    WHERE ci.CartID = $CartID
      AND ci.IsActive = 1
";

$items = _getSQLRecords($conn, $sql);

/* ================= CALCULATE TOTAL ================= */
$grandTotal = 0;
foreach ($items as $item) {
    $grandTotal += (float)$item['TotalPrice'];
}

/* ================= RESPONSE ================= */
$response['error'] = false;
$response['message'] = "Cart items fetched";
$response['data'] = $items;
$response['summary'] = [
    "CartID" => $CartID,
    "TotalItems" => count($items),
    "GrandTotal" => $grandTotal
];

echo json_encode($response);
exit;
