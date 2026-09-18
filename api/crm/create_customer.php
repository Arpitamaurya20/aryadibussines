<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/crm/controller/crm_controller.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (isset($data['AccountName']) && $data['AccountName'] != '') {
    $conn = _connectodb();
    $insert_result = insertCustomer($conn, $data);
    
    if (!$insert_result['error']) {
        $response['error'] = false;
        $response['message'] = "Customer created successfully";
        $response['customer_id'] = $insert_result['last_insert_id'];
    } else {
        $response['error'] = true;
        $response['message'] = $insert_result['message'];
    }
} else {
    $response["error"] = true;
    $response["message"] = "AccountName is required";
}

echo json_encode($response);
?>
