<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../../attendance-list/controller/attendance_controller.php');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$ids = isset($_POST['IDs']) && is_array($_POST['IDs']) ? $_POST['IDs'] : array();
$ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
    return $id > 0;
})));
$reason = isset($_POST['RejectionReason']) ? trim((string) $_POST['RejectionReason']) : '';

if (empty($ids)) {
    $response['message'] = 'Please select at least one attendance record.';
    echo json_encode($response);
    exit;
}

$roles = $_SESSION['Roles'] ?? array();
$approver_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : -1;
if ($approver_employee_id <= 0 || !employeeHasSupervisedTeam($conn, $approver_employee_id)) {
    $response['message'] = 'You are not allowed to reject these attendance records.';
    echo json_encode($response);
    exit;
}

$summary = supervisorBulkRejectEmployeeAttendance($conn, $ids, $approver_employee_id, $reason);
$response['error'] = ((int) ($summary['success'] ?? 0)) <= 0;
$response['message'] = buildAttendanceBulkActionMessage($summary, 'rejected by supervisor');
$response['summary'] = $summary;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
