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

    // Generate QR code with center logo - larger size for better print quality
    $qrCode = QrCode::create($url)->setSize(300)->setMargin(15);
    $writer = new PngWriter();
    $centerLogo = Logo::create(__DIR__ . "/../img/tech-logo.jpg")->setResizeToWidth(50);
    $filePath = __DIR__ . "/qrcode/asset_" . $uniqueCode . ".png";
    $writer->write($qrCode, $centerLogo)->saveToFile($filePath);

    // Load QR with center logo already embedded
    $qr = imagecreatefrompng($filePath);
    $qrW = imagesx($qr);
    $qrH = imagesy($qr);

    // Professional dimensions for printing (3.5" x 2.5" at 300 DPI)
    $canvasWidth = 1050;  // 3.5 inches at 300 DPI
    $canvasHeight = 750;  // 2.5 inches at 300 DPI
    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
    
    // Professional color scheme
    $white = imagecolorallocate($canvas, 255, 255, 255);
    $lightGray = imagecolorallocate($canvas, 248, 249, 250);
    $darkGray = imagecolorallocate($canvas, 52, 58, 64);
    $borderGray = imagecolorallocate($canvas, 200, 200, 200);
    $accentBlue = imagecolorallocate($canvas, 0, 123, 255);
    
    // Fill background with light gray
    imagefill($canvas, 0, 0, $lightGray);
    
    // Create professional border
    $borderWidth = 8;
    imagefilledrectangle($canvas, $borderWidth, $borderWidth, 
                        $canvasWidth - $borderWidth, $canvasHeight - $borderWidth, $white);
    
    // Add inner border for professional look
    $innerBorderWidth = 2;
    imagefilledrectangle($canvas, $borderWidth + $innerBorderWidth, $borderWidth + $innerBorderWidth, 
                        $canvasWidth - $borderWidth - $innerBorderWidth, $canvasHeight - $borderWidth - $innerBorderWidth, $white);
    
    // Calculate QR code position (centered)
    $qrScale = 0.6; // Scale QR code to fit nicely
    $scaledQrW = $qrW * $qrScale;
    $scaledQrH = $qrH * $qrScale;
    $qrX = ($canvasWidth - $scaledQrW) / 2;
    $qrY = ($canvasHeight - $scaledQrH) / 2 + 20; // Slightly lower for logo space
    
    // Scale and place QR code
    $scaledQr = imagescale($qr, $scaledQrW, $scaledQrH);
    imagecopy($canvas, $scaledQr, $qrX, $qrY, 0, 0, $scaledQrW, $scaledQrH);
    
    // Load and prepare company logo
    $logo = imagecreatefromjpeg(__DIR__ . "/../img/tech-logo.jpg");
    $logoWidth = 200;
    $logoHeight = 60;
    $logo = imagescale($logo, $logoWidth, $logoHeight);
    
    // Place logo at the top
    $logoX = ($canvasWidth - $logoWidth) / 2;
    $logoY = 30;
    imagecopy($canvas, $logo, $logoX, $logoY, 0, 0, $logoWidth, $logoHeight);
    
    // Add company name below logo
    $fontSize = 4;
    $companyName = "TECHXPERT INDIA";
    $textColor = $darkGray;
    
    // Calculate text position
    $textWidth = strlen($companyName) * imagefontwidth($fontSize);
    $textX = ($canvasWidth - $textWidth) / 2;
    $textY = $logoY + $logoHeight + 10;
    
    imagestring($canvas, $fontSize, $textX, $textY, $companyName, $textColor);
    
    // Add asset information at the bottom
    $assetText = "Asset ID: " . $uniqueCode;
    $assetTextWidth = strlen($assetText) * imagefontwidth(3);
    $assetTextX = ($canvasWidth - $assetTextWidth) / 2;
    $assetTextY = $canvasHeight - 40;
    
    imagestring($canvas, 3, $assetTextX, $assetTextY, $assetText, $darkGray);
    
    // Add "Scan for Details" text
    $scanText = "Scan QR Code for Asset Details";
    $scanTextWidth = strlen($scanText) * imagefontwidth(2);
    $scanTextX = ($canvasWidth - $scanTextWidth) / 2;
    $scanTextY = $canvasHeight - 25;
    
    imagestring($canvas, 2, $scanTextX, $scanTextY, $scanText, $accentBlue);
    
    // Add decorative elements for professional look
    // Top decorative line
    imageline($canvas, $canvasWidth * 0.1, $logoY + $logoHeight + 5, 
              $canvasWidth * 0.9, $logoY + $logoHeight + 5, $borderGray);
    
    // Bottom decorative line
    imageline($canvas, $canvasWidth * 0.1, $canvasHeight - 50, 
              $canvasWidth * 0.9, $canvasHeight - 50, $borderGray);
    
    // Add corner accents
    $cornerSize = 15;
    // Top-left corner
    imagefilledrectangle($canvas, $borderWidth + 5, $borderWidth + 5, 
                        $borderWidth + 5 + $cornerSize, $borderWidth + 5 + 3, $accentBlue);
    imagefilledrectangle($canvas, $borderWidth + 5, $borderWidth + 5, 
                        $borderWidth + 5 + 3, $borderWidth + 5 + $cornerSize, $accentBlue);
    
    // Top-right corner
    imagefilledrectangle($canvas, $canvasWidth - $borderWidth - 5 - $cornerSize, $borderWidth + 5, 
                        $canvasWidth - $borderWidth - 5, $borderWidth + 5 + 3, $accentBlue);
    imagefilledrectangle($canvas, $canvasWidth - $borderWidth - 5 - 3, $borderWidth + 5, 
                        $canvasWidth - $borderWidth - 5, $borderWidth + 5 + $cornerSize, $accentBlue);
    
    // Bottom-left corner
    imagefilledrectangle($canvas, $borderWidth + 5, $canvasHeight - $borderWidth - 5 - $cornerSize, 
                        $borderWidth + 5 + $cornerSize, $canvasHeight - $borderWidth - 5, $accentBlue);
    imagefilledrectangle($canvas, $borderWidth + 5, $canvasHeight - $borderWidth - 5 - $cornerSize, 
                        $borderWidth + 5 + 3, $canvasHeight - $borderWidth - 5, $accentBlue);
    
    // Bottom-right corner
    imagefilledrectangle($canvas, $canvasWidth - $borderWidth - 5 - $cornerSize, $canvasHeight - $borderWidth - 5 - $cornerSize, 
                        $canvasWidth - $borderWidth - 5, $canvasHeight - $borderWidth - 5, $accentBlue);
    imagefilledrectangle($canvas, $canvasWidth - $borderWidth - 5 - 3, $canvasHeight - $borderWidth - 5 - $cornerSize, 
                        $canvasWidth - $borderWidth - 5, $canvasHeight - $borderWidth - 5, $accentBlue);
    
    // Save final professional sticker
    $finalPath = __DIR__ . "/qrcode/sticker_" . $uniqueCode . ".png";
    imagepng($canvas, $finalPath, 9); // High quality PNG
    
    // Clean up memory
    imagedestroy($qr);
    imagedestroy($scaledQr);
    imagedestroy($logo);
    imagedestroy($canvas);
    
    echo "<div style='text-align: center; margin: 20px;'>";
    echo "<h3>Professional QR Code Generated</h3>";
    echo "<p><strong>Asset ID:</strong> " . $uniqueCode . "</p>";
    echo "<div style='border: 2px solid #ddd; padding: 20px; display: inline-block; background: #f8f9fa;'>";
    echo "<img src='qrcode/sticker_" . $uniqueCode . ".png' style='max-width: 400px; height: auto;'>";
    echo "</div>";
    echo "<p style='color: #666; font-size: 12px; margin-top: 10px;'>";
    echo "Dimensions: 3.5\" x 2.5\" | Print Ready at 300 DPI";
    echo "</p>";
    echo "</div>";
}
?>