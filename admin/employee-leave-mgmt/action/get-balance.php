<?php
header('Content-Type: application/json; charset=utf-8');
@ini_set('display_errors', '0');
session_start();require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
include('../controller/employee_leave_mgmt_controller.php');

$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);
elm_require_employee_access();

$conn = _connectodb();
$elm = new Employeeleavemgmt($conn);
$balance = $elm->getBalanceSummary(elm_session_employee_id());
echo json_encode(['error' => false, 'data' => $balance]);
