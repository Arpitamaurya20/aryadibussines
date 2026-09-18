<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/attendance-list/controller/attendance_controller.php');

header('Content-Type: application/json; charset=utf-8');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

setTimeZone();

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
if (!is_array($data)) {
    $data = array();
}
if (!empty($_GET)) {
    $data = array_merge($_GET, $data);
}

$response = array();

if (!isset($data['EmployeeID']) || $data['EmployeeID'] === '') {
    $response['error'] = true;
    $response['message'] = 'Missing User Fields!';
    echo json_encode($response);
    exit;
}

try {
    $conn = _connectodb();
} catch (Throwable $e) {
    $response['error'] = true;
    $response['message'] = 'Technical Problem. Please try again';
    echo json_encode($response);
    exit;
}

if (!$conn) {
    $response['error'] = true;
    $response['message'] = 'Technical Problem. Please try again';
    echo json_encode($response);
    exit;
}

$EmployeeID = (int) $data['EmployeeID'];
$from_date = trim((string) ($data['from_date'] ?? $data['FromDate'] ?? ''));
$to_date = trim((string) ($data['to_date'] ?? $data['ToDate'] ?? ''));

$records = getEmployeeAttendanceRegularizationHistory($conn, $EmployeeID, $from_date, $to_date);

$response['error'] = false;
$response['message'] = 'Records fetched';
$response['count'] = count($records);
$response['data'] = $records;

echo json_encode($response);
