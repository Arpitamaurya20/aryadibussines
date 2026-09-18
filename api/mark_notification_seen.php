<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/controllers/portal_notification_controller.php');

$conn = _connectodb();
setTimeZone();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array(
        'error' => true,
        'message' => 'Only POST allowed',
    ));
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
    $notificationId = $data['notification_id'] ?? null;
    $userId = $data['user_id'] ?? null;
} else {
    if (empty($_POST)) {
        parse_str($rawInput, $_POST);
    }
    $notificationId = $_POST['notification_id'] ?? null;
    $userId = $_POST['user_id'] ?? null;
}

if ($notificationId === null || $notificationId === '' || empty($userId)) {
    echo json_encode(array(
        'error' => true,
        'message' => 'notification_id and user_id required',
    ));
    exit;
}

$userId = cleantext($userId);
$result = pnc_markMobileNotificationSeen($conn, $notificationId, $userId);
echo json_encode($result);
exit;
