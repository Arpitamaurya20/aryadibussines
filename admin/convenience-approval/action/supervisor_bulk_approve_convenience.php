<?php
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
include('../../attendance-list/controller/attendance_controller.php');
include('../../employees-convenience/controller/convenience_controller.php');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$ids = isset($_POST['IDs']) && is_array($_POST['IDs']) ? $_POST['IDs'] : array();
$ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
    return $id > 0;
})));
$remarks = isset($_POST['remarks']) ? trim((string) $_POST['remarks']) : '';

if (empty($ids)) {
    $response['message'] = 'Please select at least one convenience request.';
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

$summary = supervisorBulkApproveEmployeeConvenience($conn, $ids, $approver_employee_id, $remarks);
$response['error'] = ((int) ($summary['success'] ?? 0)) <= 0;
$response['message'] = buildConvenienceBulkActionMessage($summary, 'approved by supervisor');
$response['summary'] = $summary;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
