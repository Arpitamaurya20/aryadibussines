<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
	if($Type == "CT")
	{
		$response = getCorporateTicketDetail($conn,$data);
		if($response['Type']=="AMC")
		{
			 $spareStatus=getSparePartStatus($conn,$TicketID);
			 $response['SparePartStatus'] = $spareStatus; 
		}
	}
	else
	{
		$ppm_ticket_obj = new Ppmtickets($conn);
		$response = $ppm_ticket_obj->getPPMTicketDetails($data);
	}
	
	if(isset($response['data']['AssignedTo']))
	{
		if($response['data']['AssignedTo'] != -1)
		{
			$AssignedTo = $response['data']['AssignedTo'];
			$where = " where ID = $AssignedTo";
			$respone_employee = _getTableDetails($conn,'employees', $where);
			if(isset($respone_employee['Name']))
				$response['data']['EmployeeName'] = $respone_employee['Name'];
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