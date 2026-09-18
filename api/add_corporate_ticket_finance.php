<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

require_once('../admin/corporate-tickets/controller/corporate_tickets_controller.php');

setTimeZone();

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();

if(isset($data['TicketID']) && isset($data['T_VisitorNo']) && isset($data['T_VisitCharge']) && isset($data['T_MaterialCost']) && isset($data['T_LabourCost']) && isset($data['T_TotalPrice']) && isset($data['Status']) && isset($data['C_VisitorNo']) && isset($data['C_VisitCharge']) && isset($data['C_MaterialCost']) && isset($data['C_LabourCost']) && isset($data['C_TotalPrice']))

{

	$TicketID = $data['TicketID'];

	$data['CreatedDate'] = date('Y-m-d');

	$data['CreatedTime'] = date('H:i:s');

	$conn = _connectodb();

	$response = InserCorporateFinance($conn,$data);

}

else

{

	$response["error"] = true;

	$response["message"] = "Missing User Fields";

}

echo json_encode($response);

?>