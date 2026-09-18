<?php
function applyAnalyticsBranchFilter(&$filter)
{
	$BranchID = -1;
	if(isset($_SESSION['UserType']) && $_SESSION['UserType'] == "Corporate Branch User")
	{
		if(isset($_SESSION['Roles']['BranchID']) && $_SESSION['Roles']['BranchID'] != "")
		{
			$BranchID = (int)$_SESSION['Roles']['BranchID'];
		}
	}
	if($BranchID != -1)
	{
		$filter['branch'] = $BranchID;
	}
	return $BranchID;
}

function renderAnalyticsStatusCountBadge($count, $background_color, $ticket_scope, $ticket_type, $ticket_status, $status_label, $section_label)
{
	$badge_style = 'background:'.$background_color.';';
	$base_class = 'd-inline-block badge badge-success text-center p-1 width-6 font-size-1-2em';
	if((int)$count > 0)
	{
		return '<span class="'.$base_class.' cursor-pointer analytics-status-ticket-link" style="'.$badge_style.'"'
			.' data-ticket-scope="'.htmlspecialchars($ticket_scope, ENT_QUOTES, 'UTF-8').'"'
			.' data-ticket-type="'.htmlspecialchars($ticket_type, ENT_QUOTES, 'UTF-8').'"'
			.' data-ticket-status="'.htmlspecialchars($ticket_status, ENT_QUOTES, 'UTF-8').'"'
			.' data-status-label="'.htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8').'"'
			.' data-section-label="'.htmlspecialchars($section_label, ENT_QUOTES, 'UTF-8').'"'
			.' title="Click to view tickets">'.(int)$count.'</span>';
	}
	return '<span class="'.$base_class.'" style="'.$badge_style.'">'.(int)$count.'</span>';
}

function getTicketStatus($conn,$CorporateID,$BranchID)
{
	$ticket_status_array = array();
	$where = " where IsActive = 1";
	$status_array = _getTableRecords($conn,'corporate_tickets_status',$where);
	foreach($status_array as $status)
	{
		$status_name = $status['Status'];
		$ticket_status_array[$status_name] = 0;
	}
	$where = " where 1";
	if($CorporateID != -1)
	{
		$where = $where." AND CorporateID = $CorporateID";
	}
	if($BranchID != -1)
    {
		$where = $where." AND BranchID = $BranchID";
	}
	$sql = "SELECT Status,COUNT(*) as count FROM `corporate_tickets` $where GROUP BY Status";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				extract($row);
				$Status = rtrim($Status);
				$ticket_status_array[$Status] = $count;
			}
		}
	} else {
		//echo $sql;
	}
	return $ticket_status_array;
}

function getTXWorkAPPTicketStatus($conn,$EmployeeID)
{
	$ticket_status_array = array();
	$where = " where IsActive = 1";
	$status_array = _getTableRecords($conn,'corporate_tickets_status',$where);
	foreach($status_array as $status)
	{
		$status_name = $status['Status'];
		$ticket_status_array[$status_name] = 0;
	}
	$where = " where 1";
	if($EmployeeID != -1)
	{
		$where = $where." AND AssignedTo = $EmployeeID";
	}
	
	$sql = "SELECT Status,COUNT(*) as count FROM `corporate_tickets` $where GROUP BY Status";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				extract($row);
				$Status = rtrim($Status);
				$ticket_status_array[$Status] = $count;
			}
		}
	} else {
		//echo $sql;
	}
	return $ticket_status_array;
}

