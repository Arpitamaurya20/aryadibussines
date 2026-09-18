<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once('../admin/booking/controller/booking_controller.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();



function getBookedHomeTickets($conn,$data)

{

	$phonenumber = $data['phonenumber'];

	$response = array();

	$response['data'] = array();

	$where = " where CreatedBy = '$phonenumber' and Status = 'Closed' ORDER BY ID DESC";

	$response['data'] = _getTableRecords($conn,'confirm_booking', $where);

	$response['error'] = false;

	$response['message'] = "Tickets fetched";

	return $response;

}



if(isset($data['phonenumber']))

{

	$conn = _connectodb();

	$response = getBookedHomeTickets($conn,$data);

}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>