<?php 
class Sitevisits extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}	
	public function InitiateSiteVisit($data)
	{
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		$TicketID=-1;
		extract($data);
		$Summary = "";
		 $rowData = [
		 	    'TicketID' => $TicketID,
                'BranchID' => $BranchID,
                'CorporateID' => $CorporateID,
                'VisitTitle' => $VisitTitle,
                'ReportNumber' => $ReportNumber,
                'ContactPerson' => $ContactPerson,
                'ContactPersonPhone' => $ContactPersonPhone,
                'ContactPersonEmail' => $ContactPersonEmail,
                'Summary' => $Summary,
                'CreatedDate' => $CreatedDate,
                'CreatedTime' => $CreatedTime,
                'CreatedBy' => $CreatedBy
            ];
            $response = $this->_InsertTableRecords_prepare($this->conn, 'site_visits', $rowData);
		return $response;
	}
	public function GetSiteVisitDetail($SiteVisitID)
	{
		$where = " where ID = $SiteVisitID";
        $response = $this->_getTableDetails($this->conn, 'site_visits', $where);
		return $response;
	}
	public function GetSiteVisitCompleteDetail($SiteVisitID)
	{
		$sql = "Select a.*,b.CompanyName,c.BranchSite from site_visits a INNER JOIN company b ON a.CorporateID = b.ID INNER JOIN branch c ON a.BranchID = c.ID where a.ID = $SiteVisitID";
        $response = $this->_getSQLDetails($this->conn,$sql);
		return $response;
	}
	public function GetAllSiteVisits($data)
	{
		$response = array();
		$CreatedBy = $data['CreatedBy'];
		$sql = "SELECT a.*,b.BranchSite,c.CompanyName,c.CorporateName FROM `site_visits` a INNER JOIN branch b on a.BranchID = b.ID INNER JOIN company c ON a.CorporateID = c.ID WHERE a.CreatedBy = '$CreatedBy' ORDER BY a.ID DESC";
		if(isset($data['filter_limit']))
		{
			$filter_limit = $data['filter_limit'];
			$sql = $sql.$filter_limit;
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
		return $response;
	}


	public function GetAllSiteVisitsTicketID($data)
	{
		$response = array();
		$TicketID = $data['TicketID'];
		$sql = "SELECT * FROM ` site_visit_observation`  WHERE TicketID = '$TicketID' ORDER BY ID DESC";
		if(isset($data['filter_limit']))
		{
			$filter_limit = $data['filter_limit'];
			$sql = $sql.$filter_limit;
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
		return $response;
	}

	public function SubmitSiteObservation($data)
	{
		$response = array();
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		$TicketID=-1;
		$Location = "";
		if(isset($data['Location']))
			$Location = $data['Location'];
		extract($data);
		 $rowData = [
                'SiteVisitID' => $SiteVisitID,
                'TicketID'=>$TicketID,
                'Category' => $Category,
                'Priority' => $Priority,
                'Observation' => $Observation,
                'CompanyRecommendation' => $CompanyRecommendation,
                'ClientRecommendation' => $ClientRecommendation,
                'Location' => $Location,
                'CreatedDate' => $CreatedDate,
                'CreatedTime' => $CreatedTime,
                'CreatedBy' => $CreatedBy
            ];
        $response = $this->_InsertTableRecords_prepare($this->conn, 'site_visit_observation', $rowData);
        
        if($response['error'] == false)
        {
        	$ObservationID = $response['last_insert_id'];
        	$sql_update = "SiteObservationID = $ObservationID where TempObservationID = '$TempObservationID'";
        	$this->_UpdateTableRecords($this->conn,'site_observation_media',$sql_update);
        	$sql_update_site_visit = "Status = 'Observation Recorded' where ID = $SiteVisitID";
        	$this->_UpdateTableRecords($this->conn,'site_visits',$sql_update_site_visit);
        }
		return $response;
	}

	public function UpdateSVSignature($file_name,$data)
	{
		$SiteVisitID = intval($data['SiteVisitID']);
		return $this->_UpdateTableRecords_prepare($this->conn, 'site_visits', [
			'ClientSignature' => $file_name,
		], [
			'ID' => $SiteVisitID,
		]);
	}

	public function CompleteSiteVisit($data)
	{
		$SiteVisitID = intval($data['SiteVisitID']);
		return $this->_UpdateTableRecords_prepare($this->conn, 'site_visits', [
			'Status' => 'Completed',
			'Summary' => $data['Summary'],
			'CompletedDate' => date('Y-m-d'),
			'CompletedTime' => date('H:i:s'),
		], [
			'ID' => $SiteVisitID,
		]);
	}
	public function GetSiteObservations($SiteVisitID)
	{
		$response = array();
		$where = " where SiteVisitID = $SiteVisitID";
		$response = $this->_getTableRecords($this->conn,'site_visit_observation',$where);
		if(sizeof($response) > 0)
		{
			foreach($response as $i=>$i_response)
			{
				$ObservationID = $i_response['ID'];
				//Get Observation media
				$where_observation_media = " where SiteObservationID = $ObservationID";
				$response_observation_media = $this->_getTableRecords($this->conn,'site_observation_media',$where_observation_media);
				$response[$i]['observation_media'] = $response_observation_media;
			}
		}
		return $response;
	}

}