function getEmployeeDashboardStats($conn,$EmployeeID)
{
	$Total_Corporate = 0;
	$Pending_Corporate = 0;
	$Pending_Corporate_percentage = "N.A.";
	$Closed_Corporate = 0;
	$Closed_Corporate_percentage = "N.A.";
	$Cancel_Corporate = 0;
	$Cancel_Corporate_percentage = "N.A.";
	$ticket_status_array = array();
	$where = " where IsActive = 1";
	$status_array = _getTableRecords($conn,'corporate_tickets_status',$where);
	foreach($status_array as $status)
	{
		$status_name = $status['Status'];
		$ticket_status_array['Corporate'][$status_name] = 0;
	}
	$where = " where 1";
	if($EmployeeID != -1)
	{
		$where = $where." AND AssignedTo = $EmployeeID";
	}
	
	$sql = "SELECT Status,COUNT(*) as count FROM `corporate_tickets` $where GROUP BY Status";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				extract($row);
				$Status = rtrim($Status);
				$ticket_status_array['Corporate'][$Status] = $count;
				if($Status == "Work In Progress" || $Status == "Assigned")
				{
					$Pending_Corporate = $Pending_Corporate + $count;
				}
				if($Status == "Work In Progress" || $Status == "Assigned" || $Status == "Closed" || $Status == "Cancel")
				{
					$Total_Corporate = $Total_Corporate + $count;
				}
				if($Status == "Cancel")
				{
					$Cancel_Corporate = $Cancel_Corporate + $count;
				}
				if($Status == "Closed")
				{
					$Closed_Corporate = $Closed_Corporate + $count;
				}
			}
		}
	} else {
		//echo $sql;
	}
	$ticket_status_array['Corporate']['Pending'] = $Pending_Corporate;
	if($Total_Corporate != 0)
	{
		$Pending_Corporate_percentage = round(($Pending_Corporate / $Total_Corporate) * 100);
	}
	$ticket_status_array['Corporate']['Pending_percentage'] = $Pending_Corporate_percentage;
	$ticket_status_array['Corporate']['Closed'] = $Closed_Corporate;
	if($Total_Corporate != 0)
	{
		$Closed_Corporate_percentage = round(($Closed_Corporate / $Total_Corporate) * 100);
	}
	$ticket_status_array['Corporate']['Closed_percentage'] = $Closed_Corporate_percentage;
	$ticket_status_array['Corporate']['Cancel'] = $Cancel_Corporate;
	if($Total_Corporate != 0)
	{
		$Cancel_Corporate_percentage = round(($Cancel_Corporate / $Total_Corporate) * 100);
	}
	$ticket_status_array['Corporate']['Cancel_percentage'] = $Cancel_Corporate_percentage;
	$ticket_status_array['Corporate']['Total'] = $Total_Corporate;


	$Total_PPM = 0;
	$Pending_PPM = 0;
	$Pending_PPM_percentage = "N.A.";
	$Closed_PPM = 0;
	$Closed_PPM_percentage = "N.A.";
	$Cancel_PPM = 0;
	$Cancel_PPM_percentage = "N.A.";

	$where = " where IsActive = 1";
	$status_array = _getTableRecords($conn,'ppm_ticket_status',$where);
	foreach($status_array as $status)
	{
		$status_name = $status['Status'];
		$ticket_status_array['PPM'][$status_name] = 0;
	}
	$where = " where 1";
	if($EmployeeID != -1)
	{
		$where = $where." AND AssignedTo = $EmployeeID";
	}
	
	$sql = "SELECT Status,COUNT(*) as count FROM `ppm_tickets` $where GROUP BY Status";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				extract($row);
				$Status = rtrim($Status);
				$ticket_status_array['PPM'][$Status] = $count;
				if($Status == "Work In Progress" || $Status == "Assigned")
				{
					$Pending_PPM = $Pending_PPM + $count;
				}
				if($Status == "Work in Progress" || $Status == "Assigned" || $Status == "Closed" || $Status == "Cancel")
				{
					$Total_PPM = $Total_PPM + $count;
				}
				if($Status == "Cancel")
				{
					$Cancel_PPM = $Cancel_PPM + $count;
				}
				if($Status == "Closed")
				{
					$Closed_PPM = $Closed_PPM + $count;
				}
			}
		}
	} else {
		//echo $sql;
	}
	$ticket_status_array['PPM']['Pending'] = $Pending_PPM;
	if($Total_PPM != 0)
	{
		$Pending_PPM_percentage = round(($Pending_PPM / $Total_PPM) * 100);
	}
	$ticket_status_array['PPM']['Pending_percentage'] = $Pending_PPM_percentage;
	$ticket_status_array['PPM']['Closed'] = $Closed_PPM;
	if($Total_PPM != 0)
	{
		$Closed_PPM_percentage = round(($Closed_PPM / $Total_PPM) * 100);
	}
	$ticket_status_array['PPM']['Closed_percentage'] = $Closed_PPM_percentage;
	$ticket_status_array['PPM']['Cancel'] = $Cancel_PPM;
	if($Total_PPM != 0)
	{
		$Cancel_PPM_percentage = round(($Cancel_PPM / $Total_PPM) * 100);
	}
	$ticket_status_array['PPM']['Cancel_percentage'] = $Cancel_PPM_percentage;
	$ticket_status_array['PPM']['Total'] = $Total_PPM;

	return $ticket_status_array;
}

