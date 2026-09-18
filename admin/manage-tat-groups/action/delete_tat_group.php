<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$conf = new Config($conn);
$TATGroupID = $_POST['deleteid'];
$response = $conf->DeleteTATGroup($TATGroupID);
echo json_encode($response);
?>
