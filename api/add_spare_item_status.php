<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
setTimeZone();
require_once("../admin/branch/controller/branch_controller.php");
require_once("../admin/corporate-tickets/controller/corporate_tickets_controller.php");
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['TicketID']) && isset($data['SparePartDecs']))
{
   $conn = _connectodb();
   $TicketID = $data['TicketID'];
   $response = UpdateSparePart($conn,$TicketID,$data);
   $response['error'] = false;
   $response['emessage'] = "Spare Part Update Succesfully!";
}
else
{
    $response['error'] = true;
    $response['emessage'] = "Missing User Fields!";
}
echo json_encode($response);
?>