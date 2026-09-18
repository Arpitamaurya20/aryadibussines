<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['EmployeeID']) && isset($data['FromDate']) && isset($data['ToDate']) && isset($data['TypeOfLeave']) && isset($data['CreatedBy']) && isset($data['Duration']))
{
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	$core->setTimeZone();
	
	$data['CreatedDate'] = date("Y-m-d");
	$data['CreatedTime'] = date("H:i:s");
	$employee = new Employee($conn);
	$response = $employee->addLeave($data);
	if($response['error'] == true)
	{
		$response["message"] = "Technical Problem, please try again later";
	}
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>