<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$EmployeeID = $_POST['EmployeeID'];
$Role = $_POST['Role'];
$core = new Core();
$query = " where EmployeeID = $EmployeeID and Role = '$Role'";
$response = $core->delete_identity_filter($conn,"user_roles",$query);
$response_array = array();
if($response)
{
	$response_array['message'] = "Role Modified";
}
else
{
	$response_array['message'] = "Technical Problem occured";
}
echo json_encode($response);
?>