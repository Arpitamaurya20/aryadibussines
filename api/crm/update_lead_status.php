<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (isset($data['LeadID']) && isset($data['LeadStatus'])) {
    $conn = _connectodb();
    $LeadID = (int)$data['LeadID'];
    $LeadStatus = mysqli_real_escape_string($conn, $data['LeadStatus']);
    
    $sql = "UPDATE crm_leads SET LeadStatus = '$LeadStatus' WHERE ID = $LeadID";
    
    if (mysqli_query($conn, $sql)) {
        $response['error'] = false;
        $response['message'] = "Lead status updated successfully";
    } else {
        $response['error'] = true;
        $response['message'] = "Failed to update lead status: " . mysqli_error($conn);
    }
} else {
    $response["error"] = true;
    $response["message"] = "LeadID and LeadStatus are required";
}

echo json_encode($response);
?>
