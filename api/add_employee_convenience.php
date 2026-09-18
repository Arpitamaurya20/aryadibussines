<?php

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/authentication/auth_controller/authentication_controller.php');
require_once('../admin/employees-convenience/controller/convenience_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
setTimeZone();
function InsertEmployeeConvenience($conn,$data)
{
	$EmployeeID = $data['EmployeeID'];
	$ConvenienceFrom = $data['ConvenienceFrom'];
	$ConvenienceTo = $data['ConvenienceTo'];
	$ConvenienceAmount = $data['ConvenienceAmount'];
	$ConvenienceDate = $data['ConvenienceDate'];
	$Reference = "";
	if(isset($data['Reference']))
		$Reference = $data['Reference'];
	$CreatedBy = $data['CreatedBy'];
	$Status = $data['Status'];
	$CreatedDate = date("Y-m-d");
   	$CreatedTime = date("H:i:s");
	$response = array();
	$response['data'] = array();
	$company_query = "INSERT INTO employee_convenience (EmployeeID,ConvenienceFrom,ConvenienceTo,ConvenienceAmount,ConvenienceDate,Reference,Status,ApproverRemarks,CreatedBy,CreatedDate,CreatedTime) VALUES('$EmployeeID','$ConvenienceFrom','$ConvenienceTo','$ConvenienceAmount','$ConvenienceDate','$Reference','$Status','','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $company_query);
	$response['error'] = false;
	$response['message'] = "Employee Convenience Added";
	return $response;
}
if(isset($data['EmployeeID']) && isset($data['ConvenienceFrom']) && isset($data['ConvenienceTo']) && isset($data['ConvenienceAmount']) && isset($data['ConvenienceDate']) && isset($data['CreatedBy']))
{
	$conn = _connectodb();
	// Get Employee Role
	$EmployeeID = $data['EmployeeID'];
	$roles = getUserRole($conn,$EmployeeID,'None');
	$data['Status'] = 1;
	foreach($roles['EmployeeRoles'] as $Employee_Role)
	{
		if(strpos($Employee_Role,'Lead')!== false)
		{
			$data['Status'] = 2;
		}
		if(strpos($Employee_Role,'Accounts')!== false)
		{
			$data['Status'] = 3;
		}
	}
	$response = InsertEmployeeConvenience($conn,$data);
	if (empty($response['error']) && !empty($response['last_insert_id'])) {
		$convenience_id = (int) $response['last_insert_id'];
		if (convenienceStatusMatchesPendingSupervisor((string) ($data['Status'] ?? '1'))) {
			convenienceNotifySupervisorPending($conn, $convenience_id);
		}
	}
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}

echo json_encode($response);
?>