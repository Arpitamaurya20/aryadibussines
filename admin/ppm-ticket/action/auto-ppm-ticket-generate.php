<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// include("../../controllers/common_controllers.php");
// include('../controller/ppm_controller.php');
// require_once("../../branch/controller/branch_controller.php");
// require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
setTimeZone();

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

$CorporateID = 182;
$BranchID = 7378;
$CreatedBy = 'Sivani';
$PPMDate = '2025-12-15';
$CreatedDate = date('Y-m-d'); // 2025-11-10 (today)
$CreatedTime = date('H:i:s');

// 1️⃣ Get all 84 assets
$assets = [];
$query = "SELECT ID FROM branch_assets WHERE BranchID = $BranchID AND EquipmentName LIKE '%FOAM%' ORDER BY Model ASC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $assets[] = $row['ID'];
}

// 2️⃣ Insert PPM tickets
$insertCount = 0;
foreach ($assets as $assetID) {
    $insertData = [
        'CorporateID'   => $CorporateID,
        'BranchID'      => $BranchID,
        'BranchAssetID' => $assetID,
        'PPMDate'       => $PPMDate,
        'CreatedDate'   => $CreatedDate,
        'CreatedTime'   => $CreatedTime,
        'CreatedBy'     => $CreatedBy,
        'Status'        => 'Raised',
        'AssignedTo'    => -1,
        'IsActive'      => 1
    ];

    // Insert
    $resp = $core->_InsertTableRecords_prepare($conn, 'ppm_tickets', $insertData);

    if ($resp['error'] === false) {
        // 3️⃣ Format TicketID like CS-PPM-028554
        $id = $resp['last_insert_id'];
        $ticketCode = 'CS-PPM-' . str_pad($id, 6, '0', STR_PAD_LEFT);

        // 4️⃣ Update ticket with formatted ID
        $update = "UPDATE ppm_tickets SET TicketID = '$ticketCode' WHERE ID = $id";
        mysqli_query($conn, $update);

        $insertCount++;
    }
}

echo json_encode([
    'error' => false,
    'message' => "$insertCount PPM tickets created successfully."
]);
?>
