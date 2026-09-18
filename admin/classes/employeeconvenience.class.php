<?php 
class Employeeconvenience extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function getAllEmployeesConvenience()
	{
		$where = " where 1 ORDER BY ID DESC";
		$response = _getTableRecords($this->conn,'employee_convenience', $where);
		return $response;
	}

	public function DeleteEmployeeConvenience($data)
	{
		$ID = $data['ID'];
		$query = "where ID = $ID";
		$response = delete_identity_filter($this->conn,'employee_convenience', $query);
		return $response;
	}
	public function getEmployeesConvenienceStatusArray()
	{
		$status_array = array();
		$where = " where 1";
		$status_array_raw = $this->_getTableRecords($this->conn,'employee_convenience_status',$where);
		foreach($status_array_raw as $e_status)
		{
			extract($e_status);
			$status_array[$Status] = $StatusName;
		}
		return $status_array;

	}

	public function UpdateConvenienceApproval($data)
	{
		$convenience_charge_remarks = $data['convenience_charge_remarks'];
		$convenience_charge_ID = $data['convenience_charge_ID'];
		$convenience_next_status = $data['convenience_next_status'];
		// Get Remarks
		$where = " where ID = $convenience_charge_ID";
		$ApproverRemarks = $this->_getTableDetails($this->conn,'employee_convenience',$where)['ApproverRemarks'];
		if($ApproverRemarks == "")
		{
			$ApproverRemarks = $convenience_charge_remarks;
		}
		else
		{
			$ApproverRemarks = $ApproverRemarks."<br>".$convenience_charge_remarks;
		}
		$update_sql = " Status = $convenience_next_status,ApproverRemarks='$ApproverRemarks' where ID = $convenience_charge_ID";
		return $this->_UpdateTableRecords($this->conn,'employee_convenience',$update_sql);
	}

	

}