<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['CartID'])) { echo json_encode(['error'=>true,'message'=>'Missing CartID']); exit; }
$dbh = new Dbh(); $core = new Core(); $conn = $dbh->_connectodb(); $core->setTimeZone();

$CartID = $conn->real_escape_string($data['CartID']);
$ApprovedBy = isset($data['ApprovedBy']) ? $data['ApprovedBy'] : 'SYSTEM';
$Remarks = isset($data['Remarks']) ? $data['Remarks'] : '';
$today = date("Y-m-d"); $now = date("H:i:s");

$cart = $core->_getSQLDetails($conn, "SELECT * FROM sparepart_cart WHERE CartID = '$CartID' AND IsActive = 1");
if (empty($cart)) { echo json_encode(['error'=>true,'message'=>'Cart not found']); exit; }
$items = $core->_getSQLRecords($conn, "SELECT * FROM sparepart_cart_items WHERE CartID = '$CartID' AND IsActive = 1");
if (empty($items)) { echo json_encode(['error'=>true,'message'=>'Cart has no items']); exit; }

// start transaction
mysqli_begin_transaction($conn);
try {
    foreach ($items as $it) {
        $finalData = [
            'CartID' => $CartID,
            'TicketID' => $it['TicketID'],
            'SparePartID' => $it['SparePartID'],
            'Qty' => $it['Qty'],
            'FinalPrice' => $it['Price'],
            'FinalTotalAmount' => $it['TotalAmount'],
            'ApprovedBy' => $ApprovedBy,
            'ApprovedDate' => $today,
            'ApprovedTime' => $now,
            'Remarks' => $Remarks,
            'CreatedBy' => $ApprovedBy,
            'CreatedDate' => $today,
            'CreatedTime' => $now,
            'IsActive' => 1
        ];
        $ins = $core->_InsertTableRecords_prepare($conn, 'sparepart_final_items', $finalData);
        if ($ins['error'] !== false) throw new Exception('Insert final item failed: '.$ins['message']);
    }

    // update cart status to Approved
    $u = $core->_UpdateTableRecords_prepare($conn, 'sparepart_cart', ['Status'=>'Approved','UpdatedDate'=>$today,'UpdatedTime'=>$now], ['CartID'=>$CartID]);
    if ($u['error'] !== false) throw new Exception('Update cart status failed: '.$u['message']);

    mysqli_commit($conn);
    echo json_encode(['error'=>false,'message'=>'Cart approved and final items created']);
} catch (Exception $e) {
    mysqli_roll_back($conn);
    echo json_encode(['error'=>true,'message'=>$e->getMessage()]);
}
