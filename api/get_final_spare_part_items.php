<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$CartID = isset($data['CartID']) ? $data['CartID'] : null;

if (!$CartID) { 
    echo json_encode(['error'=>true,'message'=>'Missing CartID']); 
    exit; 
}

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

$CartID = $conn->real_escape_string($CartID);

$sql = "
    SELECT f.*, s.SparePartCode, s.SparePart, s.UOM 
    FROM sparepart_final_items f 
    LEFT JOIN sparepartlist s ON s.ID = f.SparePartID 
    WHERE f.CartID = '$CartID' AND f.IsActive = 1
";

$items = $core->_getSQLRecords($conn, $sql);

$total = 0;
foreach ($items as $i) {
    $total += floatval($i['FinalTotalAmount']);
}

echo json_encode([
    'error' => false,
    'Items' => $items,
    'Summary' => [
        'GrandTotal' => $total
    ]
]);
?>
