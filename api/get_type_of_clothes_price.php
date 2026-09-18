<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
function getTypeofClothesPrice($conn,$type_of_clothes_id)
{
	$where = " where ID = $type_of_clothes_id AND IsActive = 1";
	$response = _getTableDetails($conn,'laundry_sub_service', $where);
	return $response;
}

if(isset($data['type_of_clothes_id']))
{
	
	$type_of_clothes_id = $data['type_of_clothes_id'];

	$conn = _connectodb();
	$Get_price_details = getTypeofClothesPrice($conn,$type_of_clothes_id);

	$response['data'] = $Get_price_details;
	
	$response["error"] = false;
	$response["message"] = "Price Details Fetched";
}
else
{

	$response["error"] = true;
	$response["message"] = "Missing User Fields";

}

echo json_encode($response);



?>