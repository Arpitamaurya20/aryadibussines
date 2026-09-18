<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/approval-pending/controller/approval_pending_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['CorporateID']))
{
	$CorporateID = $data['CorporateID'];
	// echo $BranchAssetID;
	// die();
	$conn = _connectodb();
	$response = getAllPeindingTickets($conn,$CorporateID);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>