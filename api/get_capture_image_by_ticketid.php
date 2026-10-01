<?php

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

function getCaptureImageByTicketID($conn,$data)

{

	$TicketID = (int)$data['TicketID'];

	$Action = mysqli_real_escape_string($conn, (string)$data['Action']);

	$response = array();

	$response['data'] = array();

	$where = " where TicketID = $TicketID and Action = '$Action'  ORDER BY ID DESC";

	$response['data'] = _getTableRecords($conn,'ticket_media', $where);

	$response['error'] = false;

	$response['message'] = "Tickets Images fetched";

	return $response;

}

function getPPMCaptureImagesByTicketID($conn,$data)
{
	$TicketID = (int)$data['TicketID'];
	$where = " where TicketID = '$TicketID'";
	if(isset($data['Action']) && $data['Action'] !== '')
	{
		$Action = mysqli_real_escape_string($conn, (string)$data['Action']);
		$where .= " and Action = '$Action'";
	}
	$where .= " ORDER BY ID ASC";

	$host = $_SERVER['HTTP_HOST'] ?? '';
	$isLocal = stripos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || preg_match('/^(192\.168|10\.)\./', $host);
	$base_url = $isLocal ? 'http://' . $host . '/Projects/aryadibussines/admin/media/ppm_ticket_media/' : 'https://techxpertindia.in/admin/media/ppm_ticket_media/';

	$rows = _getTableRecords($conn,'ppm_ticket_media', $where);
	foreach ($rows as $key => $row)
	{
		$rows[$key]['ImageURL'] = $base_url . $row['Image'];
	}

	return array(
		'data' => $rows,
		'error' => false,
		'message' => "Tickets Images fetched",
	);
}

if(isset($data['TicketID']) && isset($data['Type']) && $data['Type'] == 'ppm_tickets')
{
	$conn = _connectodb();
	$response = getPPMCaptureImagesByTicketID($conn,$data);
}
else if(isset($data['TicketID']) && isset($data['Action']))

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
