<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');



$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

function getAllCorporateTickets($conn,$data)

{

	$BranchID = -1;

	if(isset($data['BranchID']))

	{

		$BranchID = $data['BranchID'];

	}

	$filter_limit = "";
	if(isset($data['start_counter']))
	{
		$start_counter = $data['start_counter'];
		$no_of_records = $data['no_of_records'];
		$filter_limit = " LIMIT $start_counter,$no_of_records ";
	}

	$CorporateID = $data['CorporateID'];

	$response = array();

	$response['data'] = array();

	if($BranchID == -1)

	{

		$where = " where CorporateID = $CorporateID ORDER BY ID DESC $filter_limit";

	}

	else

	{

		$where = " where CorporateID = $CorporateID and BranchID = $BranchID ORDER BY ID DESC";

	}

	$response['data'] = _getTableRecords($conn,'corporate_tickets', $where);

	$response['error'] = false;

	$response['message'] = "Tickets fetched";

	return $response;

}

if(isset($data['CorporateID']))

{

	$conn = _connectodb();

	$response = getAllCorporateTickets($conn,$data);


}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>