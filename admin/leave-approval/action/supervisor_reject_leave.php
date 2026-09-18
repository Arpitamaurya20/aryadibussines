<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../../attendance-list/controller/attendance_controller.php');
include('../../employees/controller/employee_controller.php');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$leave_id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$reason = isset($_POST['RejectionReason']) ? $_POST['RejectionReason'] : '';
if ($leave_id <= 0) {
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

if (!employeeHasSupervisedTeam($conn, $approver_employee_id)) {
    $response['message'] = 'You do not have any employees assigned under your supervision.';
    echo json_encode($response);
    exit;
}

if (!canSupervisorApproveLeave($conn, $leave_id, $approver_employee_id)) {
    $response['message'] = 'You are not allowed to reject this leave request.';
    echo json_encode($response);
    exit;
}

$update = supervisorRejectEmployeeLeave($conn, $leave_id, $approver_employee_id, $reason);
if (!empty($update['success'])) {
    $response['error'] = false;
    $response['message'] = 'Leave rejected successfully.';
} else {
    $response['message'] = 'Unable to reject leave. ' . leaveUpdateErrorMessage($update['result']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
