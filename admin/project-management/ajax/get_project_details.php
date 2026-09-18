<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$data = $_POST;
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$project_obj = new Projects($conn);
$response = $project_obj->GetProjectDetails($data['ProjectID']);
echo json_encode($response);
?>