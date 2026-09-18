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
$employee = getEmployeeData($conn, $EmployeeID);

if (empty($employee)) {
    $response['error'] = true;
    $response['message'] = 'Employee not found';
    echo json_encode($response);
    exit;
}

$branchLocations = getBranchAttendanceLocationsApiData($conn, $EmployeeID);
$policy = getEmployeeAttendanceLocationPolicy($conn, $EmployeeID);

$response['error'] = false;
$response['message'] = 'Branch attendance locations fetched';
$response['data'] = [
    'EmployeeID' => $EmployeeID,
    'AttendanceRadiusMeters' => getEmployeeAttendanceRadiusMeters($employee),
    'boundaryEnabled' => !empty($policy['boundaryEnabled']),
    'employeeLocation' => [
        'AttendanceLatitude' => $policy['latitude'],
        'AttendanceLongitude' => $policy['longitude'],
    ],
    'branchLocations' => $branchLocations,
    'totalBranches' => count($branchLocations),
];

echo json_encode($response);
