<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
function getAssignedCompletedPPMTickets($conn,$data)
{
	$EmployeeID = $data['EmployeeID'];
	$response = array();
	$response['data'] = array();
	$where = " where AssignedTo = $EmployeeID and (Status='Completed' OR Status = 'Closed')  ORDER BY ID DESC";
	$response['data'] = _getTableRecords($conn,'ppm_tickets', $where);
	$response['error'] = false;
	$response['message'] = "Tickets fetched";
	return $response;
}
if(isset($data['EmployeeID']))
{
	$conn = _connectodb();
	$response = getAssignedCompletedPPMTickets($conn,$data);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>