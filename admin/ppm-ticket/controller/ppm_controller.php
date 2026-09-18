<?php

function getPPMTicketDetail($conn,$data)
{
   $TicketID = "";
   if(isset($data['TicketID']))
   {
      $TicketID = $data['TicketID'];
   }
   $response = array();
   $response['data'] = array();
   $where = " where ID = $TicketID";
   $response['data'] = _getTableDetails($conn,'ppm_tickets', $where);
   $response['error'] = false;
   $response['message'] = "Ticket Details fetched";

   if($response == true)
   {
      $sql = "SELECT a.*,b.CompanyName,c.BranchSite,c.BranchAddress1,c.BranchCode,d.EquipmentName,d.Make,d.Model,d.EquipmentLocation,d.SNo,d.Category from `ppm_tickets` a,company b,branch c,branch_assets d WHERE a.CorporateID = b.ID and a.BranchID = c.ID and a.BranchAssetID = d.ID and a.ID = $TicketID";

      $result=mysqli_query($conn,$sql);
      if($result)
      {
         $row = $result->fetch_assoc();
         $row['Escalated'] = "yes";
      }
      else
      {
         $error = mysqli_error($conn);
         echo $sql;
         echo $error;
      }
      $response['data'] = $row;
   }
   return $response;
}

function CreatePPMTicket($conn,$data,$branch_details)
{
   $CorporateID = $data['CorporateID'];
   $BranchID = $data['BranchID'];
   if($BranchID != -1)
   {
      $POCPhoneNumber = $branch_details['BranchMobile'];
      $SiteIncharge = $branch_details['SiteIncharge'];
   }
   $BranchAssetID = $data['BranchAssetID'];
   $PPM_date = $data['ppmdate'];
   $CreatedBy = $data['CreatedBy'];
   $TicketDate = date('Y-m-d');
   $TicketTime = date('H:i:s');
   $Employee_PhoneNumber = "";

   $MessagerAlert = false;

   // Get Branch Assets Name 

      $where = " where ID = $BranchAssetID";
      $branch_assets_Detail = _getTableDetails($conn,'branch_assets',$where);
      $Equipment_name = $branch_assets_Detail['EquipmentName'];

   // Get corporate details from Ticket
   
      $where = " where ID = $CorporateID";
      $company_Detail = _getTableDetails($conn,'company',$where);
      $CorporatePhone = $company_Detail['CompanyMobile'];

   // Get City Branches
      $CityName = $branch_details['BranchCity'];
      // echo $CityName;
      // die();
      $BranchSite = $branch_details['BranchSite'];

   // Get City Corporate Lead
      $AssignedTo = $branch_details['AccountBranchManager'];
      if($AssignedTo == "")
      {
         $AssignedTo = -1;
      }
      if($AssignedTo == -1)
      {
         $where = " where CityName = '$CityName'";
         $result_city_lead = _getTableDetails($conn,'citydata',$where);
         $CorporateLead = $result_city_lead['CorporateLead'];
         $AssignedTo = $CorporateLead;
      }
      if($AssignedTo != -1 && $AssignedTo != "")
      {
         $where_emp = " where ID = $AssignedTo";
         $result_emp = _getTableDetails($conn,'employees',$where_emp);
         $Employee_PhoneNumber = $result_emp['ContactNumber'];
         $EmployeeName = $result_emp['Name'];
      }

   foreach ($PPM_date as $key => $val) 
   {

      $MessagerAlert = true;

      $PPMDate =  $PPM_date[$key];

      $sql = "INSERT INTO ppm_tickets(CorporateID,BranchID,BranchAssetID,PPMDate,AssignedTo,Status,CreatedDate,CreatedTime,CreatedBy) VALUES ($CorporateID,$BranchID,$BranchAssetID,'$PPMDate','$AssignedTo','Raised','$TicketDate','$TicketTime','$CreatedBy')";
      $insert_ticket = _InsertTableRecords($conn,$sql);
      // echo $sql."<br>";
      
       $ID = $insert_ticket['last_insert_id'];
      $formatted_id = sprintf('%06d', $ID);
   
      $TicketID = "CS-PPM-".$formatted_id;

      $query_parameter = " TicketID = '$TicketID' where ID = $ID";
      _UpdateTableRecords($conn,'ppm_tickets',$query_parameter);
      // echo $query_parameter."<br>";
      $response['error'] = false;
      $response['message'] = "PPM Tickets has been raised succesfully!";
      $response['ID'] = $ID;


      if($Employee_PhoneNumber != "")
      {
         $emp_message = "Dear $EmployeeName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n\n Please take approporate action !\n\nWarm regards,\nTechXpert Team";
          $phonenumber = "+91".$Employee_PhoneNumber;
         //sendWhatsAppMessage($phonenumber,$emp_message);
      }
   }

   if($MessagerAlert)
   {

      if($POCPhoneNumber != ""){
         $poc_message = "Dear $SiteIncharge,\n\nPPM Ticket for $Equipment_name has been raised succesfully!\n\nWarm regards,\nTechXpert Team";
        $POCPhoneNumber = "+91".$POCPhoneNumber;
        //sendWhatsAppMessage($POCPhoneNumber,$poc_message);
      }
   }


   return $response;
}


