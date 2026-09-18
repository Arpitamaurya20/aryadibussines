<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once('../admin/ppm-ticket/controller/ppm_controller.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

if(isset($data['BranchAssetID']))

{

	$BranchAssetID = $data['BranchAssetID'];

	// echo $BranchAssetID;

	// die();

	$conn = _connectodb();

	$response = getPPMTicketDetailByBranchAssetsID($conn,$BranchAssetID);

}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>