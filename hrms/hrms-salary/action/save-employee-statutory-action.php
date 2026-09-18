<?php
@session_start();
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$applyEPF = !empty($_POST['apply_epf']) ? 1 : 0;
$applyESI = !empty($_POST['apply_esi']) ? 1 : 0;
$returnY = isset($_POST['return_y']) ? intval($_POST['return_y']) : intval(date('Y'));
$returnM = isset($_POST['return_m']) ? intval($_POST['return_m']) : intval(date('n'));

$salary = new Salarypayroll($conn);
$res = $salary->updateEmployeeStatutoryFlags($employeeId, $applyEPF, $applyESI);

$base = '../view-payroll.php?y=' . $returnY . '&m=' . $returnM;
if (!empty($res['error'])) {
	header('Location: ' . $base . '&err=' . urlencode($res['message']));
} else {
	header('Location: ' . $base . '&ok=' . urlencode('Employee statutory settings updated'));
}
exit;
