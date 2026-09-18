<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/booking/controller/booking_controller.php');
require_once('../admin/authentication/auth_controller/authentication_controller.php');
$data_raw = file_get_contents('php://input');
setTimeZone();
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['BookingID']) && isset($data['BookingStatus']) && isset($data['AssignedTo']) && isset($data['UpdatedBy']))
{
	$BookingID = $data['BookingID'];
	$data['UpdatedDate'] = date('Y-m-d');
	$data['UpdatedTime'] = date('H:i:s');
	$conn = _connectodb();
	if($data['BookingStatus'] == "Assigned")
	{
		$otp = generateOTP();
		$data['BookingStartOTP'] = $otp;
	}
	if($data['BookingStatus'] == "Generate OTP for Closure")
	{
		$otp = generateOTP();
		$data['BookingCloseOTP'] = $otp;
	}
	$response = _api_changeBookingStatus($conn,$data);

}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>