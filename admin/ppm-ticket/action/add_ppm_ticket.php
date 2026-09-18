<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("../../controllers/common_controllers.php");
include('../controller/ppm_controller.php');
require_once("../../branch/controller/branch_controller.php");
$conn = _connectodb();
setTimeZone();
@session_start();

$data = $_POST;
$BranchID = $_POST['BranchID'];
$branch_details = GetBranchDetailsbyID($conn,$BranchID);
$Raise_ticket = CreatePPMTicket($conn,$data,$branch_details);

echo json_encode($Raise_ticket);

?>