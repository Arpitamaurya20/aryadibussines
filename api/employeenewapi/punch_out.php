<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/employees/controller/employee_controller.php');

setTimeZone();

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = [];

if (!is_array($data) || !isset($data['EmployeeID']) || $data['EmployeeID'] === '') {
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
    $response['data'] = [
        'hoursWorked' => $checkoutCheck['hoursWorked'] ?? 0,
        'minutesWorked' => $checkoutCheck['minutesWorked'] ?? 0,
        'remainingMinutes' => $checkoutCheck['remainingMinutes'] ?? 0,
        'minHoursRequired' => $checkoutCheck['minHoursRequired'] ?? 2,
        'checkInTime' => $checkoutCheck['checkInTime'] ?? null,
    ];
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
    $rawImage = $data['imageData'];
    if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $rawImage)) {
        $rawImage = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $rawImage);
    }
    $rawImage = preg_replace('/\s+/', '', $rawImage);
    $imageBinary = base64_decode($rawImage, true);
    if ($imageBinary === false || strlen($imageBinary) < 100) {
        $response['error'] = true;
        $response['message'] = 'Invalid check-out image data';
        echo json_encode($response);
        exit;
    }

    $var_name = "checkout_{$EmployeeID}_{$current_date}";
    $filename = 'ea_' . $var_name . uniqid() . '.jpg';
    $storageDirectory = __DIR__ . '/../../admin/media/employee_attendance/';
    if (!is_dir($storageDirectory)) {
        mkdir($storageDirectory, 0775, true);
    }
    $saved = file_put_contents($storageDirectory . $filename, $imageBinary);
    if ($saved === false || $saved < 100) {
        $response['error'] = true;
        $response['message'] = 'Could not save check-out image';
        echo json_encode($response);
        exit;
    }
}

if ($filename === '') {
    $response['error'] = true;
    $response['message'] = 'Check-out selfie (imageData) is required';
    echo json_encode($response);
    exit;
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
    $response['CheckoutImage'] = $filename;
    if (!empty($geofence['matchedLocation'])) {
        $response['data'] = ['matchedLocation' => $geofence['matchedLocation']];
    }
} else {
    $response['error'] = true;
    $response['message'] = $result['message'] ?? 'Could not punch out';
}

echo json_encode($response);
