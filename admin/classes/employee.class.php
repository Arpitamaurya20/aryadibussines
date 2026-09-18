<?php 
class Employee extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function setEmployeeArray($action)
	{
		$response = array();
		if($action == "Active")
			$employees_raw = $this->_getTableRecords($this->conn, 'employees', 'where IsActive = 1');
		else
			$employees_raw = $this->_getTableRecords($this->conn, 'employees', 'where 1');
		foreach($employees_raw as $employee)
		{
			$ID = $employee['ID'];
			$Name = $employee['Name'];
			$response[$ID]['Name'] = $Name;
			$response[$ID]['ContactNumber'] = $employee['ContactNumber'];
			$response[$ID]['Email'] = $employee['Email'];
		}
		return $response;

	}

	public function setEmployeeArrayByName($action)
	{
		$response = array();
		if($action == "Active")
			$employees_raw = $this->_getTableRecords($this->conn, 'employees', 'where IsActive = 1');
		else
			$employees_raw = $this->_getTableRecords($this->conn, 'employees', 'where 1');
		foreach($employees_raw as $employee)
		{
			$ID = $employee['ID'];
			$Name = $employee['Name'];
			$response[$Name]['ID'] = $ID;
		}
		return $response;

	}

	public function getEmployeeDetailsfromUserName($Username)
	{
		$row = array();
		$sql = "Select a.* from employees a INNER JOIN users b on a.ID = b.EmployeeID where b.UserName = '$Username'";
		$result = mysqli_query($this->conn, $sql);
		if ($result) 
		{
			if ($result->num_rows > 0) 
			{
				$row = $result->fetch_assoc(); 
			}
		} 
		else 
		{
			//echo $sql;
		}
		return $row;
	}

	public function getMappedEmployeesofCityLead($EmployeeID)
	{
		if($EmployeeID == -1)
		{
			$where = " where 1";
			return $this->_getTableRecords($this->conn,'employees',$where);
		}
		else
		{
			$response = array();
			$sql = "SELECT a.* from employees a INNER JOIN citydata b ON a.City = b.CityName where b.StateID IN (Select DISTINCT(StateID) FROM `citydata` where CorporateLead = $EmployeeID)";
			//echo $sql;
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
		//SELECT a.* from employees a INNER JOIN citydata b ON a.City = b.CityName where b.StateID IN (Select DISTINCT(StateID) FROM `citydata` where CorporateLead = 83);
	}

	public function GetSupervisedEmployees($EmployeeID)
	{
		if($EmployeeID == -1)
		{
			$where = " where 1";
		}
		else
		{
			$where = " where Supervisor = $EmployeeID";
		}
		return $this->_getTableRecords($this->conn,'employees',$where);
	}

	public function getLeaveTypes()
	{
		$response = array();
		$sql = "Select DISTINCT(TypeOfLeave) from leaveconfiguation";
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row['TypeOfLeave']);
				}
			}
		} else {
			//echo $sql;
		}
		return $response;

	}

	public function addLeave($data)
	{
		$response = array();
		$EmployeeID = $data['EmployeeID'];
		$TypeOfLeave = $data['TypeOfLeave'];
		$ReasonOfLeave = $data['ReasonOfLeave'];
		$FromDate = $data['FromDate'];
		$ToDate = $data['ToDate'];
		$CreatedBy = $data['CreatedBy'];
		$CreatedDate = $data['CreatedDate'];
		$CreatedTime = $data['CreatedTime'];
		$Duration = $data['Duration'];
		$sql_insert = "INSERT INTO employee_leave(EmployeeID,TypeOfLeave,ReasonOfLeave,FromDate,ToDate,Duration,CreatedBy,CreatedDate,CreatedTime) VALUES ($EmployeeID,'$TypeOfLeave','$ReasonOfLeave','$FromDate','$ToDate','$Duration','$CreatedBy','$CreatedDate','$CreatedTime')";
		return $this->_InsertTableRecords($this->conn,$sql_insert);
	}

	public function getEmployeeLeaves($data)
	{
		$response = array();
		$EmployeeID = $data['EmployeeID'];
		$where = " where EmployeeID = $EmployeeID ORDER BY ID DESC";
		return $this->_getTableRecords($this->conn,'employee_leave',$where);
	}

	public function InsertRole($data)
	{
		$EmployeeID = $data['EmployeeID'];
		$EmployeeRole = $data['EmployeeRole'];
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		$where = " where EmployeeID = $EmployeeID and Role = '$EmployeeRole' and IsActive = 1";
		echo $where;
		$not_duplicate = $this->check_unique_identity_filter($this->conn,'user_roles',$where);
		if($not_duplicate)
		{
			$sql_insert = " INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($EmployeeID,'$EmployeeRole','$CreatedDate','$CreatedTime')";
			echo $sql_insert;
			$response = $this->_InsertTableRecords($this->conn,$sql_insert);
		}
		return $response;

	}

	public function getEmployeeAttendanceRecords($data)
	{
		$EmployeeID = $data['EmployeeID'];
		$s_year = $data['s_year'];
		$s_month = $data['s_month'];
		$where = " where YEAR(RecordDate) = '$s_year' AND MONTH(RecordDate) = '$s_month' and EmployeeID = $EmployeeID ORDER BY RecordDate DESC";
		$records = $this->_getTableRecords($this->conn,'employee_attendance',$where);
		return $records;
	}


	public function fetchEmployeeAttendanceRecords($RecordDate, $EmployeeID)
	{
		/*if($RecordDate == null || $EmployeeID == null)
		{
			return array();
		}*/
		$where = " where RecordDate = '$RecordDate' and EmployeeID = '$EmployeeID' ORDER BY RecordDate DESC LIMIT 1";
		$records = $this->_getTableRecords($this->conn,'employee_attendance',$where);
		return $records;
	}


	public function calculatetimeDifference($inTime,$outTime) 
	{
	    // Create DateTime objects for out and in times
	    if($inTime == "" || $outTime == "")
	    {
	    	return "N.A.";
	    }
	    else
	    {

		    $out = new DateTime($outTime);
		    $in = new DateTime($inTime);

		    // Calculate the difference
		    $interval = $out->diff($in);

		    // Calculate total minutes
		    $hours = $interval->h + ($interval->days * 24);
		    $minutes = $interval->i;

		    // Format the difference as HH:MM
		    return sprintf('%02d:%02d', $hours, $minutes);
		}
	}

	

}