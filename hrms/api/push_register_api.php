<?php
/**
 * Register device for push notifications (FCM).
 * JWT required. Stores token for water reminder (7 AM–11:30 PM, every 30 min) and future notifications.
 */
require_once __DIR__ . '/common-api-header.php';
require_once __DIR__ . '/auth_jwt.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

$user_id = (int) $auth_user['user_id'];
if ($user_id <= 0) {
    http_response_code(401);
    echo json_encode(['error' => true, 'message' => 'Unauthorized']);
    exit;
}

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $token = isset($data['FCMToken']) ? trim($data['FCMToken']) : '';
    if ($token === '') {
        echo json_encode(['error' => true, 'message' => 'FCMToken is required.']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM push_tokens WHERE UserID = ? AND FCMToken = ?");
    if (!$stmt) {
        echo json_encode(['error' => true, 'message' => 'Database error.']);
        exit;
    }
    $stmt->bind_param('is', $user_id, $token);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['error' => false, 'message' => 'Device unregistered from push.']);
    exit;
}

// POST: register or update
$fcm_token = isset($data['FCMToken']) ? trim($data['FCMToken']) : '';
$platform = isset($data['Platform']) ? trim($data['Platform']) : 'android';
$water_reminder = isset($data['WaterReminderEnabled']) ? (int) (bool) $data['WaterReminderEnabled'] : 1;

if ($fcm_token === '') {
    echo json_encode(['error' => true, 'message' => 'FCMToken is required.']);
    exit;
}
if (!in_array($platform, ['android', 'ios', 'web'], true)) {
    $platform = 'android';
}
if (strlen($fcm_token) > 512) {
    echo json_encode(['error' => true, 'message' => 'FCMToken too long.']);
    exit;
}

$now = date('Y-m-d H:i:s');
// Upsert: same user + platform + token -> update UpdatedAt and WaterReminderEnabled
$stmt = $conn->prepare(
    "INSERT INTO push_tokens (UserID, FCMToken, Platform, WaterReminderEnabled, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE UpdatedAt = ?, WaterReminderEnabled = VALUES(WaterReminderEnabled)"
);
if (!$stmt) {
    echo json_encode(['error' => true, 'message' => 'Database error. Ensure push_tokens table exists.']);
    exit;
}
$stmt->bind_param('ississs', $user_id, $fcm_token, $platform, $water_reminder, $now, $now, $now);
$stmt->execute();
$stmt->close();

echo json_encode([
    'error' => false,
    'message' => 'Device registered for push. Water reminder: ' . ($water_reminder ? '7 AM–11:30 PM every 30 min' : 'off') . '.',
]);
