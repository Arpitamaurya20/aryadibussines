<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['CartID']) || !isset($data['SparePartID'])) { echo json_encode(['error'=>true,'message'=>'Missing CartID or SparePartID']); exit; }

$dbh = new Dbh(); $core = new Core(); $conn = $dbh->_connectodb(); $core->setTimeZone();
$CartID = $conn->real_escape_string($data['CartID']);
$SparePartID = intval($data['SparePartID']);

$sql = "SELECT * FROM sparepart_cart_items WHERE CartID = '$CartID' AND SparePartID = $SparePartID AND IsActive = 1";
$item = $core->_getSQLDetails($conn, $sql);
if (empty($item)) { echo json_encode(['error'=>true,'message'=>'Item not found']); exit; }

$update = $core->_UpdateTableRecords_prepare($conn,'sparepart_cart_items',['IsActive'=>0], ['ID' => $item['ID']]);
if ($update['error'] === false) echo json_encode(['error'=>false,'message'=>'Item removed']);
else echo json_encode(['error'=>true,'message'=>'Remove failed: '.$update['message']]);
?>