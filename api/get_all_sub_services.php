<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
function getAllSubservices($conn,$ServiceID)
{
	$where = " where service_id = $ServiceID";
	return _getTableRecords($conn,'subservice',$where);
}
if(isset($data['service_id']))
{
	$ServiceID = $data['service_id'];
	$conn = _connectodb();
	$subservices = getAllSubservices($conn,$ServiceID);
	$path = "https://techxpertindia.in/admin/media/Services/";
	$response['data'] = array();
	foreach($subservices as $service)
	{
		$service['sub_service_image'] = $path.$service['sub_service_image'];
		array_push($response['data'],$service);
	}
	$response["error"] = false;
	$response["message"] = "Sub Services Fetched";
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>