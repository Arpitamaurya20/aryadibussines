<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/corporate-tickets/controller/corporate_tickets_controller.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['TicketID']))
{
	$conn = _connectodb();
	$Type = "CT";
	if(isset($data['Type']))
	{
		$Type = $data['Type'];

	}
	if($Type == "ppm_tickets")
	{
		$ppm_ticket_obj = new Ppmtickets($conn);
		$response = $ppm_ticket_obj->getPPMGeneralServiceReportDetails($data);
	}
	else
	{
		$response = getGeneralServiceReportDetails($conn,$data);
	}
	$response['error'] = false;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>