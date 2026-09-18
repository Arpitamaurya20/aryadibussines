<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/crm/controller/crm_controller.php');

$response = array();
$conn = _connectodb();

$leads = getAllLeads($conn);

if ($leads !== null) {
    $response['data'] = $leads;
    $response['error'] = false;
    $response['message'] = "Leads fetched successfully";
} else {
    $response["error"] = true;
    $response["message"] = "Failed to fetch leads";
}

echo json_encode($response);
?>
