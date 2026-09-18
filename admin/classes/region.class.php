<?php 
class Region extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}	
	public function GetDistinctRegionByStateNames($state_string)
	{
		$response = array();
		$sql = "Select DISTINCT(r.RegionName) as RegionName from region r JOIN state s ON r.ID = s.RegionID where s.StateName IN ('$state_string')";
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
	public function getTicketStatusArrayGroupedByRegion($CorporateID,$filter)
	{
		$response = array();
		$filter_state = "";
		$filter_region = "";
		$filter_ticket_type = "";
		$filter_ticket_status = "";
		if($filter['state'] != "")
		{
			$filter_state = " AND s.StateName = '".$filter['state']."'";
		}
		if($filter['region'] != "")
		{
			$filter_region = " AND r.RegionName = '".$filter['region']."'";
		}
		if($filter['ticket_type'] != "")
		{
			$filter_ticket_type = " AND ct.Type = '".$filter['ticket_type']."'";
		}
		if($filter['ticket_status'] != "")
		{
			$filter_ticket_status = " AND ct.Status = '".$filter['ticket_status']."'";
		}
		$filter_branch = "";
		if(isset($filter['branch']) && $filter['branch'] != "" && $filter['branch'] != -1)
		{
			$filter_branch = " AND ct.BranchID = ".(int)$filter['branch'];
		}
		$filter_state_lead = "";
		if(isset($filter['sql_in_state_string']))
		{
			if($filter['sql_in_state_string'] != "")
			{
				$filter_state_lead = "AND ct.BranchID IN (Select ID from branch where BranchState IN (".$filter['sql_in_state_string']."))";
			}
		}
		$filter_account_branch_manager = "";
		if(isset($filter['sql_in_branch_account_string']))
		{
			if($filter['sql_in_branch_account_string'] != "")
			{
				$filter_account_branch_manager = "AND ct.BranchID IN (".$filter['sql_in_branch_account_string'].")";
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
				$filter_date = " AND (ct.CreatedDate >= '$StartDate' and ct.CreatedDate <= '$EndDate') ";
			}
		}
		if($CorporateID != -1)
		{
			$sql =  "SELECT r.RegionName as RegionName, COUNT(ct.ID) AS TicketCount FROM region r JOIN state s ON r.ID = s.RegionID JOIN branch b ON s.StateName = b.BranchState JOIN corporate_tickets ct ON b.ID = ct.BranchID WHERE ct.IsActive = 1 AND ct.CorporateID = $CorporateID $filter_ticket_status $filter_ticket_type $filter_state $filter_region $filter_branch $filter_date $filter_state_lead $filter_account_branch_manager GROUP BY r.RegionName";
		}
		else
		{
			$sql =  "SELECT r.RegionName as RegionName, COUNT(ct.ID) AS TicketCount FROM region r JOIN state s ON r.ID = s.RegionID JOIN branch b ON s.StateName = b.BranchState JOIN corporate_tickets ct ON b.ID = ct.BranchID WHERE ct.IsActive = 1 $filter_ticket_status $filter_ticket_type $filter_state $filter_region $filter_branch $filter_date $filter_state_lead $filter_account_branch_manager GROUP BY r.RegionName";
		}
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					$RegionName = $row['RegionName'];
					$response[$RegionName] = $row['TicketCount'];
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}

}