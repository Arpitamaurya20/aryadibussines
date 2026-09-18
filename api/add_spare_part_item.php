<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents('php://input'), true);
$required = ['CartID','TicketID','SparePartID','Qty'];
foreach ($required as $r) if (!isset($data[$r])) { echo json_encode(['error'=>true,'message'=>"Missing $r"]); exit; }

$dbh = new Dbh(); $core = new Core(); $conn = $dbh->_connectodb(); $core->setTimeZone();

$CartID = $conn->real_escape_string($data['CartID']);
$TicketID = intval($data['TicketID']);
$SparePartID = intval($data['SparePartID']);
$Qty = floatval($data['Qty']);
$CreatedBy = isset($data['CreatedBy']) ? $data['CreatedBy'] : 'APPSYSTEM';
$today = date("Y-m-d"); $now = date("H:i:s");

if ($Qty <= 0) { echo json_encode(['error'=>true,'message'=>'Qty must be > 0']); exit; }

// get current price from sparepartlist
$priceSQL = "SELECT Price FROM sparepartlist WHERE ID = $SparePartID AND IsActive = 1";
$priceRow = $core->_getSQLDetails($conn, $priceSQL);
if (empty($priceRow)) { echo json_encode(['error'=>true,'message'=>'Invalid SparePartID']); exit; }
$Price = floatval($priceRow['Price']);
$TotalAmount = $Price * $Qty;

// check existing item in cart
$checkSQL = "SELECT * FROM sparepart_cart_items WHERE CartID = '$CartID' AND SparePartID = $SparePartID AND IsActive = 1";
$exist = $core->_getSQLDetails($conn, $checkSQL);

if (!empty($exist)) {
    // update qty
    $NewQty = floatval($exist['Qty']) + $Qty;
    $NewTotal = $NewQty * $Price;
    $updateData = [
        'Qty' => $NewQty,
        'Price' => $Price,
        'TotalAmount' => $NewTotal,
        'CreatedDate' => $today,
        'CreatedTime' => $now
    ];
    $where = ['ID' => $exist['ID']];
    $u = $core->_UpdateTableRecords_prepare($conn,'sparepart_cart_items',$updateData,$where);
    if ($u['error'] === false) {
        echo json_encode(['error'=>false,'message'=>'Item updated','Qty'=>$NewQty,'TotalAmount'=>$NewTotal]);
    } else echo json_encode(['error'=>true,'message'=>'Update failed: '.$u['message']]);
    exit;
}

// insert new
$insertData = [
    'CartID' => $CartID,
    'TicketID' => $TicketID,
    'SparePartID' => $SparePartID,
    'Qty' => $Qty,
    'Price' => $Price,
    'TotalAmount' => $TotalAmount,
    'CreatedBy' => $CreatedBy,
    'CreatedDate' => $today,
    'CreatedTime' => $now,
    'IsActive' => 1
];
$i = $core->_InsertTableRecords_prepare($conn, 'sparepart_cart_items', $insertData);
if ($i['error'] === false) echo json_encode(['error'=>false,'message'=>'Item added','Qty'=>$Qty,'TotalAmount'=>$TotalAmount]);
else echo json_encode(['error'=>true,'message'=>'Insert failed: '.$i['message']]);
