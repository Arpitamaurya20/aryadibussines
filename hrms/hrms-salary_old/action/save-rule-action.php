<?php
@session_start();
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$bulk = isset($_POST['bulk_rules']) ? trim($_POST['bulk_rules']) : '';
$returnY = isset($_POST['return_y']) ? intval($_POST['return_y']) : intval(date('Y'));
$returnM = isset($_POST['return_m']) ? intval($_POST['return_m']) : intval(date('n'));
$returnPage = isset($_POST['return_page']) ? basename($_POST['return_page']) : 'view-rules.php';
if ($returnPage === '' || strpos($returnPage, '.php') === false) {
	$returnPage = 'view-rules.php';
}

$by = isset($_SESSION['pp_email']) ? $_SESSION['pp_email'] : 'admin';
$salary = new Salarypayroll($conn);

$lines = preg_split('/\r\n|\r|\n/', $bulk);
$saved = 0;
foreach ($lines as $line) {
	$line = trim($line);
	if ($line === '' || strpos($line, '=') === false) {
		continue;
	}
	list($k, $v) = explode('=', $line, 2);
	$k = trim($k);
	$v = trim($v);
	if ($k === '') {
		continue;
	}
	$r = $salary->saveRuleKey($k, $v, $by);
	if (isset($r['error']) && $r['error'] === false) {
		$saved++;
	}
}

header('Location: ../' . $returnPage . '?y=' . $returnY . '&m=' . $returnM . '&ok=' . urlencode('Rules saved (' . $saved . ' keys).'));
exit;
