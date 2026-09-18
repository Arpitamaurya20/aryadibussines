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

$where = " where IsActive = 1 ORDER BY CompanyName ASC";
$companies = _getTableRecords($conn, 'company', $where);

if (is_array($companies)) {
    $response['data'] = $companies;
    $response['message'] = "Companies fetched successfully";
} else {
    $response['error'] = true;
    $response['message'] = "No companies found";
}

echo json_encode($response);
?>
