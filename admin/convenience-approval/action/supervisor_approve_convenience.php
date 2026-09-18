<?php
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
include('../../attendance-list/controller/attendance_controller.php');
include('../../employees-convenience/controller/convenience_controller.php');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$convenience_id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$remarks = isset($_POST['remarks']) ? trim((string) $_POST['remarks']) : '';
if ($convenience_id <= 0) {
    echo json_encode($response);
    exit;
}

$roles = $_SESSION['Roles'] ?? array();
$approver_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : -1;
if ($approver_employee_id <= 0) {
    $response['message'] = 'You are not linked to an employee profile for approval.';
    echo json_encode($response);
    exit;
}

if (!employeeHasConvenienceTeam($conn, $approver_employee_id)) {
    $response['message'] = 'You do not have any employees assigned under your supervision.';
    echo json_encode($response);
    exit;
}

if (!canSupervisorApproveConvenience($conn, $convenience_id, $approver_employee_id)) {
    $response['message'] = 'You are not allowed to approve this convenience request.';
    echo json_encode($response);
    exit;
}

$update = supervisorApproveEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $remarks);
if (!empty($update['success'])) {
    $response['error'] = false;
    $response['message'] = 'Supervisor approval done. Convenience sent to HR for final approval.';
} else {
    $response['message'] = 'Unable to approve convenience. ' . convenienceUpdateErrorMessage($update['result']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
