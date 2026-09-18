<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/includes/autoloader.inc.php');
require_once('../core/notification.php');

$conn = _connectodb();
setTimeZone();

$response = [];

/* =========================
   STEP 1: FETCH PENDING NOTIFICATIONS
========================= */
$notifications = _getTableRecords(
    $conn,
    "push_notifications_log",
    "WHERE status = 'failed' ORDER BY id ASC LIMIT 20"
);

if (empty($notifications)) {
    echo json_encode([
        "error"   => false,
        "message" => "No pending notifications"
    ]);
    exit;
}

/* =========================
   STEP 2: PROCESS ONE BY ONE
========================= */
foreach ($notifications as $n) {

    $notificationId = (int)$n['id'];
    $userId         = (int)$n['user_id'];
    $title          = $n['title'];
    $body           = $n['body'];
    $payload        = json_decode($n['payload'], true) ?: [];

    /* =========================
       GET USER DEVICES
    ========================= */
    $devices = _getTableRecords(
        $conn,
        "user_devices",
        "WHERE user_id = '$userId' AND IsActive = 1"
    );

    if (empty($devices)) {

        // ❌ No devices → FAILED
        _UpdateTableRecords_prepare(
            $conn,
            "push_notifications_log",
            [
                "status"        => "FAILED",
                "error_message" => "No active devices"
            ],
            ["id" => $notificationId]
        );

        continue;
    }

    /* =========================
       SEND PUSH TO ALL DEVICES
    ========================= */
    $success = 0;
    $failed  = 0;

    foreach ($devices as $device) {

        $token = trim($device['device_token']);
        if ($token === '') {
            $failed++;
            continue;
        }

        try {
            $result = sendPush($token, $title, $body, $payload);

            if ($result) {
                $success++;
            } else {
                $failed++;
            }

        } catch (Exception $e) {
            $failed++;
        }
    }

    /* =========================
       UPDATE FINAL STATUS
    ========================= */
    if ($success > 0) {

        _UpdateTableRecords_prepare(
            $conn,
            "push_notifications_log",
            [
                "status"        => "SENT",
                "error_message" => ""
            ],
            ["id" => $notificationId]
        );

    } else {

        _UpdateTableRecords_prepare(
            $conn,
            "push_notifications_log",
            [
                "status"        => "FAILED",
                "error_message" => "Push failed on all devices"
            ],
            ["id" => $notificationId]
        );
    }
}

echo json_encode([
    "error"   => false,
    "message" => "Notification processing completed"
]);
exit;
