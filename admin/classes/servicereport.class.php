<?php 
class Servicereport extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function GetServiceReportDetails($TicketID)
	{
		$filter = " where TicketID = $TicketID";
		$response = $this->_getTableDetails($this->conn,'corporate_ticket_general_service_report',$filter);
		return $response;
	}
	public function GetPPMServiceReportDetails($TicketID)
	{
		$filter = " where TicketID = $TicketID";
		$response = $this->_getTableDetails($this->conn,'ppm_ticket_general_service_report',$filter);
		return $response;
	}

	public function GetServiceReportDetailsbyID($ServicereportID)
	{
		$filter = " where ID = $ServicereportID";
		$response = $this->_getTableDetails($this->conn,'corporate_ticket_general_service_report',$filter);
		return $response;
	}
	public function GetPPMServiceReportDetailsbyID($ServicereportID)
	{
		$filter = " where ID = $ServicereportID";
		$response = $this->_getTableDetails($this->conn,'ppm_ticket_general_service_report',$filter);
		return $response;
	}

	public function GetHvacServiceReportDetailsbyID($HvacServicereportID)
	{
		$filter = " where ID = $HvacServicereportID";
		$response = $this->_getTableDetails($this->conn,'hvac_general_service_report',$filter);
		return $response;
	}

	public function UpdateSignature($file_name,$data)
	{
		if($data['GeneralServiceReportID'] != -1)
		{
			$GeneralServiceReportID = $data['GeneralServiceReportID'];
			$update_sql = " ClientSignature = '$file_name' where ID = $GeneralServiceReportID";
			$response = $this->_UpdateTableRecords($this->conn,'corporate_ticket_general_service_report',$update_sql);
		}
		else
		{
			$TicketID = $data['TicketID'];
			$update_sql = " IsActive = 0 where TicketID = $TicketID";
			$response = $this->_UpdateTableRecords($this->conn,'temp_client_signature',$update_sql);
			$insert_sql = " INSERT INTO temp_client_signature(ClientSignature,TicketID) VALUES ('$file_name',$TicketID)";
			$response = $this->_InsertTableRecords($this->conn,$insert_sql);
		}
		return $response;
	}

	public function UpdatePPMSignature($file_name,$data)
	{
		if($data['GeneralServiceReportID'] != -1)
		{
			$GeneralServiceReportID = $data['GeneralServiceReportID'];
			$update_sql = " ClientSignature = '$file_name' where ID = $GeneralServiceReportID";
			$response = $this->_UpdateTableRecords($this->conn,'ppm_ticket_general_service_report',$update_sql);
		}
		else
		{
			$TicketID = $data['TicketID'];
			$update_sql = " IsActive = 0 where TicketID = $TicketID and Type='PPM'";
			$response = $this->_UpdateTableRecords($this->conn,'temp_client_signature',$update_sql);
			$insert_sql = " INSERT INTO temp_client_signature(ClientSignature,TicketID,Type) VALUES ('$file_name',$TicketID,'PPM')";
			$response = $this->_InsertTableRecords($this->conn,$insert_sql);
		}
		return $response;
	}

	
}