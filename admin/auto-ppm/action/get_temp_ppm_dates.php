<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/auto_ppm_controller.php');
$response = array();

$conn = _connectodb();
setTimeZone();

$TempAssetInfoID = isset($_GET['TempAssetInfoID']) ? $_GET['TempAssetInfoID'] : -1;
$IsTicketRaised = isset($_GET['IsTicketRaised']) ? $_GET['IsTicketRaised'] : -1;

$response['data'] = GetTempPPMDates($conn, $TempAssetInfoID, $IsTicketRaised);
$response['error'] = false;
$response['message'] = "Temp PPM Dates Fetched";

echo json_encode($response);
?>

