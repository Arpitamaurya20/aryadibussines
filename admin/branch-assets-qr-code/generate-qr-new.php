<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

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

    // Generate QR code with center logo
    $qrCode = QrCode::create($url)->setSize(200)->setMargin(8);
    $writer = new PngWriter();
    $centerLogo = Logo::create(__DIR__ . "/../img/tech-logo.jpg")->setResizeToWidth(35);

    $qrResult = $writer->write($qrCode, $centerLogo);
    $qr = $qrResult->getImage();
    $qrW = imagesx($qr);
    $qrH = imagesy($qr);

    // Landscape format - wider than tall
    $canvasWidth = 500;
    $canvasHeight = 280;
    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

    // Colors
    $white = imagecolorallocate($canvas, 255, 255, 255);
    $darkBlue = imagecolorallocate($canvas, 25, 25, 112);
    $lightBlue = imagecolorallocate($canvas, 135, 206, 235);
    $darkGray = imagecolorallocate($canvas, 45, 45, 45);
    $lightGray = imagecolorallocate($canvas, 240, 240, 240);
    $accentOrange = imagecolorallocate($canvas, 255, 140, 0);

    // Gradient background (cast floats to int)
    for ($i = 0; $i < $canvasHeight; $i++) {
        $color = imagecolorallocate(
            $canvas,
            (int)(255 - ($i * 0.1)),
            (int)(255 - ($i * 0.05)),
            (int)(255 - ($i * 0.02))
        );
        imageline($canvas, 0, $i, $canvasWidth, $i, $color);
    }

    // Main content area
    $contentPadding = 15;
    imagefilledrectangle($canvas, $contentPadding, $contentPadding,
        $canvasWidth - $contentPadding, $canvasHeight - $contentPadding, $white);

    // Shadow
    $shadowColor = imagecolorallocate($canvas, 200, 200, 200);
    imagefilledrectangle($canvas, $contentPadding + 2, $contentPadding + 2,
        $canvasWidth - $contentPadding + 2, $canvasHeight - $contentPadding + 2, $shadowColor);
    imagefilledrectangle($canvas, $contentPadding, $contentPadding,
        $canvasWidth - $contentPadding, $canvasHeight - $contentPadding, $white);

    // Place QR code
    $qrX = 25;
    $qrY = (int)(($canvasHeight - $qrH) / 2);
    imagecopy($canvas, $qr, $qrX, $qrY, 0, 0, $qrW, $qrH);

    // QR border
    $qrBorderColor = imagecolorallocate($canvas, 220, 220, 220);
    imagerectangle($canvas, $qrX - 2, $qrY - 2, $qrX + $qrW + 1, $qrY + $qrH + 1, $qrBorderColor);

    // Right content
    $contentX = $qrX + $qrW + 20;

    // Company logo
    $logo = imagecreatefromjpeg(__DIR__ . "/../img/tech-logo.jpg");
    $logoWidth = 150;
    $logoHeight = 45;
    $logo = imagescale($logo, (int)$logoWidth, (int)$logoHeight);

    // Content height for centering
    $decorativeLineHeight = 20;
    $assetInfoHeight = 20;
    $scanInstructionHeight = 30;
    $totalContentHeight = $logoHeight + $decorativeLineHeight + $assetInfoHeight + $scanInstructionHeight;
    $startY = (int)(($canvasHeight - $totalContentHeight) / 2);

    // Place logo
    $logoY = $startY;
    imagecopy($canvas, $logo, $contentX, $logoY, 0, 0, $logoWidth, $logoHeight);

    // Decorative line
    $decorativeLineY = $logoY + $logoHeight + 15;
    imageline($canvas, $contentX, $decorativeLineY,
        $contentX + 200, $decorativeLineY, $lightBlue);

    // Asset info
    $assetInfoY = $decorativeLineY + 20;
    $assetBgColor = imagecolorallocate($canvas, 245, 245, 245);
    imagefilledrectangle($canvas, $contentX - 5, $assetInfoY - 3,
        $contentX + 180, $assetInfoY + 15, $assetBgColor);

    $assetText = "Asset ID: " . $uniqueCode;
    imagestring($canvas, 2, $contentX, $assetInfoY, $assetText, $darkGray);

    // Scan instructions
    $scanY = $assetInfoY + 25;
    imagestring($canvas, 2, $contentX, $scanY, "Scan QR Code", $darkBlue);
    imagestring($canvas, 1, $contentX, $scanY + 15, "for Asset Details", $darkGray);

    // Corner accents (no $num_points param)
    $cornerSize = 20;
    imagefilledpolygon($canvas, [
        $contentPadding, $contentPadding,
        $contentPadding + $cornerSize, $contentPadding,
        $contentPadding, $contentPadding + $cornerSize
    ], $accentOrange);

    imagefilledpolygon($canvas, [
        $canvasWidth - $contentPadding, $canvasHeight - $contentPadding,
        $canvasWidth - $contentPadding - $cornerSize, $canvasHeight - $contentPadding,
        $canvasWidth - $contentPadding, $canvasHeight - $contentPadding - $cornerSize
    ], $accentOrange);

    // Subtle pattern
    for ($i = 0; $i < 5; $i++) {
        $patternColor = imagecolorallocate($canvas, 250, 250, 250);
        $x = $contentX + ($i * 40);
        $y = $canvasHeight - 20;
        imagefilledellipse($canvas, $x, $y, 3, 3, $patternColor);
    }

    // Save QR code
    $finalPath = __DIR__ . "/qrcode_b2/techxpertqr_" . $uniqueCode . ".png";
    imagepng($canvas, $finalPath, 9);

    // Cleanup
    imagedestroy($qr);
    imagedestroy($logo);
    imagedestroy($canvas);

    // Output HTML preview
    echo "<div style='text-align: center; margin: 20px;'>";
    echo "<h3>Landscape Format QR Code Generated</h3>";
    echo "<p><strong>Asset ID:</strong> " . $uniqueCode . "</p>";
    echo "<div style='border: 2px solid #ddd; padding: 15px; display: inline-block; background: #f8f9fa;'>";
    echo "<img src='qrcode_b2/techxpertqr_" . $uniqueCode . ".png' style='max-width: 400px; height: auto;'>";
    echo "</div>";
    echo "<p style='color: #666; font-size: 12px; margin-top: 10px;'>Landscape Format | Professional Design</p>";
    echo "</div>";
}
?>
