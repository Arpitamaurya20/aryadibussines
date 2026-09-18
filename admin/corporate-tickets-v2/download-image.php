<?php
$file = $_GET['file'] ?? '';
$downloadName = $_GET['name'] ?? '';

if (!$file || !$downloadName) {
    http_response_code(400);
    echo "Missing file or name parameter.";
    exit;
}

// Secure the input
$file = basename($file);
$downloadName = basename($downloadName);

// Construct file path
$baseDir = __DIR__ . '/../media/ticket_media/';
$filePath = $baseDir . $file;

if (!file_exists($filePath)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

// Detect MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $filePath);
finfo_close($finfo);

// ⛳️ THIS IS THE KEY LINE
header("Content-Disposition: attachment; filename=\"" . $downloadName . "\"");

header('Content-Description: File Transfer');
header("Content-Type: $mime_type");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
