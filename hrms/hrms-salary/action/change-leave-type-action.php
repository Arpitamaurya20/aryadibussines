<?php
@session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$leaveId = isset($_POST['leave_id']) ? intval($_POST['leave_id']) : 0;
$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$newType = isset($_POST['new_type']) ? strtoupper(trim((string) $_POST['new_type'])) : '';

if ($leaveId <= 0 || $employeeId <= 0) {
	echo json_encode(['error' => true, 'message' => 'Leave and employee are required.']);
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
$changedBy = $_SESSION['pb_username'] ?? $_SESSION['pp_username'] ?? $userType;
$result = $elm->changeApprovedLeaveType($leaveId, $employeeId, $newType, $changedBy);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
