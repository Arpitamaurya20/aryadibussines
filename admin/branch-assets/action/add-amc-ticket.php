<?php
@session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("../../controllers/common_controllers.php");
include('../../corporate-tickets/controller/corporate_tickets_controller.php');
require_once("../../branch/controller/branch_controller.php");
$conn = _connectodb();
setTimeZone();
$data = $_POST;
$data['CreatedBy']=$_SESSION['pb_username'] ?? '';
$BranchID = $_POST['BranchID'];
$branch_details = GetBranchDetailsbyID($conn,$BranchID);
$Raise_ticket = CreateCorporateTicket($conn,$data,$branch_details);
echo json_encode($Raise_ticket);
?>