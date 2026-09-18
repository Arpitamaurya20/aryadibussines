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
$current_date = date('Y-m-d');
$OutTime = date('H:i:s');
$EmployeeID = (int) $data['EmployeeID'];
$Latitude = $data['Latitude'] ?? '';
$Longitude = $data['Longitude'] ?? '';
$gpsAccuracy = parseGpsAccuracyMeters($data['GpsAccuracy'] ?? $data['gpsAccuracy'] ?? $data['Accuracy'] ?? $data['accuracy'] ?? 0);

$where = " where EmployeeID = $EmployeeID and RecordDate = '$current_date' and OutTime != ''";
if (!check_unique_identity_filter($conn, 'employee_attendance', $where)) {
    $response['error'] = true;
    $response['message'] = 'Attendance already punched out';
    echo json_encode($response);
    exit;
}

$checkoutCheck = getEmployeeCheckoutEligibility($conn, $EmployeeID);
if (!$checkoutCheck['canCheckout']) {
    $response['error'] = true;
    $response['message'] = $checkoutCheck['message'];
    echo json_encode($response);
    exit;
}

$geofence = validateEmployeeAttendanceGeofenceWithBranches($conn, $EmployeeID, $Latitude, $Longitude, 'checkout', $gpsAccuracy);
if (!$geofence['allowed']) {
    $response['error'] = true;
    $response['message'] = $geofence['message'];
    echo json_encode($response);
    exit;
}

$filename = '';
if (isset($data['imageData']) && $data['imageData'] !== '') {
    $imageData = base64_decode($data['imageData']);
    $var_name = "checkout_{$EmployeeID}_{$current_date}";
    $filename = 'ea_' . $var_name . uniqid() . '.jpg';
    $storageDirectory = '../../admin/media/employee_attendance/';
    file_put_contents($storageDirectory . $filename, $imageData);
}

$LatitudeSql = mysqli_real_escape_string($conn, (string) $Latitude);
$LongitudeSql = mysqli_real_escape_string($conn, (string) $Longitude);
$filenameSql = mysqli_real_escape_string($conn, $filename);

$query_parameter = "OutTime = '$OutTime',CheckoutLatitude='$LatitudeSql',CheckoutLongitude='$LongitudeSql',CheckoutImage='$filenameSql'"
    . " where EmployeeID = $EmployeeID and RecordDate = '$current_date'";
$result = _UpdateTableRecords($conn, 'employee_attendance', $query_parameter);

if ($result['error'] == false) {
    $response['error'] = false;
    $response['message'] = 'Attendance punched out';
    if (!empty($geofence['matchedLocation'])) {
        $response['data'] = ['matchedLocation' => $geofence['matchedLocation']];
    }
} else {
    $response['error'] = true;
    $response['message'] = $result['message'] ?? 'Could not punch out';
}

echo json_encode($response);
