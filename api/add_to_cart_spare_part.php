<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['TicketID'])) {
    echo json_encode(['error'=>true,'message'=>'Missing TicketID']); exit;
}

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

$TicketID = intval($data['TicketID']);
$AssetsID=   intval($data['AssetsID']);
$CreatedBy = isset($data['CreatedBy']) ? $data['CreatedBy'] : 'APPSYSTEM';
$today = date("Y-m-d"); $now = date("H:i:s");

$sql = "SELECT * FROM sparepart_cart WHERE TicketID = $TicketID AND IsActive = 1 AND Status IN ('Pending','Submitted')";
$existing = $core->_getSQLDetails($conn, $sql);

if (!empty($existing)) {
    echo json_encode(['error'=>false,'message'=>'Cart exists','Cart'=>$existing]);
    exit;
}


$CartID = 'C' . date('Ymd') . '-' . str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
$insertData = [
    'CartID' => $CartID,
    'TicketID' => $TicketID,
    'AssetsID'=> $AssetsID,
    'Status' => 'Pending',
    'CreatedBy' => $CreatedBy,
    'CreatedDate' => $today,
    'CreatedTime' => $now,
    'IsActive' => 1
];

$resp = $core->_InsertTableRecords_prepare($conn, 'sparepart_cart', $insertData);
if ($resp['error'] === false) {
    echo json_encode(['error'=>false,'message'=>'Cart created','CartID'=>$CartID]);
} else {
    echo json_encode(['error'=>true,'message'=>'Create cart failed: '.$resp['message']]);
}
