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
if (!is_array($data)) {
    $data = array();
}
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

if (empty($data['user_id'])) {
    echo json_encode(array(
        'error' => true,
        'message' => 'user_id required',
    ));
    exit;
}

$userId = cleantext($data['user_id']);
$ctx = pnc_resolveMobileNotificationUserContext($conn, $userId);
if ((int) ($ctx['app_log_user_id'] ?? 0) <= 0 && (int) ($ctx['request_user_id'] ?? 0) <= 0) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Invalid user_id',
    ));
    exit;
}

$pushUpdated = pnc_markAllPushLogNotificationsSeen($conn, $userId);
if ($pushUpdated === false) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Failed to clear push notifications',
    ));
    exit;
}

$portalUpdated = pnc_markAllPortalNotificationsSeenForMobileUser($conn, $userId);

echo json_encode(array(
    'error' => false,
    'message' => 'All notifications cleared successfully',
    'push_updated' => (int) $pushUpdated,
    'portal_updated' => (int) $portalUpdated,
));
exit;
