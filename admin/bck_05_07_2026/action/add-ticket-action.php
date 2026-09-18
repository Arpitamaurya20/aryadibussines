<?php
include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
include('../../branch/controller/branch_controller.php');
require_once('../../includes/autoloader.inc.php');
$ticket_message_details = "";
$UserType = SessionCheck();
$conn = _connectodb();
setTimeZone();
$TicketManager = false;
if(isset($_SESSION['Roles']['EmployeeRoles']))
{
    $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
    foreach($EmployeeRoles as $E_Role)
    {
        if($E_Role == "Ticket Manager")
        {
            $TicketManager = true;
        }
    }
}

$Corporate_name = $_POST["corporate_name"];
// Get Company Details
$company_object = new Company($conn);
$company_details = $company_object->GetCompanyDetailsbyID($Corporate_name);

$Branch_name = $_POST["branch_name"];
$Service_type = $_POST["service_type"];
$ticket_message_details = $ticket_message_details."\nType - ".$Service_type;
$Service_name = $_POST["service_name"];
$ticket_message_details = $ticket_message_details."\nService - ".$Service_name;

$Sub_Service_name = $_POST["sub_service_name"];
$ticket_message_details = $ticket_message_details."\nSub Service - ".$Sub_Service_name;

$Message = $_POST["message"];
$ticket_message_details = $ticket_message_details."\nIssues discription - ".$Message;

$Sub_Service_others = "";
if($Sub_Service_name == "Others")
{
    $Sub_Service_others = $_POST['sub_services_others'];
    $ticket_message_details = $ticket_message_details."\nSub Service (Others) - ".$Sub_Service_others;
}

$ClientTicketID = trim($_POST['ClientTicketID']);

