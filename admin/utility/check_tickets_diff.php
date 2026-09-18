<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../includes/autoloader.inc.php');
$core = new Core();
// Database connection details
$servername = "localhost"; 
$username = "root"; 
$password = "TechXpert@123"; // Replace with your actual password

// Connect to both databases
$techxpert_conn = new mysqli($servername, $username, $password, "techxpertindia");
$techxpert_old_conn = new mysqli($servername, $username, $password, "techxpert_old");

// Check connection
if ($techxpert_conn->connect_error) {
    die("Connection to techxpertindia failed: " . $techxpert_conn->connect_error);
}
if ($techxpert_old_conn->connect_error) {
    die("Connection to techxpert_old failed: " . $techxpert_old_conn->connect_error);
}

$sql = "Select * from corporate_tickets_old where ClientTicketID NOT IN (Select ClientTicketID from corporate_tickets)";
$records = $core->_getSQLRecords($techxpert_conn,$sql);
foreach($records as $record)
{
	echo "Parsing Ticket {$record['TicketID']} , {$record['ID']} -- <br>";
	$ID_old = $record['ID'];
	// Insert Ticket


	$CorporateID_ot = $record['CorporateID'];
	$BranchID_ot = $record['BranchID'];
	$Type_ot = $record['Type'];
	$param1_ot = $record['param1'];
	$param2_ot = $record['param2'];
	$param3_ot = $record['param3'];
	$param4_ot = $record['param4'];
	$param5_ot = $record['param5'];
	$BranchAssetID_ot = $record['BranchAssetID'];
	$SparePart_ot = $record['SparePart'];
	$Service_ot = $record['Service'];
	$Subservice_ot = $record['Subservice'];
	$SubService_Others_ot = $record['SubService_Others'];
	$Price_ot = $record['Price'];
	$Message_ot = $record['Message'];
	$ClientTicketID_ot = $record['ClientTicketID'];
	$TicketAttachment_ot = $record['TicketAttachment'];
	$CallType_ot = $record['CallType'];
	$CustumerPrice_ot = $record['CustumerPrice'];
	$ExpensePrice_ot = $record['ExpensePrice'];
	$Description_ot = $record['Description'];
	$QuotationUpload_ot = $record['QuotationUpload'];
	$QuotationStatus_ot = $record['QuotationStatus'];
	$CreatedDate_ot = $record['CreatedDate'];
	$CreatedTime_ot = $record['CreatedTime'];
	$CloseDate_ot = $record['CloseDate'];
	$CloseTime_ot = $record['CloseTime'];
	$CreatedBy_ot = $record['CreatedBy'];
	$TicketOTP_ot = $record['TicketOTP'];
	$TicketCloseOTP_ot = $record['TicketCloseOTP'];
	$DueDate_ot = $record['DueDate'];
	$AssignedTo_ot = $record['AssignedTo'];
	$Technician_ot = $record['Technician'];
	$Status_ot = $record['Status'];
	$Remarks_ot = $record['Remarks'];
	$Priority_ot = $record['Priority'];
	$IsActive_ot = $record['IsActive'];

	// Define the row data for insertion
	$rowData = [
	    'CorporateID' => $CorporateID_ot,
	    'BranchID' => $BranchID_ot,
	    'Type' => $Type_ot,
	    'param1' => $param1_ot,
	    'param2' => $param2_ot,
	    'param3' => $param3_ot,
	    'param4' => $param4_ot,
	    'param5' => $param5_ot,
	    'BranchAssetID' => $BranchAssetID_ot,
	    'SparePart' => $SparePart_ot,
	    'Service' => $Service_ot,
	    'Subservice' => $Subservice_ot,
	    'SubService_Others' => $SubService_Others_ot,
	    'Price' => $Price_ot,
	    'Message' => $Message_ot,
	    'ClientTicketID' => $ClientTicketID_ot,
	    'TicketAttachment' => $TicketAttachment_ot,
	    'CallType' => $CallType_ot,
	    'CustumerPrice' => $CustumerPrice_ot,
	    'ExpensePrice' => $ExpensePrice_ot,
	    'Description' => $Description_ot,
	    'QuotationUpload' => $QuotationUpload_ot,
	    'QuotationStatus' => $QuotationStatus_ot,
	    'CreatedDate' => $CreatedDate_ot,
	    'CreatedTime' => $CreatedTime_ot,
	    'CloseDate' => $CloseDate_ot,
	    'CloseTime' => $CloseTime_ot,
	    'CreatedBy' => $CreatedBy_ot,
	    'TicketOTP' => $TicketOTP_ot,
	    'TicketCloseOTP' => $TicketCloseOTP_ot,
	    'DueDate' => $DueDate_ot,
	    'AssignedTo' => $AssignedTo_ot,
	    'Technician' => $Technician_ot,
	    'Status' => $Status_ot,
	    'Remarks' => $Remarks_ot,
	    'Priority' => $Priority_ot,
	    'IsActive' => $IsActive_ot
	];


    $response_insert_ticket = $core->_InsertTableRecords_prepare($techxpert_conn,'corporate_tickets',$rowData);
    // Execute the insert query
    if ($response_insert_ticket['error'] == false) 
    {
        // Get the last insert ID
       	$last_id = $response_insert_ticket['last_insert_id'];
        $LatestID = $last_id;
        echo "Record inserted successfully. Last Insert ID: " . $last_id . "<br>";
        $formatted_id = sprintf('%06d', $last_id);
	    if($Type_ot == "Supply")
	    {
	       $TicketID = "CS-SUP-".$formatted_id;
	    }
	    else if($Type_ot == "Projects"){
	        $TicketID = "CS-PROJECT-".$formatted_id;
	    }
	    else
	    {
	       $TicketID = "CS-RM-".$formatted_id;
	    }

	    $raise_response = array();
	    $query_parameter = " TicketID = '$TicketID' where ID = $last_id";
	    $core->_UpdateTableRecords($techxpert_conn,'corporate_tickets',$query_parameter);

	    // Get Status history
	    $where = " where TicketID = $ID_old ORDER BY ID ASC";
	    $status_history_records = $core->_getTableRecords($techxpert_old_conn,'corporate_ticket_status_history',$where);
	    // Insert Status History
	    $sh_response = "";
	    foreach($status_history_records as $status_history)
	    {
	    	$AssignedTo_sh = $status_history['AssignedTo'];
	    	$Status_sh = $status_history['Status'];
	    	$Remarks_sh = $status_history['Remarks'];
	    	$CreatedDate_sh = $status_history['CreatedDate'];
	    	$CreatedTime_sh = $status_history['CreatedTime'];
	    	$CreatedBy_sh = $status_history['CreatedBy'];
	    	$sql_sh = "INSERT INTO corporate_ticket_status_history(TicketID,AssignedTo,Status,Remarks,CreatedDate,CreatedTime,CreatedBy) VALUES ($LatestID,$AssignedTo_sh,'$Status_sh','$Remarks_sh','$CreatedDate_sh','$CreatedTime_sh','$CreatedBy_sh')";
	    	$insert_sh_response = $core->_InsertTableRecords($techxpert_conn,$sql_sh);
	    	if($insert_sh_response['error'] == false)
	    	{
	    		 $sh_response = $sh_response." Inserted";
	    	}
	    }
	    echo "Status History Log -- ".$sh_response."<br>";

	    // Get ticket_media
	    $where = " where TicketID = $ID_old ORDER BY ID ASC";
	    $ticket_media_records = $core->_getTableRecords($techxpert_old_conn,'ticket_media',$where);
	    // Insert Status History
	    $tm_response = "";
	    foreach($ticket_media_records as $ticket_media)
	    {
	    	$Action_tm = $ticket_media['Action'];
	    	$Image_tm = $ticket_media['Image'];
	    	$CreatedDate_tm = $ticket_media['CreatedDate'];
	    	$CreatedTime_tm = $ticket_media['CreatedTime'];
	    	$CreatedBy_tm = $ticket_media['CreatedBy'];
	    	$sql_tm	 = "INSERT INTO ticket_media(TicketID,Action,Image,CreatedBy,CreatedDate,CreatedTime) VALUES ($LatestID,'$Action_tm','$Image_tm','$CreatedBy_tm','$CreatedDate_tm','$CreatedTime_tm')";
	    	$insert_tm_response = $core->_InsertTableRecords($techxpert_conn,$sql_tm);
	    	if($insert_tm_response['error'] == false)
	    	{
	    		 $tm_response = $tm_response." Inserted";
	    	}
	    }
	    echo "Image Log -- ".$tm_response."<br>";

	    // Get Service Report
	    $where = " where TicketID = $ID_old";
		$service_report_details = $core->_getTableDetails($techxpert_old_conn,'corporate_ticket_general_service_report',$where);
		 $sr_response = "";
		if($service_report_details != null)
		{
			echo "Parsing Service Report -- ";
			$Type_sr = $service_report_details['Type'];
	    	$ProblemReportedByClient_sr = $service_report_details['ProblemReportedByClient'];
	    	$Observation_sr = $service_report_details['Observation'];
	    	$ActionTaken_sr = $service_report_details['ActionTaken'];
	    	$Remarks_sr = $service_report_details['Remarks'];
	    	$ClientRepresentative_sr = $service_report_details['ClientRepresentative'];
	    	$ClientRepresentativeContact_sr = $service_report_details['ClientRepresentativeContact'];
	    	$ClientRepresentativeEmails_sr = $service_report_details['ClientRepresentativeEmails'];
	    	$ClientRepresentativeDesignation_sr = $service_report_details['ClientRepresentativeDesignation'];
	    	$ClientSignature_sr = $service_report_details['ClientSignature'];
	    	$Latitude_sr = $service_report_details['Latitude'];
	    	$Longitude_sr = $service_report_details['Longitude'];
	    	$CreatedDate_sr = $service_report_details['CreatedDate'];
	    	$CreatedTime_sr = $service_report_details['CreatedTime'];
	    	$CreatedBy_sr = $service_report_details['CreatedBy'];
    	 	$sql_sr = "INSERT INTO corporate_ticket_general_service_report(TicketID,ProblemReportedByClient,Observation,ActionTaken,Remarks,ClientRepresentative,ClientRepresentativeContact,ClientRepresentativeEmails,ClientRepresentativeDesignation,ClientSignature,Latitude,Longitude,CreatedDate,CreatedTime,CreatedBy) VALUES ($LatestID,'$ProblemReportedByClient_sr','$Observation_sr','$ActionTaken_sr','$Remarks_sr','$ClientRepresentative_sr','$ClientRepresentativeContact_sr','$ClientRepresentativeEmails_sr','$ClientRepresentativeDesignation_sr','$ClientSignature_sr','$Latitude_sr','$Longitude_sr','$CreatedDate_sr','$CreatedTime_sr','$CreatedBy_sr')";
    	 	$insert_sr_response = $core->_InsertTableRecords($techxpert_conn,$sql_sr);
	    	if($insert_sr_response['error'] == false)
	    	{
	    		 $sr_response = " Inserted";
	    	}
	    	echo $sr_response."<br>";
		}

		// Get Quotation Details
		$where = " where TicketID = $ID_old";
		$quotation_details = $core->_getTableDetails($techxpert_old_conn,'corporate_ticket_quotation',$where);
		if($quotation_details != null)
		{
			$old_quotation_id = $quotation_details['ID'];
			// Insert and get Quotation ID
			echo "Parsing Quotation -- ";
			$QuotationStatus_qd = $quotation_details['QuotationStatus'];
	    	$QuotationDate_qd = $quotation_details['QuotationDate'];
	    	$QuotationTC_qd = $quotation_details['QuotationTC'];
	    	$QuotationExpiryDate_qd = $quotation_details['QuotationExpiryDate'];
	    	$Remarks_qd = $quotation_details['Remarks'];
	    	$CreatedBy_qd = $quotation_details['CreatedBy'];
	    	$CreatedDate_qd = $quotation_details['CreatedDate'];
	    	$CreatedTime_qd = $quotation_details['CreatedTime'];
	    	$IsActive_qd = $quotation_details['IsActive'];
	    	$sql_qd = "INSERT INTO `corporate_ticket_quotation`(`TicketID`, `QuotationStatus`, `QuotationDate`, `QuotationTC`, `QuotationExpiryDate`, `Remarks`, `CreatedBy`, `CreatedDate`, `CreatedTime`, `IsActive`) VALUES ($LatestID,'$QuotationStatus_qd','$QuotationDate_qd','$QuotationTC_qd','$QuotationExpiryDate_qd','$Remarks_qd','$CreatedBy_qd','$CreatedDate_qd','$CreatedTime_qd',$IsActive_qd)";
	    	$insert_qd_response = $core->_InsertTableRecords($techxpert_conn,$sql_qd);
	    	if($insert_qd_response['error'] == false)
	    	{
	    		 $qd_response = " Details Inserted";
	    		 echo $qd_response."<br>";
	    		 $LatestQuotationID = $insert_qd_response['last_insert_id'];

	    		 // Fetch Old Quotation Status History
			    $where = " where QuotationID = $old_quotation_id ORDER BY ID ASC";
			    $quotation_status_history_records = $core->_getTableRecords($techxpert_old_conn,'corporate_ticket_quotation_history',$where);
			    // Insert Status History
			    $qh_response = "";
			    foreach($quotation_status_history_records as $quotation_status_history)
			    {
			    	$QuoationStatus_qh = $quotation_status_history['QuotationStatus'];
			    	$Remarks_qh = $quotation_status_history['Remarks'];
			    	$CreatedDate_qh = $quotation_status_history['CreatedDate'];
			    	$CreatedTime_qh = $quotation_status_history['CreatedTime'];
			    	$CreatedBy_qh = $status_history['CreatedBy'];
			    	$sql_sh = "INSERT INTO corporate_ticket_quotation_history(QuotationID,QuotationStatus,Remarks,CreatedDate,CreatedTime,CreatedBy) VALUES ($LatestQuotationID,'$QuoationStatus_qh','$Remarks_qh','$CreatedDate_qh','$CreatedTime_qh','$CreatedBy_qh')";
			    	$insert_qh_response = $core->_InsertTableRecords($techxpert_conn,$sql_sh);
			    	if($insert_sh_response['error'] == false)
			    	{
			    		 $qh_response = $qh_response." Inserted";
			    	}
			    }
			    echo "Quoation Status History Log -- ".$qh_response."<br>";

			    // Fetch Old Quotation Line Items
			    $where = " where QuotationID = $old_quotation_id ORDER BY ID ASC";
			    $quotation_line_item_records = $core->_getTableRecords($techxpert_old_conn,'corporate_ticket_quotation_items',$where);
			    // Insert Status History
			    $qli_response = "";
			    foreach($quotation_line_item_records as $quotation_line_item)
			    {
			    	$LineItemID_to_be_inserted = -1;
			    	$old_line_item_id = $quotation_line_item['LineItemID'];
			    	$where_li = " where ID = $old_line_item_id";
			    	$old_line_item_details = $core->_getTableDetails($techxpert_old_conn,'corporate_rate_card',$where_li);
			    	$old_LineItemName = $old_line_item_details['LineItemName'];
			    	$where = " where ID = $old_line_item_id and LineItemName = '$old_LineItemName'";
			    	$number_of_records = $core->_getTotalRows($techxpert_conn,'corporate_rate_card',$where);
			    	if($number_of_records > 0)
			    	{
			    		echo "<br> {$old_LineItemName} already exist<br>";
			    		$LineItemID_to_be_inserted = $old_line_item_id;
			    	}
			    	else
			    	{
			    		$CompanyID_rc = $old_line_item_details['CompanyID'];
			    		$Type_rc = $old_line_item_details['Type'];
			    		$Category_rc = $old_line_item_details['Category'];
			    		$SubCategory_rc = $old_line_item_details['SubCategory'];
			    		$LineItemName_rc = $old_line_item_details['LineItemName'];
			    		$Make_rc = $old_line_item_details['Make'];
			    		$HSN_rc = $old_line_item_details['HSN'];
			    		$ARCCode_rc = $old_line_item_details['ARCCode'];
			    		$UoM_rc = $old_line_item_details['UoM'];
			    		$Price_rc = $old_line_item_details['Price'];
			    		$Tax_rc = $old_line_item_details['Tax'];
			    		$CreatedDate_rc = $old_line_item_details['CreatedDate'];
			    		$CreatedTime_rc = $old_line_item_details['CreatedTime'];
			    		$ARCItem_rc = $old_line_item_details['ARCItem'];
			    		$IsActive_rc = $old_line_item_details['IsActive'];
			    		$SNo_rc = $old_line_item_details['SNo'];
			    		$Tag_rc = $old_line_item_details['Tag'];

			    		// Insert Line Item and get new lineitem id
			    		$rowData = [
			                'CompanyID' => $CompanyID_rc,
			                'Type' => $Type_rc,
			                'Category' => $Category_rc,
			                'SubCategory' => $SubCategory_rc,
			                'LineItemName' => $LineItemName_rc,
			                'Make' => $Make_rc,
			                'HSN' => $HSN_rc,
			                'ARCCode' => $ARCCode_rc,
			                'UoM' => $UoM_rc,
			                'Price' => $Price_rc,
			                'Tax' => $Tax_rc,
			                'CreatedDate' => $CreatedDate_rc,
			                'CreatedTime' => $CreatedTime_rc,
			                'CreatedBy' => $IsActive_rc
			            ];
			            $response_lii = $core->_InsertTableRecords_prepare($techxpert_conn, 'corporate_rate_card', $rowData);
			            $LineItemID_to_be_inserted = $response_lii['last_insert_id'];
			            echo "Line Item (rate card) Inserted -- <br>";
			    	}

			    	$Qty_qli = $quotation_line_item['Qty'];
			    	$PerItemPrice_qli = $quotation_line_item['PerItemPrice'];
			    	$TotalPrice_qli = $quotation_line_item['TotalPrice'];
			    	$CreatedBy_qli = $quotation_line_item['CreatedBy'];
			    	$CreatedDate_qli = $quotation_line_item['CreatedDate'];
			    	$CreatedTime_qli = $quotation_line_item['CreatedTime'];
			    	// Insert Quotation Line Items 
			    	$sql_qli = "INSERT INTO corporate_ticket_quotation_items(QuotationID,LineItemID,Qty,PerItemPrice,TotalPrice,CreatedBy,CreatedDate,CreatedTime)VALUES($LatestQuotationID,$LineItemID_to_be_inserted,$PerItemPrice_qli,$TotalPrice_qli,'$CreatedBy_qli','$CreatedDate_qli','$CreatedTime_qli')";
			    	$insert_qli_response = $core->_InsertTableRecords($techxpert_conn,$sql_sh);
			    	if($insert_qli_response['error'] == false)
			    	{
			    		 $qli_response = $qli_response." Inserted";
			    	}
			    }
			    echo $qli_response."<br>";

	    	}
			    	
		}


    } 
    else 
    {
    	echo "Error: ";
    }

	// Get corporate_ticket_status_history
	


}

// Close connections
$techxpert_conn->close();
$techxpert_old_conn->close();
?>