function getTXWorkAPPAllTicketStatus($conn,$EmployeeID)
{
	$ticket_status_array = array();
	$where = " where IsActive = 1";
	$status_array = _getTableRecords($conn,'corporate_tickets_status',$where);
	foreach($status_array as $status)
	{
		$status_name = $status['Status'];
		$ticket_status_array[$status_name] = 0;
	}
	// $where = " where 1";
	// if($EmployeeID != -1)
	// {
	// 	$where = $where." AND AssignedTo = $EmployeeID";
	// }
	
	// $sql = "SELECT Status,COUNT(*) as count FROM `corporate_tickets` $where GROUP BY Status";
	$sql = "SELECT 'corporate_tickets' AS TableName, COUNT(*) AS TotalCoun FROM corporate_tickets WHERE AssignedTo = '$EmployeeID' UNION ALL SELECT 'confirm_booking' AS TableName, COUNT(*) AS TotalCount FROM confirm_booking WHERE AssignedTo = '$EmployeeID' UNION ALL SELECT 'ppm_tickets' AS TableName, COUNT(*) AS TotalCount FROM ppm_tickets WHERE AssignedTo = '$EmployeeID';";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				extract($row);
				$Status = rtrim($Status);
				$ticket_status_array[$Status] = $count;
			}
		}
	} else {
		echo $sql;
	}
	return $ticket_status_array;
}

function getChartData($conn,$status_array,$TicketStatusArray)
{
	$response = array();
	$bg_color = array();
	$stat_array = array();
	$stat_data = array();
	foreach($status_array as $i_status)
	{
		array_push($stat_array,$i_status['Status']);
		array_push($bg_color,$i_status['Color']);
		$s_data = $TicketStatusArray[$i_status['Status']];
		array_push($stat_data,$s_data);
	}
	$response['bg_color'] = $bg_color;
	$response['stat_array'] = $stat_array;
	$response['stat_data'] = $stat_data;
	return $response;
}

function getVendorTicketStatus($conn,$EmployeeID)
{
	$ticket_status_array = array();
	$where = " where IsActive = 1";
	$status_array = _getTableRecords($conn,'booking_status',$where);
	foreach($status_array as $status)
	{
		$status_name = $status['Status'];
		$ticket_status_array[$status_name] = 0;
	}
	$where = " where 1";
	if($EmployeeID != -1)
	{
		$where = $where." AND AssignedTo = $EmployeeID";
	}
	$sql = "SELECT Status,COUNT(*) as count FROM `confirm_booking` $where GROUP BY Status";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				extract($row);
				$Status = rtrim($Status);
				$ticket_status_array[$Status] = $count;
			}
		}
	} else {
		//echo $sql;
	}
	return $ticket_status_array;
}

function GetTicketsByStatusAndEmployeeID($conn,$EmployeeID,$Status)
{
	$where = " where AssignedTo = $EmployeeID AND Status = '$Status'";
	$vendor_tickets_details = _getTableRecords($conn,'confirm_booking', $where);
	return $vendor_tickets_details;
}

function GetCorporateTicketsByStatusAndEmployeeID($conn,$EmployeeID,$Status)
{
	$where = " where AssignedTo = $EmployeeID AND Status = '$Status'";
	$corporate_tickets_details = _getTableRecords($conn,'corporate_tickets', $where);
	return $corporate_tickets_details;
}

function GetTicketsByStatusBranchCorporate($conn,$CorporateID,$BranchID,$Status)
{
	if($BranchID == -1){
     $where = " where CorporateID = $CorporateID AND Status = '$Status'";
	}else{
		$where = " where CorporateID = $CorporateID AND BranchID = $BranchID AND Status = '$Status'";
	}

	$corporate_tickets_details = _getTableRecords($conn,'corporate_tickets', $where);
	return $corporate_tickets_details;
}
function GetGroupedTicketStatusByCorporate($conn,$corporate_array)
{
	$sql = "SELECT CorporateID,Status,Count(*) as count_tickets FROM `corporate_tickets` where (CorporateID != 1 AND CorporateID != 3 and CorporateID != 10) and IsActive = 1 GROUP by CorporateID,Status ORDER BY CorporateID";
	$result = mysqli_query($conn, $sql);
	$response = array();
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				extract($row);
				$Status = rtrim($Status);
				$response[$CorporateID]['CorporateName'] = $corporate_array[$CorporateID]['CompanyName'];
				$response[$CorporateID][$Status] = $count_tickets;
				
			}
		}
	} else {
		//echo $sql;
	}
	return $response;
}


?>