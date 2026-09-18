<?php

require_once('common_api_header.php');

require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');

$data = json_decode($data_raw,true);

$response = array();

function getPpmBillingStatus($conn,$data)

{

	$TicketID = $data['TicketID'];

	$response = array();

	$response['data'] = array();
    
    $where = " where TicketID = '$TicketID' and IsActive=1 ORDER BY ID DESC";

	$records = _getTableRecords($conn,'ppm_billing_tracking', $where);
    
    if (count($records) > 0) {
        $response['data'] = $records;
        $response['error'] = false;
        $response['message'] = "Billing status fetched successfully.";
    } else {
        $response['error'] = true;
        $response['message'] = "No billing records found for the given TicketID.";
    }

	return $response;

}

if(isset($data['TicketID']))

{

	$conn = _connectodb();

	$response = getPpmBillingStatus($conn,$data);

}

else

{

	$response["error"] = true;

	$response["message"] = "Missing TicketID";

}

echo json_encode($response);

?>
