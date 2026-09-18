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

    // Generate QR code with center logo
    $qrCode = QrCode::create($url)->setSize(200)->setMargin(8);
    $writer = new PngWriter();
    $centerLogo = Logo::create(__DIR__ . "/../img/tech-logo.jpg")->setResizeToWidth(35);
    $filePath = __DIR__ . "/qrcode/asset_" . $uniqueCode . ".png";
    $writer->write($qrCode, $centerLogo)->saveToFile($filePath);

    // Load QR with center logo already embedded
    $qr = imagecreatefrompng($filePath);
    $qrW = imagesx($qr);
    $qrH = imagesy($qr);

    // Landscape format - wider than tall
    $canvasWidth = 500;   // Landscape width
    $canvasHeight = 280;  // Landscape height
    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
    
    // Modern color scheme
    $white = imagecolorallocate($canvas, 255, 255, 255);
    $darkBlue = imagecolorallocate($canvas, 25, 25, 112);
    $lightBlue = imagecolorallocate($canvas, 135, 206, 235);
    $darkGray = imagecolorallocate($canvas, 45, 45, 45);
    $lightGray = imagecolorallocate($canvas, 240, 240, 240);
    $accentOrange = imagecolorallocate($canvas, 255, 140, 0);
    
    // Create gradient-like background
    for ($i = 0; $i < $canvasHeight; $i++) {
        $color = imagecolorallocate($canvas, 
            255 - ($i * 0.1), 
            255 - ($i * 0.05), 
            255 - ($i * 0.02));
        imageline($canvas, 0, $i, $canvasWidth, $i, $color);
    }
    
    // Create main content area with rounded corners effect
    $contentPadding = 15;
    imagefilledrectangle($canvas, $contentPadding, $contentPadding, 
                        $canvasWidth - $contentPadding, $canvasHeight - $contentPadding, $white);
    
    // Add subtle shadow effect
    $shadowColor = imagecolorallocate($canvas, 200, 200, 200);
    imagefilledrectangle($canvas, $contentPadding + 2, $contentPadding + 2, 
                        $canvasWidth - $contentPadding + 2, $canvasHeight - $contentPadding + 2, $shadowColor);
    imagefilledrectangle($canvas, $contentPadding, $contentPadding, 
                        $canvasWidth - $contentPadding, $canvasHeight - $contentPadding, $white);
    
    // Place QR code on the left side
    $qrX = 25;
    $qrY = ($canvasHeight - $qrH) / 2;
    imagecopy($canvas, $qr, $qrX, $qrY, 0, 0, $qrW, $qrH);
    
    // Add QR code border
    $qrBorderColor = imagecolorallocate($canvas, 220, 220, 220);
    imagerectangle($canvas, $qrX - 2, $qrY - 2, $qrX + $qrW + 1, $qrY + $qrH + 1, $qrBorderColor);
    
    // Content area on the right
    $contentX = $qrX + $qrW + 20;
    $contentY = 30;
    
    // Load and prepare company logo
    $logo = imagecreatefromjpeg(__DIR__ . "/../img/tech-logo.jpg");
    $logoWidth = 150;
    $logoHeight = 45;
    $logo = imagescale($logo, $logoWidth, $logoHeight);
    
    // Place logo
    imagecopy($canvas, $logo, $contentX, $contentY, 0, 0, $logoWidth, $logoHeight);
    
    // Add company tagline
    $tagline = "Professional Asset Management";
    $taglineColor = imagecolorallocate($canvas, 100, 100, 100);
    imagestring($canvas, 2, $contentX, $contentY + $logoHeight + 5, $tagline, $taglineColor);
    
    // Add decorative line
    imageline($canvas, $contentX, $contentY + $logoHeight + 20, 
              $contentX + 200, $contentY + $logoHeight + 20, $lightBlue);
    
    // Add asset information
    $assetInfoY = $contentY + $logoHeight + 35;
    
    // Asset ID with icon-like background
    $assetBgColor = imagecolorallocate($canvas, 245, 245, 245);
    imagefilledrectangle($canvas, $contentX - 5, $assetInfoY - 3, 
                        $contentX + 180, $assetInfoY + 15, $assetBgColor);
    
    $assetText = "Asset ID: " . $uniqueCode;
    imagestring($canvas, 2, $contentX, $assetInfoY, $assetText, $darkGray);
    
    // Scan instruction
    $scanY = $assetInfoY + 25;
    $scanText = "Scan QR Code";
    imagestring($canvas, 2, $contentX, $scanY, $scanText, $darkBlue);
    
    $scanSubText = "for Asset Details";
    imagestring($canvas, 1, $contentX, $scanY + 15, $scanSubText, $darkGray);
    
    // Add status indicator
    $statusY = $scanY + 35;
    $statusBg = imagecolorallocate($canvas, 0, 200, 0);
    imagefilledellipse($canvas, $contentX + 8, $statusY + 8, 12, 12, $statusBg);
    imagestring($canvas, 1, $contentX + 20, $statusY + 2, "Active Asset", $darkGray);
    
    // Add corner design elements
    $cornerSize = 20;
    $cornerColor = $accentOrange;
    
    // Top-left corner accent
    imagefilledpolygon($canvas, [
        $contentPadding, $contentPadding,
        $contentPadding + $cornerSize, $contentPadding,
        $contentPadding, $contentPadding + $cornerSize
    ], 3, $cornerColor);
    
    // Bottom-right corner accent
    imagefilledpolygon($canvas, [
        $canvasWidth - $contentPadding, $canvasHeight - $contentPadding,
        $canvasWidth - $contentPadding - $cornerSize, $canvasHeight - $contentPadding,
        $canvasWidth - $contentPadding, $canvasHeight - $contentPadding - $cornerSize
    ], 3, $cornerColor);
    
    // Add subtle pattern overlay
    for ($i = 0; $i < 5; $i++) {
        $patternColor = imagecolorallocate($canvas, 250, 250, 250);
        $x = $contentX + ($i * 40);
        $y = $canvasHeight - 20;
        imagefilledellipse($canvas, $x, $y, 3, 3, $patternColor);
    }
    
    // Save final landscape sticker
    $finalPath = __DIR__ . "/qrcode/sticker_landscape_" . $uniqueCode . ".png";
    imagepng($canvas, $finalPath, 9);
    
    // Clean up memory
    imagedestroy($qr);
    imagedestroy($logo);
    imagedestroy($canvas);
    
    echo "<div style='text-align: center; margin: 20px;'>";
    echo "<h3>Landscape Format QR Code Generated</h3>";
    echo "<p><strong>Asset ID:</strong> " . $uniqueCode . "</p>";
    echo "<div style='border: 2px solid #ddd; padding: 15px; display: inline-block; background: #f8f9fa;'>";
    echo "<img src='qrcode/sticker_landscape_" . $uniqueCode . ".png' style='max-width: 400px; height: auto;'>";
    echo "</div>";
    echo "<p style='color: #666; font-size: 12px; margin-top: 10px;'>";
    echo "Landscape Format | Professional Design";
    echo "</p>";
    echo "</div>";
}
?>