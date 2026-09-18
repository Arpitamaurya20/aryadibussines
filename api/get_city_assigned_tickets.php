<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/booking/controller/booking_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
function getCityAssignedCorporateTickets($conn,$data)
{

	$filter_limit = "";
	if(isset($data['start_counter']))
	{
		$start_counter = $data['start_counter'];
		$no_of_records = $data['no_of_records'];
		$filter_limit = " LIMIT $start_counter,$no_of_records ";
	}
	$EmployeeID = $data['EmployeeID'];
	// Get City from Employee ID
	$where = " where CorporateLead = $EmployeeID";
	$cities_array =  _getTableRecords($conn,'citydata', $where);
	if(sizeof($cities_array) > 0)
	{
		$city_name_array = array();
		foreach($cities_array as $city)
		{
			array_push($city_name_array,$city['CityName']);
		}
		// Get branches in that city 
		$quoted_city_name_array = array_map(function($value) {
		  return "'" . $value . "'";
		}, $city_name_array);
		$city_in_query = '(' . implode(',', $quoted_city_name_array) . ')';
		$where = " where BranchCity IN $city_in_query";
		$branches_array = _getTableRecords($conn,'branch', $where);
		$branch_id_array = array();
		foreach($branches_array as $branch)
		{
			array_push($branch_id_array,$branch['ID']);
		}
		$branch_id_in_query = '(' . implode(',', $branch_id_array) . ')';
		$response = array();
		$response['data'] = array();
		$where = " where BranchID IN $branch_id_in_query ORDER BY ID DESC";
		//echo $where;
		$response['data'] = _getTableRecords($conn,'corporate_tickets', $where);
		$response['error'] = false;
		$response['message'] = "Tickets fetched";
	}
	else
	{
		$response['data'] = array();
		$response['error'] = false;
		$response['message'] = "Tickets fetched";
	}

	$branch_id_in_query = '(' . implode(',', $branch_id_array) . ')';
	$response = array();
	$response['data'] = array();
	$where = " where BranchID IN $branch_id_in_query ORDER BY ID DESC $filter_limit";
	//echo $where;
	$response['data'] = _getTableRecords($conn,'corporate_tickets', $where);
	$response['error'] = false;
	$response['message'] = "Tickets fetched";

	return $response;
}
if(isset($data['EmployeeID']))
{
	$conn = _connectodb();
	$response = getCityAssignedCorporateTickets($conn,$data);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>