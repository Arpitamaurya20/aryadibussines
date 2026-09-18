<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once('../admin/customer/controller/customer_controller.php');

require_once('../admin/authentication/auth_controller/authentication_controller.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

setTimeZone();

$response = array();

if(isset($data['phonenumber']) && isset($data['otp']))

{

	$phonenumber = $data['phonenumber'];

	$otp = $data['otp'];

	$conn = _connectodb();

	$response = checkOTP($conn,$phonenumber,$otp);

	$myfile = fopen("logs.txt", "a") or die("Unable to open file!");
	fwrite($myfile, "\n". $response['message']);

	if($response['error'] == false)

	{

		CreateCustomer($conn,$phonenumber);

	}

}

else 

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>