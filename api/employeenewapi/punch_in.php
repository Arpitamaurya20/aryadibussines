<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
$InTime = date('H:i:s');
$EmployeeID = (int) $data['EmployeeID'];
$Latitude = $data['Latitude'] ?? '';
$Longitude = $data['Longitude'] ?? '';
$gpsAccuracy = parseGpsAccuracyMeters($data['GpsAccuracy'] ?? $data['gpsAccuracy'] ?? $data['Accuracy'] ?? $data['accuracy'] ?? 0);

$filename = '';
if (isset($data['imageData']) && $data['imageData'] !== '') {
    $rawImage = $data['imageData'];
    // Strip data-url prefix if client sends data:image/jpeg;base64,...
    if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $rawImage)) {
        $rawImage = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $rawImage);
    }
    $rawImage = preg_replace('/\s+/', '', $rawImage);
    $imageBinary = base64_decode($rawImage, true);
    if ($imageBinary === false || strlen($imageBinary) < 100) {
        $response['error'] = true;
        $response['message'] = 'Invalid check-in image data';
        echo json_encode($response);
        exit;
    }

    $var_name = "checkin_{$EmployeeID}_{$current_date}";
    $filename = 'ea_' . $var_name . uniqid() . '.jpg';
    $storageDirectory = __DIR__ . '/../../admin/media/employee_attendance/';
    if (!is_dir($storageDirectory)) {
        mkdir($storageDirectory, 0775, true);
    }
    $saved = file_put_contents($storageDirectory . $filename, $imageBinary);
    if ($saved === false || $saved < 100) {
        $response['error'] = true;
        $response['message'] = 'Could not save check-in image';
        echo json_encode($response);
        exit;
    }
}

$where = " where EmployeeID = $EmployeeID and RecordDate = '$current_date'";
if (!check_unique_identity_filter($conn, 'employee_attendance', $where)) {
    // Already punched in — if check-in photo is missing, attach it now
    $existing = _getTableDetails($conn, 'employee_attendance', $where);
    if (is_array($existing) && empty($existing['CheckinImage']) && $filename !== '') {
        $filenameSql = mysqli_real_escape_string($conn, $filename);
        $id = (int) $existing['ID'];
        $update = "CheckinImage='$filenameSql' WHERE ID=$id";
        $result = _UpdateTableRecords($conn, 'employee_attendance', $update);
        if ($result['error'] == false) {
            $response['error'] = false;
            $response['message'] = 'Check-in photo saved';
            $response['CheckinImage'] = $filename;
            echo json_encode($response);
            exit;
        }
    }
    $response['error'] = true;
    $response['message'] = 'Attendance already punched in';
    echo json_encode($response);
    exit;
}

$geofence = validateEmployeeAttendanceGeofenceWithBranches($conn, $EmployeeID, $Latitude, $Longitude, 'checkin', $gpsAccuracy);
if (!$geofence['allowed']) {
    $response['error'] = true;
    $response['message'] = $geofence['message'];
    echo json_encode($response);
    exit;
}

if ($filename === '') {
    $response['error'] = true;
    $response['message'] = 'Check-in selfie (imageData) is required';
    echo json_encode($response);
    exit;
}

$LatitudeSql = mysqli_real_escape_string($conn, (string) $Latitude);
$LongitudeSql = mysqli_real_escape_string($conn, (string) $Longitude);
$filenameSql = mysqli_real_escape_string($conn, $filename);

$insert_attendance = "INSERT INTO employee_attendance(EmployeeID,RecordDate,InTime,Latitude,Longitude,CheckinImage,ApprovalStatus)"
    . " VALUES ($EmployeeID,'$current_date','$InTime','$LatitudeSql','$LongitudeSql','$filenameSql','Pending')";
$result = _InsertTableRecords($conn, $insert_attendance);

if ($result['error'] == false) {
    $response['error'] = false;
    $response['message'] = 'Attendance punched in';
    $response['CheckinImage'] = $filename;
    if (!empty($geofence['matchedLocation'])) {
        $response['data'] = [
            'matchedLocation' => $geofence['matchedLocation'],
        ];
    }
} else {
    $response['error'] = true;
    $response['message'] = $result['message'] ?? 'Could not punch in';
}

echo json_encode($response);
