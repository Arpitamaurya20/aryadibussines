<?php
include("../controllers/common_controllers.php");
include('../branch-assets/controller/branch_assets_controller.php');
$conn = _connectodb();

$uniqueCode = $_POST['code'];
$assetId = $_POST['asset_id'];

$sql = "UPDATE branchassets_qr_code SET AssetID = '$assetId', Assigned = 1 WHERE UniqueCode = '$uniqueCode'";
if ($conn->query($sql)) {
    echo "QR successfully assigned to Asset ID $assetId";
} else {
    echo "Error: " . $conn->error;
}
?>
