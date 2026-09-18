<?php

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (
    isset($data['UserID']) &&
    isset($data['Title']) &&
    isset($data['Body'])
) {
    $conn = _connectodb();
    setTimeZone();
    require_once('../admin/controllers/push_notification_controller.php');

    $employeeId = pnc_resolveEmployeeId($conn, (int) $data['UserID']);
    $title  = $data['Title'];
    $body   = $data['Body'];
    $payload = isset($data['payload']) && is_array($data['payload']) ? $data['payload'] : array('screen' => 'dashboard');

    $response = pnc_sendPushDirect($conn, $employeeId, $title, $body, $payload);
} else {
    $response['error'] = true;
    $response['message'] = 'Missing Required Fields';
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
