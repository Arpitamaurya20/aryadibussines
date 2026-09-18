<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$conf = new Config($conn);
$data = $_POST;
$data['UpdatedBy'] = $_SESSION['pb_username'];
$site_response = $conf->AddUpdateTatGroup($data);
echo json_encode($site_response);
?>