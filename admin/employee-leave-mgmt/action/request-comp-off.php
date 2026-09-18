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
$result = $elm->requestCompOff(
    elm_session_employee_id(),
    $_POST['work_date'] ?? '',
    $_POST['credit_days'] ?? 1,
    $_POST['reason'] ?? '',
    elm_session_username()
);
echo json_encode($result);
