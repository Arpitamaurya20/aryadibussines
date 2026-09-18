<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/crm/controller/crm_controller.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (isset($data['LeadName']) && $data['LeadName'] != '') {
    $conn = _connectodb();
    $insert_result = insertLead($conn, $data);
    
    if (!$insert_result['error']) {
        $response['error'] = false;
        $response['message'] = "Lead created successfully";
        $response['lead_id'] = $insert_result['last_insert_id'];
    } else {
        $response['error'] = true;
        $response['message'] = $insert_result['message'];
    }
} else {
    $response["error"] = true;
    $response["message"] = "LeadName is required";
}

echo json_encode($response);
?>
