<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/Services/controller/service_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(1)
{
	$conn = _connectodb();
	$where = " where status = 1";
	$services = _getTableRecords($conn, 'services', $where);
	$path = "https://techxpertindia.in/admin/media/Services/";
	$response['data'] = array();
	foreach($services as $service)
	{
		$service['service_icon'] = $path.$service['service_icon'];
		array_push($response['data'],$service);
	}
	
	$response['error'] = false;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing Services";
}
echo json_encode($response);
?>