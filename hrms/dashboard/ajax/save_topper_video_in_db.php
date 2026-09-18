<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
@session_start();
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$authentication = new Authentication($conn);
$authenticated = $authentication->SessionCheck();


if (!isset($_POST['video_url'])) {
    echo json_encode(['error' => true, 'message' => 'Link and Video URL are required.' , "data" => $_POST]);
    exit;
}

$video_url = $_POST['video_url'];
$status = $_POST['status'] ?? '';
$title = $_POST['title'] ?? 'N/A';
$order = $_POST['order'] ?? '';


$title = mysqli_real_escape_string($conn, $title);
$video_url = mysqli_real_escape_string($conn, $video_url);
$order = mysqli_real_escape_string($conn, $order);
$status = mysqli_real_escape_string($conn, $status);


$query = "INSERT INTO topper_videos(`title`, `video_url`, `order`, `status`) VALUES ('$title', '$video_url', '$order','$status')";
$insert_result = $core->_InsertTableRecords($conn, $query);
echo json_encode(['error' => false, 'message' => 'Video uploaded successfully.']);
exit;
