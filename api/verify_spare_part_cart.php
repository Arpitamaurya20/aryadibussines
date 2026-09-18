<?php
header('Content-Type: application/json');
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents('php://input'), true);

// REQUIRED FIELD
if (!isset($data['CartID'])) {
    echo json_encode(['error' => true, 'message' => 'Missing CartID']);
    exit;
}

$CartID = intval($data['CartID']);
$VerifiedBy = $data['VerifiedBy'] ?? "SYSTEM";

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

$today = date("Y-m-d");
$now = date("H:i:s");

// Check if cart exists
$checkSQL = "SELECT CartID FROM sparepart_cart WHERE CartID = '$CartID' AND IsActive = 1";
$exist = $core->_getSQLDetails($conn, $checkSQL);

if (empty($exist)) {
    echo json_encode(['error' => true, 'message' => 'Invalid CartID']);
    exit;
}

// UPDATE DATA
$updateData = [
    "Status"       => "Verified",
    "VerifiedBy"   => $VerifiedBy,
    "VerifiedDate" => $today,
    "VerifiedTime" => $now,
    "UpdatedDate"  => $today,
    "UpdatedTime"  => $now
];

$where = ["CartID" => $CartID];

// CALL COMMON UPDATE FUNCTION (same method you used for items)
$update = $core->_UpdateTableRecords_prepare($conn, "sparepart_cart", $updateData, $where);

if ($update['error'] === false) {
    echo json_encode([
        "error" => false,
        "message" => "Cart verified successfully!",
        "CartID" => $CartID
    ]);
} else {
    echo json_encode([
        "error" => true,
        "message" => "Verification failed: " . $update['message']
    ]);
}
?>
