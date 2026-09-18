<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');
require __DIR__ . '/vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
$conn = _connectodb();
$sql = "SELECT ID, UniqueID, EquipmentName FROM branch_assets LIMIT 3";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $uniqueId = $row['UniqueID'];
    $url = "https://techxpertindia.in/admin/branch-assets/view-qr-assets.php?UniqueID=" . $uniqueId;
    $qrCode = new QrCode($url);
    $writer = new PngWriter();
    $filePath = __DIR__ . "/newqrcodes/asset_" . $row['ID'] . ".png";
    $writer->write($qrCode)->saveToFile($filePath);

    echo "QR generated for " . $row['EquipmentName'] . " - <img src='qrcodes/asset_" . $row['ID'] . ".png'><br>";
}
?>
