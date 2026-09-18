<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents('php://input'), true);

// Required fields
$required = ['ItemID', 'Qty', 'Price'];
foreach ($required as $r) {
    if (!isset($data[$r])) {
        echo json_encode(['error' => true, 'message' => "Missing $r"]);
        exit;
    }
}

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

// Assign variables
$ItemID = intval($data['ItemID']);
$Qty = floatval($data['Qty']);
$Price = floatval($data['Price']);
$TotalAmount = $Qty * $Price;

$today = date("Y-m-d");
$now = date("H:i:s");

// Validation
if ($Qty <= 0) {
    echo json_encode(['error' => true, 'message' => 'Qty must be greater than 0']);
    exit;
}
if ($Price < 0) {
    echo json_encode(['error' => true, 'message' => 'Price cannot be negative']);
    exit;
}

// Check if valid item
$checkSQL = "SELECT ID FROM sparepart_cart_items WHERE ID = $ItemID AND IsActive = 1";
$exist = $core->_getSQLDetails($conn, $checkSQL);

if (empty($exist)) {
    echo json_encode(['error' => true, 'message' => 'Invalid ItemID']);
    exit;
}

// Update item
$updateData = [
    'Qty' => $Qty,
    'Price' => $Price,
    'TotalAmount' => $TotalAmount,
    'UpdatedDate' => $today,
    'UpdatedTime' => $now
];

$where = ['ID' => $ItemID];

$update = $core->_UpdateTableRecords_prepare($conn, 'sparepart_cart_items', $updateData, $where);

if ($update['error'] === false) {
    echo json_encode([
        'error' => false,
        'message' => 'Item updated successfully',
        'ItemID' => $ItemID,
        'Qty' => $Qty,
        'Price' => $Price,
        'TotalAmount' => $TotalAmount
    ]);
} else {
    echo json_encode([
        'error' => true,
        'message' => 'Update failed: ' . $update['message']
    ]);
}
