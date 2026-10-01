<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/authentication/auth_controller/authentication_controller.php');
require_once('../admin/corporate-tickets/controller/corporate_tickets_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
setTimeZone();
$response = array();
if(isset($data['BranchID']))
{
	$conn = _connectodb();
	$BranchID = $data['BranchID'];
	$TicketID = $data['TicketID'];
    // Get City ID from Branch ID
	$where = "where ID  = $BranchID";
	$BranchMobile = trim((string) (_getTableDetails($conn, 'branch', $where)['BranchMobile'] ?? ''));
	if(strlen(preg_replace('/\D/', '', $BranchMobile)) < 10)
	{
		$response["error"] = true;
		$response["message"] = "Branch mobile number is not set. Please ask the admin to update it.";
		echo json_encode($response);
		exit;
	}
	$otp = generateOTP($BranchMobile);
	InsertTempTicketOTP($conn,$TicketID,$otp);
	$message = "Please use OTP $otp to login into TechXpert App";
	$BranchMobile = "+91".$BranchMobile;
	sendWhatsAppMessage($BranchMobile,$message);
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