<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(1)
{
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	
	$employee = new Employee($conn);
	$leaveTypes = $employee->getLeaveTypes();

	echo json_encode($leaveTypes);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
?>