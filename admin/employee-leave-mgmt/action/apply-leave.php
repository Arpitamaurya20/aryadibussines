<?php
header('Content-Type: application/json; charset=utf-8');
@ini_set('display_errors', '0');
session_start();require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
include('../controller/employee_leave_mgmt_controller.php');

$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);
elm_require_employee_access();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'message' => 'Invalid request.']);
    exit;
}

$conn = _connectodb();
$elm = new Employeeleavemgmt($conn);
$employeeId = elm_session_employee_id();
$data = array_merge($_POST, ['EmployeeID' => $employeeId]);
$result = $elm->applyLeave($data, elm_session_username());
echo json_encode($result);
