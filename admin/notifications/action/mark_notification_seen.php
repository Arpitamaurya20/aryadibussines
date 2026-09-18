<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../../controllers/portal_notification_controller.php');

header('Content-Type: application/json; charset=utf-8');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

if (!isset($_SESSION['pb_username'])) {
    $response['message'] = 'Not authenticated';
    echo json_encode($response);
    exit;
}

$notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;
$ctx = pnc_getPortalUserContextFromSession($conn, $_SESSION);
$userId = (int) ($ctx['user_id'] ?? 0);
$employeeId = (int) ($ctx['employee_id'] ?? 0);

if ($notificationId <= 0 || $userId <= 0) {
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}

if (!pnc_userCanViewNotification($conn, $notificationId, $userId, $employeeId)) {
    $response['message'] = 'Notification not found';
    echo json_encode($response);
    exit;
}

echo json_encode(pnc_markNotificationSeen($conn, $notificationId, $userId));
exit;