function getAllPPMTickets($conn,$CorporateID,$BranchID,$BranchAssetID)
{
   $branch_assets_check = "1";
   $corporate_check = "1";
   $branch_check = "1";
   if($CorporateID != -1)
   {
      $corporate_check = " CorporateID = $CorporateID ";
   }
   if($BranchID != -1)
   {
      $branch_check = " BranchID = $BranchID ";
   }
   if($BranchAssetID != -1)
   {
      $branch_assets_check = " BranchAssetID = $BranchAssetID ";
   }
   $where = " where $corporate_check AND $branch_check AND $branch_assets_check AND IsActive = 1 order by ID desc";
   $response = _getTableRecords($conn,'ppm_tickets', $where);
   return $response;
}


function ppmNormalizeAssignmentDueDate($dueDate)
{
   $dueDate = trim((string) $dueDate);
   if ($dueDate === '' || $dueDate === '0000-00-00') {
      return '';
   }
   return $dueDate;
}

function ManagePPMTicketAssignmentStatus($conn,$data)
{
   $TicketID = "";
   if(isset($data['TicketID']))
   {
      $TicketID = $data['TicketID'];
   }

   $DueDate = "";
   if(isset($data['DueDate']))
   {
      $DueDate = $data['DueDate'];
   }

   $TicketStatus = isset($data['TicketStatus']) ? $data['TicketStatus'] : '';
   $newAssignedTo = isset($data['AssignedTo']) ? (int) $data['AssignedTo'] : 0;

   $response = array();
   // $TicketID = $data['TicketID'];
   $old_ticket_data = getPPMTicketDetail($conn, $data)['data'];
   if (!$old_ticket_data) {
      $response['message'] = "PPM ticket not found.";
      $response['error'] = true;
      return $response;
   }

   if ($newAssignedTo <= 0) {
      $response['message'] = "Please assign an employee.";
      $response['error'] = true;
      return $response;
   }

   $oldAssignedTo = (int) $old_ticket_data['AssignedTo'];
   $oldDueDate = ppmNormalizeAssignmentDueDate(isset($old_ticket_data['DueDate']) ? $old_ticket_data['DueDate'] : '');
   $newDueDate = ppmNormalizeAssignmentDueDate($DueDate);

   if($old_ticket_data['Status'] == $TicketStatus && $oldAssignedTo === $newAssignedTo && $oldDueDate === $newDueDate)
   {
      $response['message'] = "Either change Status or Assigned Employee!";
      $response['error'] = true;
   }
   else
   {
      $Ticket_number = $old_ticket_data['TicketID'];
      $AssignedTo = $newAssignedTo;
      $DueDate = $newDueDate;
      $query_parameter = " Status = '$TicketStatus',AssignedTo = $AssignedTo, DueDate = '$DueDate' where ID = $TicketID";
      $response = _UpdateTableRecords($conn,'ppm_tickets', $query_parameter);
      if($response['error'] == false)
      {
         $response['message'] = "Ticket Updated!";
      }

      // send whatsapp message

      // Get Branch details from Ticket
      $BranchID = $old_ticket_data['BranchID'];
      $where = " where ID = $BranchID";
      $branch_Detail = _getTableDetails($conn,'branch',$where);
      $BranchPhone = $branch_Detail['BranchMobile'];
      $CityName = $branch_Detail['BranchCity'];
      $BranchCity = $branch_Detail['BranchSite'];
      $BranchSiteIncharge = $branch_Detail['SiteIncharge'];

      // get employee details

      if($BranchPhone != "")
      {

         $where = " where ID = $AssignedTo";
         $employee_details = _getTableDetails($conn,'employees',$where);
         $EmployeeName = $employee_details['Name'];
         $EmployeePhone = $employee_details['ContactNumber'];

         // send message to Branch
            $AssignedTo = $EmployeeName;
         $message = "Dear $BranchSiteIncharge, \n\nYour Ticket Number is $Ticket_number. Your Ticket has been assigned to one of our Technician.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $EmployeePhone\n\n📍 Location - $BranchCity\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
            $BranchPhonenumber = "+91".$BranchPhone;
            //sendWhatsAppMessage($BranchPhonenumber,$message);

         // send message to employee
         $MessageForEmployee = "Dear $EmployeeName, \n\nThis is to inform you that we have a ticket that requires your attention. The ticket number is $Ticket_number.\n\nTicket Details SOS\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $BranchPhone\n\n📍 Location - $BranchCity\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nTechXpert Team";
            $EmployeePhoneNumber = "+91".$EmployeePhone;
            //sendWhatsAppMessage($EmployeePhoneNumber,$MessageForEmployee);

         // Get City Corporate Lead
         $where = " where CityName = '$CityName'";
         $result_city_lead = _getTableDetails($conn,'citydata',$where);
         $CorporateLead = $result_city_lead['CorporateLead'];
         $AssignedToCity = -1;
         if($CorporateLead != -1 && $CorporateLead != "")
         {
         $AssignedToCity = $CorporateLead;
         $where_emp = " where ID = $AssignedToCity";
          $result_emp = _getTableDetails($conn,'employees',$where_emp);
          $Citylead_PhoneNumber = $result_emp['ContactNumber'];
          $EmployeeName = $result_emp['Name'];
         }

         // send message to city lead 

         if($Citylead_PhoneNumber != "")
         {
         $message = "Dear $EmployeeName, \n\nThis is to inform you that we have a ticket that requires your attention. The ticket number is $Ticket_number.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo \n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nTechXpert Team";
         $phonenumber = "+91".$Citylead_PhoneNumber;
         //sendWhatsAppMessage($phonenumber,$message);
         }

      }

      if($TicketStatus == "Closed"){
         $MessageForFeedback = "Hello, \n\nYour Ticket Number is $Ticket_number. I wanted to let you know that your ticket has been resolved and closed. We hope that our team was able to assist you with your concern in a timely and satisfactory manner.\n\nAs part of our ongoing efforts to improve our services, we would love to hear your feedback on your experience with our support team. If you have a few minutes, please feel free to share any comments or suggestions you may have.\n\nFeeback Link- https://techxpertindia.in/customer-rating.php\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
            $BranchPhoneNumber = "+91".$BranchPhone;
            //sendWhatsAppMessage($BranchPhoneNumber,$MessageForFeedback);
      }

      // $response = _api_changeBookingStatus($conn,$data);
   }

   return $response;
}


