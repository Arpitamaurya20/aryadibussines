<?php
require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);

$response = array();

 


function getCorporatTicketByEmpID($conn,$data){
  $BranchID=-1;
  $CorporateID=-1;

  if(isset($data['BranchID'])){
    $BranchID=$data['BranchID'];
  }
  if(isset($data['CorporateID'])){
    $CorporateID=$data['CorporateID'];
  }

  $filter_limit = "";
	if(isset($data['start_counter']))
	{
		$start_counter = $data['start_counter'];
		$no_of_records = $data['no_of_records'];
		$filter_limit = " LIMIT $start_counter,$no_of_records ";
	}

  $response['data'] = array();
  $AssignedTo = $data['EmployeeID'];

  if($BranchID == -1 && $CorporateID==-1)

	{

		$where = " where AssignedTo = $AssignedTo ORDER BY ID DESC $filter_limit";

	}
  else if(isset($data['CorporateID'])){
    
    $where = " where CorporateID = $CorporateID  and AssignedTo=$AssignedTo ORDER BY ID DESC";

  }
  else if(isset($data['BranchID'])){
    
    $where = " where BranchID = $BranchID  and AssignedTo=$AssignedTo ORDER BY ID DESC";

  }

  else {
    $where = " where BranchID = $CorporateID  and BranchID=$BranchID and AssignedTo=$AssignedTo ORDER BY ID DESC";
  }

  $response['data'] = _getTableRecords($conn,'corporate_tickets', $where);

	$response['error'] = false;

	$response['message'] = "Tickets fetched";

	return $response;


}


if(isset($data['EmployeeID']))

{

	$conn = _connectodb();

	$response = getCorporatTicketByEmpID($conn,$data);


}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);


?>