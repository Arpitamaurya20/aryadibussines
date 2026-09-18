<?php
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../branch/controller/branch_controller.php');

$conn = _connectodb();
setTimeZone();

$response = array();

$CompanyID = isset($_GET['CompanyID']) ? $_GET['CompanyID'] : (isset($_POST['CompanyID']) ? $_POST['CompanyID'] : -1);

if ($CompanyID != -1) {
    $response['data'] = getAllBranches($conn, $CompanyID);
    $response['error'] = false;
    $response['message'] = "Branches Fetched";
} else {
    $response['error'] = true;
    $response['message'] = "Company ID is required";
}

echo json_encode($response);
?>

