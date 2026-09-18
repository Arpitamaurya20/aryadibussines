<?php
/**
 * Legacy/web alias — use validate_employee_attendance_location.php for mobile.
 * Same payload: EmployeeID, Latitude, Longitude
 */
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/employees/controller/employee_controller.php');

setTimeZone();

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

if (!isset($data['EmployeeID']) || $data['EmployeeID'] === '') {
    echo json_encode([
        'error' => true,
        'message' => 'Missing User Fields!',
        'allowed' => false,
        'boundaryEnabled' => false,
    ]);
    exit;
}

$conn = _connectodb();
$action = $data['Action'] ?? $data['action'] ?? 'checkin';
$gpsAccuracy = parseGpsAccuracyMeters($data['GpsAccuracy'] ?? $data['gpsAccuracy'] ?? $data['Accuracy'] ?? $data['accuracy'] ?? 0);
$result = validateEmployeeAttendanceLocationWithBranchesApi(
    $conn,
    (int) $data['EmployeeID'],
    $data['Latitude'] ?? '',
    $data['Longitude'] ?? '',
    $action,
    $gpsAccuracy
);

$legacy = [
    'error' => $result['error'],
    'message' => $result['message'],
    'allowed' => !empty($result['data']['allowed']),
    'boundaryEnabled' => !empty($result['data']['boundaryEnabled']),
];

if (!empty($result['data']['distanceMeters'])) {
    $legacy['distanceMeters'] = $result['data']['distanceMeters'];
    $legacy['radiusMeters'] = $result['data']['AttendanceRadiusMeters'] ?? 100;
}

if (!empty($result['data']['matchedLocation'])) {
    $legacy['matchedLocation'] = $result['data']['matchedLocation'];
}

echo json_encode($legacy);
