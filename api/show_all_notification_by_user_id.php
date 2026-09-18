<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
require_once('../admin/controllers/common_controllers.php');

$conn = _connectodb();
setTimeZone();

/* =========================
   1️⃣ POST ONLY
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "error" => true,
        "message" => "Only POST method allowed"
    ]);
    exit;
}

/* =========================
   2️⃣ READ RAW INPUT
========================= */
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

/* =========================
   3️⃣ SUPPORT ALL FORMATS
========================= */
if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
    // JSON BODY
    $userId = $data['user_id'] ?? null;
} else {
    // form-data OR x-www-form-urlencoded OR raw text
    if (empty($_POST)) {
        parse_str($rawInput, $_POST);
    }
    $userId = $_POST['user_id'] ?? null;
}

/* =========================
   4️⃣ VALIDATION
========================= */
if (empty($userId)) {
    echo json_encode([
        "error" => true,
        "message" => "user_id required"
    ]);
    exit;
}

$userId = cleantext($userId);
$last24 = date("Y-m-d H:i:s", strtotime("-24 hours"));

require_once('../admin/controllers/portal_notification_controller.php');

$employeeId = 0;
$portalUserId = 0;
$userIdInt = (int) $userId;

$employee = _getTableDetails($conn, 'employees', " WHERE ID = $userIdInt AND IsActive = 1");
if (is_array($employee) && !empty($employee['ID'])) {
    $employeeId = $userIdInt;
    $userRow = _getTableDetails($conn, 'users', " WHERE EmployeeID = $employeeId");
    if (is_array($userRow) && !empty($userRow['UserID'])) {
        $portalUserId = (int) $userRow['UserID'];
    }
} else {
    $portalUserId = $userIdInt;
    $userRow = _getTableDetails($conn, 'users', " WHERE UserID = $userIdInt");
    if (is_array($userRow) && !empty($userRow['EmployeeID']) && (int) $userRow['EmployeeID'] > 0) {
        $employeeId = (int) $userRow['EmployeeID'];
    }
}

$appLogUserId = $employeeId > 0 ? $employeeId : $userIdInt;
$appLogUserIdEsc = mysqli_real_escape_string($conn, (string) $appLogUserId);
$legacyUserIdEsc = mysqli_real_escape_string($conn, (string) $userId);

/* =========================
   5️⃣ FETCH NOTIFICATIONS
========================= */
$sql = "
    SELECT 
        id,
        title,
        body,
        payload,
        userstatus,
        created_at
    FROM push_notifications_log
    WHERE (user_id = '$appLogUserIdEsc' OR user_id = '$legacyUserIdEsc')
      AND userstatus = 'NotSeen'
      AND created_at >= '$last24'
    ORDER BY id DESC
";

$result = mysqli_query($conn, $sql);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['payload'] = $row['payload']
        ? json_decode($row['payload'], true)
        : null;

    $notifications[] = $row;
}

$portalItems = pnc_getNotificationsForUser($conn, $portalUserId, $employeeId, 50);
foreach ($portalItems as $portalRow) {
    if (($portalRow['userstatus'] ?? 'NotSeen') !== 'NotSeen') {
        continue;
    }
    $createdAt = $portalRow['created_at'] ?? '';
    if ($createdAt !== '' && $createdAt < $last24) {
        continue;
    }
    $notifications[] = array(
        'id' => 'portal_' . ($portalRow['id'] ?? 0),
        'title' => $portalRow['title'] ?? '',
        'body' => $portalRow['body'] ?? '',
        'payload' => $portalRow['payload'] ?? array(),
        'userstatus' => $portalRow['userstatus'] ?? 'NotSeen',
        'created_at' => $createdAt,
        'source' => 'portal',
        'portal_notification_id' => (int) ($portalRow['id'] ?? 0),
    );
}

usort($notifications, function ($a, $b) {
    return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
});

/* =========================
   6️⃣ COUNT UNSEEN
========================= */
$countSql = "
    SELECT COUNT(*) AS total
    FROM push_notifications_log
    WHERE (user_id = '$appLogUserIdEsc' OR user_id = '$legacyUserIdEsc')
      AND userstatus = 'NotSeen'
      AND created_at >= '$last24'
";

$countRes = mysqli_fetch_assoc(mysqli_query($conn, $countSql));
$portalUnread = $portalUserId > 0 ? pnc_getUnreadCountForUser($conn, $portalUserId, $employeeId) : 0;

/* =========================
   7️⃣ RESPONSE
========================= */
echo json_encode([
    "error" => false,
    "count" => (int)($countRes['total'] ?? 0) + (int) $portalUnread,
    "notifications" => $notifications
]);
