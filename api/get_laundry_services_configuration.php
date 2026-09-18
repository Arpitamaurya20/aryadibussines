<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
function getAllLuandryServices($conn,$Sub_Service_ID)
{
	$where = " where SubServiceID = $Sub_Service_ID AND IsActive = 1";
	$response = _getTableRecords($conn,'laundry_sub_service', $where);
	return $response;
}

if(isset($data['sub_service_id']))
{
	
	$Sub_Service_ID = $data['sub_service_id'];

	$conn = _connectodb();
	$laundry_service_conf = getAllLuandryServices($conn,$Sub_Service_ID);

	$response['data'] = array();
	foreach($laundry_service_conf as $LaundryService)
	{
		array_push($response['data'],$LaundryService);
	}
	$response["error"] = false;
	$response["message"] = "Laundry Service Configuration Fetched";
}
else
{

	$response["error"] = true;
	$response["message"] = "Missing User Fields";

}

echo json_encode($response);



?>