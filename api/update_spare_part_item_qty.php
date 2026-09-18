<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['CartID']) || !isset($data['SparePartID']) || !isset($data['Qty'])) {
    echo json_encode(['error'=>true,'message'=>'Missing CartID or SparePartID or Qty']); exit;
}

$dbh = new Dbh(); $core = new Core(); $conn = $dbh->_connectodb(); $core->setTimeZone();

$CartID = $conn->real_escape_string($data['CartID']);
$SparePartID = intval($data['SparePartID']);
$Qty = floatval($data['Qty']);
$today = date("Y-m-d"); $now = date("H:i:s");
if ($Qty < 0) { echo json_encode(['error'=>true,'message'=>'Invalid Qty']); exit; }

// get item
$sql = "SELECT * FROM sparepart_cart_items WHERE CartID = '$CartID' AND SparePartID = $SparePartID AND IsActive = 1";
$item = $core->_getSQLDetails($conn, $sql);
if (empty($item)) { echo json_encode(['error'=>true,'message'=>'Item not found']); exit; }

// price remains stored in item.Price
$Price = floatval($item['Price']);
$Total = $Qty * $Price;

$updateData = [
    'Qty' => $Qty,
    'TotalAmount' => $Total,
    'CreatedDate' => $today,
    'CreatedTime' => $now
];
$where = ['ID' => $item['ID']];
$r = $core->_UpdateTableRecords_prepare($conn,'sparepart_cart_items',$updateData,$where);
if ($r['error'] === false) echo json_encode(['error'=>false,'message'=>'Qty updated','Qty'=>$Qty,'TotalAmount'=>$Total]);
else echo json_encode(['error'=>true,'message'=>'Update failed: '.$r['message']]);
