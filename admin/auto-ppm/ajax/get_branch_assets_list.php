<?php
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../branch-assets/controller/branch_assets_controller.php');

$conn = _connectodb();
setTimeZone();

$response = array();

$BranchID = isset($_GET['BranchID']) ? $_GET['BranchID'] : (isset($_POST['BranchID']) ? $_POST['BranchID'] : -1);
$CorporateID = isset($_GET['CorporateID']) ? $_GET['CorporateID'] : -1;

if ($BranchID != -1) {
    $response['data'] = getAllBranchAssetsList($conn, $BranchID, $CorporateID);
    $response['error'] = false;
    $response['message'] = "Branch Assets Fetched";
} else {
    $response['error'] = true;
    $response['message'] = "Branch ID is required";
}

echo json_encode($response);
?>

