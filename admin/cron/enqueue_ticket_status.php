<?php
// cron/create_queue.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once('../includes/autoloader.inc.php');
include("../controllers/common_controllers.php");
setTimeZone();

$db = new Dbh();
$conn = $db->_connectodb();

echo "== Enqueue Ticket Status Settings ==\n";

// fetch active settings
$sql = "SELECT ID FROM ticket_status_setting WHERE IsActive = 1 ORDER BY ID ASC";
$res = $conn->query($sql);
if (!$res) {
    echo "DB error: " . $conn->error . "\n";
    exit;
}

$count = 0;
while ($r = $res->fetch_assoc()) {
    $settingID = intval($r['ID']);

    // check today duplicate
    $chkSql = "SELECT id FROM ticket_status_queue WHERE setting_id = $settingID AND DATE(created_at) = CURDATE() LIMIT 1";
    $chk = $conn->query($chkSql);

    if ($chk && $chk->num_rows > 0) {
        echo "Skipped (already queued today): Setting ID $settingID\n";
        continue;
    }

    // insert queue (use simple escaping)
    $insSql = "INSERT INTO ticket_status_queue (setting_id, status, created_at) VALUES ($settingID, 'pending', NOW())";
    if ($conn->query($insSql)) {
        echo "Enqueued: Setting ID $settingID\n";
        $count++;
    } else {
        echo "Failed to enqueue Setting ID $settingID — " . $conn->error . "\n";
    }
}

echo "Done. Total enqueued: $count\n";
?>
