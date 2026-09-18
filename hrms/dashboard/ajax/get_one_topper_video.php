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

$record = $core->_getTableRecordsassoc($conn, "topper_videos", $query);

echo json_encode(['error' => false, 'record' => $record]);
exit;
