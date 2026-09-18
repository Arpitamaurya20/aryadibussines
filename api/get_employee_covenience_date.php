<?php

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/booking/controller/booking_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
function getEmployeeConvenienceDate($conn,$data)

{
	$EmployeeID = $data['EmployeeID'];
	$ConvenienceDate = $data['ConvenienceDate'];
	$response = array();
	$response['data'] = array();
	$where = " where EmployeeID = $EmployeeID and ConvenienceDate = '$ConvenienceDate' ORDER BY ID DESC";
	$response['data'] = _getTableRecords($conn,'employee_convenience', $where);

	$response['error'] = false;
	$response['message'] = "Convienience Fetched";
	return $response;
}

if(isset($data['EmployeeID']) && isset($data['ConvenienceDate']) )
{
    $conn = _connectodb();
	$response = getEmployeeConvenienceDate($conn,$data);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}

echo json_encode($response);

?>