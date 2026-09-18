<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
function getAllPPMTickets($conn,$data)
{
	$BranchID = -1;
	if(isset($data['BranchID']))
	{
		$BranchID = $data['BranchID'];
	}
	$BranchAssetID = -1;
	if(isset($data['BranchAssetID']))
	{
		$BranchAssetID = $data['BranchAssetID'];
	}
	$CorporateID = $data['CorporateID'];
	$response = array();
	$response['data'] = array();
	if($BranchID == -1)
	{
		$where = " where CorporateID = $CorporateID ORDER BY ID DESC";
	}
	else
	{
		$where = " where CorporateID = $CorporateID and BranchID = $BranchID and BranchAssetID = $BranchAssetID ORDER BY ID DESC";
	}
	$response['data'] = _getTableRecords($conn,'ppm_tickets', $where);
	$response['error'] = false;
	$response['message'] = "Tickets fetched";
	return $response;
}

if(isset($data['CorporateID']))
{
	$conn = _connectodb();
	$response = getAllPPMTickets($conn,$data);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>