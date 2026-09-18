<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/customer/controller/customer_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
setTimeZone();
$response = array();
if(isset($data['phonenumber']))
{
	$conn = _connectodb();
	$phonenumber = $data['phonenumber'];
	$response = getCustomerDetails($conn,$phonenumber);
}
else 
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>