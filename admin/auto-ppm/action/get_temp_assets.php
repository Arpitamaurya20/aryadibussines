<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/auto_ppm_controller.php');
$response = array();

$conn = _connectodb();
setTimeZone();

$BranchAssetID = isset($_GET['BranchAssetID']) ? $_GET['BranchAssetID'] : -1;
$CorporateID = isset($_GET['CorporateID']) ? $_GET['CorporateID'] : -1;
$BranchID = isset($_GET['BranchID']) ? $_GET['BranchID'] : -1;

if ($CorporateID != -1 || $BranchID != -1) {
    $response['data'] = GetTempBranchAssetsInfoWithDetails($conn, $CorporateID, $BranchID);
} else {
    $response['data'] = GetTempBranchAssetsInfo($conn, $BranchAssetID);
}

$response['error'] = false;
$response['message'] = "Temp Assets Info Fetched";

echo json_encode($response);
?>

