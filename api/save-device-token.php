<?php

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/controllers/push_notification_controller.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (
    isset($data['UserID']) &&
    isset($data['DeviceToken']) &&
    isset($data['Platform'])
) {
    $conn = _connectodb();
    setTimeZone();

    $employeeId = pnc_resolveEmployeeId(
        $conn,
        (int) $data['UserID'],
        isset($data['EmployeeID']) ? (int) $data['EmployeeID'] : 0
    );
    $token = trim((string) $data['DeviceToken']);
    $platform = strtolower(trim((string) $data['Platform']));
    if ($platform !== 'ios') {
        $platform = 'android';
    }
    $now = date('Y-m-d H:i:s');

    if ($employeeId <= 0 || $token === '') {
        $response['error'] = true;
        $response['message'] = 'Invalid EmployeeID/UserID or DeviceToken';
    } else {
        $tokenEsc = mysqli_real_escape_string($conn, $token);
        $platformEsc = mysqli_real_escape_string($conn, $platform);
        $existing = _getSQLDetails($conn, "SELECT id FROM user_devices WHERE device_token = '$tokenEsc' LIMIT 1");

        if (is_array($existing) && !empty($existing['id'])) {
            $update = _UpdateTableRecords(
                $conn,
                'user_devices',
                "user_id = $employeeId, platform = '$platformEsc', last_active = '$now', IsActive = 1 WHERE device_token = '$tokenEsc'"
            );
            $response['error'] = !empty($update['error']);
            $response['message'] = $response['error'] ? 'Unable to update device token' : 'Device token updated successfully';
        } else {
            $insert = _InsertTableRecords_prepare($conn, 'user_devices', array(
                'user_id' => $employeeId,
                'device_token' => $token,
                'platform' => $platform,
                'last_active' => $now,
                'IsActive' => 1,
            ));
            $response['error'] = !empty($insert['error']);
            $response['message'] = $response['error'] ? 'Technical problem, please try again later' : 'Device token saved successfully';
        }
        $response['employee_id'] = $employeeId;
    }
} else {
    $response['error'] = true;
    $response['message'] = 'Missing Required Fields';
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
