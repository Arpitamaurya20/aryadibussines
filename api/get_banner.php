<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

function getBanners($conn)
{

	
	$where = " ";

	return _getTableRecords($conn,'banners',$where);

}

if(1)

{

	

	$conn = _connectodb();

	$response = getBanners($conn);

}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>