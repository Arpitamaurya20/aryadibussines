<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/booking/controller/booking_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(1)
{
	$conn = _connectodb();
	$response = getBookingStatus($conn);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>