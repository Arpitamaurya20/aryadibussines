<?php

function normalizePhone($number) {
    if (empty($number)) return null;
    $n = preg_replace('/\D/', '', $number);
    return substr($n, -10); // last 10 digits only
}



 function sendWhatsAppMessageIs($data){
    $curl = curl_init();
      $phonenumber = preg_replace('/\D/', '', $data['phonenumber']);
      $phonenumber = substr($phonenumber, -10);
    $ticketNumber  = $data['ticket_number']; // {{1}}
    $otp           = $data['otp'];           // {{2}}
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode([
            "countryCode" => "+91",
            "phoneNumber" => $phonenumber,
            "type" => "Template",
            "template" => [
                "name" => "ticket_start_otp", // EXACT approved template name
                "languageCode" => "en",
                "bodyValues" => [
                    $ticketNumber, // {{1}}
                    $otp           // {{2}}
                ]
            ]
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic TVZPai1Sb3VRbE9fN3ltYWxNOTlRTWxFd0kwckt2NmFBak4zSUNEQnRaczo=',
            'Content-Type: application/json'
        ],
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}


 function sendWhatsAppMessageIc($data){
    $curl = curl_init();
      $phonenumber = preg_replace('/\D/', '', $data['phonenumber']);
      $phonenumber = substr($phonenumber, -10);
    $ticketNumber  = $data['ticket_number']; // {{1}}
    $otp           = $data['otp'];           // {{2}}
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode([
            "countryCode" => "+91",
            "phoneNumber" => $phonenumber,
            "type" => "Template",
            "template" => [
                "name" => "ticket_close_otp", // EXACT approved template name
                "languageCode" => "en",
                "bodyValues" => [
                    $ticketNumber, // {{1}}
                    $otp           // {{2}}
                ]
            ]
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic TVZPai1Sb3VRbE9fN3ltYWxNOTlRTWxFd0kwckt2NmFBak4zSUNEQnRaczo=',
            'Content-Type: application/json'
        ],
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}
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
            $TicketQuotationID=-1;
	        $quotation_details_where = " where TicketID = $TicketID";
			$quotation_details = _getTableDetails($conn,'corporate_ticket_quotation',$quotation_details_where);
			
			$row['QuoteApproved'] = 0;
			$row['TicketQuotationID'] = -1;
			if($quotation_details != null)
			{
				 $TicketQuotationID = $quotation_details['ID'];
				if($quotation_details['QuotationStatus'] == "Quote Approved")
				{
					$row['QuoteApproved'] = 1;
				}
			}

			

		$categories_array_raw = _getTableRecords($conn,'manage_categories','where 1');
		$categories_array = array();
		foreach($categories_array_raw as $category)
		{
			$ID = $category['ID'];
			$categories_array[$ID] = $category['CategoriesName'];
		}
		$sql = "SELECT a.*,b.CompanyName,b.ID AS CompanyID,c.BranchSite,c.BranchState,c.BranchAddress1,d.EquipmentName,d.Category,d.SubCategory,d.Make,d.Model,d.SNo from `corporate_tickets` a,company b,branch c,branch_assets d WHERE a.CorporateID = b.ID AND a.BranchID = c.ID and a.BranchAssetID = d.ID and a.ID = $TicketID";

		$result=mysqli_query($conn,$sql);
		if($result)
		{    
			
			$row = $result->fetch_assoc();
			$row['TicketQuotationID'] = $TicketQuotationID;
			$CategoryID = $row['Category'];
			$row['CategoryName'] = "Not Set";
			if(isset($categories_array[$CategoryID]))
			{
				$row['CategoryName'] = $categories_array[$CategoryID];
			}
			

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

		 if ($response['data']['Type'] == "Projects" || $response['data']['Type'] == "Supply") {
			    $sql = "SELECT TaskStatus FROM project_tasks WHERE TicketID = '$TicketID' AND IsActive = 1";
			    $result = $conn->query($sql);

			    $IsProjectCompleted = 1;

			    if ($result && $result->num_rows > 0) {
			        while ($row = $result->fetch_assoc()) {
			            $status = strtolower(trim($row['TaskStatus']));

			            // If any task is not completed, mark as not completed
			            if ($status == 'to start' || $status == 'in progress') {
			                $IsProjectCompleted = 0;
			                break;
			            }
			        }
			    } else {
			        $IsProjectCompleted = 0;
			    }
			}

		$sql = "SELECT a.*,b.CompanyName,b.ID as CompanyID,c.BranchSite,c.BranchState,c.BranchAddress1,c.BranchCode from `corporate_tickets` a,company b,branch c WHERE a.CorporateID = b.ID AND a.BranchID = c.ID and a.ID = $TicketID";
		//echo $sql;
		$result=mysqli_query($conn,$sql);
		if($result)
		{
			$row = $result->fetch_assoc();
			$row['Escalated'] = "yes";

			if ($response['data']['Type'] == "Projects" || $response['data']['Type'] == "Supply") {
	            $row['IsProjectCompleted'] = $IsProjectCompleted;
	        }
			$quotation_details_where = " where TicketID = $TicketID";
			$quotation_details = _getTableDetails($conn,'corporate_ticket_quotation',$quotation_details_where);
			$row['QuoteApproved'] = 0;
			$row['TicketQuotationID'] = -1;
			if($quotation_details != null)
			{
				$row['TicketQuotationID'] = $quotation_details['ID'];
				if($quotation_details['QuotationStatus'] == "Quote Approved")
				{
					$row['QuoteApproved'] = 1;
				}
			}
			
		}
		else
		{
			$error = mysqli_error($conn);
			echo $sql;
			echo $error;
		}
		$response['data'] = $row;
	}
	$where = " where TicketID = $TicketID And IsActive=1";
	$response_images = _getTableRecords($conn,'ticket_media', $where);
	//print_r($response_images);
	$response_final_images = array();
	$response['data']['PreImg'] = 0;
	$response['data']['PostImg'] = 0;
	foreach($response_images as $response_image)
	{
		//print_r($response_image);
		$temp =  $response_image;
		$temp['Image'] = "https://techxpertindia.in/admin/media/ticket_media/".$response_image['Image'];
		if($response_image['Action'] == "post_img")
		{
			$response['data']['PostImg'] = 1;
		}
		if($response_image['Action'] == "pre_img")
		{
			$response['data']['PreImg'] = 1;
		}
		array_push($response_final_images,$temp); 
	}
	// check for general service report
	$response['data']['GeneralServiceReport'] = 1;
	$response['data']['GeneralServiceReportID'] = -1;

	if($response['data']['Service']=='HVACC' || $response['data']['Service']=='CCTVV' )
	{
       $general_service_report_details = _getTableDetails($conn,'ppm_ticket_general_service_report','where TicketID = '.$TicketID);
			if($general_service_report_details != null)
			{
				$response['data']['GeneralServiceReportID'] = $general_service_report_details['ID'];
				if($general_service_report_details['ProblemReportedByClient'] == "" || $general_service_report_details['Observation'] == "" || $general_service_report_details['ActionTaken'] == "" || $general_service_report_details['ClientSignature'] == "")
				{
					$response['data']['GeneralServiceReport'] = 0;
				}
			}
			else
			{
				$response['data']['GeneralServiceReport'] = 0;
			}
	}
	else{
			$general_service_report_details = _getTableDetails($conn,'corporate_ticket_general_service_report','where TicketID = '.$TicketID);
			if($general_service_report_details != null)
			{
				$response['data']['GeneralServiceReportID'] = $general_service_report_details['ID'];
				if($general_service_report_details['ProblemReportedByClient'] == "" || $general_service_report_details['Observation'] == "" || $general_service_report_details['ActionTaken'] == "" || $general_service_report_details['ClientSignature'] == "")
				{
					$response['data']['GeneralServiceReport'] = 0;
				}
			}
			else
			{
				$response['data']['GeneralServiceReport'] = 0;
			}
	}
	
	$response['data']['ticket_images'] = $response_final_images;
	return $response;
}
function getGeneralServiceReportDetails($conn,$data)
{
	$TicketID = $data['TicketID'];
	$general_service_report_details = _getTableDetails($conn,'corporate_ticket_general_service_report','where TicketID = '.$TicketID);
	if($general_service_report_details != null)
	{
		if($general_service_report_details['ClientSignature'] != "")
		{
			$general_service_report_details['ClientSignature'] = "https://techxpertindia.in/admin/media/signature/".$general_service_report_details['ClientSignature'];
		}
		$response = $general_service_report_details;
	}
	else
	{
		// Get Ticket Message
		$ticket_details = _getTableDetails($conn,'corporate_tickets','where ID = '.$TicketID);
		$BranchID = $ticket_details['BranchID'];
		$branch_details = _getTableDetails($conn,'branch','where ID = '.$BranchID);
		$response['ProblemReportedByClient'] = $ticket_details['Message'];
		$response['Observation'] = "";
		$response['ActionTaken'] = "";
		$response['Remarks'] = "";
		$response['ClientRepresentative'] = $branch_details['SiteIncharge'];
		$response['ClientRepresentativeEmails'] = "";
		$response['ClientRepresentativeDesignation'] = "";
		$response['ClientRepresentativeContact'] = $branch_details['BranchMobile'];
		$response['ClientSignature'] = "";
		// Get Site Incharge - Client
		// Get Site Mobile Number
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

function ct_resolveStatusAfterReassign($conn, $ticket)
{
	$current = trim((string) ($ticket['Status'] ?? ''));
	if ($current !== 'Escalated') {
		return $current !== '' ? $current : 'Assigned';
	}

	$ticketPK = (int) ($ticket['ID'] ?? 0);
	if ($ticketPK <= 0) {
		return 'Assigned';
	}

	$prev = _getSQLDetails(
		$conn,
		"SELECT Status FROM corporate_ticket_status_history
         WHERE TicketID = '$ticketPK'
           AND Status NOT IN ('Escalated', 'Raised', 'PreAssigned')
         ORDER BY ID DESC
         LIMIT 1"
	);

	if (is_array($prev) && !empty($prev['Status'])) {
		return trim((string) $prev['Status']);
	}

	return 'Assigned';
}

function ct_reassignTicketToTechnician($conn, $data)
{
	$TicketID = isset($data['TicketID']) ? (int) $data['TicketID'] : 0;
	$NewAssigned = isset($data['AssignedTo']) ? (int) $data['AssignedTo'] : 0;
	$UserID = isset($data['UpdatedBy']) ? (string) $data['UpdatedBy'] : '';
	$remarksText = isset($data['Remarks']) ? trim((string) $data['Remarks']) : 'Ticket reassigned to technician';
	$remarks = mysqli_real_escape_string($conn, $remarksText);

	if ($TicketID <= 0 || $NewAssigned <= 0) {
		return array('error' => true, 'message' => 'Invalid ticket or technician');
	}

	$ticket = _getTableDetails($conn, 'corporate_tickets', " WHERE ID = $TicketID");
	if (!is_array($ticket) || empty($ticket['ID'])) {
		return array('error' => true, 'message' => 'Ticket not found');
	}

	$closedStatuses = array('Closed', 'Cancel', 'Cancelled');
	if (in_array((string) ($ticket['Status'] ?? ''), $closedStatuses, true)) {
		return array('error' => true, 'message' => 'Cannot reassign a closed or cancelled ticket');
	}

	$OldAssigned = (int) ($ticket['AssignedTo'] ?? 0);
	if ($OldAssigned === $NewAssigned) {
		$emp = getEmployeeDetailsfromID($conn, $OldAssigned);
		$name = is_array($emp) && !empty($emp['Name']) ? $emp['Name'] : 'this technician';
		return array(
			'error' => true,
			'message' => 'Please select a different technician. Ticket is already assigned to ' . $name . '.',
		);
	}

	$nowDate = date('Y-m-d');
	$nowTime = date('H:i:s');
	require_once __DIR__ . '/ticket_escalation_controller.php';
	$newDueDate = te_resolveDueDateAfterReassign($ticket, $data);
	$newDueDateSql = mysqli_real_escape_string($conn, $newDueDate);
	$preservedStatus = ct_resolveStatusAfterReassign($conn, $ticket);
	$preservedStatusSql = mysqli_real_escape_string($conn, $preservedStatus);

	$historyResult = RecordTicketHistory($conn, array(
		'TicketID' => $TicketID,
		'AssignedTo' => $NewAssigned,
		'Status' => $preservedStatus,
		'Remarks' => $remarksText,
		'CreatedDate' => $nowDate,
		'CreatedTime' => $nowTime,
		'CreatedBy' => $UserID,
	));

	if (isset($historyResult) && is_array($historyResult) && !empty($historyResult['error'])) {
		return array(
			'error' => true,
			'message' => 'Failed to save reassignment history: ' . ($historyResult['message'] ?? 'Unknown error'),
		);
	}

	_UpdateTableRecords(
		$conn,
		'corporate_ticket_status_history',
		"AssignedTo = '$NewAssigned'
         WHERE TicketID = '$TicketID'
           AND Status NOT IN ('Raised','PreAssigned')"
	);

	te_resolveEscalationsOnReassign($conn, $TicketID, $UserID, $remarksText);

	_UpdateTableRecords(
		$conn,
		'corporate_tickets',
		"Status = '$preservedStatusSql',
         AssignedTo = '$NewAssigned',
         Technician = '$NewAssigned',
         DueDate = '$newDueDateSql'
         WHERE ID = '$TicketID'"
	);

	return array(
		'error' => false,
		'message' => 'Ticket reassigned to technician. Escalation cleared. Status kept as ' . $preservedStatus . '. New due date: ' . $newDueDate . '.',
	);
}

function ct_wantsTechnicianReassign($data, $oldTicket, $isReassignFlag)
{
	$oldAssigned = (int) ($oldTicket['AssignedTo'] ?? 0);
	$newAssigned = isset($data['AssignedTo']) ? (int) $data['AssignedTo'] : 0;
	$newStatus = isset($data['TicketStatus']) ? trim((string) $data['TicketStatus']) : '';
	$oldStatus = trim((string) ($oldTicket['Status'] ?? ''));

	if ($isReassignFlag || $newStatus === '__REASSIGN__') {
		return true;
	}

	if ($oldAssigned > 0 && $newAssigned > 0 && $newAssigned !== $oldAssigned) {
		if ($newStatus === $oldStatus) {
			return true;
		}
		// Assignee changed without an intentional status change (e.g. Escalated missing from dropdown).
		if ($newStatus !== '' && $newStatus !== $oldStatus) {
			return false;
		}
		return true;
	}

	return false;
}

function ct_validateTechnicianReassignTarget($conn, $oldTicket, $newAssigned)
{
	$newAssigned = (int) $newAssigned;
	if ($newAssigned <= 0) {
		return array('error' => true, 'message' => 'Please select a technician');
	}

	$oldAssigned = (int) ($oldTicket['AssignedTo'] ?? 0);
	if ($oldAssigned > 0 && $oldAssigned === $newAssigned) {
		$emp = getEmployeeDetailsfromID($conn, $oldAssigned);
		$name = is_array($emp) && !empty($emp['Name']) ? $emp['Name'] : 'current technician';
		return array(
			'error' => true,
			'message' => 'Please select a different technician. Ticket is already assigned to ' . $name . '.',
		);
	}

	return array('error' => false);
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

   $CallType = "";
	if(isset($data['CallType']))
   {
   	$CallType = $data['CallType'];
   }

   $CustumerPrice = "";
	if(isset($data['CustumerPrice']))
   {
   	$CustumerPrice = $data['CustumerPrice'];
   }

   $ExpensePrice = "";
	if(isset($data['ExpensePrice']))
   {
   	$ExpensePrice = $data['ExpensePrice'];
   }

   $Description = "";
	if(isset($data['Description']))
   {
   	$Description = $data['Description'];
   }

   $current_date = date("Y-m-d");
   $current_time = date("H:i:s");

	$response = array();
	// $TicketID = $data['TicketID'];
	$old_ticket_data = getCorporateTicketDetail($conn, $data)['data'];

	$isReassign = isset($data['IsReassign']) && $data['IsReassign'] == 1;
	$wantsReassign = ct_wantsTechnicianReassign($data, $old_ticket_data, $isReassign);

	if ($wantsReassign) {
		@session_start();
		$session = isset($_SESSION) ? $_SESSION : array();
		require_once __DIR__ . '/ticket_escalation_controller.php';
		if (!te_userCanReassignTechnician($conn, $session, (int) $TicketID)) {
			return array(
				'error' => true,
				'message' => 'You are not authorized to reassign this ticket to a technician',
			);
		}
		$reassignCheck = ct_validateTechnicianReassignTarget(
			$conn,
			$old_ticket_data,
			isset($data['AssignedTo']) ? $data['AssignedTo'] : 0
		);
		if ($reassignCheck['error']) {
			return $reassignCheck;
		}
		return ct_reassignTicketToTechnician($conn, $data);
	}

	if(isset($data['Remarks']))
	{
		$Remarks = $data['Remarks'];
	}
	else
	{
		$Remarks = $old_ticket_data['Remarks'];
	}

	if(($old_ticket_data['Status'] == $data['TicketStatus'] && $old_ticket_data['AssignedTo'] == $data['AssignedTo'] && $old_ticket_data['DueDate'] == $data['DueDate'] && $old_ticket_data['CallType'] == $CallType && $old_ticket_data['CustumerPrice'] == $CustumerPrice && $old_ticket_data['ExpensePrice'] == $ExpensePrice && $old_ticket_data['Description'] == $Description && $old_ticket_data['Remarks'] == $Remarks) && $data['Status'] != 'Generate OTP to Start')
	{
		$response['message'] = "Change something!";
		$response['error'] = true;
	}
	else
	{
		// print_r($old_ticket_data);
		$Ticket_number = $old_ticket_data['TicketID'];
		$Old_TicketStatus = $old_ticket_data['Status'];
		$Remarks = $old_ticket_data['Remarks'];
		$TicketStatus = $data['TicketStatus'];
		$AssignedTo = $AssignedTo_raw = $data['AssignedTo'];	
		$DueDate = $data['DueDate'];
		if(isset($data['Remarks']))
		{
			$Remarks = $data['Remarks'];
		}
		if(($TicketStatus != 'Generate OTP to Start') && ($TicketStatus != 'Generate OTP to Close'))
		{
			if($TicketStatus == "")
			{
				$response['error'] = false;
				$response['message'] = "Ticket Status cant be blank";
				return $response;
			}

			else
			{
				$query_parameter = " Status = '$TicketStatus',AssignedTo = $AssignedTo,Technician = $AssignedTo,DueDate = '$DueDate',CallType = '$CallType',CustumerPrice = '$ExpensePrice',Description = '$Description',Remarks='$Remarks' where ID = $TicketID";
				$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
				if($response['error'] == false)
				{

				   if ($TicketStatus === 'Closed' || $TicketStatus === 'Cancel') {
						require_once __DIR__ . '/ticket_escalation_controller.php';
						$escBy = isset($data['UpdatedBy']) ? $data['UpdatedBy'] : 'system';
						te_resolveEscalationsOnClose($conn, (int) $TicketID, $escBy);
					}
					
					$data_history['TicketID'] = $TicketID;
				   $data_history['CreatedDate'] = $current_date;
				   $data_history['CreatedTime'] = $current_time;
				   if(isset($data['UpdatedBy']))
				   {
				   		$data_history['CreatedBy'] = $data['UpdatedBy'];
				   	}
				   	else
				   	{
				   		$data_history['CreatedBy'] = "";
				   	}
				   $data_history['Status'] = $TicketStatus;
				   $data_history['AssignedTo'] = $AssignedTo;
				   RecordTicketHistory($conn,$data_history);
					$response['message'] = "Ticket Updated!";

					if($TicketStatus == "Closed")
					{
						$CloseDate = date("Y-m-d");
						if(isset($_POST['CloseDate']))
						{
							if($CloseDate != "")
							{
								$CloseDate = $_POST['CloseDate'];
							}
						}
						$CloseTime = date("H:i:s");
						$query_parameter = " CloseDate = '$CloseDate',CloseTime = '$CloseTime' where ID = $TicketID";
						$response_update_closing = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
					}
				}
				else
				{
					return $response;
				}
			}
		}


		// generate ticket details
		if($old_ticket_data['Type'] == "R&M")
		{
			$ticket_message_details = "\nType - ".$old_ticket_data['Type']."\nService - ".$old_ticket_data['Service']."\nSub Service - ".$old_ticket_data['Subservice'];
		}
		else
		{
			$ticket_message_details = "\nType - ".$old_ticket_data['Type'];
		}

		if($old_ticket_data['ClientTicketID'] != "")
		{
			$ticket_message_details = $ticket_message_details."\nClient Ticket ID - ".$old_ticket_data['ClientTicketID'];
		}

		// Get Branch details from Ticket
		$BranchID = $old_ticket_data['BranchID'];
		$where = " where ID = $BranchID";
		$branch_Detail = _getTableDetails($conn,'branch',$where);
		$BranchPhone = $branch_Detail['BranchMobile'];
		$BranchAlternetPhone=$branch_Detail['BranchLandline'];
		$CityName = $branch_Detail['BranchCity'];
		$BranchCity = $branch_Detail['BranchSite'];
		$BranchSiteIncharge = $branch_Detail['SiteIncharge'];
		$BranchCode = $branch_Detail['BranchCode'];
		$BranchState = $branch_Detail['BranchState'];
		$BranchEmail = $branch_Detail['BranchEmail'];
		$ticket_branch_address = "\nBranch Site - ".$branch_Detail['BranchSite']."\nBranch Code - ".$branch_Detail['BranchCode']."\nBranch Address - ".$branch_Detail['BranchAddress1']."\nBranch City - ".$branch_Detail['BranchCity'];
		$ticket_branch_address_url = "";
		if($branch_Detail['Latitude'] != "" && $branch_Detail['Longitude'] != "")
		{

			$ticket_branch_address_url = "\nBranch Location - https://www.google.com/maps?q=".$branch_Detail['Latitude'].",".$branch_Detail['Longitude'];
		}
		$ticket_message_bm = "\nBranch Site SiteIncharge - $BranchSiteIncharge\nBranch Manager Contact - $BranchPhone";
		$BranchPhone = "+91".$BranchPhone;

		// Get Corporate Account List
		$CorporateID = $old_ticket_data['CorporateID'];
		$where = " where ID = $CorporateID";
		$company_Detail = _getTableDetails($conn,'company',$where);
		$CorporateEmail = $company_Detail['CompanyEmail'];
		$corporate_wa = 0;
		if($company_Detail['TicketNeedsWAMessage'] == 1)
		{
			$corporate_wa = 1;
			$CorporatePhone = $company_Detail['CompanyPhone'];
			$CorporateEmail = $company_Detail['CompanyEmail'];
			$CompanyName = $company_Detail['CompanyName'];
			$CompanyMobile = $company_Detail['CompanyMobile'];
			$CompanyEmail = $company_Detail['CompanyEmail'];
			if($CorporatePhone != "")
			{
				$Corporatephonenumber = "+91".$CorporatePhone;				
			}
		}
		
		// get employee details
		$where = " where ID = $AssignedTo";
		$employee_details = _getTableDetails($conn,'employees',$where);
		$EmployeeName = $employee_details['Name'];
		$EmployeePhone = $employee_details['ContactNumber'];
		$EmployeeEmail = $employee_details['Email'];
		$EmployeePhoneNumber = "+91".$EmployeePhone;
		$AssignedTo = $EmployeeName;
		$ticket_employee_details_message = "\nTechnician Name - ".$EmployeeName."\nTechnician Contact - ".$EmployeePhoneNumber;

		// Account Manager
		$ticket_message_abm = "";
		$AccountBranchManager = $branch_Detail['AccountBranchManager'];
		$ABM_PhoneNumber = "";
		if($AccountBranchManager != "" && $AccountBranchManager != -1)
		{
			
			$where_emp = " where ID = $AccountBranchManager";
			$result_emp = _getTableDetails($conn,'employees',$where_emp);
			$ABM_PhoneNumber = "+91".$result_emp['ContactNumber'];
			$ABM_Name = $result_emp['Name'];
			$ABM_Email = $result_emp['Email'];
			$ticket_message_abm = "\nAccount Branch Manager Name - $ABM_Name\nAccount Branch Manager Contact - $ABM_PhoneNumber";
			
		}

		// City Lead
		$where = " where CityName = '$CityName'";
		$result_city_lead = _getTableDetails($conn,'citydata',$where);
		$CorporateLead = $result_city_lead['CorporateLead'];
		$AssignedToCity = -1;
		if($CorporateLead != -1 && $CorporateLead != "")
		{
			$AssignedToCity = $CorporateLead;
			$where_emp = " where ID = $AssignedToCity";
		 	$result_emp = _getTableDetails($conn,'employees',$where_emp);
		 	$CL_PhoneNumber = "+91".$result_emp['ContactNumber'];
		 	$CL_Name = $result_emp['Name'];
		}

		// State OTP Numbers
		$StateNumberOTP = "";
		$where = " where State = '$BranchState'";
		$rows = _getTotalRows($conn,'state_otp_number',$where);
		if($rows > 0)
		{
			$result_state_otp =  _getTableDetails($conn,'state_otp_number',$where);
			$StateNumberOTP = "+91".$result_state_otp['ContactNumber'];
		}

	
		if($TicketStatus != $Old_TicketStatus)
		{
			if($TicketStatus == "Assigned")
			{
				
				// Send message to Technician
				$message = "Hello,\n\nTicket $Ticket_number has been Assigned to you. Ticket Details are as follows - \n$ticket_message_details".$ticket_branch_address.$ticket_branch_address_url;
				sendWhatsAppMessage($EmployeePhoneNumber,$message);

				$message = "Hello,\n\nTicket $Ticket_number has been Assigned. \nPlease find details - \n$ticket_employee_details_message".$ticket_message_details.$ticket_branch_address.$ticket_branch_address_url;
				sendWhatsAppMessage($BranchPhone,$message);
				if($corporate_wa == 1)
				{
					$message = "Hello,\n\nTicket $Ticket_number has been Assigned. \nPlease find details - \n$ticket_employee_details_message".$ticket_message_details.$ticket_branch_address.$ticket_branch_address_url;
					sendWhatsAppMessage($Corporatephonenumber,$message);
				}


			}
			else if($TicketStatus == "Generate OTP to Start")
			{
			   $data_history['TicketID'] = $TicketID;
			   $data_history['CreatedDate'] = $current_date;
			   $data_history['CreatedTime'] = $current_time;
			   $data_history['CreatedBy'] = $data['UpdatedBy'];
			   $data_history['Status'] = $TicketStatus;
			   $data_history['AssignedTo'] = $AssignedTo_raw;
			   RecordTicketHistory($conn,$data_history);

				$otp = generateTicketOTP();
				InsertTempTicketOTP($conn,$TicketID,$otp);

				// Send message to Technician
				$message = "Hello,\n\nTicket $Ticket_number OTP has been generated. Ticket Details are as follows - \n$ticket_message_details";
				$message = $message."\n\nYou can get the OTP from these numbers\n$ticket_message_abm".$ticket_message_bm;
				sendWhatsAppMessage($EmployeePhoneNumber,$message);
				       
          
				// Send Message to Branch Manager
				$message = "Hello,\n\nTicket $Ticket_number Start Work OTP has been generated. \nPlease find details - \n\nOTP - $otp\n\nAssigned Technician Details - $ticket_employee_details_message".$ticket_message_details.$ticket_branch_address.$ticket_branch_address_url;
				
				if($CorporateID != 183)
				{
					sendWhatsAppMessage($BranchPhone,$message);
					
				}
				else
				{
			        $emailPostData = [
			           'CorporateEmail' => $CorporateEmail, 
			           'BranchEmail' => $BranchEmail,
			           'subject' => "OTP Generated",
			            'message' => $message,
			            'action'=>'start_otpticket'
			           ];
	           		sendInnovMailRequest($emailPostData);
				}

				if($corporate_wa == 1)
				{
					if($CorporateID == 183)
				    {
				       
				    }
				    else 
				    {
						sendWhatsAppMessage($Corporatephonenumber,$message);
					} 					
				}

				// Send Message to Account Branch Manager


                sendWhatsAppMessage($ABM_PhoneNumber,$message);
				
				if($StateNumberOTP != "")
				{
					sendWhatsAppMessage($StateNumberOTP,$message);
				}
				sendWhatsAppMessage($BranchAlternetPhone,$message);
				// 3️⃣ Collect ALL WhatsApp numbers (roles)
				// normalizePhone($EmployeePhoneNumber),
				// normalizePhone($StateNumberOTP),
				// normalizePhone($Corporatephonenumber)  
				// normalizePhone($BranchAlternetPhone),  
					$waUsers = array_unique(array_filter([
						normalizePhone($BranchPhone),          // Branch
						normalizePhone($ABM_PhoneNumber), 
					]));
			if (!empty($waUsers)) {
    foreach ($waUsers as $phone) {
        sendWhatsAppMessageIs([
            'phonenumber'   => $phone,
            'ticket_number' => $Ticket_number,
            'otp'           => $otp
        ]);

        sleep(1); // 1 second delay
    }
}


				
				$response['error'] = false;
				$response['message'] = "OTP has been generated and been sent";
				$response['phonenumber']=$waUsers;


				

			}
	 		
			else if($TicketStatus == "Work In Progress")
			{
				
				// Send Message to Branch Manager
				$message = "Hello,\n\nTicket $Ticket_number is now in Progress. \nPlease find details - \n\nAssigned Technician Details - $ticket_employee_details_message".$ticket_message_details;
				// sendWhatsAppMessage($BranchPhone,$message);
				if($corporate_wa == 1)
				{
					// sendWhatsAppMessage($Corporatephonenumber,$message);
				}

				// Send Message to Account Branch Manager
				// sendWhatsAppMessage($ABM_PhoneNumber,$message);
				

			}
			else if($TicketStatus == "Generate OTP to Close")
			{
			   $data_history['TicketID'] = $TicketID;
			   $data_history['CreatedDate'] = $current_date;
			   $data_history['CreatedTime'] = $current_time;
			   $data_history['CreatedBy'] = $data['UpdatedBy'];
			   $data_history['Status'] = $TicketStatus;
			   $data_history['AssignedTo'] = $AssignedTo_raw;
			   RecordTicketHistory($conn,$data_history);

				$otp = generateTicketOTP();
				InsertTicketCloseOTP($conn,$TicketID,$otp);

				// Send message to Technician
				$message = "Hello,\n\nTicket $Ticket_number Closure OTP has been generated. Ticket Details are as follows - \n$ticket_message_details";
				$message = $message."\n\nYou can get the OTP from these numbers\n$ticket_message_abm".$ticket_message_bm;
				sendWhatsAppMessage($EmployeePhoneNumber,$message);

				// Send Message to Branch Manager
				$message = "Hello,\n\nTicket $Ticket_number Close Work OTP has been generated. \nPlease find details - \n\nOTP - $otp\n\nAssigned Technician Details - $ticket_employee_details_message".$ticket_message_details;
				if($CorporateID != 183)
				{
					sendWhatsAppMessage($BranchPhone,$message);
				}
				else
				{
					$emailPostData = [
			           'CorporateEmail' => $CorporateEmail, 
			           'BranchEmail' => $BranchEmail,
			           'subject' => "OTP Generated",
			            'message' => $message,
			            'action'=>'close_otpticket'
			           ];
	           		sendInnovMailRequest($emailPostData);
				}
				sendWhatsAppMessage($ABM_PhoneNumber,$message);
				sendWhatsAppMessage($BranchPhone,$message);
                if(!empty($BranchAlternetPhone))
                {
                	$BranchAlternetPhone="+91".$BranchAlternetPhone;
				    sendWhatsAppMessage($BranchAlternetPhone,$message);
                }
                  


				 /* ---------------- Collect ALL WhatsApp Numbers ---------------- */
				//  normalizePhone($StateNumberOTP),
				// normalizePhone($BranchAlternetPhone)
					$waUsers = array_unique(array_filter([
						normalizePhone($BranchPhone),
						normalizePhone($ABM_PhoneNumber)
						
					]));

					/* ---------------- Send WhatsApp to ALL (Loop) ---------------- */
					
					if (!empty($waUsers)) {
						foreach ($waUsers as $phone) {
							  sendWhatsAppMessageIc([
									'phonenumber'   => $phone,
									'ticket_number' => $Ticket_number,
									'otp'           => $otp
								]);

								sleep(1); // 1 second delay
						}
					}
				   

				// Send Message to Account Branch Manager
				// if($ABM_PhoneNumber != "")
				// {
				// 	sendWhatsAppMessage($ABM_PhoneNumber,$message);
				// }



				if($StateNumberOTP != "")
				{
						sendWhatsAppMessage($StateNumberOTP,$message);
				}
				$response['error'] = false;
				$response['message'] = "OTP has been generated and been sent";
				$response['phonenumber']=$waUsers;

			}
			else if($TicketStatus == "Closed")
			{
				// Send message to Technician
				$message = "Hello,\n\nTicket $Ticket_number has been Closed. Ticket Details are as follows - \n$ticket_message_details";
				//$message = $message."\n\nYou can get the OTP from these numbers\n$ticket_message_abm".$ticket_message_bm;
				// sendWhatsAppMessage($EmployeePhoneNumber,$message);

				// Send Message to Branch Manager
				$message = "Hello,\n\nTicket $Ticket_number has been Closed. \nPlease find details - \n\nAssigned Technician Details - $ticket_employee_details_message".$ticket_message_details.$ticket_branch_address.$ticket_branch_address_url;
				// sendWhatsAppMessage($BranchPhone,$message);
                if(!empty($BranchAlternetPhone))
                {
                	$BranchAlternetPhone="+91".$BranchAlternetPhone;
				    // sendWhatsAppMessage($BranchAlternetPhone,$message);
                }
				// if($corporate_wa == 1)
				// {


				// 	sendWhatsAppMessage($Corporatephonenumber,$message);
				// }

				if($corporate_wa == 1)
				{

					if($CorporateID == 183)
						    {
						       if($CorporateEmail != "") {
						        $emailPostData = [
						           'to' => $CorporateEmail, 
						           'subject' => "OTP Generated",
						            'message' => $message,
						            'action'=>'emailticket'
						           ];
						           sendInnovMailRequest($emailPostData);
						            
						        }
						    }
						    else {
							
							   
									// sendWhatsAppMessage($Corporatephonenumber,$message);
				
						} 

					
				}

				// Send Message to Account Branch Manager

				if($CorporateID == 183)
						    {
						       if($ABM_Email != "") {
						        $emailPostData = [
						           'to' => $ABM_Email, 
						           'subject' => "OTP Generated",
						            'message' => $message,
						            'action'=>'emailticket'
						           ];
						           sendInnovMailRequest($emailPostData);
						            
						        }
						    }
						    else {
							
							   if($ABM_PhoneNumber!="")
								{
									// sendWhatsAppMessage($ABM_PhoneNumber,$message);
								}
						} 
				  // sendWhatsAppMessage($ABM_PhoneNumber,$message);



				$response['error'] = false;
				$response['message'] = "Ticket is Closed";


			}
			else if($TicketStatus == "Cancel")
			{

				//$message = "Ticket Number - $Ticket_number has been Canceled.\n\nTicket Details \n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
				$message = "Ticket Number - $Ticket_number has been Canceled.\n\nTicket Details \n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.";
				 $BranchPhonenumber = "+91".$BranchPhone;
				 sendWhatsAppMessage($BranchPhonenumber,$message);
				 if($corporate_wa == 1)
				{
					sendWhatsAppMessage($Corporatephonenumber,$message);
				}
				sendWhatsAppMessage($ABM_PhoneNumber,$message);

			}
			else
			{

				// send message to Branch     
				//$message = "Dear $BranchSiteIncharge, \n\nYour Ticket Number is $Ticket_number. Your Ticket has been assigned.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $EmployeePhone\n\n📍 Location - $BranchCity\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
				$message = "Dear $BranchSiteIncharge, \n\nYour Ticket Number is $Ticket_number. Your Ticket has been assigned.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $EmployeePhone\n\n📍 Location - $BranchCity\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.";
		   		$BranchPhonenumber = "+91".$BranchPhone;
		   		sendWhatsAppMessage($BranchPhonenumber,$message);

				// send message to employee
				//$MessageForEmployee = "Dear $EmployeeName, \n\nThis is to inform you that we have a ticket that requires your attention. The ticket number is $Ticket_number.\n\nPlease Verify the ticket through OTP then start your work.\n\nTicket Details SOS\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $BranchPhone\n\n📍 Location - $BranchCity\n\n📍 Branch Code - $BranchCode\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nTechXpert Team";
				$MessageForEmployee = "Dear $EmployeeName, \n\nThis is to inform you that we have a ticket that requires your attention. The ticket number is $Ticket_number.\n\nPlease Verify the ticket through OTP then start your work.\n\nTicket Details SOS\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $BranchPhone\n\n📍 Location - $BranchCity\n\n📍 Branch Code - $BranchCode\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!";
		   		$EmployeePhoneNumber = "+91".$EmployeePhone;
		   		sendWhatsAppMessage($EmployeePhoneNumber,$MessageForEmployee);

						

				// send message to city lead 

				if($Citylead_PhoneNumber != "")
				{
					//$message = "Dear $EmployeeName, \n\nThis is to inform you the ticket $Ticket_number has been assigned.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo \n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nTechXpert Team";
					$message = "Dear $EmployeeName, \n\nThis is to inform you the ticket $Ticket_number has been assigned.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo \n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!";
					$phonenumber = "+91".$Citylead_PhoneNumber;
					sendWhatsAppMessage($phonenumber,$message);
				}
			}
	   }

		// $response = _api_changeBookingStatus($conn,$data);
	}

	
	
	return $response;
}

function SubmitTicketforClosure($conn,$TicketID,$data)
{
	// $TicketID = $data['TicketID'];
	$ticket_data = getCorporateTicketDetail($conn, $data)['data'];
	// Get Branch details from Ticket
	$BranchID = $ticket_data['BranchID'];
	$Ticket_name = $ticket_data['TicketID'];
	$where = " where ID = $BranchID";
	$branch_Detail = _getTableDetails($conn,'branch',$where);
	$BranchPhone = $branch_Detail['BranchMobile'];
	$CityName = $branch_Detail['BranchCity'];
	$BranchCity = $branch_Detail['BranchSite'];
	$BranchSiteIncharge = $branch_Detail['SiteIncharge'];
	$BranchCode = $branch_Detail['BranchCode'];
	
	// get employee details
	$AssignedTo = $ticket_data['AssignedTo'];
	$where = " where ID = $AssignedTo";
	$employee_details = _getTableDetails($conn,'employees',$where);
	$EmployeeName = $employee_details['Name'];
	$EmployeePhone = $employee_details['ContactNumber'];
	$AssignedTo = $EmployeeName;

	// Change status and send whatsapp messages
   

   $query_parameter = " Status = 'Submitted for Closure' where ID = $TicketID";
	$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);

	if($BranchPhone != ""){
	    $MessageForFeedback = "Hello, \n\nYour Ticket Number is $Ticket_name. I wanted to let you know that your ticket has been resolved and closed. We hope that our team was able to assist you with your concern in a timely and satisfactory manner.\n\nAs part of our ongoing efforts to improve our services, we would love to hear your feedback on your experience with our support team. If you have a few minutes, please feel free to share any comments or suggestions you may have.\n\nFeeback Link- https://techxpertindia.in/customer-rating.php?ticket=$TicketID\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
		$BranchPhoneNumber = "+91".$BranchPhone;
		sendWhatsAppMessage($BranchPhoneNumber,$MessageForFeedback);
	}
	
}

/*function GetBranchDetailsbyID($conn,$BranchID)
{
   $where = " where ID = $BranchID";
   return _getTableDetails($conn,'branch', $where);
}
*/
function CreateCorporateTicket($conn,$data,$branch_details)
{
	$ticket_message_details = "";

   $BranchID = $data['BranchID'];
   if(isset($data['CorporateID']))
	{
   	$CorporateID = $data['CorporateID'];
   }
   else
   {
   	$where = " where ID = $BranchID";
   	$CorporateID = _getTableDetails($conn,'branch',$where)['CompanyID'];
   }
   if($BranchID != -1)
   {
      $POCPhoneNumber = $branch_details['BranchMobile'];
      $SiteIncharge = $branch_details['SiteIncharge'];
      $BranchSite = $branch_details['BranchSite'];
   }
   $BranchAssetID = $data['BranchAssetID'];

   $Type = $data['Type'];
   $ticket_message_details = $ticket_message_details."\nType - ".$Type;
   $Message = $data['Message'];
   $CreatedBy = $data['CreatedBy'];
   if($Type == "AMC" && $BranchAssetID != "" && $BranchAssetID != -1)
   {
   		$where = " where ID = $BranchAssetID";
   		$BranchAsset_details = _getTableDetails($conn,'branch_assets',$where);
   		$EquipmentName = $BranchAsset_details['EquipmentName'];
   		$SNo = $BranchAsset_details['SNo'];
   		$ticket_message_details = $ticket_message_details."\nBranch - ".$BranchSite."\nEquipment Name - ".$EquipmentName."\nSNo. - ".$SNo; 
   }
   
   $TicketDate = date('Y-m-d');
   $TicketTime = date('H:i:s');
   $Employee_PhoneNumber = "";
   $Service = "";
   if(isset($data['Service']))
   {
   	$Service = $data['Service'];
   	$ticket_message_details = $ticket_message_details."\nService - ".$Service;
   }
   $Subservice = "";
	if(isset($data["Subservice"]))
	{
		$Subservice = $data["Subservice"];
		$ticket_message_details = $ticket_message_details."\nSub Service - ".$Subservice;
	}

   $Priority = "";
   if(isset($data['Priority']))
   {
   	$Priority = $data['Priority'];

   }
   $ClientTicketID = "";
   if(isset($data['ClientTicketID']))
   {
   	$ClientTicketID = $data['ClientTicketID'];
   	$ticket_message_details = $ticket_message_details."\nTicket Reference - ".$ClientTicketID;
   }

   $AssignedTo = -1;
   $Status = "Raised";

	//Get City Branches
	$where = " where ID = $BranchID";
	$result_branch_details = _getTableDetails($conn,'branch',$where);
	$CityName = $result_branch_details['BranchCity'];
	$BranchSite = $result_branch_details['BranchSite'];
	$AssignedTo = -1;

	//Get Branch Account Manager
	$BranchAccountManager = $result_branch_details['AccountBranchManager'];
	$CorporateLead = "";
	if($BranchAccountManager == -1)
	{
		//Get City Corporate Lead
		$where = " where CityName = '$CityName'";
		$result_city_lead = _getTableDetails($conn,'citydata',$where);
		$CorporateLead = $result_city_lead['CorporateLead'];
		
		if($CorporateLead != -1 && $CorporateLead != "")
		{
			$AssignedTo = $CorporateLead;
		}
	}
	else
	{
		$AssignedTo = $BranchAccountManager;
	}

	if($AssignedTo != -1 && $AssignedTo != "")
	{
		$where_emp = " where ID = $AssignedTo";
		$result_emp = _getTableDetails($conn,'employees',$where_emp);
		$Employee_PhoneNumber = $result_emp['ContactNumber'];
		$EmployeeName = $result_emp['Name'];
	}
	if($AssignedTo == "")
	{
		$AssignedTo = -1;
	}

   $sql = "INSERT INTO corporate_tickets(CorporateID,BranchID,Type,BranchAssetID,Service,Subservice,Message,ClientTicketID,Description,CreatedDate,CreatedTime,CreatedBy,AssignedTo,Priority,Status,Remarks) VALUES ($CorporateID,$BranchID,'$Type',$BranchAssetID,'$Service','$Subservice','$Message','$ClientTicketID','','$TicketDate','$TicketTime','$CreatedBy',$AssignedTo,'$Priority','$Status','')";
   $insert_ticket = _InsertTableRecords($conn,$sql);
   $ID = $insert_ticket['last_insert_id'];
   $TicketIDNN=$insert_ticket['last_insert_id'];
   $formatted_id = sprintf('%06d', $ID);
   if($Type == "AMC")
   {
      $TicketID = "CS-AMC-".$formatted_id;
   }
   else
   {
      $TicketID = "CS-RM-".$formatted_id;
   }

   $query_parameter = " TicketID = '$TicketID' where ID = $ID";
   _UpdateTableRecords($conn,'corporate_tickets',$query_parameter);

   // Insert into history
   $data_history['TicketID'] = $ID;
   $data_history['CreatedDate'] = $TicketDate;
   $data_history['CreatedTime'] = $TicketTime;
   $data_history['CreatedBy'] = $CreatedBy;
   $data_history['Status'] = $Status;
   $data_history['AssignedTo'] = $AssignedTo;
   RecordTicketHistory($conn,$data_history);
   $POCEmail = $result_branch_details['BranchEmail'];
   if($CorporateID == 183)
    {
       $message = "Dear $SiteIncharge,\n\nTicket with number $TicketID has been raised succesfully!\n\nTicket Details are as follows - $ticket_message_details\n\nWarm regards,\n Innov Team";

       if($POCEmail != "") {
        $emailPostData = [
           'to' => $POCEmail, 
           'subject' => "Ticket has been raised ",
            'message' => $message,
            'action'=>'emailticket'
           ];
           sendInnovMailRequest($emailPostData);
            
        }
    }

   $response['error'] = false;
   $response['message'] = "Ticket with number $TicketID has been raised succesfully!";
   $response['last_insert_id']=$insert_ticket['last_insert_id'];
   $response['TicketIDNew']=$TicketIDNN;

   //$message = "Dear $SiteIncharge,\n\nTicket with number $TicketID has been raised succesfully!\n\nTicket Details are as follows - $ticket_message_details\n\nWarm regards,\nTechXpert Team";
   $message = "Dear $SiteIncharge,\n\nTicket with number $TicketID has been raised succesfully!\n\nTicket Details are as follows - $ticket_message_details";
   $POCPhoneNumber = "+91".$POCPhoneNumber;
   sendWhatsAppMessage($POCPhoneNumber,$message);


    if (isset($data['TempImageID'])) 
    {
		$ImageID = $data['TempImageID'];
	   $where = " where TempImageID = $ImageID";
		$TempImageData = _getTableRecords($conn, 'temp_capture_image', $where);
	   foreach($TempImageData as $ImageDataValue)
	   {
			$Action = "Raised Ticket";
	    	$filename =  $ImageDataValue['Image'];     
	    	$ticket_media_query = "INSERT INTO ticket_media (TicketID,Action,Image,CreatedBy,CreatedDate,CreatedTime ) VALUES('$ID','$Action','$filename','$CreatedBy','$CreatedDate','$CreatedTime')";
	    	$media_response  = _InsertTableRecords($conn, $ticket_media_query);
	    }
		}

		// Send Message to City Lead
	   if($Employee_PhoneNumber != "")
		{
			//$message = "Dear $EmployeeName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n$CityName $ticket_message_details\n\n Please take approporate action !\n\nWarm regards,\nTechXpert Team";
			$message = "Dear $EmployeeName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n$CityName $ticket_message_details\n\n Please take approporate action !";
		   $phonenumber = "+91".$Employee_PhoneNumber;
			sendWhatsAppMessage($phonenumber,$message);
		}

		// Send Message to Branch Account Manager
		$AccountBranchManager = $branch_details['AccountBranchManager'];
		if($AccountBranchManager != "" && $AccountBranchManager != -1)
		{
			if($AccountBranchManager != $CorporateLead)
			{
				$where_emp = " where ID = $AccountBranchManager";
				$result_emp = _getTableDetails($conn,'employees',$where_emp);
				$Employee_PhoneNumber = $result_emp['ContactNumber'];
				$EmployeeName = $result_emp['Name'];
				if($Employee_PhoneNumber != "")
				{
					//$message = "Dear $EmployeeName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n$CityName $ticket_message_details\n\n Please take approporate action !\n\nWarm regards,\nTechXpert Team";
					$message = "Dear $EmployeeName,\n\nWe have received a ticket with following details:\n\n🧰 $TicketID \n📍 $BranchSite\n$CityName $ticket_message_details\n\n Please take approporate action !";
				   $phonenumber = "+91".$Employee_PhoneNumber;
					sendWhatsAppMessage($phonenumber,$message);
				}
			}
		}
	   

   // Get corporate details from Ticket

	$where = " where ID = $CorporateID";
	$company_Detail = _getTableDetails($conn,'company',$where);
	if($company_Detail['TicketNeedsWAMessage'] == 1)
	{
		$CorporatePhone = $company_Detail['CompanyPhone'];
		$CompanyName = $company_Detail['CompanyName'];
		$CompanyMobile = $company_Detail['CompanyMobile'];
		if($CorporatePhone != "")
		{
			//$message = "Dear $CompanyName,\n\nWe have received a ticket that requires your approval.\n\nPlease take appropriate action !\n\nTicket details:\n\n🧰 $TicketID \n📍 $BranchSite\n\nWarm regards,\nTechXpert Team";
			//$message = "$CompanyName,\n\nTicket with number $TicketID has been raised succesfully!\n\nTicket Details are as follows - $ticket_message_details\n\nWarm regards,\nTechXpert Team";
			$message = "$CompanyName,\n\nTicket with number $TicketID has been raised succesfully!\n\nTicket Details are as follows - $ticket_message_details";
		   $Corporatephonenumber = "+91".$CorporatePhone;
			sendWhatsAppMessage($Corporatephonenumber,$message);
		}
	}
   return $response;
}

function getCorporateTicketStatusArray($conn)
{
	$where  = " where IsActive = 1";
	$response = _getTableRecords($conn,'corporate_tickets_status', $where);
	return $response;
}

function getCorporateTicketStatusArray_CityLead($conn)
{
	$where  = " where IsActive = 1 and CityLead = 1";
	$response = _getTableRecords($conn,'corporate_tickets_status', $where);
	return $response;
}

function escalateTicket_old($conn,$data)
{
	$TicketID = $data['TicketID'];
	$query_parameter = " Status = 'Escalated' where ID = $TicketID";
	$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
	return $response;
}

function escalateTicket($conn,$data)
{
	require_once __DIR__ . '/ticket_escalation_controller.php';
	$TicketID = (int) $data['TicketID'];
	$ticket = _getTableDetails($conn, 'corporate_tickets', " WHERE ID = $TicketID");
	if (!is_array($ticket)) {
		return array('error' => true, 'message' => 'Ticket not found');
	}
	if (!te_isTicketEligibleForEscalation($ticket)) {
		$query_parameter = " Status = 'Escalated' where ID = $TicketID";
		return _UpdateTableRecords($conn, 'corporate_tickets', $query_parameter);
	}
	return te_processTicketEscalation($conn, $TicketID, 'api');
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

function UpdateClientID($conn,$TicketID,$data)
{

	$ClientTicketID = $data['ClientTicketID'];
	$where = " ClientTicketID = '$ClientTicketID' where ID = $TicketID";
	$response = _UpdateTableRecords($conn, 'corporate_tickets', $where);
	return $response;
}

function UpdateSparePart($conn,$TicketID,$data)
{

	$SparePart = $data['SparePartDecs'];
	$where = " SparePart = '$SparePart' where ID = $TicketID";
	$response = _UpdateTableRecords($conn, 'corporate_tickets', $where);
	return $response;
}


function getBranchByCorporateID($conn,$CorporateID)
{
	$where = "where CompanyID = $CorporateID";
	$response = _getTableRecords($conn, 'branch', $where);
	return $response;
}

function getRatingByTicketID($conn,$TicketID)
{
	$where = " where TicketID = '$TicketID'";
	$response = _getTableDetails($conn, 'customer_rating', $where);
	return $response;
}

function UpdateTicketFinance($conn,$data)
{
   $TicketID = $data['TicketID'];
	$CallType = $data['CallType'];
	$CustumerPrice = $data['CustumerPrice'];
	$ExpensePrice = $data['ExpensePrice'];
	$Description = $data['Description'];
	
	$response = array();

   $old_ticket_data = getCorporateTicketDetail($conn, $data)['data'];
   $BranchID = $old_ticket_data['BranchID'];
   $TicketIDCode = $old_ticket_data['TicketID'];

   if($old_ticket_data['CallType'] == $data['CallType'] && $old_ticket_data['CustumerPrice'] == $data['CustumerPrice'] && $old_ticket_data['ExpensePrice'] == $data['ExpensePrice'] && $old_ticket_data['Description'] == $data['Description'])
	{
		$response['message'] = "No Changes Found!";
		$response['error'] = true;
	}
	else
	{
		$where = " CallType = '$CallType', CustumerPrice = '$CustumerPrice', ExpensePrice = '$ExpensePrice', Description = '$Description'  where ID = $TicketID";
		$response = _UpdateTableRecords($conn, 'corporate_tickets', $where);
	   $response['message'] = "Ticket Finance Details Has been Updated!";
		$response['error'] = true;

	}

	

	return $response;
}


function QuotationUpload($conn,$data){
	$TicketID = $data['TicketID'];
	$CreatedBy = $data['CreatedBy'];
   $CreatedDate = date('Y-m-d');
   $CreatedTime = date('H:i:s');

   $quotation_upload = "";

   $old_ticket_data = getCorporateTicketDetail($conn, $data)['data'];
   $BranchID = $old_ticket_data['BranchID'];
   $Message_TicketID = $old_ticket_data['TicketID'];
	// print_r($old_ticket_data);
	if (isset($_FILES['quotation_upload']['name'])  && $_FILES['quotation_upload']['name'] != '')
	   {
	      $extn_pan = ($_FILES["quotation_upload"]["name"]);
	      $quotation_upload   = $TicketID.$extn_pan;
	      $path = "../media/".$quotation_upload;
	      //echo "<br>".$path;
	      move_uploaded_file($_FILES["quotation_upload"]["tmp_name"], $path);
	      $where = " QuotationUpload = '$quotation_upload' where ID = '$TicketID'";
	      $response = _UpdateTableRecords($conn, 'corporate_tickets', $where);
	   }

	$ticket_quotation_sql = "INSERT INTO ticket_quotation(TicketID,BranchID,Quotation,Status,CreatedBy,CreatedDate,CreatedTime) VALUES ('$TicketID',$BranchID,'$quotation_upload','Approval Pending','$CreatedBy','$CreatedDate','$CreatedTime')";
	$response = _InsertTableRecords($conn,$ticket_quotation_sql);

	$where = " where ID = $BranchID";
	$branch_Detail = _getTableDetails($conn,'branch',$where);
	$BranchPhone = $branch_Detail['BranchMobile'];
	$BranchSiteIncharge = $branch_Detail['SiteIncharge'];
	if($BranchID != ""){

		//$message = "Dear $BranchSiteIncharge,\n\nTicket Number $Message_TicketID. Please Check Quotation Has been Uploaded.\n\nPlease take appropriate action !\n\nWarm regards,\nTechXpert Team";
		$message = "Dear $BranchSiteIncharge,\n\nTicket Number $Message_TicketID. Please Check Quotation Has been Uploaded.\n\nPlease take appropriate action !";
	    $BranchMobilenumber = "+91".$BranchPhone;
		sendWhatsAppMessage($BranchMobilenumber,$message);
	}


	 if($old_ticket_data['QuotationStatus'] == "NA" || $old_ticket_data['QuotationStatus'] == "Rejected")
		{
			$QuotationStatus = "Approval Pending";
			$query_parameter = " QuotationStatus = '$QuotationStatus' where ID = '$TicketID'";
			$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
		}

	return $response;

}


function InsertTempTicketOTP($conn,$ticketid,$otp)
{
	$query_parameter = " TicketOTP = '$otp' where ID = '$ticketid'";
	$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
	return $response;
}

function InsertTicketCloseOTP($conn,$ticketid,$otp)
{
	$query_parameter = " TicketCloseOTP = '$otp' where ID = '$ticketid'";
	$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
	return $response;
}


function generateTicketOTP()
{
	$otp = "";
  	for ($i = 0; $i < 4; $i++) {
    	$otp .= rand(0, 9);
  	}
  	return $otp;
}


function getMediaTicketImage($conn,$TicketID)
{
	$where  = " where TicketID = '$TicketID' ";
	$response = _getTableRecords($conn,'ticket_media', $where);
	return $response;
}

function InserTicketConversation($conn,$data){
	$TicketID = $data['TicketID'];
	$Message = $data['message'];
	$CreatedBy = $data['CreatedBy'];
   $CreatedDate = date('Y-m-d');
   $CreatedTime = date('H:i:s');

	$ticket_quotation_sql = "INSERT INTO ticket_conversation(TicketID,Message,CreatedBy,CreatedDate,CreatedTime) VALUES ('$TicketID','$Message','$CreatedBy','$CreatedDate','$CreatedTime')";
	$response = _InsertTableRecords($conn,$ticket_quotation_sql);

	return $response;

}

function getTicketConversation($conn,$TicketID)
{
	$where  = " where TicketID = '$TicketID' ";
	$response = _getTableRecords($conn,'ticket_conversation', $where);
	return $response;
}

function RejectQoutation($conn,$data)
{

   $TicketID = $data['ID'];
	$Status = "Rejected";
	$where = " Status = '$Status' where TicketID = $TicketID";
	$response = _UpdateTableRecords($conn, 'ticket_quotation', $where);
	$where = " QuotationStatus = '$Status' where ID = $TicketID";
	$response = _UpdateTableRecords($conn, 'corporate_tickets', $where);
	return $response;
}

function ApproveQuotation($conn,$data)
{
   $TicketID = $data['ID'];
	$Status = "Approved";
	// $where = " Status = '$Status' where TicketID = $TicketID";
	// $response = _UpdateTableRecords($conn, 'ticket_quotation', $where);
	$where = " QuotationStatus = '$Status' where ID = $TicketID";
	$response = _UpdateTableRecords($conn, 'corporate_tickets', $where);
	return $response;
}

function GetAllRejectedQoutationByTicketID($conn,$TicketID)
{
   $where  = " where Status = 'Rejected' and TicketID = '$TicketID' ";
	$response = _getTableRecords($conn,'ticket_quotation', $where);
	return $response;
}


function InserCorporateFinance($conn,$data){
	$TicketID = $data['TicketID'];
	$T_VisitorNo = $data['T_VisitorNo'];
	$T_VisitCharge = $data['T_VisitCharge'];
	$T_MaterialCost = $data['T_MaterialCost'];
	$T_LabourCost = $data['T_LabourCost'];
	//$T_CostumerPrice = $data['T_CostumerPrice'];
	$T_TotalPrice = $data['T_TotalPrice'];

	$C_VisitorNo = $data['C_VisitorNo'];
	$C_VisitCharge = $data['C_VisitCharge'];
	$C_MaterialCost = $data['C_MaterialCost'];
	$C_LabourCost = $data['C_LabourCost'];
	//$C_CostumerPrice = $data['C_CostumerPrice'];
	$C_TotalPrice = $data['C_TotalPrice'];

	$Status = $data['Status'];

	$CreatedBy = $data['CreatedBy'];
	$CreatedDate = date('Y-m-d');
	$CreatedTime = date('H:i:s');

	$ticket_finance_sql = "INSERT INTO corporate_tickets_finance(TicketID,T_VisitorNo,T_VisitCharge,T_MaterialCost,T_LabourCost,T_TotalPrice,C_VisitorNo,C_VisitCharge,C_MaterialCost,C_LabourCost,C_TotalPrice,Status,Remarks,CreatedDate,CreatedTime) VALUES ('$TicketID','$T_VisitorNo','$T_VisitCharge','$T_MaterialCost','$T_LabourCost','$T_TotalPrice','$C_VisitorNo','$C_VisitCharge','$C_MaterialCost','$C_LabourCost','$C_TotalPrice','$Status','','$CreatedDate','$CreatedTime')";
	$response = _InsertTableRecords($conn,$ticket_finance_sql);

	return $response;

}

function GetCorporateFinanceTicketID($conn,$TicketID)
{
   $where  = " where TicketID = '$TicketID' ";
	$response = _getTableDetails($conn,'corporate_tickets_finance', $where);
	return $response;
}





// function ManageTicketAssignmentStatus2($conn,$data)
// {
// 	$TicketID = "";
// 	if(isset($data['TicketID']))
// 	{
// 		$TicketID = $data['TicketID'];
// 	}

// 	$DueDate = "";
// 	if(isset($data['DueDate']))
//    {
//    	$DueDate = $data['DueDate'];
//    }

//    $CallType = "";
// 	if(isset($data['CallType']))
//    {
//    	$CallType = $data['CallType'];
//    }

//    $CustumerPrice = "";
// 	if(isset($data['CustumerPrice']))
//    {
//    	$CustumerPrice = $data['CustumerPrice'];
//    }

//    $ExpensePrice = "";
// 	if(isset($data['ExpensePrice']))
//    {
//    	$ExpensePrice = $data['ExpensePrice'];
//    }

//    $Description = "";
// 	if(isset($data['Description']))
//    {
//    	$Description = $data['Description'];
//    }

// 	$response = array();
// 	// $TicketID = $data['TicketID'];
// 	$old_ticket_data = getCorporateTicketDetail($conn, $data)['data'];
// 	$Old_TicketStatus = $old_ticket_data['Status'];
// 	$Ticket_number = $old_ticket_data['TicketID'];
// 	if($old_ticket_data['AssignedTo'] == $data['AssignedTo'] && $old_ticket_data['DueDate'] == $data['DueDate'] && $old_ticket_data['CallType'] == $data['CallType'] && $old_ticket_data['CustumerPrice'] == $data['CustumerPrice'] && $old_ticket_data['ExpensePrice'] == $data['ExpensePrice'] && $old_ticket_data['Description'] == $data['Description'])
// 	{
// 		$response['message'] = "Either change Status or Assigned Employee!";
// 		$response['error'] = true;
// 	}
// 	else{

// 		if ($Old_TicketStatus == "Raised")
// 		{
// 			// print_r($old_ticket_data);
// 			$TicketStatus = "Assigned to Technician";
// 			$AssignedTo = $data['AssignedTo'];
// 			$query_parameter = " Status = '$TicketStatus',AssignedTo = $AssignedTo,DueDate = '$DueDate',CallType = '$CallType',CustumerPrice = '$ExpensePrice',Description = '$Description' where ID = $TicketID";
// 			$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
// 			if($response['error'] == false)
// 			{
// 				$response['message'] = "Ticket Updated!";
// 			}


// 			// $response = _api_changeBookingStatus($conn,$data);
// 		}

// 		if($Old_TicketStatus == "Assigned to Technician")
// 		{

// 			$TicketStatus = "Work In Progress";
// 			$AssignedTo = $data['AssignedTo'];
// 			$query_parameter = " Status = '$TicketStatus',AssignedTo = $AssignedTo,DueDate = '$DueDate',CallType = '$CallType',CustumerPrice = '$ExpensePrice',Description = '$Description' where ID = $TicketID";
// 			$response = _UpdateTableRecords($conn,'corporate_tickets', $query_parameter);
// 			if($response['error'] == false)
// 			{
// 				$response['message'] = "Ticket Updated!";
// 			}
			
// 		}

// 		// send whatsapp message

// 		// Get Branch details from Ticket
// 		$BranchID = $old_ticket_data['BranchID'];
// 		$where = " where ID = $BranchID";
// 		$branch_Detail = _getTableDetails($conn,'branch',$where);
// 		$BranchPhone = $branch_Detail['BranchMobile'];
// 		$CityName = $branch_Detail['BranchCity'];
// 		$BranchCity = $branch_Detail['BranchSite'];
// 		$BranchSiteIncharge = $branch_Detail['SiteIncharge'];


// 		$otp = generateOTP($BranchPhone);
// 		InsertTempTicketOTP($conn,$TicketID,$otp);
// 		$message = "Hello, \n\n Please use OTP $otp to login into TechXpert App";
// 		$BranchPhone = "+91".$BranchPhone;
// 		sendWhatsAppMessage($BranchPhone,$message);

// 		// get employee details

// 		$where = " where ID = $AssignedTo";
// 		$employee_details = _getTableDetails($conn,'employees',$where);
// 		$EmployeeName = $employee_details['Name'];
// 		$EmployeePhone = $employee_details['ContactNumber'];

// 		if($TicketStatus != $Old_TicketStatus)
// 		{

// 			 // Ticket Status closed
// 			 	if($TicketStatus == "Closed")
// 				{
// 					$MessageForFeedback = "Hello, \n\nYour Ticket Number is $Ticket_number. I wanted to let you know that your ticket has been resolved and closed. We hope that our team was able to assist you with your concern in a timely and satisfactory manner.\n\nAs part of our ongoing efforts to improve our services, we would love to hear your feedback on your experience with our support team. If you have a few minutes, please feel free to share any comments or suggestions you may have.\n\nFeeback Link- https://techxpertindia.in/customer-rating.php?ticket=$Ticket_number\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
// 				   	$BranchPhoneNumber = "+91".$BranchPhone;
// 			   	sendWhatsAppMessage($BranchPhoneNumber,$MessageForFeedback);

// 			   		// send message to employee
// 						$MessageForEmployee = "Dear $EmployeeName, \n\nThe Ticket has been Closed. The ticket number is $Ticket_number.\n\nTicket Details SOS\n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nTechXpert Team";
// 					   $EmployeePhoneNumber = "+91".$EmployeePhone;
// 					   sendWhatsAppMessage($EmployeePhoneNumber,$MessageForEmployee);


// 				}else{



// 							// send message to Branch
// 				            $AssignedTo = $EmployeeName;
// 								$message = "Dear $BranchSiteIncharge, \n\nYour Ticket Number is $Ticket_number. Your Ticket has been assigned to one of our Technician.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $EmployeePhone\n\n📍 Location - $BranchCity\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
// 					   		$BranchPhonenumber = "+91".$BranchPhone;
// 					   		sendWhatsAppMessage($BranchPhonenumber,$message);

// 							// send message to employee
// 							$MessageForEmployee = "Dear $EmployeeName, \n\nThis is to inform you that we have a ticket that requires your attention. The ticket number is $Ticket_number.\n\nTicket Details SOS\n\n🧰 Status - $TicketStatus\n\n☎ Mobile No. - $BranchPhone\n\n📍 Location - $BranchCity\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nTechXpert Team";
// 					   		$EmployeePhoneNumber = "+91".$EmployeePhone;
// 					   		sendWhatsAppMessage($EmployeePhoneNumber,$MessageForEmployee);

// 							// Get City Corporate Lead
// 							$where = " where CityName = '$CityName'";
// 							$result_city_lead = _getTableDetails($conn,'citydata',$where);
// 							$CorporateLead = $result_city_lead['CorporateLead'];
// 							$AssignedToCity = -1;
// 							if($CorporateLead != -1 && $CorporateLead != "")
// 							{
// 							$AssignedToCity = $CorporateLead;
// 							$where_emp = " where ID = $AssignedToCity";
// 							 $result_emp = _getTableDetails($conn,'employees',$where_emp);
// 							 $Citylead_PhoneNumber = $result_emp['ContactNumber'];
// 							 $EmployeeName = $result_emp['Name'];

							

// 							}

// 							// send message to city lead 

// 							if($Citylead_PhoneNumber != "")
// 							{
// 							$message = "Dear $EmployeeName, \n\nThis is to inform you that we have a ticket that requires your attention. The ticket number is $Ticket_number.\n\nTicket Details SOS\n\n📍 Assigned To - $AssignedTo \n\n🧰 Status - $TicketStatus\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you in advance for your prompt assistance!\n\nRegards,\nTechXpert Team";
// 							$phonenumber = "+91".$Citylead_PhoneNumber;
// 							sendWhatsAppMessage($phonenumber,$message);
// 							}

// 				}

// 	   }

// 	}

	


	
	
// 	return $response;
// }

function RecordTicketHistory($conn,$data_history)
{
	$TicketID = mysqli_real_escape_string($conn, (string) $data_history['TicketID']);
	$AssignedTo = (int) $data_history['AssignedTo'];
	$CreatedDate = mysqli_real_escape_string($conn, (string) $data_history['CreatedDate']);
	$CreatedTime = mysqli_real_escape_string($conn, (string) $data_history['CreatedTime']);
	$CreatedBy = mysqli_real_escape_string($conn, (string) $data_history['CreatedBy']);
	$Status = mysqli_real_escape_string($conn, (string) $data_history['Status']);
	$Remarks = '';
	if (isset($data_history['Remarks'])) {
		$Remarks = mysqli_real_escape_string($conn, (string) $data_history['Remarks']);
	}

	$nextRow = _getSQLDetails($conn, 'SELECT COALESCE(MAX(`ID`), 0) + 1 AS next_id FROM corporate_ticket_status_history');
	$nextId = (int) ($nextRow['next_id'] ?? 1);
	if ($nextId <= 0) {
		$nextId = 1;
	}

	$sql = "INSERT INTO corporate_ticket_status_history(`ID`, `TicketID`, `AssignedTo`, `Status`, `Remarks`, `CreatedDate`, `CreatedTime`, `CreatedBy`)
	        VALUES ($nextId, '$TicketID', $AssignedTo, '$Status', '$Remarks', '$CreatedDate', '$CreatedTime', '$CreatedBy')";

	return _InsertTableRecords($conn, $sql);
}
function gethvacServiceReportDetails($conn,$data)
{

	$TicketID = $data['TicketID'];
	$sql="SELECT * FROM ppm_hvac_service_report ph LEFT JOIN ppm_ticket_general_service_report pg ON pg.TicketID = ph.TicketID 
	WHERE ph.TicketID = $TicketID";
	$general_service_report_details = _getSQLDetails($conn,$sql);
	// $general_service_report_details = _getTableDetails($conn,'ppm_hvac_service_report','where TicketID = '.$TicketID);
	if($general_service_report_details != null)
	{
		if($general_service_report_details['ClientSignature'] != "")
		{
			$general_service_report_details['ClientSignature'] = "https://techxpertindia.in/admin/media/signature/".$general_service_report_details['ClientSignature'];
		}
		$response = $general_service_report_details;
	}
	else
	{
		// Get Ticket Message
		$ticket_details = _getTableDetails($conn,'ppm_tickets','where ID = '.$TicketID);
		$BranchID = $ticket_details['BranchID'];
		$branch_details = _getTableDetails($conn,'branch','where ID = '.$BranchID);
		$response['ProblemReportedByClient'] = $ticket_details['Message'];
		$response['Observation'] = "";
		$response['ActionTaken'] = "";
		$response['Remarks'] = "";
		$response['ClientRepresentative'] = $branch_details['SiteIncharge'];
		$response['ClientRepresentativeEmails'] = "";
		$response['ClientRepresentativeDesignation'] = "";
		$response['ClientRepresentativeContact'] = $branch_details['BranchMobile'];
		$response['ClientSignature'] = "";
		// Get Site Incharge - Client
		// Get Site Mobile Number
	}
	return $response;
}


function getCCtvServiceReportDetails($conn,$data)
{

	$TicketID = $data['TicketID'];
	$sql="SELECT * FROM post_cctv_service_report ph LEFT JOIN ppm_ticket_general_service_report pg ON pg.TicketID = ph.TicketID 
	WHERE ph.TicketID = $TicketID
	";
	$general_service_report_details = _getSQLDetails($conn,$sql);
	// $general_service_report_details = _getTableDetails($conn,'ppm_hvac_service_report','where TicketID = '.$TicketID);
	if($general_service_report_details != null)
	{
		if($general_service_report_details['ClientSignature'] != "")
		{
			$general_service_report_details['ClientSignature'] = "https://techxpertindia.in/admin/media/signature/".$general_service_report_details['ClientSignature'];
		}
		$response = $general_service_report_details;
	}
	else
	{
		// Get Ticket Message
		$ticket_details = _getTableDetails($conn,'ppm_tickets','where ID = '.$TicketID);
		$BranchID = $ticket_details['BranchID'];
		$branch_details = _getTableDetails($conn,'branch','where ID = '.$BranchID);
		$response['ProblemReportedByClient'] = $ticket_details['Message'];
		$response['Observation'] = "";
		$response['ActionTaken'] = "";
		$response['Remarks'] = "";
		$response['ClientRepresentative'] = $branch_details['SiteIncharge'];
		$response['ClientRepresentativeEmails'] = "";
		$response['ClientRepresentativeDesignation'] = "";
		$response['ClientRepresentativeContact'] = $branch_details['BranchMobile'];
		$response['ClientSignature'] = "";
		// Get Site Incharge - Client
		// Get Site Mobile Number
	}
	return $response;
}


function getFASServiceReportDetails($conn,$data)
{
  $TicketID = $data['TicketID'];
	$sql="SELECT * FROM ppm_fire_extinguisher_service_report ph LEFT JOIN ppm_ticket_general_service_report pg ON pg.TicketID = ph.TicketID 
	WHERE ph.TicketID = $TicketID
	";
	$general_service_report_details = _getSQLDetails($conn,$sql);
	// $general_service_report_details = _getTableDetails($conn,'ppm_hvac_service_report','where TicketID = '.$TicketID);
	if($general_service_report_details != null)
	{
		if($general_service_report_details['ClientSignature'] != "")
		{
			$general_service_report_details['ClientSignature'] = "https://techxpertindia.in/admin/media/signature/".$general_service_report_details['ClientSignature'];
		}
		$response = $general_service_report_details;
	}
	else
	{
		// Get Ticket Message
		$ticket_details = _getTableDetails($conn,'ppm_tickets','where ID = '.$TicketID);
		$BranchID = $ticket_details['BranchID'];
		$branch_details = _getTableDetails($conn,'branch','where ID = '.$BranchID);
		$response['ProblemReportedByClient'] = $ticket_details['Message'];
		$response['Observation'] = "";
		$response['ActionTaken'] = "";
		$response['Remarks'] = "";
		$response['ClientRepresentative'] = $branch_details['SiteIncharge'];
		$response['ClientRepresentativeEmails'] = "";
		$response['ClientRepresentativeDesignation'] = "";
		$response['ClientRepresentativeContact'] = $branch_details['BranchMobile'];
		$response['ClientSignature'] = "";
		// Get Site Incharge - Client
		// Get Site Mobile Number
	}
	return $response;
}


function getUPSServiceReportDetails($conn,$data)
{
	$TicketID = $data['TicketID'];
	$sql="SELECT * FROM ppm_ups_service_report ph LEFT JOIN ppm_ticket_general_service_report pg ON pg.TicketID = ph.TicketID 
	WHERE ph.TicketID = $TicketID
	";
	$general_service_report_details = _getSQLDetails($conn,$sql);
	// $general_service_report_details = _getTableDetails($conn,'ppm_hvac_service_report','where TicketID = '.$TicketID);
	if($general_service_report_details != null)
	{
		if($general_service_report_details['ClientSignature'] != "")
		{
			$general_service_report_details['ClientSignature'] = "https://techxpertindia.in/admin/media/signature/".$general_service_report_details['ClientSignature'];
		}
		$response = $general_service_report_details;
	}
	else
	{
		// Get Ticket Message
		$ticket_details = _getTableDetails($conn,'ppm_tickets','where ID = '.$TicketID);
		$BranchID = $ticket_details['BranchID'];
		$branch_details = _getTableDetails($conn,'branch','where ID = '.$BranchID);
		$response['ProblemReportedByClient'] = $ticket_details['Message'];
		$response['Observation'] = "";
		$response['ActionTaken'] = "";
		$response['Remarks'] = "";
		$response['ClientRepresentative'] = $branch_details['SiteIncharge'];
		$response['ClientRepresentativeEmails'] = "";
		$response['ClientRepresentativeDesignation'] = "";
		$response['ClientRepresentativeContact'] = $branch_details['BranchMobile'];
		$response['ClientSignature'] = "";
		// Get Site Incharge - Client
		// Get Site Mobile Number
	}
	return $response;
}

function getEpServiceReportDetails($conn,$data)
{

	$TicketID = $data['TicketID'];
	$sql="SELECT * FROM ppm_ep_service_report ph LEFT JOIN ppm_ticket_general_service_report pg ON pg.TicketID = ph.TicketID 
	WHERE ph.TicketID = $TicketID
	";
	$general_service_report_details = _getSQLDetails($conn,$sql);
	// $general_service_report_details = _getTableDetails($conn,'ppm_hvac_service_report','where TicketID = '.$TicketID);
	if($general_service_report_details != null)
	{
		if($general_service_report_details['ClientSignature'] != "")
		{
			$general_service_report_details['ClientSignature'] = "https://techxpertindia.in/admin/media/signature/".$general_service_report_details['ClientSignature'];
		}
		$response = $general_service_report_details;
	}
	else
	{
		// Get Ticket Message
		$ticket_details = _getTableDetails($conn,'ppm_tickets','where ID = '.$TicketID);
		$BranchID = $ticket_details['BranchID'];
		$branch_details = _getTableDetails($conn,'branch','where ID = '.$BranchID);
		$response['ProblemReportedByClient'] = $ticket_details['Message'];
		$response['Observation'] = "";
		$response['ActionTaken'] = "";
		$response['Remarks'] = "";
		$response['ClientRepresentative'] = $branch_details['SiteIncharge'];
		$response['ClientRepresentativeEmails'] = "";
		$response['ClientRepresentativeDesignation'] = "";
		$response['ClientRepresentativeContact'] = $branch_details['BranchMobile'];
		$response['ClientSignature'] = "";
		// Get Site Incharge - Client
		// Get Site Mobile Number
	}
	return $response;
}



function CheckTechnicianSafty($conn, $data)
{
    $TicketID             = $data['TicketID'];
    $Is_Uniform           = $data['Is_Uniform'];
    $Has_Jacket           = $data['Has_Jacket'];
    $Has_Toolkit_Isolated = $data['Has_Toolkit_Isolated'];
    $Has_Safety_Shoes     = $data['Has_Safety_Shoes'];
    $Has_Ppe_Kit          = $data['Has_Ppe_Kit'];
    $CreatedDate          = $data['CreatedDate'];
    $CreatedTime          = $data['CreatedTime'];
    $CreatedBy            = $data['CreatedBy'] ?? '';

    $sql = "INSERT INTO technician_safety_check
            (`TicketID`, `Is_Uniform`, `Has_Jacket`, `Has_Toolkit_Isolated`, `Has_Safety_Shoes`, `Has_Ppe_Kit`, `CreatedDate`, `CreatedTime`, `CreatedBy`, `IsActive`)
            VALUES
            ('$TicketID', '$Is_Uniform', '$Has_Jacket', '$Has_Toolkit_Isolated', '$Has_Safety_Shoes', '$Has_Ppe_Kit', '$CreatedDate', '$CreatedTime', '$CreatedBy', 1)";
    
    $insert_result = _InsertTableRecords($conn, $sql);

    if ($insert_result['last_insert_id']) {
        return [
            "error" => false,
            "message" => "Technician safety check inserted successfully",
            "last_insert_id" => $insert_result['last_insert_id']
        ];
    } else {
        return [
            "error" => true,
            "message" => "Failed to insert technician safety check"
        ];
    }
}

function getAmcTicketCartID($conn,$TicketID)
{
	$sql="Select CartID from sparepart_cart where TicketID=$TicketID And (Status='Pending' OR  Status='Submitted' OR Status='Verified') ";
	$cartID = _getSQLDetails($conn,$sql);
	return $cartID;
}

function getAmcBranchAssetsID($conn,$TicketID)
{
	$sql="Select BranchAssetID from corporate_tickets where ID=$TicketID";
	$BranchAssetID = _getSQLDetails($conn,$sql);
	return $BranchAssetID;
}

// function getSparePartStatus($conn, $TicketID)
// {
//     $sqlCheck = "SELECT COUNT(*) as cnt FROM sparepart_cart WHERE TicketID = '$TicketID'";
//     $checkResult = _getSQLDetails($conn, $sqlCheck);

//     if ($checkResult['cnt'] == 0) {
//         return "Pending";
//     }

//     $sql = "SELECT Status FROM sparepart_cart 
//             WHERE TicketID = '$TicketID' 
//             AND Status != 'Approved' 
//             LIMIT 1";

//     $result = _getSQLDetails($conn, $sql);

//     if (empty($result)) {
//         return "Approved";
//     }
//     return $result['Status'];
// }

function getSparePartStatus($conn, $TicketID)
{
    // STEP 1: Check if any record exists for this TicketID
    $stmtCheck = $conn->prepare("
        SELECT COUNT(*) as cnt 
        FROM sparepart_cart 
        WHERE TicketID = ?
    ");
    
    $stmtCheck->bind_param("s", $TicketID);
    $stmtCheck->execute();
    $checkResult = $stmtCheck->get_result()->fetch_assoc();

    if ($checkResult['cnt'] == 0) {
        return "Pending";
    }

    // STEP 2: Get ONLY the last inserted record
    // ⚠️ Replace 'id' with your actual primary key column (e.g., SparePartID)
    $stmt = $conn->prepare("
        SELECT Status 
        FROM sparepart_cart 
        WHERE TicketID = ? 
        ORDER BY id DESC 
        LIMIT 1
    ");

    $stmt->bind_param("s", $TicketID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // STEP 3: Return status
    if (empty($result)) {
        return "Pending";
    }

    return $result['Status'];
}

function AutoCreateQuotationFromCartItems($conn, $CorporateID, $TicketID, $CreatedBy)
{
    $corporateticket_obj = new Corporateticket($conn);

    $date = date('Y-m-d');
    $time = date('H:i:s');

    /* ===============================
       1️⃣ FETCH CART
    =============================== */
    $cart = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT *
        FROM corporate_quotation_cart
        WHERE CompanyID = $CorporateID
        AND IsActive = 1
        AND Status = 'Initialized'
        LIMIT 1
    "));

    if (!$cart) return;

    $CartID = $cart['ID'];

    /* ===============================
       2️⃣ FETCH CART ITEMS
    =============================== */
    $items = mysqli_query($conn, "
        SELECT *
        FROM corporate_quotation_cart_item
        WHERE CartID = $CartID
        AND IsActive = 1
    ");

    if (mysqli_num_rows($items) == 0) return;

    $TicketQuotationID = -1;

    /* ===============================
       3️⃣ LOOP CART ITEMS
    =============================== */
    while ($row = mysqli_fetch_assoc($items)) {

        $data = [
            'TicketQuotationID' => $TicketQuotationID,
            'TicketID'          => $TicketID,
            'LineItemID'        => $row['LineItemID'],
            'quantity'          => $row['Qty'],
            'CreatedBy'         => $CreatedBy,
            'CreatedDate'       => $date,
            'CreatedTime'       => $time,
            'QuotationStatus'   => 'Draft',
            'Remarks'           => ''
        ];

        /* ---- CREATE QUOTATION (FIRST ITEM) ---- */
        if ($TicketQuotationID == -1) {

            $quotation_response = $corporateticket_obj->UpdateTicketQuotation($data);
            if ($quotation_response['error']) return;

            $TicketQuotationID = $quotation_response['last_insert_id'];

            $data['QuotationID'] = $TicketQuotationID;
            $corporateticket_obj->UpdateTicketQuotationHistory($data);

        } else {
            $data['QuotationID'] = $TicketQuotationID;
        }

        /* ---- ADD LINE ITEM ---- */
        $corporateticket_obj->UpdateQuotationLineItem($data);
    }

    /* ===============================
       4️⃣ UPDATE CART HEADER
    =============================== */
    mysqli_query($conn, "
        UPDATE corporate_quotation_cart
        SET TicketID = $TicketID,
            QuotationID = $TicketQuotationID,
            Status = 'Converted'
        WHERE ID = $CartID
    ");
}


function nextCorporateTicketStatusHistoryId($conn)
{
	$row = _getSQLDetails($conn, "SELECT COALESCE(MAX(ID), 0) + 1 AS next_id FROM corporate_ticket_status_history");
	return max(1, (int) ($row['next_id'] ?? 1));
}

?>