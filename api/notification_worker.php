<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
require_once('../admin/controllers/common_controllers.php');

$dbh  = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$core->setTimeZone();

$notifications = _getTableRecords(
    $conn,
    'push_notifications_log',
    "WHERE status = 'PENDING' LIMIT 10"
);

foreach ($notifications as $n) {

    $payload = json_decode($n['payload'], true);

    sendPushFromPayload($conn, array_merge($payload, [
        "user_id" => $n['user_id'],
        "title"   => $n['title'],
        "body"    => $n['body']
    ]));

    sendWhatsAppFromPayload($conn, $payload);
    sendEmailFromPayload($conn, $payload);

    _UpdateTableRecords_prepare(
        $conn,
        'push_notifications_log',
        ["status" => "SENT"],
        ["id" => $n['id']]
    );
}
