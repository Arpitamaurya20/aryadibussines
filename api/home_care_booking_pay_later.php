<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/booking/controller/booking_controller.php');
require_once('../admin/authentication/auth_controller/authentication_controller.php');
$data_raw = file_get_contents('php://input');
setTimeZone();
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['BookingID']))
{
	$conn = _connectodb();
	$data['UpdatedDate'] = date('Y-m-d');
	$data['UpdatedTime'] = date('H:i:s');
	$response = BookingPayLater($conn,$data);
	$response["error"] = false;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>