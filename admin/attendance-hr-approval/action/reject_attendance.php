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
$reason = isset($_POST['RejectionReason']) ? $_POST['RejectionReason'] : '';
$roles = $_SESSION['Roles'] ?? array();

if (!hasHrAttendanceApprovalAccess($roles)) {
    $response['message'] = 'You are not allowed to perform HR final rejection.';
    echo json_encode($response);
    exit;
}

if (!canHrApproveAttendance($conn, $attendance_id, $roles)) {
    $response['message'] = 'This record is not pending HR final approval.';
    echo json_encode($response);
    exit;
}

$approver_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : 0;
$update = hrRejectEmployeeAttendance($conn, $attendance_id, $approver_employee_id, $reason);
if (!empty($update['success'])) {
    $response['error'] = false;
    $response['message'] = 'Attendance rejected by HR.';
} else {
    $response['message'] = 'Unable to reject attendance. ' . attendanceUpdateErrorMessage($update['result']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
