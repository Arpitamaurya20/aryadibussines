<?php
@session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$compOffId = isset($_POST['comp_off_id']) ? intval($_POST['comp_off_id']) : 0;
$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$approve = !isset($_POST['approve']) || intval($_POST['approve']) === 1;
$reason = trim((string) ($_POST['reason'] ?? ''));

if ($compOffId <= 0) {
	echo json_encode(['error' => true, 'message' => 'Comp-off request is required.']);
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
$res = mysqli_query($conn, 'SELECT employee_id, status FROM employee_comp_off WHERE id = ' . $compOffId . ' LIMIT 1');
$row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
if (!$row || ($employeeId > 0 && intval($row['employee_id']) !== $employeeId)) {
	echo json_encode(['error' => true, 'message' => 'Comp-off record not found for this employee.']);
	exit;
}

$approverId = intval($_SESSION['Roles']['EmployeeID'] ?? 0);
$result = $elm->approveCompOff($compOffId, $approverId, $approve, $reason);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
