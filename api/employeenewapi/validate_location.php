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

$EmployeeID = (int) $data['EmployeeID'];
$Latitude = $data['Latitude'] ?? '';
$Longitude = $data['Longitude'] ?? '';
$action = $data['Action'] ?? $data['action'] ?? 'checkin';
$gpsAccuracy = parseGpsAccuracyMeters($data['GpsAccuracy'] ?? $data['gpsAccuracy'] ?? $data['Accuracy'] ?? $data['accuracy'] ?? 0);

$conn = _connectodb();
$response = validateEmployeeAttendanceLocationWithBranchesApi($conn, $EmployeeID, $Latitude, $Longitude, $action, $gpsAccuracy);

echo json_encode($response);
