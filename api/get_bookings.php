<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once('../admin/booking/controller/booking_controller.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

if(isset($data['EmployeeID']))

{

	$EmployeeID = $data['EmployeeID'];

	$conn = _connectodb();

	$response = getAllBookingsByAssignedTo($conn,$EmployeeID);

}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>