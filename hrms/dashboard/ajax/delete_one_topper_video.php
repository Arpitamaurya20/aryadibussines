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

if (!isset($_POST['id'])) {
    echo json_encode(['error' => true, 'message' => 'Invalid or missing ID']);
    exit;
}

$id = intval($_POST['id']);
$query = "WHERE id = $id";

$was_deleted = $core->delete_identity_filter($conn, "topper_videos", $query);

if ($was_deleted) {
    echo json_encode(['error' => false, 'message' => "Record Deleted"]);
    exit;
} else {
    echo json_encode(['error' => true, 'message' => "Record not deleted"]);
    exit;
}
