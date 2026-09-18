<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $config_obj = new Config($conn);
    $_POST['CreatedBy'] = $_SESSION['pb_username'];
    $response = $config_obj->SaveCircle($_POST);
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>