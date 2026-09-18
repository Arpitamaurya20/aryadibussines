<?php
include("../../controllers/common_controllers.php");
include('../../attendance-list/controller/attendance_controller.php');

$UserType = SessionCheck();
$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

if (!isset($_POST['ID'])) {
    echo json_encode($response);
    exit;
}

$attendance_id = (int) $_POST['ID'];
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

if (!canSupervisorApproveAttendance($conn, $attendance_id, $approver_employee_id)) {
    $response['message'] = 'You are not allowed to approve this attendance record.';
    echo json_encode($response);
    exit;
}

$update = supervisorApproveEmployeeAttendance($conn, $attendance_id, $approver_employee_id);
if (!empty($update['success'])) {
    $response['error'] = false;
    $response['message'] = 'Supervisor approval done. Record sent to HR for final approval.';
} else {
    $response['message'] = 'Unable to approve attendance. ' . attendanceUpdateErrorMessage($update['result']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
