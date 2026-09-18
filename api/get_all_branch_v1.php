<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/Services/controller/service_controller.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

// Get page number and limit from request, default to 1 and 10
$page = isset($data['page']) ? (int)$data['page'] : 1;
$limit = isset($data['limit']) ? (int)$data['limit'] : 10;
$offset = ($page - 1) * $limit;

$conn = _connectodb();

// Fetch total count for pagination info
$total_branches = _getTableRecords($conn, 'branch', " WHERE IsActive = 1");
$total_count = count($total_branches);

// Fetch only the records for this page
$where = " WHERE IsActive = 1 LIMIT $limit OFFSET $offset";
$branches = _getTableRecords($conn, 'branch', $where);

// Format branch list
$branch_list = array_map(function($branch) {
    return [
        'ID' => $branch['ID'],
        'BranchSite' => $branch['BranchSite']
    ];
}, $branches);

$response['data'] = $branch_list;
$response['error'] = false;
echo json_encode($response);
?>
