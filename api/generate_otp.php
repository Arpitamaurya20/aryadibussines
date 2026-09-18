<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/authentication/auth_controller/authentication_controller.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
setTimeZone();
$response = array();
if(isset($data['phonenumber']))
{
	$conn = _connectodb();
	$phonenumber = $data['phonenumber'];
	$otp = generateOTP($phonenumber);
	InsertTempOTP($conn,$phonenumber,$otp);
	$data['phonenumber'] = $phonenumber;
	$data['otp'] = $otp;
	$whatsapp = new Whatsapp();
	$whatsapp->sendOTPWhatsAppMessage($data);
	$sms_obj = new Sms();
	$sms_obj->sendOTPSMS($data);
	$response["error"] = false;
	$response["message"] = "Kindly Enter the OTP sent to your phone number on whatsapp";
}
else 
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>