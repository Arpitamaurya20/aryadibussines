<?php
@session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$workDate = trim((string) ($_POST['work_date'] ?? ''));
$creditDays = isset($_POST['credit_days']) ? floatval($_POST['credit_days']) : 1;
$reason = trim((string) ($_POST['reason'] ?? 'Worked on weekly off / holiday'));

if ($employeeId <= 0 || $workDate === '') {
	echo json_encode(['error' => true, 'message' => 'Employee and work date are required.']);
	exit;
}

$elmClassFile = dirname(__DIR__, 3) . '/admin/classes/employeeleavemgmt.class.php';
if (!class_exists('Employeeleavemgmt', false) && is_file($elmClassFile)) {
	require_once $elmClassFile;
}
if (!class_exists('Employeeleavemgmt')) {
	echo json_encode(['error' => true, 'message' => 'Leave management module is not available.']);
	exit;
}

$elm = new Employeeleavemgmt($conn);
$userType = $_SESSION['pp_UserType'] ?? $_SESSION['UserType'] ?? '';
$createdBy = $_SESSION['pb_username'] ?? $_SESSION['pp_username'] ?? $userType;
$autoApprove = !isset($_POST['auto_approve']) || intval($_POST['auto_approve']) === 1;

$result = $elm->hrGrantCompOff($employeeId, $workDate, $creditDays, $reason, $createdBy, $autoApprove);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
