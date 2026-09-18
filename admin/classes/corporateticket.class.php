<?php 
class Corporateticket extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function CheckforDuplicateClientTicketID($ClientTicketID)
	{
		$duplicate = false;
		$filter = " where ClientTicketID = '$ClientTicketID'";
		if(!$this->check_unique_identity_filter($this->conn,'corporate_tickets', $filter))
		{
			$duplicate = true;
		}
		return $duplicate;
	}

	public function getCorporateTicketStatusArray($action)
	{
		$status_array = array();
		if($action == "All")
		{
			$where = " where 1";
		}
		else
		{
			$where = " where IsActive = 1";
		}
		$where = $where." ORDER BY Priority DESC";
		$status_array = $this->_getTableRecords($this->conn,'corporate_tickets_status',$where);
		return $status_array;

	}

	public function UpdateTicketType($data)
	{
		$service_type = $data['service_type'];
		$TicketID = $data['TicketID'];
		$where = " Type = '$service_type' where ID = $TicketID";
		$response = $this->_UpdateTableRecords($this->conn, 'corporate_tickets', $where);
		return $response;
	}

	public function UpdateTicketService($data)
	{
		$service_name = $data['service_name'];
		$sub_service_name = $data['sub_service_name'];
		$TicketID = $data['TicketID'];
		$where = " Service = '$service_name',Subservice = '$sub_service_name' where ID = $TicketID";
		$response = $this->_UpdateTableRecords($this->conn, 'corporate_tickets', $where);
		return $response;
	}

	public function UpdateTicketBranch($data)
	{
		$edit_branch_modal_branch_id = $data['edit_branch_modal_branch_id'];
		$TicketID = $data['TicketID'];
		$where = " BranchID = $edit_branch_modal_branch_id where ID = $TicketID";
		$response = $this->_UpdateTableRecords($this->conn, 'corporate_tickets', $where);
		return $response;
	}

	public function DeleteTicket($data)
	{
		$ID = $data['ID'];
		$query = " where TicketID = $ID";	
		$this->delete_identity_filter($this->conn,'ticket_quotation', $query);
		$this->delete_identity_filter($this->conn,'corporate_tickets_finance', $query);
		$this->delete_identity_filter($this->conn,'ticket_conversation', $query);
		$this->delete_identity_filter($this->conn,'ticket_media', $query);
		$this->delete_identity_filter($this->conn,'corporate_ticket_status_history', $query);
		$query = " where ID = $ID";
		return $this->delete_identity_filter($this->conn,'corporate_tickets', $query);
	}

	public function UpdateTicketFinances($data)
	{
		$this->setTimeZone();
		if($data['form_action'] == "add")
		{
			$TicketID = $data['TicketID'];
			$T_VisitorNo = $data['T_VisitorNo'];
			$T_VisitCharge = $data['T_VisitCharge'];
			if($T_VisitCharge == "")
			{
				$T_VisitCharge = 0;
			}
			$T_MaterialCost = $data['T_MaterialCost'];
			if($T_MaterialCost == "")
			{
				$T_MaterialCost = 0;
			}
			$T_LabourCost = $data['T_LabourCost'];
			if($T_LabourCost == "")
			{
				$T_LabourCost = 0;
			}
			//$T_CostumerPrice = $data['T_CostumerPrice'];
			$T_TotalPrice = $data['T_TotalPrice'];

			$C_VisitorNo = $data['C_VisitorNo'];
			$C_VisitCharge = $data['C_VisitCharge'];
			if($C_VisitCharge == "")
			{
				$C_VisitCharge = 0;
			}
			$C_MaterialCost = $data['C_MaterialCost'];
			if($C_MaterialCost == "")
			{
				$C_MaterialCost = 0;
			}
			$C_LabourCost = $data['C_LabourCost'];
			if($C_LabourCost == "")
			{
				$C_LabourCost = 0;
			}
			//$C_CostumerPrice = $data['C_CostumerPrice'];
			$C_TotalPrice = $data['C_TotalPrice'];
			if(isset($data['Status']))
			{
				$Status = $data['Status'];
			}
			else
			{
				$Status = 1;
			}
			$CreatedBy = $data['CreatedBy'];
			$CreatedDate = date('Y-m-d');
			$CreatedTime = date('H:i:s');

			$ticket_finance_sql = "INSERT INTO corporate_tickets_finance(TicketID,T_VisitorNo,T_VisitCharge,T_MaterialCost,T_LabourCost,T_TotalPrice,C_VisitorNo,C_VisitCharge,C_MaterialCost,C_LabourCost,C_TotalPrice,Status,Remarks,CreatedDate,CreatedTime) VALUES ('$TicketID','$T_VisitorNo','$T_VisitCharge','$T_MaterialCost','$T_LabourCost','$T_TotalPrice','$C_VisitorNo','$C_VisitCharge','$C_MaterialCost','$C_LabourCost','$C_TotalPrice','$Status','','$CreatedDate','$CreatedTime')";
			$response = $this->_InsertTableRecords($this->conn,$ticket_finance_sql);
		}
		if($data['form_action'] == "edit")
		{
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

			$where_get_details = " where TicketID = '$TicketID'";
			$TicketFinanceStatus = $this->_getTableDetails($this->conn,'corporate_tickets_finance',$where_get_details)['Status'];

			if($TicketFinanceStatus == 10 || $TicketFinanceStatus == 0)
			{
				$TicketFinanceStatus = 1;
			}
			
			$ticket_finance_sql = " T_VisitorNo = $T_VisitorNo,T_VisitCharge = $T_VisitCharge,T_MaterialCost = $T_MaterialCost,T_LabourCost = $T_LabourCost,T_TotalPrice = $T_TotalPrice,C_VisitorNo = $C_VisitorNo,C_VisitCharge = $C_VisitCharge,C_MaterialCost = $C_MaterialCost,C_TotalPrice = $C_TotalPrice,Status=$TicketFinanceStatus where TicketID = '$TicketID'";
			$response = $this->_UpdateTableRecords($this->conn,'corporate_tickets_finance',$ticket_finance_sql);
		}

		return $response;
	}

	public function GetCorporateFinancebyTicketID($data)
	{
		$TicketID = $data['TicketID'];
		$where = " where TicketID = $TicketID";
		$response = $this->_getTableDetails($this->conn,'corporate_tickets_finance',$where);
		return $response;
	}

	public function getCorporateTicketFinanceStatusArray()
	{
		$status_array = array();
		$where = " where 1";
		$status_array_raw = $this->_getTableRecords($this->conn,'corporate_ticket_finance_status',$where);
		foreach($status_array_raw as $e_status)
		{
			extract($e_status);
			$status_array[$Status] = $StatusName;
		}
		return $status_array;

	}

	public function UpdateTicketFinancStatus($data)
	{
		// get current remarks
		$TicketID = $data['TicketID'];
		$where = " where TicketID = '$TicketID'";
		$old_Remarks = $this->_getTableDetails($this->conn,'corporate_tickets_finance',$where)['Remarks'];
		if($old_Remarks != "")
		{
			$new_Remarks = $old_Remarks."<br>".$data['remarks_ticket_status'];
		}
		else
		{
			$new_Remarks = $data['remarks_ticket_status'];
		}
		if($data['TicketApprovalStatus'] == 10)
		{
			$nextStatus = 10;
		}
		else
		{
			$nextStatus = $data['TicketNextStatus'] * $data['TicketApprovalStatus'];
		}
		$sql_update_status = "Remarks = '$new_Remarks',Status = $nextStatus where TicketID = '$TicketID'";
		$response = $this->_UpdateTableRecords($this->conn,'corporate_tickets_finance',$sql_update_status);
		return $response;		
	}
	public function recalculateTicketFinances()
	{
		$where = "where 1";
		$ticket_finances = $this->_getTableRecords($this->conn,'corporate_tickets_finance',$where);
		foreach($ticket_finances as $ticket_finance)
		{
			extract($ticket_finance);
			$total_customer = 0;
			$total_self = 0;
			$total_customer = $C_VisitorNo*$C_VisitCharge+$C_MaterialCost+$C_LabourCost;
			$total_self = $T_VisitorNo*$T_VisitCharge+$T_MaterialCost+$T_LabourCost;
			$sql_update = " T_TotalPrice = $total_self,C_TotalPrice=$total_customer where TicketID = $TicketID";
			echo $sql_update."<br>";
			$response = $this->_UpdateTableRecords($this->conn,'corporate_tickets_finance',$sql_update);
			print_r($response);
		}
	}
	public function LoadAllTicket_Searchtext($search_text)
	{
		$sql = "SELECT TicketID, TicketID as text FROM corporate_tickets WHERE TicketID LIKE '%$search_text%'";
	    $result = $this->conn->query($sql);

	    if ($result->num_rows > 0) {
	        // Fetch the results and format them for Select2
	        $results = array();
	        while ($row = $result->fetch_assoc()) {
	            $results[] = array(
	                'id' => $row['TicketID'],
	                'text' => $row['text']
	            );
	        }

	        // Return the results as JSON
	        return json_encode(array('results' => $results));
	    } else {
	        // No results found
	        return json_encode(array('results' => array()));
	    }
	}

	public function GetCorporateTicketsGroupedByBranchID($CorporateID,$filter)
	{
		$filter_branch = "";
		if(isset($filter['branch']) && $filter['branch'] != "" && $filter['branch'] != -1)
		{
			$filter_branch = " AND BranchID = ".(int)$filter['branch'];
		}
		$filter_state = "";
		if($filter['state'] != "")
		{
			$filter_state = " AND BranchID IN (Select ID from branch where BranchState = '".$filter['state']."')";
		}
		$filter_region = "";
		if($filter_state == "" && $filter['region'] != "")
		{
			$where = " where RegionName = '".$filter['region']."'";
			$RegionID = $this->_getTableDetails($this->conn,'region',$where)['ID'];
			$filter_region = " AND BranchID IN (Select ID from branch where BranchState IN (Select StateName from state where RegionID = $RegionID))";
		}
		$filter_ticket_type = "";
		if($filter['ticket_type'] != "")
		{
			$filter_ticket_type = " AND Type = '".$filter['ticket_type']."'";
		}
		$filter_ticket_status = "";
		if($filter['ticket_status'] != "")
		{
			$filter_ticket_status = " AND Status = '".$filter['ticket_status']."'";
		}
		$filter_state_lead = "";
		if(isset($filter['sql_in_state_string']))
		{
			if($filter['sql_in_state_string'] != "")
			{
				$filter_state_lead = " AND BranchID IN (Select ID from branch where BranchState IN (".$filter['sql_in_state_string']."))";
			}
		}
		$filter_account_branch_manager = "";
		if(isset($filter['sql_in_branch_account_string']))
		{
			if($filter['sql_in_branch_account_string'] != "")
			{
				$filter_account_branch_manager = " AND BranchID IN (".$filter['sql_in_branch_account_string'].")";
			}
		}
		$filter_date = "";
		if(isset($filter['filter_date']))
		{
			if($filter['filter_date'] != "")
			{
				$filter_date = $filter['filter_date'];
				$StartDate = explode(" - ",$filter_date)[0];
				$EndDate = explode(" - ",$filter_date)[1];
				$filter_date = " AND (CreatedDate >= '$StartDate' and CreatedDate <= '$EndDate') ";
			}
		}
		$sql = "Select BranchID,COUNT(*) as ticket_counts from corporate_tickets where CorporateID = $CorporateID $filter_ticket_type $filter_state $filter_region $filter_ticket_status $filter_branch $filter_state_lead $filter_account_branch_manager $filter_date AND IsActive = 1 Group by BranchID";
		//echo $sql;
		$response = array();
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}

	public function GetCorporateTicketsCountByFilters($CorporateID,$filter)
	{
		$filter_branch = "";
		if(isset($filter['branch']) && $filter['branch'] != "" && $filter['branch'] != -1)
		{
			$filter_branch = " AND BranchID = ".(int)$filter['branch'];
		}
		$filter_state = "";
		if($filter['state'] != "")
		{
			$filter_state = " AND BranchID IN (Select ID from branch where BranchState = '".$filter['state']."')";
		}
		$filter_region = "";
		if($filter_state == "" && $filter['region'] != "")
		{
			$where = " where RegionName = '".$filter['region']."'";
			$RegionID = $this->_getTableDetails($this->conn,'region',$where)['ID'];
			$filter_region = " AND BranchID IN (Select ID from branch where BranchState IN (Select StateName from state where RegionID = $RegionID))";
		}
		$filter_ticket_type = "";
		if($filter['ticket_type'] != "")
		{
			$filter_ticket_type = " AND Type = '".$filter['ticket_type']."'";
		}
		$filter_ticket_status = "";
		if($filter['ticket_status'] != "")
		{
			$filter_ticket_status = " AND Status = '".$filter['ticket_status']."'";
		}
		$filter_state_lead = "";
		if(isset($filter['sql_in_state_string']))
		{
			if($filter['sql_in_state_string'] != "")
			{
				$filter_state_lead = " AND BranchID IN (Select ID from branch where BranchState IN (".$filter['sql_in_state_string']."))";
			}
		}
		$filter_account_branch_manager = "";
		if(isset($filter['sql_in_branch_account_string']))
		{
			if($filter['sql_in_branch_account_string'] != "")
			{
				$filter_account_branch_manager = " AND BranchID IN (".$filter['sql_in_branch_account_string'].")";
			}
		}
		$filter_date = "";
		if(isset($filter['filter_date']))
		{
			if($filter['filter_date'] != "")
			{
				$filter_date = $filter['filter_date'];
				$StartDate = explode(" - ",$filter_date)[0];
				$EndDate = explode(" - ",$filter_date)[1];
				$filter_date = " AND (CreatedDate >= '$StartDate' and CreatedDate <= '$EndDate') ";
			}
		}
		if($CorporateID != -1)
		{
			$sql = "Select COUNT(*) as ticket_counts from corporate_tickets where IsActive = 1 AND CorporateID = $CorporateID".$filter_ticket_type.$filter_ticket_status.$filter_branch.$filter_state.$filter_region.$filter_date.$filter_state_lead.$filter_account_branch_manager;
		}
		else
		{
			$sql = "Select COUNT(*) as ticket_counts from corporate_tickets where IsActive = 1 ".$filter_ticket_type.$filter_ticket_status.$filter_branch.$filter_state.$filter_region.$filter_date.$filter_state_lead.$filter_account_branch_manager;
		}

		$response = array();
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				return $row['ticket_counts'];
			}
		} else {
			return 0;
		}
		return 0;
	}
	public function getTicketStatusArrayGorupedByTicketStatus($CorporateID,$filter)
	{
		$status_count = array();
		$status_array = $this->getCorporateTicketStatusArray("All");
		foreach($status_array as $status)
		{
			$StatusName = $status['Status'];
			$status_count[$StatusName] = 0;
		}
		$filter_state = "";
		if(isset($filter['state']))
		{
			if($filter['state'] != "")
			{
				$filter_state = " AND BranchID IN (Select ID from branch where BranchState = '".$filter['state']."')";
			}
		}
		$filter_region = "";
		if(isset($filter['region']))
		{
			if($filter_state == "" && $filter['region'] != "")
			{
				$where = " where RegionName = '".$filter['region']."'";
				$RegionID = $this->_getTableDetails($this->conn,'region',$where)['ID'];
				$filter_region = " AND BranchID IN (Select ID from branch where BranchState IN (Select StateName from state where RegionID = $RegionID))";
			}
		}

		$filter_ticket_type = "";
		if(isset($filter['ticket_type']))
		{
			if($filter['ticket_type'] != "")
			{
				$filter_ticket_type = " AND Type = '".$filter['ticket_type']."'";
			}
		}

		$filter_ticket_status = "";
		if(isset($filter['ticket_status']))
		{
			if($filter['ticket_status'] != "")
			{
				$filter_ticket_status = " AND Status = '".$filter['ticket_status']."'";
			}
		}

		$filter_branch = "";
		if(isset($filter['branch']) && $filter['branch'] != "" && $filter['branch'] != -1)
		{
			$filter_branch = " AND BranchID = ".(int)$filter['branch'];
		}

		$filter_state_lead = "";
		if(isset($filter['sql_in_state_string']))
		{
			if($filter['sql_in_state_string'] != "")
			{
				$filter_state_lead = "AND BranchID IN (Select ID from branch where BranchState IN (".$filter['sql_in_state_string']."))";
			}
		}

		// $filter_account_branch_manager = "";
		// if(isset($filter['sql_in_branch_account_string']))
		// {
		// 	if($filter['sql_in_branch_account_string'] != "")
		// 	{
		// 		$filter_account_branch_manager = " AND BranchID IN (".$filter['sql_in_branch_account_string'].")";
		// 	}
		// }

		$filter_account_branch_manager = "";

			if (
			    isset($filter['sql_in_branch_account_string']) &&
			    trim($filter['sql_in_branch_account_string']) != "" &&
			    trim($filter['sql_in_branch_account_string']) != "''"
			)
			{

				if(isset($filter['ticket_type'])=="Supply")
				{
					$sql_in_branch_account_string='';
				}

			    $branchString = trim($filter['sql_in_branch_account_string']);
			    // Extra safety: remove quotes if accidentally passed
			    $branchString = str_replace("'", "", $branchString);

			    if ($branchString != "")
			    {
			        $filter_account_branch_manager = " AND BranchID IN ($branchString)";
			    }
			}

		$filter_type = "";
		if(isset($filter['Type']))
		{
			if($filter['Type'] != "")
			{
				$filter_type = "AND Type = '".$filter['Type']."'";
			}
		}
		$filter_date = "";
		if(isset($filter['filter_date']))
		{
			if($filter['filter_date'] != "")
			{
				$filter_date = $filter['filter_date'];
				$StartDate = explode(" - ",$filter_date)[0];
				$EndDate = explode(" - ",$filter_date)[1];
				$filter_date = " AND (CreatedDate >= '$StartDate' and CreatedDate <= '$EndDate') ";
			}
		}
		$filter_active = " AND IsActive = 1";
		
		if($CorporateID != -1)
		{
			$sql = "Select Status,Count(*) as ticket_counts from corporate_tickets where CorporateID = $CorporateID AND IsActive = 1 $filter_ticket_status $filter_ticket_type $filter_state $filter_region $filter_branch $filter_type $filter_state_lead $filter_account_branch_manager $filter_date $filter_active GROUP BY Status";
		}
		else
		{
			$sql = "Select Status,Count(*) as ticket_counts from corporate_tickets where 1 AND IsActive = 1 $filter_ticket_status $filter_ticket_type $filter_state $filter_region $filter_branch $filter_type $filter_state_lead $filter_account_branch_manager $filter_date $filter_active GROUP BY Status";
		}
		$Total = 0;
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while($row = $result->fetch_assoc())
				{
					$StatusName = $row['Status'];
					if(isset($status_count[$StatusName]))
					{
						$Total = $Total + $row['ticket_counts'];
						$status_count[$StatusName] = $row['ticket_counts'];
					}
				}
			}
		} 
		$status_count['Total'] = $Total;

		return $status_count;

	}

	// public function UploadTicketMedia($data,$file_data)
	// {
	// 	$media_action = $data['media_action'];
	// 	$TicketID = $data['TicketID'];
	// 	$currentTimestamp = time();
	// 	$CreatedBy = $data['CreatedBy'];
	// 	$CreatedDate = $data['CreatedDate'];
	// 	$CreatedTime = $data['CreatedTime'];
	// 	if (isset($file_data['media_image']['name'])  && $file_data['media_image']['name'] != '')
	//     {
	//         $extn_pan = explode('.', $file_data["media_image"]["name"]);
	//         $ticket_media_name   = $TicketID."_media_".$currentTimestamp.".".$extn_pan[1];
	//         $path = "../../media/ticket_media/".$ticket_media_name;
	//        // echo "<br>".$path;
	//         move_uploaded_file($_FILES["media_image"]["tmp_name"], $path);
	//     }
	//     $ticket_media_query = "INSERT INTO ticket_media (TicketID,Action,Image,CreatedBy,CreatedDate,CreatedTime ) VALUES('$TicketID','$media_action','$ticket_media_name','$CreatedBy','$CreatedDate','$CreatedTime')";
	//     $response = $this->_InsertTableRecords($this->conn,$ticket_media_query);
	//     return $response;
	// }

		public function UploadTicketMedia($data, $file_data)
		{
			$response = array('error' => true, 'message' => '');

			$TicketID         = isset($data['TicketID']) ? (int) $data['TicketID'] : 0;
			$media_action     = isset($data['media_action']) ? trim($data['media_action']) : '';
			$CreatedBy        = isset($data['CreatedBy']) ? $data['CreatedBy'] : '';
			$CreatedDate      = isset($data['CreatedDate']) ? $data['CreatedDate'] : date('Y-m-d');
			$CreatedTime      = isset($data['CreatedTime']) ? $data['CreatedTime'] : date('H:i:s');
			$currentTimestamp = time();

			if ($TicketID <= 0 || $media_action === '') {
				$response['message'] = "Missing ticket id or action.";
				return $response;
			}

			if (!isset($file_data['media_image']) || $file_data['media_image']['error'] !== UPLOAD_ERR_OK) {
				$err = isset($file_data['media_image']['error']) ? $file_data['media_image']['error'] : -1;
				if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
					$response['message'] = "File is too large to upload. Please use an image under 5 MB.";
				} elseif ($err === UPLOAD_ERR_NO_FILE) {
					$response['message'] = "Please choose an image to upload.";
				} else {
					$response['message'] = "Image upload failed. Please try again.";
				}
				return $response;
			}

			$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
			$allowed_mime       = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

			$file_name = $file_data["media_image"]["name"];
			$file_tmp  = $file_data["media_image"]["tmp_name"];
			$file_size = (int) $file_data["media_image"]["size"];
			$file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

			if ($file_size <= 0 || $file_size > (5 * 1024 * 1024)) {
				$response['message'] = "File is too large. Maximum allowed size is 5 MB.";
				return $response;
			}

			$file_mime = '';
			if (function_exists('finfo_open')) {
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				if ($finfo) {
					$file_mime = (string) finfo_file($finfo, $file_tmp);
					finfo_close($finfo);
				}
			} elseif (function_exists('mime_content_type')) {
				$file_mime = (string) @mime_content_type($file_tmp);
			} else {
				$image_info = @getimagesize($file_tmp);
				if (is_array($image_info) && isset($image_info['mime'])) {
					$file_mime = $image_info['mime'];
				}
			}

			if (!in_array($file_ext, $allowed_extensions, true) || !in_array($file_mime, $allowed_mime, true)) {
				$response['message'] = "Only image files are allowed (jpg, jpeg, png, gif, webp).";
				return $response;
			}

			$upload_dir = __DIR__ . "/../media/ticket_media/";
			if (!is_dir($upload_dir)) {
				@mkdir($upload_dir, 0775, true);
			}

			$ticket_media_name = $TicketID . "_media_" . $currentTimestamp . "." . $file_ext;
			$path              = $upload_dir . $ticket_media_name;

			if (!move_uploaded_file($file_tmp, $path)) {
				$response['message'] = "Could not save the uploaded file. Please try again.";
				return $response;
			}

			$safe_action      = mysqli_real_escape_string($this->conn, $media_action);
			$safe_image       = mysqli_real_escape_string($this->conn, $ticket_media_name);
			$safe_created_by  = mysqli_real_escape_string($this->conn, $CreatedBy);
			$safe_created_dt  = mysqli_real_escape_string($this->conn, $CreatedDate);
			$safe_created_tm  = mysqli_real_escape_string($this->conn, $CreatedTime);

			$ticket_media_query = "INSERT INTO ticket_media
				(TicketID, Action, Image, CreatedBy, CreatedDate, CreatedTime)
				VALUES($TicketID, '$safe_action', '$safe_image', '$safe_created_by', '$safe_created_dt', '$safe_created_tm')";

			$response = $this->_InsertTableRecords($this->conn, $ticket_media_query);
			if (!is_array($response)) {
				$response = array('error' => false, 'message' => 'Media Uploaded');
			}
			return $response;
		}

	public function GetTicketMedia($TicketID)
	{
		$filter = " where TicketID = $TicketID";
		$response = $this->_getTableRecords($this->conn,'ticket_media',$filter);
		return $response;
	}

	public function GetPPMTicketMedia($TicketID)
	{
		$filter = " where TicketID = $TicketID";
		$response = $this->_getTableRecords($this->conn,'ppm_ticket_media',$filter);
		return $response;
	}

	public function CheckForTicketImages($TicketID)
	{
		$response['error'] = false;
		$response['message'] = " All images are uploaded";
		$where = " where TicketID = $TicketID and Action = 'pre_img'";
		$pre_images_uploaded = $this->_getTotalRows($this->conn,'ticket_media',$where);
		if($pre_images_uploaded == 0)
		{
			$response['error'] = true;
			$response['message'] = " No Pre Image(s) is uploaded for the ticket, Kindly upload the same from details section!";
			return $response;
		}

		$where = " where TicketID = $TicketID and Action = 'post_img'";
		$pre_images_uploaded = $this->_getTotalRows($this->conn,'ticket_media',$where);
		if($pre_images_uploaded == 0)
		{
			$response['error'] = true;
			$response['message'] = " No Post Image(s) is uploaded for the ticket, Kindly upload the same from details section!";
			return $response;
		}
		return $response;

	}

	public function GetQuotationDetail($TicketID)
	{
		$response = array();
		$filter = " where TicketID = $TicketID";
		$response = $this->_getTableDetails($this->conn,'corporate_ticket_quotation',$filter);
		return $response;	
	}

	public function GetQuotationDetailbyID($QuotationID)
	{
		$response = array();
		$filter = " where ID = $QuotationID";
		$response = $this->_getTableDetails($this->conn,'corporate_ticket_quotation',$filter);
		return $response;	
	}

	public function UpdateTicketQuotation($data)
	{
		extract($data);
		if($data['TicketQuotationID'] == -1)
		{
			$sql = "INSERT INTO `corporate_ticket_quotation`(`TicketID`, `QuotationStatus`, `Remarks`, `CreatedBy`, `CreatedDate`, `CreatedTime`) VALUES($TicketID,'$QuotationStatus','$Remarks','$CreatedBy','$CreatedDate','$CreatedTime')";
			$response = $this->_InsertTableRecords($this->conn,$sql);
			return $response;
		}
		else
		{
			$update_remarks = "";
			if(isset($data['Remarks']))
			{
				$Remarks = $data['Remarks'];
				$update_remarks = ",Remarks = '$Remarks'";
			}
			$sql = " QuotationStatus = '$QuotationStatus'".$update_remarks." where ID = $QuotationID";
			$response = $this->_UpdateTableRecords($this->conn,'corporate_ticket_quotation',$sql);
			return $response;

		}
	}

	public function UpdateTicketQuotationHistory($data)
	{
		$response = array();
		if (!isset($data['QuotationID']) || (int) $data['QuotationID'] === -1) {
			return $response;
		}

		$QuotationID = (int) $data['QuotationID'];
		$QuotationStatus = mysqli_real_escape_string($this->conn, (string) ($data['QuotationStatus'] ?? ''));
		$Remarks = mysqli_real_escape_string($this->conn, (string) ($data['Remarks'] ?? ''));
		$CreatedBy = mysqli_real_escape_string($this->conn, (string) ($data['CreatedBy'] ?? ''));
		$CreatedDate = mysqli_real_escape_string($this->conn, (string) ($data['CreatedDate'] ?? ''));
		$CreatedTime = mysqli_real_escape_string($this->conn, (string) ($data['CreatedTime'] ?? ''));

		$nextRow = $this->_getSQLDetails(
			$this->conn,
			'SELECT COALESCE(MAX(`ID`), 0) + 1 AS next_id FROM corporate_ticket_quotation_history'
		);
		$nextId = (int) ($nextRow['next_id'] ?? 1);
		if ($nextId <= 0) {
			$nextId = 1;
		}

		$sql = "INSERT INTO `corporate_ticket_quotation_history`(`ID`, `QuotationID`, `QuotationStatus`, `Remarks`, `CreatedBy`, `CreatedDate`, `CreatedTime`)
		        VALUES ($nextId, $QuotationID, '$QuotationStatus', '$Remarks', '$CreatedBy', '$CreatedDate', '$CreatedTime')";
		$response = $this->_InsertTableRecords($this->conn, $sql);

		return $response;
	}
	public function GetQuotationHistory($QuotationID)
	{
		$filter = " where QuotationID = $QuotationID ORDER BY ID DESC";
		$response = $this->_getTableRecords($this->conn,'corporate_ticket_quotation_history',$filter);
		return $response;
	}

	public function UpdateQuotationLineItem($data)
	{
		extract($data);
		// check if line item is already there
		$filter = " where ID = $LineItemID";
		$line_item_details = $this->_getTableDetails($this->conn,'corporate_rate_card',$filter);
		$PerItemPrice = $line_item_details['Price'];
		$filter = " where LineItemID = $LineItemID and QuotationID = $QuotationID";
		$num_rows = $this->_getTotalRows($this->conn,'corporate_ticket_quotation_items',$filter);
		if($num_rows > 0)
		{
			// Get Existing Line Item Qty and Price 
			$q_line_item_details = $this->_getTableDetails($this->conn,'corporate_ticket_quotation_items',$filter);
			$ID = $q_line_item_details['ID'];
			$old_qty = $q_line_item_details['Qty'];
			$new_qty = $old_qty+$quantity;
			$TotalPrice = $PerItemPrice * $new_qty;
			$update_sql = " Qty = $new_qty,TotalPrice = '$TotalPrice' where ID=$ID";
			$response = $this->_UpdateTableRecords($this->conn,'corporate_ticket_quotation_items',$update_sql);
		}
		else
		{
			$Qty = $quantity;
			$TotalPrice = $PerItemPrice * $Qty;
			$nextRow = $this->_getSQLDetails(
				$this->conn,
				'SELECT COALESCE(MAX(`ID`), 0) + 1 AS next_id FROM corporate_ticket_quotation_items'
			);
			$nextId = (int) ($nextRow['next_id'] ?? 1);
			if ($nextId <= 0) {
				$nextId = 1;
			}
			$sql = "INSERT INTO corporate_ticket_quotation_items(`ID`, `QuotationID`, `LineItemID`, `Qty`, `PerItemPrice`, `TotalPrice`, `CreatedBy`, `CreatedDate`, `CreatedTime`) VALUES($nextId, $QuotationID, $LineItemID, $Qty, '$PerItemPrice', '$TotalPrice', '$CreatedBy', '$CreatedDate', '$CreatedTime')";
			$response = $this->_InsertTableRecords($this->conn,$sql);
		}
		return $response;
	}

	public function GetQuotationLineItems($QuotationID)
	{
		$response = array();
		$sql = "SELECT a.*,b.Type,b.Category,b.SubCategory,b.LineItemName,b.Make,b.HSN,b.ARCCode,b.UoM,b.Tax FROM `corporate_ticket_quotation_items` a INNER JOIN corporate_rate_card b ON a.LineItemID = b.ID WHERE QuotationID = $QuotationID";
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}	
	
	public function GetTicketDetails($TicketID)
	{
		$response = array();

		$where = " where ID = $TicketID";
		$ticket_details = $this->_getTableDetails($this->conn,'corporate_tickets',$where);
		$CorporateID = $ticket_details['CorporateID'];
		$BranchID = $ticket_details['BranchID'];
		$response['ticket_details'] = $ticket_details;

		$where = " where ID = $BranchID";
		$branch_details = $this->_getTableDetails($this->conn,'branch',$where);
		$response['branch_details'] = $branch_details;

		$where = " where ID = $CorporateID";
		$corporate_details = $this->_getTableDetails($this->conn,'company',$where);
		$response['corporate_details'] = $corporate_details;

		$BranchCity = $branch_details['BranchCity'];
		$where = " where CityName = '$BranchCity'";
		$city_details = $this->_getTableDetails($this->conn,'citydata',$where);
		$response['city_details'] = $city_details;
		$response['CorporateID'] = $CorporateID;

		
		return $response;

	}

	public function GetPPMTicketDetails($TicketID)
	{
		$response = array();  

		$where = " where ID = $TicketID";
		$ticket_details = $this->_getTableDetails($this->conn,'ppm_tickets',$where);
		$CorporateID = $ticket_details['CorporateID'];
		$ticket_details['Type'] = 'PPM';
		$CorporateID = $ticket_details['CorporateID'];
		$BranchID = $ticket_details['BranchID'];
		$response['ticket_details'] = $ticket_details;

		$where = " where ID = $BranchID";
		$branch_details = $this->_getTableDetails($this->conn,'branch',$where);
		$response['branch_details'] = $branch_details;

		$where = " where ID = $CorporateID";
		$corporate_details = $this->_getTableDetails($this->conn,'company',$where);
		$response['corporate_details'] = $corporate_details;

		$BranchCity = $branch_details['BranchCity'];
		$where = " where CityName = '$BranchCity'";
		$city_details = $this->_getTableDetails($this->conn,'citydata',$where);
		$response['city_details'] = $city_details;
		$response['CorporateID'] = $CorporateID;

		
		return $response;

	}


	function GetCompanyDetails($CompanyID){
		
		$where = " where ID = $CompanyID";
		$company_details = $this->_getTableDetails($this->conn,'company',$where);
		return $company_details;
	}

	function GetDailyTicketStatsbyStatus($data)
	{
		$response = array();
		$response_stats = array();
		$start_date = $data['start_date'];
		$end_date = $data['end_date'];
		$CorporateID = $data['CorporateID'];
		if($CorporateID != -1)
		{
			$sql = "Select CreatedDate,Status,Count(*) as tickets_count from corporate_ticket_status_history where TicketID in (Select ID from corporate_tickets where CorporateID = $CorporateID) and (CreatedDate >= '$start_date' and CreatedDate <= '$end_date') GROUP BY CreatedDate,Status";
		}
		else
		{
			$sql = "Select CreatedDate,Status,Count(*) as tickets_count from corporate_ticket_status_history where (CreatedDate >= '$start_date' and CreatedDate <= '$end_date') GROUP BY CreatedDate,Status";
		}	
	
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			//echo $sql;
		}
		foreach($response as $i_response)
		{
			extract($i_response);
			$CreatedDate = $i_response['CreatedDate'];
			$response_stats[$CreatedDate][$Status] = $tickets_count;
		}
		return $response_stats;

	}
	function GetTicketsStatsbyAccounts($data)
	{
		$response = array();
		$response_stats = array();
		$start_date = $data['start_date'];
		$end_date = $data['end_date'];
		$Type = $data['Type'];
		$where_state = "";
		if($data['StateName'] != "")
		{
			$StateName = $data['StateName'];
			$where_state = " AND BranchID IN (Select ID from branch where BranchState = '$StateName')";
		}
		$where_state_in_string = "";
		if($data['sql_in_state_string'] != "")
		{
			$sql_in_state_string = $data['sql_in_state_string'];
			$where_state_in_string = " AND BranchID in (Select ID from branch where BranchState IN ($sql_in_state_string))";
		}
		$sql = "SELECT CorporateID,Status,Count(*) as TotalCount FROM `corporate_tickets` WHERE Type = '$Type' $where_state $where_state_in_string and (CreatedDate >= '$start_date' and CreatedDate <= '$end_date') AND IsActive = 1 GROUP BY CorporateID,Status ORDER BY TotalCount DESC";

	
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			//echo $sql;
		}
		foreach ($response as $i_response) {
        $CorporateID = $i_response['CorporateID'];
        $Status = $i_response['Status'];
        $TotalCount = $i_response['TotalCount'];
        
        // Populate response_stats
        if (!isset($response_stats[$CorporateID])) {
            $response_stats[$CorporateID] = array(
                'Total' => 0 // Initialize Total count for this CorporateID
            );
        }
        
        // Add status-specific count
        $response_stats[$CorporateID][$Status] = $TotalCount;
        
	        // Update the total count for this CorporateID
	        $response_stats[$CorporateID]['Total'] += $TotalCount;
	    }
	    
	    // Sort by 'Total' in descending order
	    uasort($response_stats, function($a, $b) {
	        return $b['Total'] - $a['Total'];
	    });
	    
	    return $response_stats;

	}

	public function GetAttendedDate($TicketID)
	{
		$filter = " where Status='Generate OTP to Start' and TicketID = $TicketID";
		$ticket_attended_details = $this->_getTableDetails($this->conn,'corporate_ticket_status_history',$filter);
		if($ticket_attended_details != null)
		{
			return $ticket_attended_details['CreatedDate'];
		}
		else
		{
			return "";
		}
	}


	function getTicketsFinanceTotalCost($filters)
	{
		$where_corporate = "";
		if($filters['CorporateID'] != -1)
		{
			$CorporateID = $filters['CorporateID'];
			$where_corporate = " AND TicketID IN (Select ID from corporate_tickets where CorporateID = $CorporateID)";
		}
		$where_state = "";
		if($filters['State'] != "")
		{
			$State = $filters['State'];
			$where_state = " AND TicketID IN (Select ID from corporate_tickets where BranchID IN (Select ID from branch where BranchState = '$State'))";
		}
		$where_city = "";
		if($filters['City'] != "")
		{
			$City = $filters['City'];
			$where_state = " AND TicketID IN (Select ID from corporate_tickets where BranchID IN (Select ID from branch where BranchCity = '$City'))";
		}
		$where_branch = "";
		if($filters['Branch'] != "")
		{
			$branch = $filters['Branch'];
			$where_state = " AND TicketID IN (Select ID from corporate_tickets where BranchID IN (Select ID from branch where BranchSite = '$branch'))";
		}
		$where_status = "";
		if($filters['FinanceStatus'] != "")
		{
			$status = $filters['FinanceStatus'];
			$where_status = " AND Status = $status";
		}
		$where = " where 1";
		$StartDate = $filters['StartDate'];
		$EndDate = $filters['EndDate'];
		$where_date = " AND TicketID IN (Select ID from corporate_tickets where CreatedDate>='$StartDate' AND CreatedDate<='$EndDate')";
		$where = $where.$where_corporate.$where_state.$where_branch.$where_status.$where_date;
		$sql = "SELECT 
		COALESCE(SUM(T_TotalPrice), 0) AS T_TotalPrice,
  		COALESCE(SUM(T_VisitorNo * T_VisitCharge), 0) AS T_VisitCharge,
  		COALESCE(SUM(T_MaterialCost), 0) AS T_MaterialCost,
  		COALESCE(SUM(T_LabourCost), 0) AS T_LabourCost,
  		COALESCE(SUM(C_VisitorNo * C_VisitCharge), 0) AS C_VisitCharge,
  		COALESCE(SUM(C_MaterialCost), 0) AS C_MaterialCost,
  		COALESCE(SUM(C_LabourCost), 0) AS C_LabourCost,
  		COALESCE(SUM(C_TotalPrice), 0) AS C_TotalPrice
		FROM `corporate_tickets_finance`
		$where";
		$response = $this->_getSQLDetails($this->conn,$sql);
		return $response;
	}

	function GetTopStatesRevenue()
	{
		$sql = "SELECT b.BranchState, SUM(ctf.C_TotalPrice) AS TotalCustomerPrice, SUM(ctf.T_TotalPrice) AS TotalTechXpertPrice FROM corporate_tickets_finance ctf JOIN corporate_tickets ct ON ctf.TicketID = ct.ID JOIN branch b ON ct.BranchID = b.ID GROUP BY b.BranchState ORDER BY TotalCustomerPrice DESC LIMIT 6";
		$response = $this->_getSQLRecords($this->conn,$sql);
		return $response;
	}

	function UpdateTicketFinancesfromQuotation($QuotationID,$TicketID)
	{
		$C_TotalPrice = 0;
		$C_LabourCost = 0;
		$C_VisitCharge = 0;
		$C_VisitorNo = 0;
		$C_MaterialCost = 0;
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		$sql = "SELECT a.Qty,a.PerItemPrice,a.TotalPrice,a.QuotationID,b.LineItemName,b.Type FROM `corporate_ticket_quotation_items` a INNER JOIN corporate_rate_card b ON a.LineItemID = b.ID WHERE a.QuotationID = $QuotationID";
		$response = $this->_getSQLRecords($this->conn,$sql);
		foreach($response as $i_response)
		{
			if($i_response['Type'] == "Visit Charge" || strpos($i_response['LineItemName'], 'Visit') !== false)
			{
				$C_VisitCharge = $i_response['PerItemPrice'] + $C_VisitCharge;
				$C_VisitorNo = $C_VisitorNo+$i_response['Qty'];
				$C_TotalPrice = $C_TotalPrice + ($C_VisitorNo * $C_VisitCharge);
			}
			else if($i_response['Type'] == "Service")
			{
				$C_LabourCost = $C_LabourCost + $i_response['TotalPrice'];
				$C_TotalPrice = $C_TotalPrice + $i_response['TotalPrice'];
			}
			else if($i_response['Type'] == "Product")
			{
				$C_MaterialCost = $C_MaterialCost + $i_response['TotalPrice'];
				$C_TotalPrice = $C_TotalPrice + $i_response['TotalPrice'];
			}
			else
			{
				// DO Nothing
			}
		}
		

		// check to insert or update
		$where = " where TicketID = '$TicketID'";
		if($this->_getTotalRows($this->conn,'corporate_tickets_finance',$where) > 0)
		{

			$rowData = [
	                'C_VisitorNo' => $C_VisitorNo,
	                'C_VisitCharge' => $C_VisitCharge,
	                'C_MaterialCost' => $C_MaterialCost,
	                'C_LabourCost' => $C_LabourCost,
	                'C_TotalPrice' => $C_TotalPrice
		            ];
	        $whereCondition = [
	            'TicketID' => $TicketID
	        ];
			$response = $this->_UpdateTableRecords_prepare($this->conn,'corporate_tickets_finance', $rowData, $whereCondition);
		}
		else
		{
			$rowData = [
	                'TicketID' => $TicketID,
	                'T_VisitorNo' => 0,
	                'T_VisitCharge' => 0,
	                'T_MaterialCost' => 0,
	                'T_LabourCost' => 0,
	                'T_TotalPrice' => 0,
	                'C_VisitorNo' => $C_VisitorNo,
	                'C_VisitCharge' => $C_VisitCharge,
	                'C_MaterialCost' => $C_MaterialCost,
	                'C_LabourCost' => $C_LabourCost,
	                'C_TotalPrice' => $C_TotalPrice,
	                'Remarks' => '',
	                'CreatedDate' => $CreatedDate,
	                'CreatedTime' => $CreatedTime,
		            ];
			$response = $this->_InsertTableRecords_prepare($this->conn,'corporate_tickets_finance',$rowData);
		}
		/*var_dump($rowData);
		var_dump($response);*/

	}

	function GetQuotationPricesGroupByStatus($filters)
	{
		$filter = " where 1 ";
		$filter_corporate = "";
		if(isset($filters['CorporateID']))
		{
			$CorporateID = $filters['CorporateID'];
			if($CorporateID != -1 && $CorporateID != "")
				$filter_corporate = " AND ct.CorporateID = $CorporateID";
		}
		$filter_branch = "";
		if(isset($filters['branch']) && $filters['branch'] != "" && $filters['branch'] != -1)
		{
			$filter_branch = " AND ct.BranchID = ".(int)$filters['branch'];
		}
		$filter_category = "";
		if(isset($filters['Category']))
		{
			$Category = $filters['Category'];
			if($Category != -1 && $Category != "")
				$filter_category = " AND ct.Service = '$Category'";
		}
		$filter_ticket_status = "";
		if(isset($filters['Category']))
		{
			$TicketStatus = $filters['TicketStatus'];
			if($TicketStatus != -1 && $TicketStatus != "")
				$filter_ticket_status = " AND ct.Status = '$TicketStatus'";
		}
		$filter_state_lead = "";
		if(isset($filters['sql_in_state_string']))
		{
			if($filters['sql_in_state_string'] != "")
				$filter_state_lead = " AND ct.BranchID IN (Select ID from branch where BranchState IN (".$filters['sql_in_state_string']."))";
		}
		$filter_account_branch_manager = "";
		if(isset($filters['sql_in_branch_account_string']))
		{
			if($filters['sql_in_branch_account_string'] != "")
			{
				$filter_account_branch_manager = " AND ct.BranchID IN (".$filters['sql_in_branch_account_string'].")";
			}
		}
		$filter_date = "";
		if(isset($filters['filter_date']))
		{
			if($filters['filter_date'] != "")
			{
				$filter_date = $filters['filter_date'];
				$StartDate = explode(" - ",$filter_date)[0];
				$EndDate = explode(" - ",$filter_date)[1];
				$filter_date = " AND (ct.CreatedDate >= '$StartDate' and ct.CreatedDate <= '$EndDate') ";
			}
		}
		$filter_state = "";
		if(isset($filters['state_filter']))
		{
			if($filters['state_filter'] != "")
			{
				$state = $filters['state_filter'];
				$filter_state = " AND (ct.BranchID IN (Select ID from branch where BranchState = '$state'))";
			}
		}
		$filter_region = "";
		if(isset($filters['region_filter']))
		{
			if($filters['region_filter'] != "")
			{
				$region = $filters['region_filter'];
				$where = " where RegionName = '".$region."'";
				$RegionID = $this->_getTableDetails($this->conn,'region',$where)['ID'];
				$filter_region = " AND (ct.BranchID IN (Select ID from branch where BranchState IN (Select StateName from state where RegionID = $RegionID)))";
			}
		}
		$filter = $filter.$filter_corporate.$filter_branch.$filter_category.$filter_ticket_status.$filter_state_lead.$filter_state.$filter_region.$filter_account_branch_manager.$filter_date;
		$sql = "SELECT ctq.QuotationStatus, crc.Type, SUM(ctqi.TotalPrice) AS TotalPriceSum FROM corporate_ticket_quotation ctq INNER JOIN corporate_ticket_quotation_items ctqi ON ctq.ID = ctqi.QuotationID INNER JOIN corporate_rate_card crc  ON ctqi.LineItemID = crc.ID INNER JOIN corporate_tickets ct ON ctq.TicketID = ct.ID ".$filter." GROUP BY ctq.QuotationStatus,crc.Type";
		$response = $this->_getSQLRecords($this->conn,$sql);
		return $response;
	}


	function GetRating($TicketID)
	{
         $filter = " where TicketID = $TicketID";
		$ticket_rating_details = $this->_getTableDetails($this->conn,'ticket_feedback',$filter);
		if($ticket_rating_details != null)
		{
			return $ticket_rating_details['Rating'];
		}
		else
		{
			return "";
		}
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
        AND Status = 'Selected'
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

	
}