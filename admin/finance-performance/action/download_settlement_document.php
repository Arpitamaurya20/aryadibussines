<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_requests.php');
setTimeZone();

try {
    $conn = fp_db_connect();
    $actor = fp_require_action($conn, 'download_settlement_document');
    $file = fp_settlement_document_for_download($conn, $actor, fp_input_int($_GET, 'ID'));
} catch (Throwable $e) {
    error_log('Finance & Performance download_settlement_document: ' . $e->getMessage());
    $file = null;
}
if (!$file) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('File not found or access denied.');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}
$name = $file['row']['OriginalName'];
$asciiName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
$disposition = fp_input_string($_GET, 'download') === '1' ? 'attachment' : 'inline';
header('Content-Type: ' . $file['row']['MimeType']);
header('Content-Length: ' . filesize($file['path']));
header('Content-Disposition: ' . $disposition . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($name));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($file['path']);
exit;
