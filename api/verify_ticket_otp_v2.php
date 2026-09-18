<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/authentication/auth_controller/authentication_controller.php');
require_once('../admin/corporate-tickets/controller/corporate_tickets_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
setTimeZone();
$response = array();
if(isset($data['TicketID']) && isset($data['TicketOTP']))
{
	$TicketID = $data['TicketID'];
	$otp = $data['TicketOTP'];
	$conn = _connectodb();
	$response = checkTicketOTP($conn,$TicketID,$otp);
	if($response['error'] == false)
	{
		// Change status to Work in Progress
		$where = " where ID = $TicketID";
		$ticket_data = _getTableDetails($conn,'corporate_tickets', $where);
		$ticket_data['TicketStatus'] = "Work In Progress";
		$ticket_data['TicketID'] = $TicketID;
		ManageTicketAssignmentStatus($conn,$ticket_data);
		$response["message"] = "OTP has been verified";
	}
}
else 
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>