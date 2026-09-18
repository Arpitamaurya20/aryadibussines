<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/booking/controller/booking_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
setTimeZone();
$response = array();
if(isset($data['BookingID']) && isset($data['BookingCloseOTP']) && isset($data['UpdatedBy']))
{
	$BookingID = $data['BookingID'];
	$otp = $data['BookingCloseOTP'];
	$conn = _connectodb();
	$response = checkBookingOTPforClosure($conn,$BookingID,$otp);
	if($response['error'] == false)
	{
		// Change status to Work in Progress
		$where = " where ID = $BookingID";
		$booking_data = _getTableDetails($conn,'confirm_booking', $where);
		$booking_date['BookingStatus'] = "Closed";
		$booking_date['BookingID'] = $BookingID;
		$booking_date['UpdatedDate'] = date('Y-m-d');
		$booking_date['UpdatedTime'] = date('H:i:s');
		$booking_date['AssignedTo'] = $booking_data['AssignedTo'];
		$booking_date['UpdatedBy'] = $data['UpdatedBy'];
		$response = _api_changeBookingStatus($conn,$booking_date);
		if($response['error'] == false)
		{
			$response['message'] = "OTP Verified, Booking is closed";
		}
	}
}
else 
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>