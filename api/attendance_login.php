<?php
@session_start();
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/attendance-list/controller/attendance_controller.php');

$data_raw = file_get_contents('php://input');

setTimeZone();

$data = json_decode($data_raw,true);

$response = array();

if(isset($data['EmployeeID']) && isset($data['RecordDate']) && isset($data['InTime']) && isset($data['Location']))
{
	$conn = _connectodb();
	$response = InsertAttendance($conn,$data);
}else{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);

?>