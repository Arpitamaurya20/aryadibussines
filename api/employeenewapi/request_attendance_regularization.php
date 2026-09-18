<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/attendance-list/controller/attendance_controller.php');
require_once('attendance_regularization_helpers.php');

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
$entries = ear_normalize_regularization_entries($data);
$reason = trim((string) ($data['reason'] ?? $data['Reason'] ?? $data['regularization_reason'] ?? ''));

if (empty($entries)) {
    $response['error'] = true;
    $response['message'] = 'At least one attendance entry is required.';
    echo json_encode($response);
    exit;
}

$result = submitAttendanceRegularizationBatch($conn, $EmployeeID, $entries, $reason);

if (!empty($result['error'])) {
    $response['error'] = true;
    $response['message'] = $result['message'] ?? 'Could not submit attendance regularization.';
    if (!empty($result['errors'])) {
        $response['errors'] = $result['errors'];
    }
    echo json_encode($response);
    exit;
}

$response['error'] = false;
$response['message'] = $result['message'] ?? 'Attendance regularization submitted successfully.';
$response['attendance_ids'] = $result['attendance_ids'] ?? array();
$response['saved_count'] = (int) ($result['saved_count'] ?? 0);
$response['failed_count'] = (int) ($result['failed_count'] ?? 0);
$response['errors'] = $result['errors'] ?? array();
$response['data'] = $result['records'] ?? array();

echo json_encode($response);
