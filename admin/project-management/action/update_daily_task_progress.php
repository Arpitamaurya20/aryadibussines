<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$data = $_POST;
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$core->setTimeZone();
$data['CreatedBy'] = $_SESSION['pb_username'];
$project_obj = new Projects($conn);
$response = $project_obj->SaveTaskProgress($data);
echo json_encode($response);
?>