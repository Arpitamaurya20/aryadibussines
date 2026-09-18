<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

function getCaptureImageByTicketID($conn,$data)

{

	$TicketID = $data['TicketID'];

	$Action = $data['Action'];

	$response = array();

	$response['data'] = array();

	$where = " where TicketID = $TicketID and Action = '$Action'  ORDER BY ID DESC";

	$response['data'] = _getTableRecords($conn,'ticket_media', $where);

	$response['error'] = false;

	$response['message'] = "Tickets Images fetched";

	return $response;

}

if(isset($data['TicketID']) && isset($data['Action']))

{

	$conn = _connectodb();

	$response = getCaptureImageByTicketID($conn,$data);

}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>