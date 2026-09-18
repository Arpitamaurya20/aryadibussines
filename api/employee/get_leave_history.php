<?php
require_once('../common_api_header.php');
require_once('../../admin/includes/autoloader.inc.php');
$response = array();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$employee_obj = new Employee($conn);
if(isset($data['EmployeeID']))
{
	$leaves_history = $employee_obj->getEmployeeLeaves($data);
	$response['data'] = $leaves_history;
	$response['error'] = false;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>