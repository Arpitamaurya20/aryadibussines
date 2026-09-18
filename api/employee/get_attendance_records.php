<?php
require_once('../common_api_header.php');
require_once('../../admin/includes/autoloader.inc.php');
$response = array();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$employee_obj = new Employee($conn);
if(isset($data['EmployeeID']) && isset($data['s_year']) && isset($data['s_month']))
{
	$attendance_records = $employee_obj->getEmployeeAttendanceRecords($data);
	foreach($attendance_records as $i=>$record)
	{
		extract($record);
		$duration = $employee_obj->calculatetimeDifference($InTime,$OutTime);
		$attendance_records[$i]['duration'] = $duration;
	}
	$response['data'] = $attendance_records;
	$response['error'] = false;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>