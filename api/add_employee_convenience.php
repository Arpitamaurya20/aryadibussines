<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
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
	$EmployeeID = (int)$data['EmployeeID'];
	$ConvenienceFrom = mysqli_real_escape_string($conn, trim((string)$data['ConvenienceFrom']));
	$ConvenienceTo = mysqli_real_escape_string($conn, trim((string)$data['ConvenienceTo']));
	$ConvenienceAmount = mysqli_real_escape_string($conn, trim((string)$data['ConvenienceAmount']));
	$ConvenienceDate = mysqli_real_escape_string($conn, trim((string)$data['ConvenienceDate']));
	$Reference = "";
	if(isset($data['Reference']))
		$Reference = mysqli_real_escape_string($conn, trim((string)$data['Reference']));
	$CreatedBy = mysqli_real_escape_string($conn, trim((string)$data['CreatedBy']));
	$Status = (int)$data['Status'];
	$CreatedDate = date("Y-m-d");
   	$CreatedTime = date("H:i:s");
	$company_query = "INSERT INTO employee_convenience (EmployeeID,ConvenienceFrom,ConvenienceTo,ConvenienceAmount,ConvenienceDate,Reference,Status,ApproverRemarks,CreatedBy,CreatedDate,CreatedTime) VALUES('$EmployeeID','$ConvenienceFrom','$ConvenienceTo','$ConvenienceAmount','$ConvenienceDate','$Reference','$Status','','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $company_query);
	if(!empty($response['error']))
	{
		error_log('add_employee_convenience: '.$response['message']);
		return array('error' => true, 'message' => 'Unable to add convenience charge.');
	}
	$response['message'] = "Employee Convenience Added";
	return $response;
}
if(isset($data['EmployeeID']) && isset($data['ConvenienceFrom']) && isset($data['ConvenienceTo']) && isset($data['ConvenienceAmount']) && isset($data['ConvenienceDate']) && isset($data['CreatedBy']))
{
	$EmployeeID = (int)$data['EmployeeID'];
	$amount = trim((string)$data['ConvenienceAmount']);
	$date = trim((string)$data['ConvenienceDate']);
	if($EmployeeID <= 0 || trim((string)$data['ConvenienceFrom']) === '' || trim((string)$data['ConvenienceTo']) === '' || trim((string)$data['CreatedBy']) === '')
	{
		$response["error"] = true;
		$response["message"] = "Missing User Fields";
	}
	else if(!is_numeric($amount) || (float)$amount <= 0)
	{
		$response["error"] = true;
		$response["message"] = "Enter a valid amount.";
	}
	else if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
	{
		$response["error"] = true;
		$response["message"] = "Convenience date must be YYYY-MM-DD.";
	}
	else
	{
		$conn = _connectodb();
		// Get Employee Role
		$roles = getUserRole($conn,$EmployeeID,'None');
		$data['Status'] = 1;
		$employeeRoles = (isset($roles['EmployeeRoles']) && is_array($roles['EmployeeRoles'])) ? $roles['EmployeeRoles'] : array();
		foreach($employeeRoles as $Employee_Role)
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
				try {
					convenienceNotifySupervisorPending($conn, $convenience_id);
				} catch (Throwable $e) {
					error_log('add_employee_convenience notify: '.$e->getMessage());
				}
			}
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
