<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
function getCorporateBranches($conn,$CorporateID)
{
	$where = " where CompanyID = $CorporateID";
	return _getTableRecords($conn,'branch',$where);
}
if(isset($data['CorporateID']))
{
	$CorporateID = $data['CorporateID'];
	$conn = _connectodb();
	$response = getCorporateBranches($conn,$CorporateID);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>