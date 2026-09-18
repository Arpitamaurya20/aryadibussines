<?php
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
include('../../attendance-list/controller/attendance_controller.php');
include('../../employees-convenience/controller/convenience_controller.php');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$convenience_id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$reason = isset($_POST['reason']) ? trim((string) $_POST['reason']) : '';
if ($convenience_id <= 0) {
    echo json_encode($response);
    exit;
}

$roles = $_SESSION['Roles'] ?? array();
$approver_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : -1;
if ($approver_employee_id <= 0 || !employeeHasConvenienceTeam($conn, $approver_employee_id)) {
    $response['message'] = 'You are not allowed to reject this convenience request.';
    echo json_encode($response);
    exit;
}

if (!canSupervisorApproveConvenience($conn, $convenience_id, $approver_employee_id)) {
    $response['message'] = 'You are not allowed to reject this convenience request.';
    echo json_encode($response);
    exit;
}

$update = supervisorRejectEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $reason);
if (!empty($update['success'])) {
    $response['error'] = false;
    $response['message'] = 'Convenience request rejected.';
} else {
    $response['message'] = 'Unable to reject convenience. ' . convenienceUpdateErrorMessage($update['result']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
