<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();

$authentication = new Authentication($conn);
$authenticated = $authentication->SessionCheck();

// Validate ID
if (!isset($_POST['topper_video_id']) || empty($_POST['topper_video_id'])) {
    echo json_encode(['error' => true, 'message' => 'Missing ID']);
    exit;
}

$id = intval($_POST['topper_video_id']);
$title = $_POST['title'] ?? '';
$video_url = $_POST['video_url'] ?? '';
$order = $_POST['order'] ?? '';
$status = $_POST['status'] ?? 'active';


$data = [
    'title' => $title,
    'video_url' => $video_url,
    '`order`' => $order,
    'status' => $status
];


$where = "id = $id";
$response = $core->_UpdateTableRecords_prepare($conn, "topper_videos", $data, $where);

echo json_encode($response);
exit;
