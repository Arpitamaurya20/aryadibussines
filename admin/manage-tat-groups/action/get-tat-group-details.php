<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$conf = new Config($conn);
$TATGroupID = $_POST['TATGroupID'];
$response = $conf->GetTATGroupDetails($TATGroupID);
echo json_encode($response);
?>