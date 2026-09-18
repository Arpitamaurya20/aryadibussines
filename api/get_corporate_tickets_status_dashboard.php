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
$CorporateID = -1;
$BranchID = -1;
if(isset($data['BranchID'])|| isset($data['CorporateID']))
{
	if (isset($data['BranchID'])) {
		$BranchID = $data['BranchID'];
}

// Check if CorporateID is set before accessing it
if (isset($data['CorporateID'])) {
		$CorporateID = $data['CorporateID'];
}


	$sfs=array("Raised"=>"Raised","Work In Progress"=>"WIP","Quote Sent Approval Pending"=>"QSAP","Hold by Customer"=>"HC","Hold by Techxpert"=>"HT","Closed"=>"Closed","Visit Done Quote Pending"=>"VDQP","Cancel"=>"Cancel","Assigned"=>"Assigned","Submitted for Closure"=>"SC","Quote Rejected by Client"=>"QRC","Rejected"=>"Rejected");
	$TicketStatusArray = getTicketStatus($conn,$CorporateID,$BranchID);
	$response['data'] = $TicketStatusArray;
	$response['error'] = false;
	$response['sfs'] = $sfs;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>