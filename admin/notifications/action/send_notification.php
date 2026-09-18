<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../../controllers/portal_notification_controller.php');

header('Content-Type: application/json; charset=utf-8');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$roles = $_SESSION['Roles'] ?? array();
if (!hasPortalNotificationAdminAccess($roles)) {
    $response['message'] = 'Not authorized.';
    echo json_encode($response);
    exit;
}

$target = isset($_POST['Target']) ? strtolower(trim((string) $_POST['Target'])) : 'user';
$title = isset($_POST['Title']) ? trim((string) $_POST['Title']) : '';
$body = isset($_POST['Body']) ? trim((string) $_POST['Body']) : '';
$userId = isset($_POST['UserID']) ? (int) $_POST['UserID'] : 0;
$employeeId = isset($_POST['EmployeeID']) ? (int) $_POST['EmployeeID'] : 0;
$sendPush = !isset($_POST['SendPush']) || $_POST['SendPush'] === '1' || $_POST['SendPush'] === 'true';
$screen = isset($_POST['Screen']) ? trim((string) $_POST['Screen']) : 'dashboard';
$module = isset($_POST['Module']) ? trim((string) $_POST['Module']) : 'general';

$payload = array(
    'screen' => $screen !== '' ? $screen : 'dashboard',
    'module' => $module !== '' ? $module : 'general',
    'type' => 'GENERAL_NOTIFICATION',
);

$result = pnc_publishNotification($conn, array(
    'target' => $target,
    'title' => $title,
    'body' => $body,
    'user_id' => $userId,
    'employee_id' => $employeeId,
    'payload' => $payload,
    'send_push' => $sendPush,
    'save_portal' => true,
    'created_by' => $_SESSION['pb_username'] ?? 'admin',
));

echo json_encode($result);
exit;
