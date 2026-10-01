<?php
ini_set('display_errors', 0);
require_once('../common_api_header.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
if (!is_array($data) || empty($data['SiteVisitID'])) {
	echo json_encode(array('error' => true, 'message' => 'Missing Site Visit'));
	exit();
}
$actionDir = realpath(__DIR__ . '/../../admin/site_visits/action');
if ($actionDir === false) {
	echo json_encode(array('error' => true, 'message' => 'PDF generator is unavailable.'));
	exit();
}
$vendor = $actionDir . '/../../vendor/autoload.php';
if (is_file($vendor)) {
	require_once $vendor;
}
if (!class_exists('Mpdf\\Mpdf')) {
	echo json_encode(array('error' => true, 'message' => 'PDF library is not installed on the server.'));
	exit();
}
$_POST['SiteVisitID'] = $data['SiteVisitID'];
$_POST['Action'] = 'Download';
chdir($actionDir);
// The admin generator turns display_errors back on, so keep its warnings out of the JSON body.
ob_start();
try {
	require $actionDir . '/generate_site_visit_pdf.php';
} catch (\Throwable $e) {
	ob_end_clean();
	echo json_encode(array('error' => true, 'message' => 'Could not generate the PDF: ' . $e->getMessage()));
	exit();
}
$output = ob_get_clean();
if (preg_match('/\{[^{}]*"pdfname"\s*:\s*"[^"]+"[^{}]*\}/', $output, $match)) {
	echo $match[0];
} else {
	echo json_encode(array('error' => true, 'message' => 'Could not generate the PDF.'));
}
?>
