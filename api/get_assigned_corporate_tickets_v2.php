<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once('../admin/booking/controller/booking_controller.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

function getAssignedCorporateTickets($conn,$data)

{
  $Status= "";
  $BranchID="";

		$EmployeeID = $data['EmployeeID'];

		if(isset($data['Status'])){
			$Status=trim($data['Status']);
		}	
	  
	  if(isset($data['BranchID'])){
			$BranchID=trim($data['BranchID']);
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

     $where = " WHERE AssignedTo = $EmployeeID ";
    if ($Status != "") {
        $where .= " AND Status = '$Status' ";
    } else {
        $where .= " AND Status != 'Closed' ";
    }
    if ($BranchID != "") {
        $where .= " AND BranchID = '$BranchID' ";
    }

    $where .= $filter_search;
	 $where .= " ORDER BY ct.ID DESC $filter_limit";
	 $sql = "SELECT ct.*, B.BranchSite
     FROM corporate_tickets ct
     INNER JOIN branch B ON ct.BranchID = B.ID
        $where";
    $response['data']=_getSQLRecords($conn,$sql);
	// $response['data'] = _getTableRecords($conn,'corporate_tickets', $where);

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