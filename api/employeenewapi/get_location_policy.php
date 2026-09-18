<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/employees/controller/employee_controller.php');

setTimeZone();

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = [];

if (!isset($data['EmployeeID']) || $data['EmployeeID'] === '') {
    $response['error'] = true;
    $response['message'] = 'Missing User Fields!';
    echo json_encode($response);
    exit;
}

$conn = _connectodb();
$EmployeeID = (int) $data['EmployeeID'];
$policyData = getEmployeeAttendanceLocationPolicyWithBranchesApiData($conn, $EmployeeID);

if ($policyData === null) {
    $response['error'] = true;
    $response['message'] = 'Employee not found';
    echo json_encode($response);
    exit;
}

$response['error'] = false;
$response['message'] = 'Attendance location policy fetched';
$response['data'] = $policyData;

echo json_encode($response);
