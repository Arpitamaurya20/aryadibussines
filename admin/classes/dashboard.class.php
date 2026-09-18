<?php 
class Dashboard extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function getTicketStatus($CorporateID,$BranchID,$sql_in_string)
	{
		$ticket_status_array = array();
		$where = " where IsActive = 1";
		$status_array = $this->_getTableRecords($this->conn,'corporate_tickets_status',$where);
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
		if($sql_in_string == "")
			$sql = "SELECT Status,COUNT(*) as count FROM `corporate_tickets` $where GROUP BY Status";
		else
			$sql = "SELECT a.Status,COUNT(*) as count FROM `corporate_tickets` a INNER JOIN branch b on a.BranchID = b.ID where b.BranchCity IN (".$sql_in_string.") GROUP BY a.Status";
		//echo "SQL = ".$sql;
		$result = mysqli_query($this->conn, $sql);
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

}