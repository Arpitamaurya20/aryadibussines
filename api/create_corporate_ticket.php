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
if(isset($data['CorporateID']) && isset($data['BranchID']) && isset($data['Type']) && isset($data['CreatedBy']))
{

   $company_object = new Company($conn);
  $company_details = $company_object->GetCompanyDetailsbyID($data['CorporateID']);

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
      $BranchSite = $branch_details['BranchSite'];  
     $BranchCity = $branch_details['BranchCity']; 
    $AccountBranchManager = $branch_details['AccountBranchManager'];
    $AccountBranchEmail=$branch_details['BranchEmail'];

   //Get City Corporate Lead
    $where = " where CityName = '$BranchCity'";
    $result_city_lead = _getTableDetails($conn,'citydata',$where);
    $CorporateLead = $result_city_lead['CorporateLead'];
    $AssignedTo = -1;
    $Employee_PhoneNumber = "";
    if($CorporateLead != -1 && $CorporateLead != "")
    {
        $AssignedTo = $CorporateLead;
        $where_emp = " where ID = $AssignedTo";
        $result_emp = _getTableDetails($conn,'employees',$where_emp);
        $Employee_PhoneNumber = $result_emp['ContactNumber'];
        $EmployeeName = $result_emp['Name'];
        $CityLeadEmail=$result_emp['Email'];
    }
   }
   $core = new Core();
   $duplicate = false;
   if(isset($data['ClientTicketID']))
   {
      $ClientTicketID = $data['ClientTicketID'];
      if($ClientTicketID != "")
      {
         $filter = " where ClientTicketID = '$ClientTicketID' and BranchID = $BranchID";
         if(!($core->check_unique_identity_filter($conn,'corporate_tickets',$filter)))
         {
            $duplicate = true;
            $response['error'] = true;
            $response['message'] = "Ticket with same Clitent Ticket ID for the same branch is already created";
         }
      }
   }
   if(!$duplicate)
   {
      $response = CreateCorporateTicket($conn,$data,$branch_details);

         $quationID=-1;
         $where="Where TicketID='$LastID'";
         $result_quote = _getTableDetails($conn,'corporate_ticket_quotation',$where);
         if(!empty($result_quote))
         {
               $quationID=$result_quote['ID'];  
         }

          $emailPayload = [
            "action"        => "Corporate Ticket Raised",
            "TicketID"      => $response['TicketIDNew'],
            "CorporateName" => $company_details['CompanyName'],
            "BranchSite"    => $BranchSite,
            "BranchCity"    => $BranchCity,
            "ServiceType"   => $Service_type,
            "ServiceName"   => $Service_name,
            "SubService"    => ($Sub_Service_name == "Others") ? $Sub_Service_others : $Sub_Service_name,
            "Priority"      => $Priority,
            "Message"       => $Message,
            "Status"        => $Status,
            "RaisedBy"      => $CreatedBy,
            "CreatedDate"   => $CreatedDate,
            "CreatedTime"   => $CreatedTime,
            "ToEmail"       => $AccountBranchEmail,
            "CCEmail"       => 'rohittechxpert@gmail.com',
            "QuationID"     =>$quationID
        ];

         // $result = sendMailRequestRaisedTicket($emailPayload);
        sendWhatsAppMessage($AccountBranchManagerContact,$message);
   }
}
else
{
    $response['error'] = true;
    $response['emessage'] = "Missing User Fields!";
}
echo json_encode($response);
?>