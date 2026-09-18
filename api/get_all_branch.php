<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/Services/controller/service_controller.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();
if (1) {
    $conn = _connectodb();
    $where = " WHERE IsActive = 1";
    $branches = _getTableRecords($conn, 'branch', $where);
    $branch_list = array_map(function($branch) {
        return [
            'ID' => $branch['ID'],
            'BranchSite' => $branch['BranchSite']
        ];
    }, $branches);

    $response['data'] = $branch_list;
    $response['error'] = false;
    $response['message'] = "Branches fetched successfully";
} else {
    $response["error"] = true;
    $response["message"] = "Missing Services";
}

echo json_encode($response);
?>
