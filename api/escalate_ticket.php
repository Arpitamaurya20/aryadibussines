<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/corporate-tickets/controller/corporate_tickets_controller.php');
setTimeZone();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['TicketID']))
{
	$conn = _connectodb();
	$response = escalateTicket($conn,$data);
	if($response['error'] == false)
	{
		$response['message'] = "Ticket Escalated. Our team will take urgent care of it!";
	}
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>