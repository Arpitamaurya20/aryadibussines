<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
setTimeZone();
require_once("../admin/branch/controller/branch_controller.php");
require_once("../admin/corporate-tickets/controller/corporate_tickets_controller.php");
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();


if(isset($data['CorporateID']) && isset($data['BranchID']) && isset($data['Type']) && isset($data['CreatedBy']))
{
   $conn = _connectodb();
   $Type = $data['Type'];
   if($Type == "R&M")
   {
      $data['BranchAssetID'] = -1;
   }
   if($data['BranchID'] != -1)
   {
      $BranchID = $data['BranchID'];
      $branch_details = GetBranchDetailsbyID($conn,$BranchID);
   }
   $response = CreateCorporateTicket($conn,$data,$branch_details);
}
else
{
    $response['error'] = true;
    $response['emessage'] = "Missing User Fields!";
}
echo json_encode($response);
?>