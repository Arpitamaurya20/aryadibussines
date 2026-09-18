<?php 
class IMSSetting extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}
	
	public function CheckDuplicateLeadStatus($data)
	{
		$LeadStatus = $data['LeadStatus'];
		$filter = " where Status = '$LeadStatus'";
		$response = $this->check_unique_identity_filter($this->conn,'lead_status', $filter);
		return $response;
	}
	public function CheckDuplicateTelecallerLeadStatus($data)
	{
		$LeadStatus = $data['LeadStatus'];
		$filter = " where Status = '$LeadStatus'";
		$response = $this->check_unique_identity_filter($this->conn,'telecaller_lead_status', $filter);
		return $response;
	}
	public function InsertLeadStatus($data)
	{
		$LeadStatus = $data['LeadStatus'];
		$final_status = $data['final_status'];
		$not_interested_status = $data['not_interested_status'];
		$default_status = $data['default_status'];
		$LeadColor = $data['LeadColor'];
		$sql = "INSERT INTO lead_status(Status,DefaultStatus,NotInterestedStatus,FinalStatus,LeadColor) VALUES ('$LeadStatus',$default_status,$not_interested_status,$final_status,'$LeadColor')";
		$response_insert_status_details= $this->_InsertTableRecords($this->conn,$sql);
		return $response_insert_status_details;

	}
	public function InsertTelecallerLeadStatus($data)
	{
		$LeadStatus = $data['LeadStatus'];
		$final_status = $data['final_status'];
		$not_interested_status = $data['not_interested_status'];
		$default_status = $data['default_status'];
		$LeadColor = $data['LeadColor'];
		$sql = "INSERT INTO telecaller_lead_status(Status,DefaultStatus,NotInterestedStatus,FinalStatus,LeadColor) VALUES ('$LeadStatus',$default_status,$not_interested_status,$final_status,'$LeadColor')";
		$response_insert_status_details= $this->_InsertTableRecords($this->conn,$sql);
		return $response_insert_status_details;

	}
	public function UpdateLeadStatus($data)
	{
		extract($data);
		if($final_status == 1)
		{
			$update_sql = "FinalStatus = 0 where 1";
			$response = $this->_UpdateTableRecords($this->conn,'lead_status',$update_sql);
		}
		if($default_status == 1)
		{
			$update_sql = "DefaultStatus = 0 where 1";
			$response = $this->_UpdateTableRecords($this->conn,'lead_status',$update_sql);
		}
		if($not_interested_status == 1)
		{
			$update_sql = "NotInterestedStatus = 0 where 1";
			$response = $this->_UpdateTableRecords($this->conn,'lead_status',$update_sql);
		}
		$update_sql = " Status = '$LeadStatus',DefaultStatus=$default_status,NotInterestedStatus=$not_interested_status,FinalStatus=$final_status,LeadColor='$LeadColor' where ID = $lead_status_form_id";
		$response = $this->_UpdateTableRecords($this->conn,'lead_status',$update_sql);
		return $response;
	}
	public function UpdateTelecallerLeadStatus($data)
	{
		extract($data);
		if($final_status == 1)
		{
			$update_sql = "FinalStatus = 0 where 1";
			$response = $this->_UpdateTableRecords($this->conn,'telecaller_lead_status',$update_sql);
		}
		if($default_status == 1)
		{
			$update_sql = "DefaultStatus = 0 where 1";
			$response = $this->_UpdateTableRecords($this->conn,'telecaller_lead_status',$update_sql);
		}
		if($not_interested_status == 1)
		{
			$update_sql = "NotInterestedStatus = 0 where 1";
			$response = $this->_UpdateTableRecords($this->conn,'telecaller_lead_status',$update_sql);
		}
		$update_sql = " Status = '$LeadStatus',DefaultStatus=$default_status,NotInterestedStatus=$not_interested_status,FinalStatus=$final_status,LeadColor='$LeadColor' where ID = $lead_status_form_id";
		$response = $this->_UpdateTableRecords($this->conn,'telecaller_lead_status',$update_sql);
		return $response;
	}
	
	
	public function GetAllLeadStatus()
	{
		$where = " where IsActive = 1";
		$lead_status_array = $this->_getTableRecords($this->conn,'lead_status',$where);
		return $lead_status_array;
	}
	public function GetAllTelecallerLeadStatus()
	{
		$where = " where IsActive = 1";
		$lead_status_array = $this->_getTableRecords($this->conn,'telecaller_lead_status',$where);
		return $lead_status_array;
	}
	public function GetAllLeadSources()
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $lead_source_details = $this->_getTableRecords($this->conn, "lead_source", $where);
        return $lead_source_details;
	}

	public function CheckDuplicateSourceName($data)
	{
		$SourceName = $data['SourceName'];
		$filter = " where SourceName = '$SourceName'";
		$response = $this->check_unique_identity_filter($this->conn,'lead_source', $filter);
		return $response;
	}
	public function InsertSourceName($data)
	{
		$SourceName = $data['SourceName'];

		$sql = "INSERT INTO lead_source(SourceName) VALUES ('$SourceName')";
		$response_insert_source_details= $this->_InsertTableRecords($this->conn,$sql);
		return $response_insert_source_details;

	}
	public function UpdateSourceName($data)
	{
		extract($data);
		$update_sql = " SourceName = '$SourceName' where ID = $lead_source_form_id";
		$response = $this->_UpdateTableRecords($this->conn,'lead_source',$update_sql);
		return $response;
	}
	public function GetFinalLeadStatus()
	{
		$FinalStatus = "";
		$filter = " where FinalStatus = 1";
		$response = $this->_getTableDetails($this->conn,'lead_status',$filter);
		if($response != NULL)
		{
			$FinalStatus = $response['Status'];
		}
		return $FinalStatus;
	}
	public function GetFinalTelecallerLeadStatus()
	{
		$FinalStatus = "";
		$filter = " where FinalStatus = 1";
		$response = $this->_getTableDetails($this->conn,'telecaller_lead_status',$filter);
		if($response != NULL)
		{
			$FinalStatus = $response['Status'];
		}
		return $FinalStatus;
	}
	public function GetDefaultLeadStatus()
	{
		$DefaultStatus = "";
		$filter = " where DefaultStatus = 1";
		$response = $this->_getTableDetails($this->conn,'lead_status',$filter);
		if($response != NULL)
		{
			$DefaultStatus = $response['Status'];
		}
		return $DefaultStatus;
	}
	public function GetDefaultTelecallerLeadStatus()
	{
		$DefaultStatus = "";
		$filter = " where DefaultStatus = 1";
		$response = $this->_getTableDetails($this->conn,'telecaller_lead_status',$filter);
		if($response != NULL)
		{
			$DefaultStatus = $response['Status'];
		}
		return $DefaultStatus;
	}


     public function GetAllCourseType()
     {
        $where = " where IsActive = 1 ORDER BY ID DESC";
        $details = $this->_getTableRecords($this->conn, "course_type", $where);
        return $details;
     }

     public function GetAllCourseProgram()
     {
     	$where = " where IsActive = 1 ORDER BY ID DESC";
        $details = $this->_getTableRecords($this->conn, "course_program", $where);
        return $details;
     }
	
 // -----offer---------

	public function GetAllOffers (){
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $lead_source_details = $this->_getTableRecords($this->conn, "l2a_offers", $where);
        return $lead_source_details;
	}



	public function CheckDuplicateofferName($data)
	{
		$SourceName = $data['offerName'];
		$filter = " where OfferName = '$SourceName'";
		$response = $this->check_unique_identity_filter($this->conn,'l2a_offers', $filter);
		return $response;
	}
	public function InsertofferName($data)
	{
		$SourceName = $data['offerName'];
		$value = $data['offerValue'];
		$sql = "INSERT INTO l2a_offers(OfferName,Discountvalue) VALUES ('$SourceName','$value')";
		$response_insert_source_details= $this->_InsertTableRecords($this->conn,$sql);
		return $response_insert_source_details;

	}
	public function UpdateofferName($data)
	{
		extract($data);
		$ID=$data['offer_form_id'];
		$update_sql = "OfferName = '$offerName',Discountvalue='$offerValue' where ID =$ID";
		$response = $this->_UpdateTableRecords($this->conn,'l2a_offers',$update_sql);
		return $response;
	}
}