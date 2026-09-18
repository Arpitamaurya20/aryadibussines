<?php

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/controllers/push_notification_controller.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (
    isset($data['UserID']) &&
    isset($data['DeviceToken'])
) {
    $conn = _connectodb();
    setTimeZone();

    $employeeId = pnc_resolveEmployeeId(
        $conn,
        (int) $data['UserID'],
        isset($data['EmployeeID']) ? (int) $data['EmployeeID'] : 0
    );
    $token = mysqli_real_escape_string($conn, trim((string) $data['DeviceToken']));

    if ($employeeId <= 0 || $token === '') {
        $response['error'] = true;
        $response['message'] = 'Invalid EmployeeID/UserID or DeviceToken';
    } else {
        $result = mysqli_query($conn, "DELETE FROM user_devices WHERE user_id = $employeeId AND device_token = '$token'");
        if ($result) {
            $response['error'] = false;
            $response['message'] = 'Device token removed successfully';
        } else {
            $response['error'] = true;
            $response['message'] = 'Unable to remove device token, try again';
        }
    }
} else {
    $response['error'] = true;
    $response['message'] = 'Missing Required Fields';
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
