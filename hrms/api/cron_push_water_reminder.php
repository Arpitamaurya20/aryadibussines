<?php
/**
 * Water reminder push: (1) Every 30 min at :00 and :30 when cron runs. (2) When you open this URL (trigger), send immediately (within 7 AM–11:30 PM).
 * Cron: run every 5–10 min so it hits :00 and :30. Browser/trigger: open URL anytime in window to send one notification now.
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

$is_cli = (php_sapi_name() === 'cli');
$show_html = !$is_cli && (!empty($_GET['show']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false));

function cron_response($data) {
    global $show_html;
    $why = isset($data['why_not_came']) ? $data['why_not_came'] : ($data['message'] ?? '');
    if ($show_html) {
        header('Content-Type: text/html; charset=utf-8');
        $title = !empty($data['sent']) ? 'Water reminder sent' : 'Why notification did not come';
        $msg = !empty($data['sent']) ? "Sent to {$data['sent']} device(s)." : $why;
        $color = isset($data['error']) && $data['error'] ? '#c00' : '#333';
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title></head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script><body style="font-family:sans-serif;max-width:560px;margin:40px auto;padding:20px;">';
        echo '<h1 style="color:' . $color . '">' . htmlspecialchars($title) . '</h1>';
        echo '<p style="font-size:1.1em;line-height:1.5">' . nl2br(htmlspecialchars($msg)) . '</p>';
        if (!empty($data['server_time'])) echo '<p style="color:#666">Server time: ' . htmlspecialchars($data['server_time']) . ' (Asia/Kolkata)</p>';
        echo '<p style="color:#666;font-size:0.9em">JSON: <code style="background:#f0f0f0;padding:2px 6px">' . htmlspecialchars(json_encode($data, JSON_UNESCAPED_SLASHES)) . '</code></p></body></html>';
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }
    exit;
}

require_once __DIR__ . '/common-api-header.php';
require_once __DIR__ . '/../include/fcm_config.php';
require_once __DIR__ . '/../include/fcm_send.php';

date_default_timezone_set('Asia/Kolkata');

// Optional: restrict to cron only (set CRON_PUSH_SECRET in config or env, then call with ?key=SECRET)
$cron_secret = defined('CRON_PUSH_SECRET') ? CRON_PUSH_SECRET : (getenv('CRON_PUSH_SECRET') ?: '');
if ($cron_secret !== '' && ($_GET['key'] ?? '') !== $cron_secret) {
    http_response_code(403);
    cron_response(['error' => true, 'message' => 'Forbidden', 'reason' => 'missing_or_invalid_key', 'why_not_came' => 'Wrong or missing key. Add ?key=YOUR_CRON_SECRET to the URL.']);
}

$hour = (int) date('G');
$minute = (int) date('i');

// Window: 7:00 AM to 11:30 PM
if ($hour < 7) {
    if (!$is_cli) cron_response(['error' => false, 'skipped' => true, 'reason' => 'before_window', 'message' => 'Before 7:00 AM. Notifications run 7:00 AM–11:30 PM only.', 'why_not_came' => 'Notification did not come because it is before 7:00 AM. Water reminders run only between 7:00 AM and 11:30 PM.', 'server_time' => date('H:i')]);
    exit;
}
if ($hour > 23 || ($hour === 23 && $minute > 30)) {
    if (!$is_cli) cron_response(['error' => false, 'skipped' => true, 'reason' => 'after_window', 'message' => 'After 11:30 PM. Notifications run 7:00 AM–11:30 PM only.', 'why_not_came' => 'Notification did not come because it is after 11:30 PM. Water reminders run only between 7:00 AM and 11:30 PM.', 'server_time' => date('H:i')]);
    exit;
}
// Cron only: send at :00 and :30. When opened in browser (trigger), send immediately.
if ($is_cli && $minute !== 0 && $minute !== 30) {
    exit;
}

if (!is_file(FCM_SERVICE_ACCOUNT_PATH)) {
    if (!$is_cli) cron_response(['error' => true, 'skipped' => true, 'reason' => 'no_fcm_config', 'message' => 'FCM config file not found.', 'why_not_came' => 'Notification did not come because Firebase config file is missing. Upload healthx-007-firebase-adminsdk-fbsvc-f588013f34.json to the config/ folder on the server.']);
    if ($is_cli) fwrite(STDERR, "FCM config not found.\n");
    exit;
}

$stmt = $conn->prepare("SELECT ID, FCMToken FROM push_tokens WHERE WaterReminderEnabled = 1 AND FCMToken != ''");
if (!$stmt) {
    if (!$is_cli) cron_response(['error' => true, 'reason' => 'no_table', 'message' => 'push_tokens table missing.', 'why_not_came' => 'Notification did not come because the push_tokens table is missing. Run api/push_tokens_table.sql on your database.']);
    exit;
}
if (!$stmt->execute()) {
    if (!$is_cli) cron_response(['error' => true, 'reason' => 'db_error', 'message' => 'Database error.', 'why_not_came' => 'Notification did not come because of a database error. Check that push_tokens table exists and the server can connect to the database.']);
    exit;
}
$res = $stmt->get_result();
$tokens = [];
while ($row = $res->fetch_assoc()) {
    $tokens[] = $row['FCMToken'];
}
$stmt->close();

if (count($tokens) === 0) {
    if (!$is_cli) cron_response(['error' => false, 'skipped' => true, 'reason' => 'no_devices', 'message' => 'No devices registered.', 'why_not_came' => 'Notification did not come because no device is registered for push. Open the HealthX app, login, and allow notifications so the app can register the device. Or call POST api/push_register_api.php with FCMToken and JWT.', 'token_count' => 0]);
    exit;
}

$accessToken = fcm_get_access_token();
$projectId = fcm_get_project_id();
if ($accessToken === null || $projectId === null) {
    if (!$is_cli) cron_response(['error' => true, 'reason' => 'fcm_auth_failed', 'message' => 'Could not get FCM access token.', 'why_not_came' => 'Notification did not come because Firebase authentication failed. Check that the service account JSON file is correct (download again from Firebase Console > Project Settings > Service Accounts > Generate new private key).']);
    if ($is_cli) fwrite(STDERR, "FCM token or project id missing.\n");
    exit;
}

$title = 'Stay hydrated';
$body = 'Time for a glass of water.';

$sent = 0;
foreach ($tokens as $token) {
    if (fcm_send_to_token($accessToken, $projectId, $token, $title, $body)) {
        $sent++;
    }
}

if ($is_cli) {
    echo "Water reminder sent to $sent devices.\n";
} else {
    cron_response(['error' => false, 'message' => 'Water reminder sent.', 'why_not_came' => '', 'sent' => $sent, 'token_count' => count($tokens)]);
}
