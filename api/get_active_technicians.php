<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once('../admin/employees/controller/employee_controller.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw, true);

$response = array();

$conn = _connectodb();

$StateName = '';

if (isset($data['StateName']) && $data['StateName'] !== '') {
	$StateName = $data['StateName'];
}

$response_data = getActiveTechniciansList($conn, $StateName);

if (sizeof($response_data) > 0) {
	$response['data'] = $response_data;
	$response['error'] = false;
	$response['message'] = "Data Fetched";
} else {
	$response['error'] = true;
	$response['message'] = "No Records Found";
}

echo json_encode($response);

?>
