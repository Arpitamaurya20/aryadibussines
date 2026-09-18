<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
require_once('../../includes/autoloader.inc.php');
$data = $_POST;

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$core->setTimeZone();
$data['CreatedBy'] = $_SESSION['pb_username'];
$project_obj = new Projects($conn);
$response = $project_obj->SaveTaskProgressNew($data);
echo json_encode($response);
?>