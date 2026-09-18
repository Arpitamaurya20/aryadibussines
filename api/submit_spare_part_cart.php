<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['CartID'])) { echo json_encode(['error'=>true,'message'=>'Missing CartID']); exit; }

$dbh = new Dbh(); $core = new Core(); $conn = $dbh->_connectodb(); $core->setTimeZone();
$CartID = $conn->real_escape_string($data['CartID']);
$UpdatedBy = isset($data['UpdatedBy']) ? $data['UpdatedBy'] : 'APPSYSTEM';
$today = date("Y-m-d"); $now = date("H:i:s");

// Check cart exists and has at least one item
$cart = $core->_getSQLDetails($conn, "SELECT * FROM sparepart_cart WHERE CartID = '$CartID' AND IsActive = 1");
if (empty($cart)) { echo json_encode(['error'=>true,'message'=>'Cart not found']); exit; }
$items = $core->_getSQLRecords($conn, "SELECT * FROM sparepart_cart_items WHERE CartID = '$CartID' AND IsActive = 1");
if (empty($items)) { echo json_encode(['error'=>true,'message'=>'Cart is empty']); exit; }

$u = $core->_UpdateTableRecords_prepare($conn, 'sparepart_cart', ['Status'=>'Submitted','UpdatedDate'=>$today,'UpdatedTime'=>$now], ['CartID'=>$CartID]);
if ($u['error'] === false) echo json_encode(['error'=>false,'message'=>'Cart submitted for approval']);
else echo json_encode(['error'=>true,'message'=>'Submit failed: '.$u['message']]);
?>