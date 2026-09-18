<?php

function getCorporateTicketDetail($conn,$data)
{
	$TicketID = "";
	if(isset($data['TicketID']))
	{
		$TicketID = $data['TicketID'];
	}
	$response = array();
	$response['data'] = array();
	$where = " where ID = $TicketID";
	$response['data'] = _getTableDetails($conn,'corporate_tickets', $where);
	$response['error'] = false;
	$response['message'] = "Ticket Details fetched";

	if($response['data']['Type'] == "AMC")
	{
		$sql = "SELECT a.*,b.CompanyName,c.BranchSite,d.EquipmentName from `corporate_tickets` a,company b,branch c,branch_assets d WHERE a.CorporateID = b.ID AND a.BranchID = c.ID and a.BranchAssetID = d.ID and a.ID = $TicketID";

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
	else
	{
		$sql = "SELECT a.*,b.CompanyName,c.BranchSite from `corporate_tickets` a,company b,branch c WHERE a.CorporateID = b.ID AND a.BranchID = c.ID and a.ID = $TicketID";
		//echo $sql;
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

function getAllCorporateTickets($conn,$CorporateID,$BranchID)
{
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
	$where = " where $corporate_check AND $branch_check AND IsActive = 1 order by ID desc";
	$response = _getTableRecords($conn,'corporate_tickets', $where);
	return $response;
}

function DeleteCustomerDetails($conn,$data)
{
	$ID = $data['ID'];
	$query = " where ID = $ID";
	return delete_identity_filter($conn,'corporate_tickets', $query);
}

function ManageTicketAssignmentStatus($conn,$data)
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

	$response = array();
	// $TicketID = $data['TicketID'];
	$old_ticket_data = getCorporateTicketDetail($conn, $data)['data'];
	if($old_ticket_data['Status'] == $data['TicketStatus'] && $old_ticket_data['AssignedTo'] == $data['AssignedTo'] && $old_ticket_data['DueDate'] == $data['DueDate'])
	{
		$response['message'] = "Either change Status or Assigned Employee!";
		$response['error'] = true;
	}
	else
	{
		$Ticket_number = $old_ticket_data['TicketID'];
		$TicketStatus = $data['TicketStatus'];
		$AssignedTo = $data['AssignedTo'];
		$DueDate = $data['DueDate'];
		$query_parameter = " Status = '$TicketStatus',AssignedTo = $AssignedTo,DueDate = '$DueDate' where ID = $TicketID";
		$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
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
			$message = "Dear $BranchSiteIncharge, \n\nYour Ticket Number is $Ticket_number. Your Ticket has been assigned to one of our Technician.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $EmployeePhone\n\n📍 Location - $BranchCity\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nAryadibusiness Team";
	   		$BranchPhonenumber = "+91".$BranchPhone;
	   		sendWhatsAppMessage($BranchPhonenumber,$message);

			// send message to employee
			$MessageForEmployee = "Dear $EmployeeName, \n\nThis is to inform you that we have a ticket that requires your attention. The ticket number is $Ticket_number.\n\nTicket Details SOS\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $BranchPhone\n\n📍 Location - $BranchCity\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nAryadibusiness Team";
	   		$EmployeePhoneNumber = "+91".$EmployeePhone;
	   		sendWhatsAppMessage($EmployeePhoneNumber,$MessageForEmployee);

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
			$message = "Dear $EmployeeName, \n\nThis is to inform you that we have a ticket that requires your attention. The ticket number is $Ticket_number.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo \n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nAryadibusiness Team";
			$phonenumber = "+91".$Citylead_PhoneNumber;
			sendWhatsAppMessage($phonenumber,$message);
			}

	   }

		// $response = _api_changeBookingStatus($conn,$data);
	}

	
	if($TicketStatus == "Closed"){

		/*
		$MessageForFeedback = "Hello, \n\nYour Ticket Number is $Ticket_number. I wanted to let you know that your ticket has been resolved and closed. We hope that our team was able to assist you with your concern in a timely and satisfactory manner.\n\nAs part of our ongoing efforts to improve our services, we would love to hear your feedback on your experience with our support team. If you have a few minutes, please feel free to share any comments or suggestions you may have.\n\nFeeback Link- https://techxpertindia.in/customer-rating.php\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nAryadibusiness Team";
	   	$BranchPhoneNumber = "+91".$BranchPhone;
	   	sendWhatsAppMessage($BranchPhoneNumber,$MessageForFeedback);
		*/

		// Get corporate details from Ticket

		// $CorporateID = $old_ticket_data['CorporateID'];
		// $where = " where ID = $CorporateID";
		// $corporate_Detail = _getTableDetails($conn,'company',$where);
		// $CorporatePhone = $corporate_Detail['CompanyMobile'];


		// send message to Corporate

		// $MessageForCorporate = "Hello, \n\nI wanted to let you know that The ticket number is $Ticket_number. has been resolved and closed.\n\nTicket Details SOS\n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nAryadibusiness Team";
		// $CorporatePhoneNumber = "+91".$CorporatePhone;
		// sendWhatsAppMessage($CorporatePhoneNumber,$MessageForCorporate);

		// send message to city lead

		// if($Citylead_PhoneNumber != "")
		// {
		// 	$message = "Hello, \n\nI wanted to let you know that The ticket number is $Ticket_number. has been resolved and closed.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo \n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nAryadibusiness Team";
		// 	$phonenumber = "+91".$Citylead_PhoneNumber;
		// 	sendWhatsAppMessage($phonenumber,$message);
		// }

	}
	return $response;
}

