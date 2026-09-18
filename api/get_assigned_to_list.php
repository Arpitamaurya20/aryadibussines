<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/employees/controller/employee_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
$conn = _connectodb();
if(1)
{
	$EmpList = "-1";
	if(isset($data['TicketID']))
	{
		// Get Branch ID from Ticket ID
		$TicketID = $data['TicketID'];
		$where = "where ID = '$TicketID'";
		$BranchID = _getTableDetails($conn, 'corporate_tickets', $where)['BranchID'];
		
		// Get City ID from Branch ID
		$where = "where ID  = $BranchID";
		$BranchCity = _getTableDetails($conn, 'branch', $where)['BranchCity'];
		// Get State from City ID
		$where = "where CityName  = '$BranchCity'";
		$StateID = _getTableDetails($conn, 'citydata', $where)['StateID'];
		
		// Get all the cities of that State from city table
		$where = "where StateID  = '$StateID'";
		$CitiesArray_complete = _getTableRecords($conn, 'citydata', $where);
		$CitiesArray = array();
		foreach($CitiesArray_complete as $City){
			$CityName = $City['CityName'];
			array_push($CitiesArray,$CityName);
		}
		// Get All the employees from that list
		$response_data = getAssignedListforCities($conn,$CitiesArray);
	}
	else
	{
	
		$response_data = getAssignedList($conn);
	}
	if(sizeof($response_data) > 0)
	{
		$response['data'] = $response_data;
		$response['error'] = false;
		$response['message'] = "Data Fetched";
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "No Records Found";
	}
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>