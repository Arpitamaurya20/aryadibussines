<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
$UserType = SessionCheck();

header('Content-Type: application/json');

$response = array();
$response['error'] = false;

require_once('../controller/ppm_billing_controller.php');

// Get and validate dates
$StartDate = isset($_GET['StartDate']) ? trim($_GET['StartDate']) : date('Y-m-01');
$EndDate = isset($_GET['EndDate']) ? trim($_GET['EndDate']) : date('Y-m-t');

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $StartDate)) {
    $StartDate = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $EndDate)) {
    $EndDate = date('Y-m-t');
}

$filters = array(
    'CorporateID' => isset($_GET['CorporateID']) ? intval($_GET['CorporateID']) : -1,
    'BranchID' => isset($_GET['BranchID']) ? intval($_GET['BranchID']) : -1,
    'StartDate' => $StartDate,
    'EndDate' => $EndDate
);

$result = GetBillingStatistics($conn, $filters);

if ($result['error'] == false) {
    $response['data'] = $result['data'];
    $response['message'] = "Billing statistics fetched successfully";
} else {
    $response['error'] = true;
    $response['message'] = isset($result['message']) ? $result['message'] : "Error fetching statistics";
    $response['debug'] = isset($result['debug']) ? $result['debug'] : null;
}

echo json_encode($response);
?>
