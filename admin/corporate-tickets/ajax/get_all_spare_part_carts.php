<?php
header('Content-Type: application/json');
include('../../includes/autoloader.inc.php');

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();

$BranchAssetsID = isset($_GET['BranchAssetsID']) ? $_GET['BranchAssetsID'] : null;

if(!$BranchAssetsID) {
    echo json_encode(['error'=>true, 'message'=>'Provide TicketID']);
    exit;
}

$sql = "SELECT `CartID`, `CreatedDate` 
        FROM `sparepart_cart` 
        WHERE IsActive = 1 AND Status='Approved' AND AssetsID = '$BranchAssetsID'
        ORDER BY CreatedDate DESC";

$carts = $core->_getSQLRecords($conn, $sql);

if(!$carts) {
    echo json_encode(['error'=>true, 'message'=>'No carts found']);
    exit;
}

echo json_encode(['error'=>false, 'data'=>$carts]);
?>
