<?php
class Ppmtickets extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}
	public function getPPMTicketStatusArray($action)
	{
		$status_array = array();
		if($action == "All")
		{
			$where = " where 1 ORDER BY DisplayPriority DESC";
		}
		else
		{
			$where = " where IsActive = 1 ORDER BY DisplayPriority DESC";
		}
		$status_array = $this->_getTableRecords($this->conn,'ppm_ticket_status',$where);
		return $status_array;
	}
	function GetPPMTicketsStatsbyAccounts($data)
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
		$sql = "SELECT CorporateID,Status,Count(*) as TotalCount FROM `ppm_tickets` WHERE 1 $where_state $where_state_in_string AND (PPMDate >= '$start_date' and PPMDate <= '$end_date') AND IsActive = 1 GROUP BY CorporateID,Status ORDER BY TotalCount DESC";
	
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
	public function getPPMMediaTicketImage($TicketID)
	{
		$where  = " where TicketID = '$TicketID' ";
		$response = $this->_getTableRecords($this->conn,'ppm_ticket_media', $where);
		return $response;
	}
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

		$upload_dir = __DIR__ . "/../media/ppm_ticket_media/";
		if (!is_dir($upload_dir)) {
			@mkdir($upload_dir, 0775, true);
		}

		$ticket_media_name = $TicketID . "_media_" . $currentTimestamp . "." . $file_ext;
		$path              = $upload_dir . $ticket_media_name;

		if (!move_uploaded_file($file_tmp, $path)) {
			$response['message'] = "Could not save the uploaded file. Please try again.";
			return $response;
		}

		$safe_action     = mysqli_real_escape_string($this->conn, $media_action);
		$safe_image      = mysqli_real_escape_string($this->conn, $ticket_media_name);
		$safe_created_by = mysqli_real_escape_string($this->conn, $CreatedBy);
		$safe_created_dt = mysqli_real_escape_string($this->conn, $CreatedDate);
		$safe_created_tm = mysqli_real_escape_string($this->conn, $CreatedTime);

		$ticket_media_query = "INSERT INTO ppm_ticket_media
			(TicketID, Action, Image, CreatedBy, CreatedDate, CreatedTime)
			VALUES($TicketID, '$safe_action', '$safe_image', '$safe_created_by', '$safe_created_dt', '$safe_created_tm')";

		$response = $this->_InsertTableRecords($this->conn, $ticket_media_query);
		if (!is_array($response)) {
			$response = array('error' => false, 'message' => 'Media Uploaded');
		}
		return $response;
	}
	public function CheckForTicketImages($TicketID)
	{
		$response['error'] = false;
		$response['message'] = " All images are uploaded";
		$where = " where TicketID = $TicketID and Action = 'pre_img'";
		$pre_images_uploaded = $this->_getTotalRows($this->conn,'ppm_ticket_media',$where);
		if($pre_images_uploaded == 0)
		{
			$response['error'] = true;
			$response['message'] = " No Pre Image(s) is uploaded for the ticket, Kindly upload the same from details section!";
			return $response;
		}

		$where = " where TicketID = $TicketID and Action = 'post_img'";
		$pre_images_uploaded = $this->_getTotalRows($this->conn,'ppm_ticket_media',$where);
		if($pre_images_uploaded == 0)
		{
			$response['error'] = true;
			$response['message'] = " No Post Image(s) is uploaded for the ticket, Kindly upload the same from details section!";
			return $response;
		}
		return $response;

	}
	public function DeletePPMTicket($data)
	{
		$ID = $data['ID'];
		$query = " where TicketID = $ID";	
		$this->delete_identity_filter_disable($this->conn,'ppm_ticket_media', $query);
		$query = " where ID = $ID";
		return $this->delete_identity_filter_disable($this->conn,'ppm_tickets', $query);
	}

	public function getPPMTicketDetails($data)
	{
		$TicketID = "";
		if(isset($data['TicketID']))
		{
			$TicketID = $data['TicketID'];
		}
		$response = array();
		$response['data'] = array();
		$where = " where ID = $TicketID";
		$response['data'] = $this->_getTableDetails($this->conn,'ppm_tickets', $where);
		$response['error'] = false;
		$response['message'] = "Ticket Details fetched";

		{
			$sql = "SELECT a.*,b.CompanyName,c.BranchSite,c.BranchAddress1,c.BranchCode from `ppm_tickets` a,company b,branch c WHERE a.CorporateID = b.ID AND a.BranchID = c.ID and a.ID = $TicketID";
			//echo $sql;
			$result=mysqli_query($this->conn,$sql);
			if($result)
			{
				$row = $result->fetch_assoc();
			}
			else
			{
				$error = mysqli_error($this->conn);
				echo $sql;
				echo $error;
			}
			$response['data'] = $row;
		}
		$where = " where TicketID = $TicketID";
		$response_images = $this->_getTableRecords($this->conn,'ppm_ticket_media', $where);
		//print_r($response_images);
		$response_final_images = array();
		$response['data']['PreImg'] = 0;
		$response['data']['PostImg'] = 0;
		foreach($response_images as $response_image)
		{
			//print_r($response_image);
			$temp =  $response_image;
			$temp['Image'] = "https://techxpertindia.in/admin/media/ppm_ticket_media/".$response_image['Image'];
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
		$general_service_report_details = $this->_getTableDetails($this->conn,'ppm_ticket_general_service_report','where TicketID = '.$TicketID);
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
		$response['data']['ticket_images'] = $response_final_images;
		return $response;
	}
	public function getPPMGeneralServiceReportDetails($data)
	{
		$TicketID = $data['TicketID'];
		$ticket_details = $this->_getTableDetails($this->conn,'ppm_tickets','where ID = '.$TicketID);
		$BranchAssetID = $ticket_details['BranchAssetID'];
		if($BranchAssetID != -1)
		{
			$branch_asset_details = $this->_getTableDetails($this->conn,'branch_assets','where ID = '.$BranchAssetID);
			$category_id = $branch_asset_details['Category'];
		}
		$general_service_report_details = $this->_getTableDetails($this->conn,'ppm_ticket_general_service_report','where TicketID = '.$TicketID);
		if($general_service_report_details != null)
		{
			if($general_service_report_details['ClientSignature'] != "")
			{
				require_once __DIR__ . '/../includes/media_url.inc.php';
				$general_service_report_details['ClientSignature'] = signatureMediaUrl($general_service_report_details['ClientSignature']);
			}
			if($category_id == 34)
			{
				$ServicereportID = $general_service_report_details['ID'];
				$hvac_service_report_data = $this->_getTableDetails($this->conn,'ppm_hvac_service_report',' where ServicereportID = '.$ServicereportID);
				if($hvac_service_report_data != null)
				{
					$general_service_report_details['hvac_data'] = $hvac_service_report_data;
				}

			}
			$response = $general_service_report_details;
		}
		else
		{
			// Get Ticket Message
			
			$BranchID = $ticket_details['BranchID'];
			$branch_details = $this->_getTableDetails($this->conn,'branch','where ID = '.$BranchID);
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

	public function closePPMTicket($data)
	{
		$response = array();
		$TicketID = $data['TicketID'];
		$TicketStatus = $data['TicketStatus'];
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		if($TicketStatus == "Closed")
		{
			$CloseDate = $CreatedDate;
			$CloseTime = $CreatedTime;
			$sql_update = " Status = 'Closed',CloseDate = '$CreatedDate',CloseTime='$CloseTime' where ID = $TicketID";
			$response = $this->_UpdateTableRecords($this->conn,'ppm_tickets',$sql_update);
		}
		return $response;
	}

	public function returnHVACChecklistValue($val)
	{
		if($val == "yes")
		{
			return "OK";
		}
		if($val == "no")
		{
			return "NOT OK";
		}
		return $val;
	}
	
    public function returnChecklistValue($val)
	{
		if($val == "yes")
		{
			return "OK";
		}
		if($val == "no")
		{
			return "NOT OK";
		}
		return $val;
	}

	public function getPPMTicketStatusArrayGorupedByTicketStatus($CorporateID,$filter)
	{
		$status_count = array();
		$status_array = $this->getPPMTicketStatusArray("All");
		foreach($status_array as $status)
		{
			$StatusName = $status['Status'];
			$status_count[$StatusName] = 0;
		}
		$status_count['Overdue'] = 0;
		$filter_is_active = " AND a.IsActive = 1";
		$filter_state = "";
		if(isset($filter['state']))
		{
			if($filter['state'] != "")
			{
				$filter_state = " AND a.BranchID IN (Select ID from branch where BranchState = '".$filter['state']."')";
			}
		}
		$filter_region = "";

		if(isset($filter['region']))
		{
			if($filter_state == "" && $filter['region'] != "")
			{
				$where = " where RegionName = '".$filter['region']."'";
				$RegionID = $this->_getTableDetails($this->conn,'region',$where)['ID'];
				$filter_region = " AND a.BranchID IN (Select ID from branch where BranchState IN (Select StateName from state where RegionID = $RegionID))";
			}
		}

		$filter_ticket_type = "";
		if(isset($filter['ticket_type']))
		{
			if($filter['ticket_type'] != "")
			{
				$filter_ticket_type = " AND a.Type = '".$filter['ticket_type']."'";
			}
		}

		$filter_ticket_status = "";
		if(isset($filter['ticket_status']))
		{
			if($filter['ticket_status'] != "")
			{
				$filter_ticket_status = " AND a.Status = '".$filter['ticket_status']."'";
			}
		}

		$filter_branch = "";
		if(isset($filter['branch']))
		{
			if($filter['branch'] != "")
			{
				$filter_branch = " AND a.BranchID = '".$filter['branch']."'";
			}
		}
		$filter_state_lead = "";
		if(isset($filter['sql_in_state_string']))
		{
			if($filter['sql_in_state_string'] != "")
			{
				$filter_state_lead = "AND a.BranchID IN (Select ID from branch where BranchState IN (".$filter['sql_in_state_string']."))";
			}
		}
		$filter_account_branch_manager = "";
		if(isset($filter['sql_in_branch_account_string']))
		{
			if($filter['sql_in_branch_account_string'] != "")
			{
				$filter_account_branch_manager = " AND a.BranchID IN (".$filter['sql_in_branch_account_string'].")";
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
				$filter_date = " AND (a.PPMDate >= '$StartDate' and a.PPMDate <= '$EndDate') ";
			}
		}
		if($CorporateID != -1)
		{
			$sql = "SELECT CASE WHEN a.Status IN ('Planned', 'Raised') AND a.PPMDate < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 'Overdue' ELSE a.Status END AS Status, COUNT(*) AS ticket_counts FROM ppm_tickets a INNER JOIN branch b ON a.BranchID = b.ID  INNER JOIN company c ON a.CorporateID = c.ID INNER JOIN branch_assets d ON a.BranchAssetID = d.ID where a.CorporateID = $CorporateID $filter_is_active $filter_ticket_status $filter_ticket_type $filter_state $filter_region $filter_branch $filter_state_lead $filter_account_branch_manager $filter_date GROUP BY CASE WHEN a.Status IN ('Planned', 'Raised') AND a.PPMDate < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 'Overdue' ELSE a.Status END";
		}
		else
		{
			$sql = "SELECT CASE WHEN a.Status IN ('Planned', 'Raised') AND a.PPMDate < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 'Overdue' ELSE a.Status END AS Status, COUNT(*) AS ticket_counts FROM ppm_tickets a INNER JOIN branch b ON a.BranchID = b.ID  INNER JOIN company c ON a.CorporateID = c.ID INNER JOIN branch_assets d ON a.BranchAssetID = d.ID where 1 $filter_is_active $filter_ticket_status $filter_ticket_type $filter_state $filter_region $filter_branch $filter_state_lead $filter_account_branch_manager $filter_date GROUP BY CASE WHEN a.Status IN ('Planned', 'Raised') AND a.PPMDate < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 'Overdue' ELSE a.Status END";
		}


		$result = mysqli_query($this->conn, $sql);
		$Total = 0;
		if ($result) {
			if ($result->num_rows > 0) {
				while($row = $result->fetch_assoc())
				{
					$StatusName = $row['Status'];
					if(isset($status_count[$StatusName]))
					{
						$Total = $Total+$row['ticket_counts'];
						$status_count[$StatusName] = $row['ticket_counts'];
					}
				}
			}
		} 
		$status_count['Total'] = $Total;
		return $status_count;

	}
}