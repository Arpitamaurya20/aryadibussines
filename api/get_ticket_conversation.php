<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once("../admin/corporate-tickets/controller/corporate_tickets_controller.php");

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

if(isset($data['TicketID']))

{
	$TicketID = $data['TicketID'];
	$conn = _connectodb();
	$response = getTicketConversation($conn,$TicketID);
	$response["error"] = true;
	$response["message"] = "Data Fetched";

}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}

echo json_encode($response);

?>