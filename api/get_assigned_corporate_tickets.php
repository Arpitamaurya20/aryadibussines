<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once('../admin/booking/controller/booking_controller.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

function getAssignedCorporateTickets($conn,$data)

{
  $Status= "";

	$EmployeeID = $data['EmployeeID'];

	if(isset($data['Status'])){
		$Status=trim($data['Status']);
	}	

	$response = array();

	$response['data'] = array();

	$filter_limit = "";
	if(isset($data['start_counter']))
	{
		$start_counter = $data['start_counter'];
		$no_of_records = $data['no_of_records'];
		$filter_limit = " LIMIT $start_counter,$no_of_records ";
	}

	$filter_search = "";
	if(isset($data['search_term']))
	{
		$search_term = $data['search_term'];
		$filter_search = " AND TicketID LIKE '%$search_term%' ";
	}

	if($Status!=""){
		
		$where = " where AssignedTo = $EmployeeID  and (Status='$Status')".$filter_search." ORDER BY ID DESC $filter_limit";
	}
	else{
	$where = " where AssignedTo = $EmployeeID  and (Status != 'Closed') ".$filter_search."ORDER BY ID DESC $filter_limit";
	}

	$response['data'] = _getTableRecords($conn,'corporate_tickets', $where);

	$response['error'] = false;

	$response['message'] = "Tickets fetched";

	return $response;

}

if(isset($data['EmployeeID']))

{

	$conn = _connectodb();

	$response = getAssignedCorporateTickets($conn,$data);

}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>