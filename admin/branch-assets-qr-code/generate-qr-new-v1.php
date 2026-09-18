<?php
@session_start();
include("../controllers/common_controllers.php");
include('../branch-assets/controller/branch_assets_controller.php');
require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Logo\Logo;

$conn = _connectodb();
$sql = "SELECT ID, EquipmentName FROM branch_assets WHERE ID NOT IN (SELECT AssetID FROM branchassets_qr_code) LIMIT 1";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $uniqueCode = uniqid("ASSET_");
    $insert = "INSERT INTO branchassets_qr_code (AssetID, UniqueCode, Assigned) 
               VALUES (-1, '$uniqueCode', 0)";
    $conn->query($insert);

    $url = "https://techxpertindia.in/admin/branch-assets-qr-code/asset-info.php?code=" . $uniqueCode;

    // Generate QR code with center logo (small)
    $qrCode = QrCode::create($url)->setSize(220)->setMargin(10);
    $writer = new PngWriter();
    $centerLogo = Logo::create(__DIR__ . "/../img/tech-logo.jpg")->setResizeToWidth(40);
    $filePath = __DIR__ . "/qrcode/asset_" . $uniqueCode . ".png";
    $writer->write($qrCode, $centerLogo)->saveToFile($filePath);

    // Load QR with center logo already embedded
    $qr = imagecreatefrompng($filePath);
    $qrW = imagesx($qr);
    $qrH = imagesy($qr);

    // Make canvas bigger (smaller gaps)
    $canvas = imagecreatetruecolor($qrW + 60, $qrH + 140);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);

    // Place QR
    $qrX = ($qrW + 60 - $qrW) / 2;
    $qrY = 60;
    imagecopy($canvas, $qr, $qrX, $qrY, 0, 0, $qrW, $qrH);

    // Load logo for top & bottom (smaller)
    $logo = imagecreatefromjpeg(__DIR__ . "/../img/tech-logo.jpg");
    $logo = imagescale($logo, 120, 45); // smaller than before

    // Place logo above QR (closer)
    $logoX = (($qrW + 60) - imagesx($logo)) / 2;
    $logoY = 15;
    imagecopy($canvas, $logo, $logoX, $logoY, 0, 0, imagesx($logo), imagesy($logo));

    // Place logo below QR (closer)
    $logoYBottom = $qrY + $qrH + 15;
    imagecopy($canvas, $logo, $logoX, $logoYBottom, 0, 0, imagesx($logo), imagesy($logo));

    // Save final sticker
    $finalPath = __DIR__ . "/qrcode/sticker_" . $uniqueCode . ".png";
    imagepng($canvas, $finalPath);

    echo "QR generated for - <img src='qrcode/sticker_" . $uniqueCode . ".png'><br>";
}
?>
