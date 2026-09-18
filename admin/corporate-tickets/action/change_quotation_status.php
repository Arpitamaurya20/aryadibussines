<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
require_once('../../includes/autoloader.inc.php');
include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
$core = new Core();
$core->setTimeZone();
$CreatedDate = date("Y-m-d");
$CreatedTime = date("H:i:s");
$CreatedBy = "";

if(isset($_SESSION['pb_username']))
{
	$CreatedBy = $_SESSION['pb_username'];
}
else
{
	$CreatedBy = $_POST['CreatedBy'];
}

$response = array();
if(isset($_POST))
{
	$data = $_POST;
	$CreatedBy = $_POST['CreatedBy'];
	$data['CreatedBy'] = $CreatedBy;
	$data['CreatedDate'] = $CreatedDate;
	$data['CreatedTime'] = $CreatedTime;
	$data['TicketQuotationID'] = $_POST['QuotationID'];
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();
	$corporateticket_obj = new Corporateticket($conn);
	$employee_obj = new Employee($conn);
	$employee_array = $employee_obj->setEmployeeArray("All");
	$QuotationStatus = $_POST['QuotationStatus'];
	$requestedQuotationStatus = $QuotationStatus;
	$companyAdminApprovalStatus = 'Quote Pending Company Admin Approval';
	$UserType = SessionCheck();

	$QuoteCompanyDetailsID = isset($_POST['QuoteCompanyDetailsID']) ? (int) $_POST['QuoteCompanyDetailsID'] : 0;
	$quotation_detail_for_approval = $corporateticket_obj->GetQuotationDetailbyID($data['QuotationID']);
	if ($QuoteCompanyDetailsID <= 0 && !empty($quotation_detail_for_approval['QuoteCompanyDetailsID'])) {
		$QuoteCompanyDetailsID = (int)$quotation_detail_for_approval['QuoteCompanyDetailsID'];
	}

	if ($QuotationStatus === 'Quote Sent Approval Pending') {
		if ($QuoteCompanyDetailsID <= 0) {
			$response['error'] = true;
			$response['message'] = 'Quote company name is required.';
			echo json_encode($response);
			exit;
		}
		$qc_row = $core->_getTableDetails($conn, 'quote_company_details', ' where ID = ' . $QuoteCompanyDetailsID . ' AND IsActive = 1');
		if (empty($qc_row) || !isset($qc_row['ID'])) {
			$response['error'] = true;
			$response['message'] = 'Invalid or inactive quote company selected.';
			echo json_encode($response);
			exit;
		}
	}

	$quoteRequiresCompanyAdminApproval = false;
	$canApproveCompanyAdminQuote = ($UserType == 'Admin');
	if (!empty($quotation_detail_for_approval) && isset($quotation_detail_for_approval['TicketID'])) {
		$ticketForApproval = $core->_getTableDetails($conn, 'corporate_tickets', ' where ID = ' . (int)$quotation_detail_for_approval['TicketID']);
		if (!empty($ticketForApproval) && isset($ticketForApproval['CorporateID'])) {
			$companyForApproval = $core->_getTableDetails($conn, 'company', ' where ID = ' . (int)$ticketForApproval['CorporateID']);
			$quoteRequiresCompanyAdminApproval = !empty($companyForApproval) && (int)($companyForApproval['TicketsNeedApproval'] ?? 0) === 1;
			if ($UserType == 'Corporate Admin' && isset($_SESSION['Roles']['CorporateID'])) {
				$canApproveCompanyAdminQuote = ((int)$_SESSION['Roles']['CorporateID'] === (int)$ticketForApproval['CorporateID']);
			}
		}
	}

	if (
		$QuotationStatus === 'Quote Sent Approval Pending'
		&& $quoteRequiresCompanyAdminApproval
		&& !$canApproveCompanyAdminQuote
		&& (($quotation_detail_for_approval['QuotationStatus'] ?? '') !== $companyAdminApprovalStatus)
	) {
		$QuotationStatus = $companyAdminApprovalStatus;
	}

	$ticket_start_techx_contact='';
	// ================= STATE MANAGER =================
		if($QuotationStatus == "StateApproved")
		{
		    $sql = "StateManagerApprovalStatus='Approved'";
		    $core->_UpdateTableRecords($conn,'corporate_ticket_quotation',$sql." WHERE ID=".$data['QuotationID']);

            $sql2 = "QuotationStatus='Quote Approved By State'";
		    $core->_UpdateTableRecords($conn,'corporate_ticket_quotation',$sql2." WHERE ID=".$data['QuotationID']);

		    $ticket_start_branch_contact = "Quotation approved by State Manager.";
			$corporateticket_obj->UpdateTicketQuotationHistory($data);

			$quotation_detail = $corporateticket_obj->GetQuotationDetailbyID($data['QuotationID']);

				// Get Associated Contacts of ticket
				$TicketID = $quotation_detail['TicketID'];
				$td = $corporateticket_obj->GetTicketDetails($TicketID);
				$ticket_details = $td['ticket_details'];

				// Update Ticket Status & History
				$sql_update = " Status = '$QuotationStatus' where ID=$TicketID";
				$core->_UpdateTableRecords($conn,'corporate_tickets',$sql_update);

				$data_history['TicketID'] = $TicketID;
				$data_history['CreatedDate'] = $CreatedDate;
				$data_history['CreatedTime'] = $CreatedTime;
				$data_history['CreatedBy'] = $CreatedBy;
				$data_history['Status'] = $QuotationStatus;
				$data_history['AssignedTo'] = $ticket_details['AssignedTo'];
				RecordTicketHistory($conn,$data_history);


					/* ==============================
					SEND NOTIFICATION TO FINANCE
					================================ */

					// Get Branch State from Ticket
					$BranchState = $td['branch_details']['BranchState'];

					// Get all Finance users who have access for this state
					$finance_query = "
						SELECT EmployeeID
						FROM user_quation_access
						WHERE
							IsApprovedFinance = 'Yes'
							AND IsActive = 1
					";

					$finance_users = $core->_getSQLRecords($conn, $finance_query);

					if (!empty($finance_users)) {

						foreach ($finance_users as $finance_user) {

							$financeEmployeeID = $finance_user['EmployeeID'];

							// Notification Title & Body
							$title = "Required Finance Approval";
							$body  = "Ticket ID ".$TicketID." is approved by State Manager. Finance approval required.";

							/* ==============================
							1️⃣ SAVE INTO push_notifications_log
							============================== */

							$insert_notification = "
								INSERT INTO push_notifications_log
								(user_id, title, body, status, created_at)
								VALUES
								(
									'".$financeEmployeeID."',
									'".$title."',
									'".$body."',
									'PENDING',
									NOW()
								)
							";

							mysqli_query($conn, $insert_notification);

							/* ==============================
							2️⃣ CALL PUSH NOTIFICATION API
							============================== */

							$postData = array(
								"UserID" => $financeEmployeeID,
								"Title"  => $title,
								"Body"   => $body
							);

							$ch = curl_init("https://techxpertindia.in/api/trigger-event.php");
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_POST, true);
							curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
							curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

							$response_r = curl_exec($ch);
							$curl_error = curl_error($ch);
							curl_close($ch);

							// Optional: Update status if error occurs
							if ($curl_error) {
								$error_update = "
									UPDATE push_notifications_log
									SET status='failed', error_message='".mysqli_real_escape_string($conn,$curl_error)."'
									WHERE user_id='".$financeEmployeeID."'
									ORDER BY id DESC LIMIT 1
								";
								mysqli_query($conn, $error_update);
							} else {
								$success_update = "
									UPDATE push_notifications_log
									SET status='Sent'
									WHERE user_id='".$financeEmployeeID."'
									ORDER BY id DESC LIMIT 1
								";
								mysqli_query($conn, $success_update);
							}
						}
					}


		}

		if($QuotationStatus == "StateRejected")
		{
		    $sql = "StateManagerApprovalStatus='Rejected'";
		    $core->_UpdateTableRecords($conn,'corporate_ticket_quotation',$sql." WHERE ID=".$data['QuotationID']);

		    $sql2 = "QuotationStatus='Quote Rejected By State'";
		    $core->_UpdateTableRecords($conn,'corporate_ticket_quotation',$sql2." WHERE ID=".$data['QuotationID']);

		    $ticket_start_branch_contact = "Quotation rejected by State Manager.";
			$corporateticket_obj->UpdateTicketQuotationHistory($data);

			$quotation_detail = $corporateticket_obj->GetQuotationDetailbyID($data['QuotationID']);

				// Get Associated Contacts of ticket
				$TicketID = $quotation_detail['TicketID'];
				$td = $corporateticket_obj->GetTicketDetails($TicketID);
				$ticket_details = $td['ticket_details'];

				// Update Ticket Status & History
				$sql_update = " Status = '$QuotationStatus' where ID=$TicketID";
				$core->_UpdateTableRecords($conn,'corporate_tickets',$sql_update);

				$data_history['TicketID'] = $TicketID;
				$data_history['CreatedDate'] = $CreatedDate;
				$data_history['CreatedTime'] = $CreatedTime;
				$data_history['CreatedBy'] = $CreatedBy;
				$data_history['Status'] = $QuotationStatus;
				$data_history['AssignedTo'] = $ticket_details['AssignedTo'];
				RecordTicketHistory($conn,$data_history);
		}


		// ================= FINANCE HEAD =================
		if($QuotationStatus == "FinanceApproved")
		{
		    $sql = "FinanceHeadApprovalStatus='Approved'";
		    $core->_UpdateTableRecords($conn,'corporate_ticket_quotation',$sql." WHERE ID=".$data['QuotationID']);

		     $sql2 = "QuotationStatus='Quote Approved By Finance'";
		    $core->_UpdateTableRecords($conn,'corporate_ticket_quotation',$sql2." WHERE ID=".$data['QuotationID']);

		    $ticket_start_branch_contact = "Quotation approved by Finance Head.";
			$corporateticket_obj->UpdateTicketQuotationHistory($data);

			$quotation_detail = $corporateticket_obj->GetQuotationDetailbyID($data['QuotationID']);

				// Get Associated Contacts of ticket
				$TicketID = $quotation_detail['TicketID'];
				$td = $corporateticket_obj->GetTicketDetails($TicketID);
				$ticket_details = $td['ticket_details'];

				// Update Ticket Status & History
				$sql_update = " Status = '$QuotationStatus' where ID=$TicketID";
				$core->_UpdateTableRecords($conn,'corporate_tickets',$sql_update);

				$data_history['TicketID'] = $TicketID;
				$data_history['CreatedDate'] = $CreatedDate;
				$data_history['CreatedTime'] = $CreatedTime;
				$data_history['CreatedBy'] = $CreatedBy;
				$data_history['Status'] = $QuotationStatus;
				$data_history['AssignedTo'] = $ticket_details['AssignedTo'];
				RecordTicketHistory($conn,$data_history);


		}

		if($QuotationStatus == "FinanceRejected")
		{
		    $sql = "FinanceHeadApprovalStatus='Rejected'";
		    $core->_UpdateTableRecords($conn,'corporate_ticket_quotation',$sql." WHERE ID=".$data['QuotationID']);

		    // Also mark main quotation rejected
		    $sql2 = "QuotationStatus='Quote Rejected By Finance'";
		    $core->_UpdateTableRecords($conn,'corporate_ticket_quotation',$sql2." WHERE ID=".$data['QuotationID']);

		    $ticket_start_branch_contact = "Quotation rejected by Finance Head.";
			$corporateticket_obj->UpdateTicketQuotationHistory($data);


				$quotation_detail = $corporateticket_obj->GetQuotationDetailbyID($data['QuotationID']);

				// Get Associated Contacts of ticket
				$TicketID = $quotation_detail['TicketID'];
				$td = $corporateticket_obj->GetTicketDetails($TicketID);
				$ticket_details = $td['ticket_details'];

				// Update Ticket Status & History
				$sql_update = " Status = '$QuotationStatus' where ID=$TicketID";
				$core->_UpdateTableRecords($conn,'corporate_tickets',$sql_update);

				$data_history['TicketID'] = $TicketID;
				$data_history['CreatedDate'] = $CreatedDate;
				$data_history['CreatedTime'] = $CreatedTime;
				$data_history['CreatedBy'] = $CreatedBy;
				$data_history['Status'] = $QuotationStatus;
				$data_history['AssignedTo'] = $ticket_details['AssignedTo'];
				RecordTicketHistory($conn,$data_history);
		}

			//11 $data['QuotationStatus'] = $QuotationStatus;
			//22 $response = $corporateticket_obj->UpdateTicketQuotation($data);

			$data['QuotationStatus'] = $QuotationStatus;
			if($QuotationStatus!="Quote Sent Approval Pending" && $QuotationStatus!="Quote Rejected by Client" && $QuotationStatus!=$companyAdminApprovalStatus)
	        {
			$quotation_detail = $corporateticket_obj->GetQuotationDetailbyID($data['QuotationID']);
			if(
				$quotation_detail['StateManagerApprovalStatus'] == "Approved" &&
				$quotation_detail['FinanceHeadApprovalStatus'] == "Approved"
			)
			{
				$response = $corporateticket_obj->UpdateTicketQuotation($data);

			}
			else
			{
				$response['error'] = true;

				if($quotation_detail['StateManagerApprovalStatus'] != "Approved")
				{
					$response['message'] = "Final approval pending. State Manager approval required.";
				}
				elseif($quotation_detail['FinanceHeadApprovalStatus'] != "Approved")
				{
					$response['message'] = "Final approval pending. Finance Head approval required.";
				}

				echo json_encode($response);
				exit;
			}
		}
	//$response['error'] = false;
		$response = $corporateticket_obj->UpdateTicketQuotation($data);
	if($response['error'] == false)
	{
		// Record Quotation History
		$corporateticket_obj->UpdateTicketQuotationHistory($data);

		// Get Quotation Details
		$quotation_detail = $corporateticket_obj->GetQuotationDetailbyID($data['QuotationID']);

		// Get Associated Contacts of ticket
		$TicketID = $quotation_detail['TicketID'];
		$td = $corporateticket_obj->GetTicketDetails($TicketID);
		$ticket_details = $td['ticket_details'];

		// Update Ticket Status & History
		$sql_update = " Status = '$QuotationStatus' where ID=$TicketID";
		$core->_UpdateTableRecords($conn,'corporate_tickets',$sql_update);

		$data_history['TicketID'] = $TicketID;
	   	$data_history['CreatedDate'] = $CreatedDate;
	   	$data_history['CreatedTime'] = $CreatedTime;
	   	$data_history['CreatedBy'] = $CreatedBy;
	   	$data_history['Status'] = $QuotationStatus;
	   	$data_history['AssignedTo'] = $ticket_details['AssignedTo'];
	   	RecordTicketHistory($conn,$data_history);



		/* ==============================
					SEND NOTIFICATION TO FINANCE
					================================ */

					// Get Branch State from Ticket
					$BranchState = $td['branch_details']['BranchState'];

					// Get all Finance users who have access for this state
					$state_query = "
						SELECT EmployeeID
						FROM user_quation_access
						WHERE
							IsApprovedState = 'Yes'
							AND StateName = '".$BranchState."'
							AND IsActive = 1
					";

					$state_users = $core->_getSQLRecords($conn, $state_query);

					if (!empty($state_users)) {

						foreach ($state_users as $state_user) {

							$stateEmployeeID = $state_user['EmployeeID'];

							// Notification Title & Body
							$title = "Required State Approval";
							$body  = "Ticket ID ".$TicketID." is send by Branch Manager. State approval required Please Approved this Ticket.";

							/* ==============================
							1️⃣ SAVE INTO push_notifications_log
							============================== */

							$insert_notification = "
								INSERT INTO push_notifications_log
								(user_id, title, body, status, created_at)
								VALUES
								(
									'".$stateEmployeeID."',
									'".$title."',
									'".$body."',
									'PENDING',
									NOW()
								)
							";

							mysqli_query($conn, $insert_notification);

							/* ==============================
							2️⃣ CALL PUSH NOTIFICATION API
							============================== */

							$postData = array(
								"UserID" => $stateEmployeeID,
								"Title"  => $title,
								"Body"   => $body
							);

							$ch = curl_init("https://techxpertindia.in/api/trigger-event.php");
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_POST, true);
							curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
							curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

							$response_r = curl_exec($ch);
							$curl_error = curl_error($ch);
							curl_close($ch);

							// Optional: Update status if error occurs
							if ($curl_error) {
								$error_update = "
									UPDATE push_notifications_log
									SET status='failed', error_message='".mysqli_real_escape_string($conn,$curl_error)."'
									WHERE user_id='".$stateEmployeeID."'
									ORDER BY id DESC LIMIT 1
								";
								mysqli_query($conn, $error_update);
							} else {
								$success_update = "
									UPDATE push_notifications_log
									SET status='Sent'
									WHERE user_id='".$stateEmployeeID."'
									ORDER BY id DESC LIMIT 1
								";
								mysqli_query($conn, $success_update);
							}
						}
					}

	   	if($QuotationStatus == "Quote Approved")
	   	{
	   		// $sql_update = " Status = 'Work In Progress' where ID=$TicketID";
			// $core->_UpdateTableRecords($conn,'corporate_tickets',$sql_update);

			// $data_history['TicketID'] = $TicketID;
		   	// $data_history['CreatedDate'] = $CreatedDate;
		   	// $data_history['CreatedTime'] = $CreatedTime;
		   	// $data_history['CreatedBy'] = $CreatedBy;
		   	// $data_history['Status'] = "Work In Progress";
		   	// $data_history['AssignedTo'] = $ticket_details['AssignedTo'];
		   	// RecordTicketHistory($conn,$data_history);

		  	$ticket_technician_message = "Hello,\n\nQuotation for the following details has been approved, please start work asap";
	   	}

	   	$td = $corporateticket_obj->GetTicketDetails($TicketID);
		$ticket_details = $td['ticket_details'];


		$ticket_details_message = " \nTicket Details are as follows - ";
		$ticket_details_message = $ticket_details_message."\nTicket ID (Techxpert) - ".$ticket_details['TicketID'];
		if($ticket_details['ClientTicketID'] != "")
		{
			$ticket_details_message = $ticket_details_message."\nTicket ID (Client) - ".$ticket_details['ClientTicketID'];
		}
		$ticket_details_message = $ticket_details_message."\nType - ".$ticket_details['Type'];
		if($ticket_details['Type'] == "R&M")
		{
			$ticket_details_message = $ticket_details_message."\nService - ".$ticket_details['Service'];
		}
		$ticket_details_message = $ticket_details_message."\nStatus - ".$ticket_details['Status'];

		$ticket_branch_details = $td['branch_details'];
		$ticket_branch_details_message = " \nBranch Details are as follows - ";
		$ticket_branch_details_message = $ticket_branch_details_message."\nBranch Site - ".$ticket_branch_details['BranchSite'];
		$ticket_branch_details_message = $ticket_branch_details_message."\nBranch Address - ".$ticket_branch_details['BranchAddress1'];
		$ticket_branch_details_message = $ticket_branch_details_message."\nBranch City - ".$ticket_branch_details['BranchCity'];
		if($ticket_branch_details['SiteIncharge'] != "")
		{
			$ticket_branch_details_message = $ticket_branch_details_message."\nSite Incharge - ".$ticket_branch_details['SiteIncharge'];
		}

		$ticket_quotation_details_message = "\nQuotation Details are as follows - ";
		$ticket_quotation_items_message = "\nApproved Item Details are as follows - ";
		$quotation_line_items = $corporateticket_obj->GetQuotationLineItems($data['QuotationID']);
		if(sizeof($quotation_line_items) > 0)
		{
			$ticket_quotation_details_message = $ticket_quotation_details_message."\nQuotation Line Items - ";
		}
		$QuotationTotalPrice = 0;
		foreach($quotation_line_items as $line_item)
		{
			$QuotationTotalPrice = $QuotationTotalPrice+$line_item['TotalPrice'];
			$ticket_quotation_details_message = $ticket_quotation_details_message."\n".$line_item['LineItemName']."(".$line_item['Qty'].") - ".$line_item['TotalPrice'];
			$ticket_quotation_items_message = $ticket_quotation_items_message."\n".$line_item['LineItemName']."(".$line_item['Qty'].")";
		}
		$ticket_quotation_details_message = $ticket_quotation_details_message."\nTotal Price - ".$QuotationTotalPrice;

		$ticket_message = $ticket_details_message."\n".$ticket_branch_details_message."\n".$ticket_quotation_details_message;


		if($QuotationStatus == "Quote Sent Approval Pending" || $QuotationStatus == $companyAdminApprovalStatus)
		{
			$QuotationID_i = $data['QuotationID'];
			$QuotationTC = isset($data['QuotationTC'])
				? mysqli_real_escape_string($conn,$data['QuotationTC'])
				: mysqli_real_escape_string($conn, (string)($quotation_detail['QuotationTC'] ?? ''));
			$QuotationExpiryDate = $data['QuotationExpiryDate'] ?? ($quotation_detail['QuotationExpiryDate'] ?? '');
			$expectedbudget = $data['expectedbudget'] ?? ($quotation_detail['expectedbudget'] ?? 0);
			// $sql_update_quotation_data = " QuotationDate = '$CreatedDate',QuotationTC='$QuotationTC',expectedbudget='$expectedbudget',QuotationExpiryDate='$QuotationExpiryDate' where ID=$QuotationID_i";
			$sql_update_quotation_data = " QuotationDate = '$CreatedDate',QuotationTC='$QuotationTC',expectedbudget='$expectedbudget',QuotationExpiryDate='$QuotationExpiryDate',QuoteCompanyDetailsID=$QuoteCompanyDetailsID where ID=$QuotationID_i";
			$core->_UpdateTableRecords($conn,'corporate_ticket_quotation',$sql_update_quotation_data);
		}
		if($QuotationStatus == "Quote Sent Approval Pending")
		{
			$QuotationID_i = $data['QuotationID'];
			// Update data in tickets finance table
			$corporateticket_obj->UpdateTicketFinancesfromQuotation($QuotationID_i,$TicketID);
			$ticket_start_branch_contact = "Hello,\n\nQuotation has been raised as per the following details. Kindly take action - ";
			$ticket_start_techx_contact = "Hello,\n\nQuotation has been raised as per the following details - ";

		}
		if($QuotationStatus == "Quote Rejected by Client")
		{
			$ticket_remarks = "";
			if($_POST['Remarks'] != "")
			{
				$ticket_remarks = "\n\nRemarks - ".$_POST['Remarks'];
			}
			$ticket_start_branch_contact = "Hello,\n\nQuotation has been rejected for the ticket with the following details -".$ticket_remarks;
			$ticket_start_techx_contact = "Hello,\n\nQuotation has been rejected for the ticket with the following details, please take action! ".$ticket_remarks;
		}
		if($QuotationStatus == "Quote Approved")
		{
			$ticket_remarks = "";
			if($_POST['Remarks'] != "")
			{
				$ticket_remarks = "\n\nRemarks - ".$_POST['Remarks'];
			}
			$ticket_start_branch_contact = "Hello,\n\nQuotation has been approved for the ticket with the following details -".$ticket_remarks;
			$ticket_start_techx_contact = "Hello,\n\nQuotation has been approved for the ticket with the following details, please take action! ".$ticket_remarks;
		}


		// Send Whatsapp message to branch person
		if($ticket_branch_details['BranchMobile'] != "")
		{
			$branch_contact = "+91".$ticket_branch_details['BranchMobile'];
			// sendWhatsAppMessage($branch_contact,$ticket_start_branch_contact."\n".$ticket_message);
	    }
	    if($td['corporate_details']['TicketNeedsWAMessage'] == 1)
	    {
	    	if($td['corporate_details']['CompanyPhone'] != "")
	    	{
	    		$company_contact = "+91".$td['corporate_details']['CompanyPhone'];
				// sendWhatsAppMessage($company_contact,$ticket_start_branch_contact."\n".$ticket_message);
	    	}
	    }

	    $BranchAccountManager = $ticket_branch_details['AccountBranchManager'];
	    if($BranchAccountManager != -1)
	    {
	    	if(isset($employee_array[$BranchAccountManager]))
	    	{
	    		$Employee_contact = $employee_array[$BranchAccountManager]['ContactNumber'];
	    		$Employee_contact = "+91".$Employee_contact;
				// sendWhatsAppMessage($Employee_contact,$ticket_start_techx_contact."\n".$ticket_message);
	    	}
	    }

	    if($QuotationStatus == "Quote Approved")
	    {
	    	$AssignedTechnician = $ticket_details['AssignedTo'];
	    	if($AssignedTechnician != -1)
	    	{
		    	if(isset($employee_array[$AssignedTechnician]))
		    	{
		    		$Employee_contact = $employee_array[$AssignedTechnician]['ContactNumber'];
		    		$Employee_contact = "+91".$Employee_contact;
					// sendWhatsAppMessage($Employee_contact,$ticket_technician_message."\n".$ticket_details_message."\n".$ticket_branch_details_message."\n".$ticket_quotation_items_message);
		    	}
		    }
	    }

		// Send Email
	}
	$response['QuotationStatus'] = $QuotationStatus;
	if ($QuotationStatus === $companyAdminApprovalStatus) {
		$response['message'] = 'Quotation saved for company admin approval.';
	}

}
else
{
	$response['error'] = true;
	$response['message'] = "A Technical problem occured!";
}
echo json_encode($response);
?>