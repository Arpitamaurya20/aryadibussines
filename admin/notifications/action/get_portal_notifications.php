<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../../controllers/portal_notification_controller.php');

header('Content-Type: application/json; charset=utf-8');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Not authenticated', 'count' => 0, 'notifications' => array());

if (!isset($_SESSION['pb_username'])) {
    echo json_encode($response);
    exit;
}

$ctx = pnc_getPortalUserContextFromSession($conn, $_SESSION);
$userId = (int) ($ctx['user_id'] ?? 0);
$employeeId = (int) ($ctx['employee_id'] ?? 0);

if ($userId <= 0) {
    $response['message'] = 'No portal user linked to this session';
    echo json_encode($response);
    exit;
}

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 30;
$notifications = pnc_getNotificationsForUser($conn, $userId, $employeeId, $limit);
$count = pnc_getUnreadCountForUser($conn, $userId, $employeeId);

echo json_encode(array(
    'error' => false,
    'count' => $count,
    'notifications' => $notifications,
));
exit;
