<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
$UserType = SessionCheck();

header('Content-Type: application/json');

$response = array();
$response['error'] = false;
$response['data'] = array();

$CompanyID = isset($_GET['CompanyID']) ? intval($_GET['CompanyID']) : -1;

if ($CompanyID != -1) {
    $where = " where CompanyID = $CompanyID AND IsActive = 1 ORDER BY BranchSite ASC";
} else {
    $where = " where IsActive = 1 ORDER BY BranchSite ASC";
}

$branches = _getTableRecords($conn, 'branch', $where);

if (is_array($branches)) {
    $response['data'] = $branches;
    $response['message'] = "Branches fetched successfully";
} else {
    $response['error'] = true;
    $response['message'] = "No branches found";
}

echo json_encode($response);
?>
