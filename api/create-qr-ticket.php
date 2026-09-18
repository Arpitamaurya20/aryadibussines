<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
setTimeZone();
require_once("../admin/branch/controller/branch_controller.php");
require_once("../admin/corporate-tickets/controller/corporate_tickets_controller.php");
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$filename = 'ticket.logs';
// Open the file in append mode
$file = fopen($filename, 'a');
// Write the content to the file
fwrite($file, $data_raw);
// Close the file
fclose($file);
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['AssetsID']) && isset($data['BranchID']) && isset($data['Type']) && isset($data['CreatedBy']))
{
   $conn = _connectodb();
   $core = new Core();
   $duplicate = false;
   if(!$duplicate)
   {
      $response = CreateCorporateTicket($conn,$data,$branch_details);
   }
}
else
{
    $response['error'] = true;
    $response['emessage'] = "Missing User Fields!";
}
echo json_encode($response);
?>