// check for duplicate client ticket id
$to_continue = true;
if($ClientTicketID != "")
{
    $ticket_message_details = $ticket_message_details."\nTicket Reference (Client) - ".$ClientTicketID;
    $corporateticket = new Corporateticket($conn);
    if($corporateticket->CheckforDuplicateClientTicketID($ClientTicketID))
    {
        $raise_response['error'] = true;
        $raise_response['message'] = "A ticket with same Client Ticket ID is already raised.";
        $to_continue = false;
    }
}
if($to_continue)
{
    $Message = $_POST["message"];
    $BranchAssetID ="-1";
    $Priority = $_POST["priority"];

    $AssignedTo = -1;
    $Status = "Need Approval By Company Admin";
    if($TicketManager)
    {
       $Status = "Raised"; 
    }

    if($company_details['TicketsNeedApproval'] == 0)
    {
        $Status = "Raised";
    }

    $CreatedBy = $_SESSION['pb_username'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');

     // Get City Branches
   $where = " where ID = $Branch_name";
   $result_branch_details = _getTableDetails($conn,'branch',$where);
   // $CityName = $result_branch_details['BranchCity'];
   $BranchSite = $result_branch_details['BranchSite'];  
   $BranchCity = $result_branch_details['BranchCity']; 
   $AccountBranchManager = $result_branch_details['AccountBranchManager'];
   $AccountBranchEmail=$result_branch_details['BranchEmail'];

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

    $AccountBranchManagerContact = "";
    if($AccountBranchManager != -1 && $AccountBranchManager != "")
    {
        $AssignedTo = $AccountBranchManager;
        $where_emp = " where ID = $AccountBranchManager";
        $result_emp = _getTableDetails($conn,'employees',$where_emp);
        $AccountBranchManagerContact = $result_emp['ContactNumber'];
        $AccountBranchManagerName = $result_emp['Name'];
        $AccountBranchManagerEmail=$result_emp['Email'];
    }

    $param1 = "";
    $param2 = "";
    $param3 = "";
    $param4 = "";
    $param5 = "";
    if(isset($_POST['param1']))
        $param1 = $_POST['param1'];
    if(isset($_POST['param2']))
        $param2 = $_POST['param2'];
    if(isset($_POST['param3']))
        $param3 = $_POST['param3'];
    if(isset($_POST['param4']))
        $param4 = $_POST['param4'];
    if(isset($_POST['param5']))
        $param5 = $_POST['param5'];

    $Raise_ticket = "INSERT INTO corporate_tickets (CorporateID,BranchID,Type,param1,param2,param3,param4,param5,BranchAssetID,Service,Subservice,SubService_Others,ClientTicketID,Message,Description,CreatedDate,CreatedTime,CreatedBy,AssignedTo,Priority,Status,Remarks) VALUES ('$Corporate_name','$Branch_name','$Service_type','$param1','$param2','$param3','$param4','$param5',$BranchAssetID,'$Service_name','$Sub_Service_name','$Sub_Service_others','$ClientTicketID','$Message','','$CreatedDate','$CreatedTime','$CreatedBy','$AssignedTo','$Priority','$Status','')";

    $raise_result = _InsertTableRecords($conn, $Raise_ticket);
    $LastID = $raise_result['last_insert_id'];
    $formatted_id = sprintf('%06d', $LastID);
    if($Service_type == "Supply")
    {
       $TicketID = "CS-SUP-".$formatted_id;
    }
    else if($Service_type == "Projects"){
        $TicketID = "CS-PROJECT-".$formatted_id;
    }
    else
    {
       $TicketID = "CS-RM-".$formatted_id;
    }

    $ticket_attachment_path = "";
    if (isset($_FILES['ticket_attachment']['name'])  && $_FILES['ticket_attachment']['name'] != '')
    {
        $extn_ticket_attachment = explode('.', $_FILES["ticket_attachment"]["name"]);
        $ticket_attachment_path   = $TicketID."_ATTACHMENT_".$extn_ticket_attachment[1];
        $path = "../../media/raise_ticket_media/".$ticket_attachment_path;
        move_uploaded_file($_FILES["ticket_attachment"]["tmp_name"], $path);
    }

    $raise_response = array();
    $query_parameter = " TicketID = '$TicketID',TicketAttachment='$ticket_attachment_path' where ID = $LastID";
    _UpdateTableRecords($conn,'corporate_tickets',$query_parameter);

    // Insert into history
   $data_history['TicketID'] = $LastID;
   $data_history['CreatedDate'] = $CreatedDate;
   $data_history['CreatedTime'] = $CreatedTime;
   $data_history['CreatedBy'] = $CreatedBy;
   $data_history['Status'] = $Status;
   $data_history['AssignedTo'] = $AssignedTo;
   RecordTicketHistory($conn,$data_history);

    $branch_details = GetBranchDetailsbyID($conn,$Branch_name);
    $POCPhoneNumber = $branch_details['BranchMobile'];
    $SiteIncharge = $branch_details['SiteIncharge'];
    $message = "Dear $SiteIncharge,\n\nTicket with number $TicketID has been raised succesfully!\n\nTicket Details are as follows - $ticket_message_details\n\nWarm regards,\nTechXpert Team";
    $POCPhoneNumber = "+91".$POCPhoneNumber;
    sendWhatsAppMessage($POCPhoneNumber,$message);
    if($Employee_PhoneNumber != "")
    {
        $message = "Dear $EmployeeName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n$BranchCity $ticket_message_details\n\n Please take approporate action !\n\nWarm regards,\nTechXpert Team";
       $phonenumber = "+91".$Employee_PhoneNumber;
        //sendWhatsAppMessage($phonenumber,$message);
    }

    if($AccountBranchManagerContact != "")
    {
        $message = "Dear $AccountBranchManagerName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n$BranchCity $ticket_message_details\n\n Please take approporate action !\n\nWarm regards,\nTechXpert Team";
       $AccountBranchManagerContact = "+91".$AccountBranchManagerContact;
        
         $quationID=-1;
         $where="Where TicketID='$LastID'";
         $result_quote = _getTableDetails($conn,'corporate_ticket_quotation',$where);
         if(!empty($result_quote))
         {
               $quationID=$result_quote['ID'];  
         }

          $emailPayload = [
            "action"        => "Corporate Ticket Raised",
            "TicketID"      => $TicketID,
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
            "CCEmail"       => $CityLeadEmail,
            "QuationID"     =>$quationID,
            "AccountManagerName"=> $AccountBranchManagerName,
            "AccountManagerNumber"=>$AccountBranchManagerContact,
            "AccountManagerEmail"=>$AccountBranchManagerEmail

        ];

         // $result = sendMailRequestRaisedTicket($emailPayload);
        sendWhatsAppMessage($AccountBranchManagerContact,$message);
    }

            // ====================== EMAIL NOTIFICATION ======================

       

       



        /*$where = " where ID = $Corporate_name";
        $company_Detail = _getTableDetails($conn,'company',$where);
        $CorporatePhone = $company_Detail['CompanyPhone'];
        $CompanyMobile = $company_Detail['CompanyMobile'];
        $CompanyName = $company_Detail['CompanyName'];

        if($CorporatePhone != "")
        {
            $message = "Dear $CompanyName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n\n Please take appropriate action !\n\nWarm regards,\nTechXpert Team";
            $Corporatephonenumber = "+91".$CorporatePhone;
            sendWhatsAppMessage($Corporatephonenumber,$message);
        }

        if($CorporatePhone == "")
        {
            $message = "Dear $CompanyName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n\n Please take appropriate action !\n\nWarm regards,\nTechXpert Team";
            $CompanyMobilenumber = "+91".$CompanyMobile;
            sendWhatsAppMessage($CompanyMobilenumber,$message);
        }*/

    // city lead message 

        // $message = "Hello $EmployeeName,\n\nTicket with number $TicketID has been raised succesfully!\n\nWarm regards,\nTechXpert Team";
        // $EMPPhoneNumber = "+91".$Employee_PhoneNumber;
        // sendWhatsAppMessage($EMPPhoneNumber,$message);

    if ($raise_result) {
        $raise_response['error'] = false;
        $raise_response['message'] = "Ticket Has been Raised";

    } else {
        echo mysqli_error($conn);
        $raise_response['error'] = true;
        $raise_response['message'] = "Please Contact to Administrtor. There is some technical issue.";
    }
}
echo json_encode($raise_response);

// ===== END CLIENT RESPONSE =====
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// ===== BACKGROUND MODE =====
ignore_user_abort(true);
set_time_limit(0);

// ===== BACKGROUND TASKS =====
sendMailRequestRaisedTicket($emailPayload);


?>