/*function GetBranchDetailsbyID($conn,$BranchID)
{
   $where = " where ID = $BranchID";
   return _getTableDetails($conn,'branch', $where);
}
*/
function CreateCorporateTicket($conn,$data,$branch_details)
{
   $CorporateID = $data['CorporateID'];
   $BranchID = $data['BranchID'];
   if($BranchID != -1)
   {
      $POCPhoneNumber = $branch_details['BranchMobile'];
      $SiteIncharge = $branch_details['SiteIncharge'];
   }
   $BranchAssetID = $data['BranchAssetID'];
   $Type = $data['Type'];
   $Message = $data['Message'];
   $CreatedBy = $data['CreatedBy'];
   $Subservice = "";
	if(isset($data["Subservice"]))
	{
		$Subservice = $data["Subservice"];
	}
	$Price = "";
	if(isset($data["Price"]))
	{
		$Price = $data["Price"];
	}
   $TicketDate = date('Y-m-d');
   $TicketTime = date('H:i:s');
   $Employee_PhoneNumber = "";
   $Service = "";
   if(isset($data['Service']))
   {
   	$Service = $data['Service'];
   }

   // Get corporate details from Ticket
		// $where = " where ID = $CorporateID";
		// $company_Detail = _getTableDetails($conn,'company',$where);
		// $CorporatePhone = $company_Detail['CompanyMobile'];

   // Get City Branches
   $where = " where ID = $BranchID";
   $result_branch_details = _getTableDetails($conn,'branch',$where);
   $CityName = $result_branch_details['BranchCity'];
   $BranchSite = $result_branch_details['BranchSite'];


   // Get City Corporate Lead
   $where = " where CityName = '$CityName'";
   $result_city_lead = _getTableDetails($conn,'citydata',$where);
   $CorporateLead = $result_city_lead['CorporateLead'];
   $AssignedTo = -1;
   if($CorporateLead != -1 && $CorporateLead != "")
   {
   	$AssignedTo = $CorporateLead;
   	$where_emp = " where ID = $AssignedTo";
		$result_emp = _getTableDetails($conn,'employees',$where_emp);
		$Employee_PhoneNumber = $result_emp['ContactNumber'];
		$EmployeeName = $result_emp['Name'];
   }

   $sql = "INSERT INTO corporate_tickets(CorporateID,BranchID,Type,BranchAssetID,Service,Subservice,Price,Message,CreatedDate,CreatedTime,CreatedBy,AssignedTo) VALUES ($CorporateID,$BranchID,'$Type',$BranchAssetID,'$Service','$Subservice','$Price','$Message','$TicketDate','$TicketTime','$CreatedBy',$AssignedTo)";
   $insert_ticket = _InsertTableRecords($conn,$sql);
   $ID = $insert_ticket['last_insert_id'];
   $formatted_id = sprintf('%06d', $ID);
   if($Type == "AMC")
   {
      $TicketID = "CS-AMC-".$formatted_id;
   }
   else
   {
      $TicketID = "CS-RM-".$formatted_id;
   }

   if (isset($data['TempImageID'])) {
		// code...
		$ImageID = $data['TempImageID'];
		
	    $where = " where TempImageID = $ImageID";
		$TempImageData = _getTableRecords($conn, 'temp_capture_image', $where);

	    foreach($TempImageData as $ImageDataValue){
			$Action = "Raised Ticket";
	    	$filename =  $ImageDataValue['Image'];     
	    	$ticket_media_query = "INSERT INTO ticket_media (TicketID,Action,Image,CreatedBy,CreatedDate,CreatedTime ) VALUES('$TicketID','$Action','$filename','$CreatedBy','$CreatedDate','$CreatedTime')";
	    	$response = _InsertTableRecords($conn, $ticket_media_query);

	    }
	}

   $query_parameter = " TicketID = '$TicketID' where ID = $ID";
   _UpdateTableRecords($conn,'corporate_tickets',$query_parameter);
   $response['error'] = false;
   $response['message'] = "Ticket with number $TicketID has been raised succesfully!";
   $message = "Dear $SiteIncharge,\n\nTicket with number $TicketID has been raised succesfully!\n\nWarm regards,\nAryadibusiness Team";
   $POCPhoneNumber = "+91".$POCPhoneNumber;
   sendWhatsAppMessage($POCPhoneNumber,$message);

   if($Employee_PhoneNumber != "")
	{
		$message = "Dear $EmployeeName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n\n Please take approporate action !\n\nWarm regards,\nAryadibusiness Team";
	    $phonenumber = "+91".$Employee_PhoneNumber;
		sendWhatsAppMessage($phonenumber,$message);
	}

	// if($CorporatePhone != "")
	// {
	// 	$message = "We have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n\n Please take approporate action !\n\nWarm regards,\nAryadibusiness Team";
	//     $Corporatephonenumber = "+91".$CorporatePhone;
	// 	sendWhatsAppMessage($Corporatephonenumber,$message);
	// }

   return $response;
}

function getCorporateTicketStatusArray($conn)
{
	$where  = " where IsActive = 1";
	$response = _getTableRecords($conn,'corporate_tickets_status', $where);
	return $response;
}

function escalateTicket($conn,$data)
{
	$TicketID = $data['TicketID'];
	$query_parameter = " Status = 'Escalated' where ID = $TicketID";
	$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
	return $response;
}

function getSubServices($conn,$ServiveID)
{
	$where = "where service_id = $ServiveID";
	$response = _getTableRecords($conn, 'subservice', $where);
	return $response;
}
function getServices($conn,$ServivesID)
{
	$where = "where ID = $ServivesID";
	$response = _getTableRecords($conn, 'services', $where);
	return $response;
}


?>