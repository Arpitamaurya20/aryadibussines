<?php

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once('../admin/ppm-ticket/controller/ppm_controller.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

if(isset($data['TicketID']))

{

	$data['TicketID'] = (int)$data['TicketID'];

	$conn = _connectodb();
	$categories_obj = new Categories($conn);
	$categories_array = $categories_obj->setCategoriesArray();

	$response = getPPMTicketDetail($conn,$data);

	if(isset($response['data']['AssignedTo']))

	{

		if($response['data']['AssignedTo'] != -1)

		{
			$CategoryName = "";
			$Category = $response['data']['Category'];
			if(isset($categories_array[$Category]['CategoryName']))
			{
				$CategoryName = $categories_array[$Category]['CategoryName'];
				$response['data']['CategoryName'] = $CategoryName;
			}
			$AssignedTo = $response['data']['AssignedTo'];

			$where = " where ID = $AssignedTo";

			$respone_employee = _getTableDetails($conn,'employees', $where);

			if(isset($respone_employee['Name']))

				$response['data']['EmployeeName'] = $respone_employee['Name'];

		}

	}

}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>