function DeleteCustomerDetails($conn,$data)
{
   $ID = $data['ID'];
   $query = " where ID = $ID";
   return delete_identity_filter($conn,'ppm_tickets', $query);
}


function ChangePPMdate($conn,$data)
{
   $TicketID = "";
   if(isset($data['TicketID']))
   {
      $TicketID = $data['TicketID'];
   }

   $response = array();
   // $TicketID = $data['TicketID'];
   $old_ticket_data = getPPMTicketDetail($conn, $data)['data'];
   if($old_ticket_data['PPMDate'] == $data['ppm_date'])
   {
      $response['message'] = "Either change PPM Date!";
      $response['error'] = true;
   }
   else
   {
      $Ticket_number = $old_ticket_data['TicketID'];
      $PPM_date = $data['ppm_date'];
      $query_parameter = " PPMDate = '$PPM_date' where ID = $TicketID";
      $response = _UpdateTableRecords($conn,'ppm_tickets', $query_parameter);
      if($response['error'] == false)
      {
         $response['message'] = "PPM Date Updated!";
      }
   }

   
   return $response;
}

function getPPMTicketDetailByBranchAssetsID($conn,$BranchAssetID){
   $where = " where BranchAssetID = $BranchAssetID";
   $response = _getTableRecords($conn,'ppm_tickets', $where);
   return $response;
}

?>