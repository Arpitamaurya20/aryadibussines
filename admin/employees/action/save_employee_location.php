<?php
include("../../controllers/common_controllers.php");
include('../controller/employee_controller.php');

header('Content-Type: application/json');
@session_start();
setTimeZone();
$conn = _connectodb();

$EmployeeID = intval($_POST['EmployeeID'] ?? 0);
if ($EmployeeID === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid Employee',
    ]);
    exit;
}

$data = [
    'AttendanceLatitude' => $_POST['attendance_latitude'] ?? '',
    'AttendanceLongitude' => $_POST['attendance_longitude'] ?? '',
    'AttendanceRadiusMeters' => $_POST['attendance_radius_meters'] ?? 100,
    'IsAllowLocationBoundary' => isset($_POST['is_allow_location_boundary']) ? 1 : 0,
    'UpdatedBy' => $_SESSION['pb_username'] ?? '',
];

$result = updateEmployeeAttendanceLocation($conn, $EmployeeID, $data);

if (!empty($result['error'])) {
    echo json_encode([
        'status' => 'error',
        'message' => $result['message'] ?? 'Could not save location',
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => 'Attendance location saved successfully',
]);
