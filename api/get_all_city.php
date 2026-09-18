<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/city/controller/city_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(1)
{
	$conn = _connectodb();
	$response = getAllCity($conn);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing Services";
}
echo $response;
?>