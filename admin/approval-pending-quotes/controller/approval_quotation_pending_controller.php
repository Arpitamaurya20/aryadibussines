<?php
function getAllPendingTicketswithQuotesApprovals($conn,$CorporateID)
{
	$where = " where CorporateID = '$CorporateID' and QuotationStatus = 'Approval Pending' ORDER BY ID DESC";
	$response = _getTableRecords($conn,'corporate_tickets', $where);
	return $response;
}

function getAllPeindingTicketsDetails($conn,$TicketID)
{
	$where = " where ID = $TicketID ORDER BY ID DESC";
	$response = _getTableDetails($conn,'corporate_tickets', $where);

	$where = " where TicketID = $TicketID";
	$response_images = _getTableRecords($conn,'ticket_media', $where);
	//print_r($response_images);
	$response_final_images = array();
	foreach($response_images as $response_image)
	{
		//print_r($response_image);
		$temp =  $response_image;
		$temp['Image'] = "https://techxpertindia.in/admin/media/ticket_media/".$response_image['Image'];
		array_push($response_final_images,$temp); 
	}
	$response['ticket_images'] = $response_final_images;
	return $response;
}

function ManageTicketApproval($conn,$TicketID)
{

    $response = array();
	$TicketStatus = 'Raised';


	// Get branch Id 

	$where = " where ID = $TicketID";
	$BranchID = _getTableDetails($conn,'corporate_tickets', $where)['BranchID'];


 	// Get City Branches
	   $where = " where ID = $BranchID";
	   $result_branch_details = _getTableDetails($conn,'branch',$where);
	   $CityName = $result_branch_details['BranchCity'];
	   $BranchSite = $result_branch_details['BranchSite'];
	   $BranchMobile = $result_branch_details['BranchMobile'];
	   $SiteIncharge = $result_branch_details['SiteIncharge'];


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


	$query_parameter = " Status = '$TicketStatus',AssignedTo = $AssignedTo where ID = $TicketID";
	$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
	if($response['error'] == false)
		{
		  $response['message'] = "Ticket Approved!";
	}else{

		  $response['message'] = "Technical Error!";

	}

	$where = " where ID = $TicketID";
	$TicketID = _getTableDetails($conn,'corporate_tickets', $where)['TicketID'];

	if($Employee_PhoneNumber != "")
		{
			$message = "Dear $EmployeeName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n\n Please take approporate action !\n\nWarm regards,\nTechXpert Team";
		    $phonenumber = "+91".$Employee_PhoneNumber;
			sendWhatsAppMessage($phonenumber,$message);
		}

	if($BranchMobile != "")
		{
			$message = "Dear $SiteIncharge,\n\nTicket with number $TicketID has been Approved and raised succesfully!\n\nTicket details:\n\nℹ City Lead - $EmployeeName \n☎ Contact - $Employee_PhoneNumber\n\nWarm regards,\nTechXpert Team";
		    $BranchMobilenumber = "+91".$BranchMobile;
			sendWhatsAppMessage($BranchMobilenumber,$message);
		}


	return $response;
}

function ManageTicketReject($conn,$TicketID)
{

    $response = array();
	$TicketStatus = 'Rejected';

	// Get branch Id 

	$where = " where ID = $TicketID";
	$BranchID = _getTableDetails($conn,'corporate_tickets', $where)['BranchID'];


 	// Get City Branches
	   $where = " where ID = $BranchID";
	   $result_branch_details = _getTableDetails($conn,'branch',$where);
	   $BranchSite = $result_branch_details['BranchSite'];
	   $BranchMobile = $result_branch_details['BranchMobile'];
	   $SiteIncharge = $result_branch_details['SiteIncharge'];



	$query_parameter = " Status = '$TicketStatus' where ID = $TicketID";
	$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
	if($response['error'] == false)
		{
		  $response['message'] = "Ticket Rejected!";
	}else{

		  $response['message'] = "Technical Error!";

	}

	$where = " where ID = $TicketID";
	$TicketID = _getTableDetails($conn,'corporate_tickets', $where)['TicketID'];


	if($BranchMobile != "")
		{
			$message = "Dear $SiteIncharge,\n\nTicket with number $TicketID has been Rejected!\n\nWarm regards,\nTechXpert Team";
		    $BranchMobilenumber = "+91".$BranchMobile;
			sendWhatsAppMessage($BranchMobilenumber,$message);
		}


	return $response;
}

function getAllRejectedTickets($conn,$CorporateID)
{
	$where = " where AssignedTo = $CorporateID and Status = 'Rejected'";
	$response = _getTableRecords($conn,'corporate_tickets', $where);
	return $response;
}

function GetApporoveTicketDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$ticket_details = _getTableDetails($conn,'corporate_tickets', $where);
	return $ticket_details;
}

?>