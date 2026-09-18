<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/corporate-tickets/controller/corporate_tickets_controller.php');
setTimeZone();
$data_raw = file_get_contents('php://input');
//echo $data_raw;
$data = json_decode($data_raw,true);
//echo $data;
$response = array();
if(isset($data['TicketID']) && isset($data['TicketStatus']) && isset($data['AssignedTo']) && isset($data['UpdatedBy']))
{
	$TicketID = $data['TicketID'];
	$data['UpdatedDate'] = date('Y-m-d');
	$data['UpdatedTime'] = date('H:i:s');
	$conn = _connectodb();
	$notification_array = array();
	$response = ManageTicketAssignmentStatus($conn,$data,$notification_array);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>