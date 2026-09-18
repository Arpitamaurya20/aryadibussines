<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
setTimeZone();
//require_once('../admin/controller/admin_dashboard_controller.php');
include('../admin/dashboard/controller/dashboard_controller.php');
$response = array();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$conn = _connectodb();
$BranchID = -1;
$CorporateID = -1;
$Status = '';
if(isset($data['BranchID']))
{
	$BranchID = $data['BranchID'];
	$CorporateID = $data['CorporateID'];
	$Status = $data['Status'];
	$TicketStatusArray = GetTicketsByStatusBranchCorporate($conn,$CorporateID,$BranchID,$Status);
	$response['data'] = $TicketStatusArray;
	$response['error'] = false;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>