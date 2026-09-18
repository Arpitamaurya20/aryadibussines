<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/employees/controller/employee_controller.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['EmployeeID']))
{
	$conn = _connectodb();
	$employee = new Employee($conn);
	$employee_array = $employee->setEmployeeArray("All");
	$response["data"] = getEmployeeData($conn,$data['EmployeeID']);
	$supervisor_name = "";
	if($response['data']['Supervisor'] != -1 && $response['data']['Supervisor'] != "")
	{
		$SupervisorId = $response['data']['Supervisor'];
		if(isset($employee_array[$SupervisorId]))
		{
			$supervisor_name = $employee_array[$SupervisorId]['Name'];
		}
		$response['data']['SupervisorName'] = $supervisor_name;
	}
	$response["error"] = false;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>