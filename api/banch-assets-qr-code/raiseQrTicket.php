<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
include("../../admin/corporate-tickets/controller/corporate_tickets_controller.php");
require_once("../../admin/branch/controller/branch_controller.php");
setTimeZone();
$conn = _connectodb();
$response = [];
$BranchID      = $_POST['BranchID']      ?? '';
$BranchAssetID = $_POST['BranchAssetID'] ?? '';
$Message       = $_POST['Message']       ?? '';
$CreatedBy     = $_POST['CreatedBy']     ?? 'system';
$Type          = $_POST['Type']          ?? 'AMC';

if ($BranchID && $BranchAssetID && $Message) {
    $data = [
        "BranchID"      => $BranchID,
        "BranchAssetID" => $BranchAssetID,
        "Message"       => $Message,
        "CreatedBy"     => $CreatedBy,
        "Type"          => $Type
    ];
    $branch_details = GetBranchDetailsbyID($conn, $BranchID);
    $Raise_ticket = CreateCorporateTicket($conn, $data, $branch_details);
    echo json_encode($Raise_ticket);
    exit;
} else {
    $response['status']  = "error";
    $response['message'] = "Missing required fields!";
    echo json_encode($response);
    exit;
}
