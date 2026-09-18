<?php
@session_start();
require_once('../include/autoloader.inc.php');

/* -------------------------------
   DB & Resource Init
-------------------------------- */
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$resource = new ResourceLibrary($conn);

/* -------------------------------
   Validate Token
-------------------------------- */
$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    exit('Token is required');
}

$signedUrl = $resource->validateSignedUrl($token);
if (!$signedUrl) {
    http_response_code(403);
    exit('Invalid or expired token');
}

/* -------------------------------
   Get File Details
-------------------------------- */
$file = $resource->getFileById($signedUrl['file_id']);
if (!$file) {
    http_response_code(404);
    exit('File not found');
}

/* -------------------------------
   File Path Validation
-------------------------------- */
$filePath = realpath(__DIR__ . '/../' . $file['file_path']);
if (!$filePath || !is_file($filePath)) {
    http_response_code(404);
    exit('File not found on server');
}

/* -------------------------------
   Log Access
-------------------------------- */
$userId = $_SESSION['UserID'] ?? null;
$resource->logFileAccess(
    $file['ID'],
    $file['folder_id'],
    'stream',
    $userId
);

/* -------------------------------
   MIME Detection
-------------------------------- */
$mimeType = $file['mime_type'] ?: mime_content_type($filePath);

/* -------------------------------
   File Size & Range
-------------------------------- */
$fileSize = filesize($filePath);
$start = 0;
$end   = $fileSize - 1;

/* -------------------------------
   Common Headers
-------------------------------- */
header('Content-Type: ' . $mimeType);
header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: no-cache');
header('Content-Disposition: inline; filename="' . basename($file['file_name']) . '"');

/* -------------------------------
   Handle HTTP Range (REQUIRED)
-------------------------------- */
if (isset($_SERVER['HTTP_RANGE'])) {
    if (preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches)) {
        $start = (int) $matches[1];
        if (isset($matches[2])) {
            $end = (int) $matches[2];
        }

        if ($end > $fileSize - 1) {
            $end = $fileSize - 1;
        }

        http_response_code(206);
        header("Content-Range: bytes $start-$end/$fileSize");
    }
}

/* -------------------------------
   Content Length
-------------------------------- */
$length = $end - $start + 1;
header("Content-Length: $length");

/* -------------------------------
   Stream File (Chunked)
-------------------------------- */
$chunkSize = 8192;
$fp = fopen($filePath, 'rb');
fseek($fp, $start);

while (!feof($fp) && ftell($fp) <= $end) {
    if (ftell($fp) + $chunkSize > $end) {
        $chunkSize = $end - ftell($fp) + 1;
    }
    echo fread($fp, $chunkSize);
    flush();
}

fclose($fp);
exit;
