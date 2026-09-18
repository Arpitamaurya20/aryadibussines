<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$conn = _connectodb();

/* ================= READ INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

$response = [
    "error" => true,
    "message" => "",
    "data" => []
];

/* ================= VALIDATION ================= */
if (
    empty($data['CartID']) || !is_numeric($data['CartID']) ||
    empty($data['LineItemID']) || !is_numeric($data['LineItemID']) ||
    empty($data['Qty']) || !is_numeric($data['Qty']) ||
    empty($data['PerItemPrice']) || !is_numeric($data['PerItemPrice'])
) {
    $response['message'] = "CartID, LineItemID, Qty, PerItemPrice are required";
    echo json_encode($response);
    exit;
}

$CartID        = (int)$data['CartID'];
$LineItemID    = (int)$data['LineItemID'];
$Qty           = (float)$data['Qty'];
$PerItemPrice  = (float)$data['PerItemPrice'];

$TotalPrice = $Qty * $PerItemPrice;

/* ================= CHECK EXISTING ITEM ================= */
$checkSql = "
    SELECT ID, Qty, TotalPrice
    FROM corporate_quotation_cart_item
    WHERE CartID = $CartID
      AND LineItemID = $LineItemID
      AND IsActive = 1
    LIMIT 1
";

$existingItem = _getSQLRecords($conn, $checkSql);

/* ================= UPDATE ITEM ================= */
if (!empty($existingItem)) {

    $itemID = (int)$existingItem[0]['ID'];
    $newQty = $existingItem[0]['Qty'] + $Qty;
    $newTotal = $newQty * $PerItemPrice;

    $updateSql = "
        UPDATE corporate_quotation_cart_item
        SET Qty = $newQty,
            PerItemPrice = $PerItemPrice,
            TotalPrice = $newTotal
        WHERE ID = $itemID
    ";

    if (!mysqli_query($conn, $updateSql)) {
        $response['message'] = "Failed to update cart item";
        echo json_encode($response);
        exit;
    }

    $response['error'] = false;
    $response['message'] = "Cart item updated";
    $response['data'] = [
        "CartItemID" => $itemID,
        "CartID" => $CartID,
        "LineItemID" => $LineItemID,
        "Qty" => $newQty,
        "TotalPrice" => $newTotal
    ];

    echo json_encode($response);
    exit;
}

/* ================= INSERT NEW ITEM ================= */
$insertData = [
    "CartID"        => $CartID,
    "LineItemID"    => $LineItemID,
    "Qty"           => $Qty,
    "PerItemPrice"  => $PerItemPrice,
    "TotalPrice"    => $TotalPrice,
    "IsActive"      => 1
];

$insertResponse = _InsertTableRecords_prepare(
    $conn,
    "corporate_quotation_cart_item",
    $insertData
);

if ($insertResponse['error']) {
    $response['message'] = $insertResponse['message'];
    echo json_encode($response);
    exit;
}

/* ================= SUCCESS RESPONSE ================= */
$response['error'] = false;
$response['message'] = "Item added to cart";
$response['data'] = [
    "CartItemID" => $insertResponse['last_insert_id'],
    "CartID" => $CartID,
    "LineItemID" => $LineItemID,
    "Qty" => $Qty,
    "PerItemPrice" => $PerItemPrice,
    "TotalPrice" => $TotalPrice
];

echo json_encode($response);
exit;
