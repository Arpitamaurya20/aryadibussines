<?php
@session_start();
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../view-kpi.php?err=' . urlencode('POST required'));
	exit;
}

$id      = isset($_POST['id']) ? intval($_POST['id']) : 0;
$returnY = isset($_POST['return_y'])  ? intval($_POST['return_y']) : intval(date('Y'));
$returnM = isset($_POST['return_m'])  ? intval($_POST['return_m']) : intval(date('n'));
$returnQ = isset($_POST['return_qs']) ? trim($_POST['return_qs']) : '';
$backUrl = '../view-kpi.php' . ($returnQ !== '' ? '?' . $returnQ : ('?y=' . $returnY . '&m=' . $returnM));
$qs = $backUrl . (strpos($backUrl, '?') === false ? '?' : '&');

if ($id <= 0) {
	header('Location: ' . $qs . 'err=' . urlencode('Invalid snapshot id'));
	exit;
}

$kpi = new Employeekpi($conn);
$result = $kpi->deleteSnapshot($id);
if (!empty($result['error'])) {
	header('Location: ' . $qs . 'err=' . urlencode($result['message'] ?? 'Failed to delete'));
} else {
	header('Location: ' . $qs . 'ok=' . urlencode('KPI snapshot deleted.'));
}
exit;
