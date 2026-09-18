<?php 
class State extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function setStateArray($action)
	{
		$response = array();
		if($action == "Active")
			$states_raw = $this->_getTableRecords($this->conn, 'state', 'where IsActive = 1 ORDER BY StateName ASC');
		else
			$states_raw = $this->_getTableRecords($this->conn, 'state', 'where 1 ORDER BY StateName ASC');
		foreach($states_raw as $state)
		{
			$ID = $state['ID'];
			extract($state);
			$response[$ID]['StateName'] = $StateName;
			$response[$ID]['StateHead'] = $StateHead;
			$response[$ID]['StateCorporateHead'] = $StateCorporateHead;
			$response[$ID]['RegionID'] = $RegionID;
		}
		return $response;

	}

	public function getStateArrayInCityArray($city_sql)
	{
		$response = array();
		$where = " where ID IN (Select StateID from citydata where CityName IN (".$city_sql."))";
		$states_raw = $this->_getTableRecords($this->conn, 'state', $where);
		foreach($states_raw as $state)
		{
			$ID = $state['ID'];
			extract($state);
			$response[$ID]['StateName'] = $StateName;
			$response[$ID]['StateHead'] = $StateHead;
			$response[$ID]['StateCorporateHead'] = $StateCorporateHead;
			$response[$ID]['RegionID'] = $RegionID;
		}
		return $response;
	}

	public function getStateswithRegion($action)
	{
		$response = array();
		if($action == "Active")
			$states_raw_sql = "SELECT a.*,b.RegionName FROM `state` a LEFT JOIN region b ON a.RegionID = b.ID where a.IsActive = 1";
		else
			$states_raw_sql = "SELECT a.*,b.RegionName FROM `state` a LEFT JOIN region b ON a.RegionID = b.ID";
		$result = mysqli_query($this->conn, $states_raw_sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					extract($row);
					$response[$StateName]['RegionName'] = $RegionName;
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}

	public function getTicketStatusArrayGroupedByState()
	{
		$response = array();
		$sql =  "SELECT  b.BranchState,COUNT(ct.ID) AS TicketCount FROM branch b JOIN corporate_tickets ct ON b.ID = ct.BranchID where ct.CorporateID = $CorporateID GROUP BY b.BranchState";
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					$BranchState = $row['BranchState'];
					$response[$BranchState] = $row['TicketCount'];
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
		
	}

	public function getTicketsGroupedByState($CorporateID,$filter)
	{
		$filter_state = "";
		if($filter['state'] != "")
		{
			$filter_state = " AND s.StateName = '".$filter['state']."'";
		}
		$filter_region = "";
		if($filter['state'] == "" && $filter['region'] != "")
		{
			$where = " where RegionName = '".$filter['region']."'";
			$RegionID = $this->_getTableDetails($this->conn,'region',$where)['ID'];
			$filter_region = " AND s.StateName IN (Select StateName from state where RegionID = $RegionID)";
		}
		$filter_ticket_type = "";
		if($filter['ticket_type'] != "")
		{
			$filter_ticket_type = " AND ct.Type = '".$filter['ticket_type']."'";
		}
		$filter_ticket_status = "";
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
				$filter_account_branch_manager = " AND ct.BranchID IN (".$filter['sql_in_branch_account_string'].")";
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
		$response = array();
		if($CorporateID != -1)
		{
			$sql =  "SELECT s.StateName as StateName, COUNT(ct.ID) AS TicketCount FROM state s JOIN branch b ON s.StateName = b.BranchState JOIN corporate_tickets ct ON b.ID = ct.BranchID WHERE ct.IsActive = 1 AND ct.CorporateID = $CorporateID $filter_ticket_type $filter_ticket_status $filter_branch $filter_state $filter_region $filter_state_lead $filter_account_branch_manager $filter_date GROUP BY s.StateName";
		}
		else
		{
			$sql =  "SELECT s.StateName as StateName, COUNT(ct.ID) AS TicketCount FROM state s JOIN branch b ON s.StateName = b.BranchState JOIN corporate_tickets ct ON b.ID = ct.BranchID WHERE ct.IsActive = 1 $filter_ticket_type $filter_ticket_status $filter_branch $filter_state $filter_region $filter_state_lead $filter_account_branch_manager $filter_date GROUP BY s.StateName";
		}
		
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					$StateName = $row['StateName'];
					$response[$StateName] = $row['TicketCount'];
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}

	public function getStatesMapped_StateLead($EmployeeID)
	{
		$EmployeeID = (int) $EmployeeID;
		if ($EmployeeID <= 0) {
			return array();
		}
		return $this->_getTableRecords($this->conn, 'state', " where StateCorporateHead = $EmployeeID AND IsActive = 1");
	}

	public function getStatesMapped_StateHead($EmployeeID)
	{
		$EmployeeID = (int) $EmployeeID;
		if ($EmployeeID <= 0) {
			return array();
		}
		return $this->_getTableRecords($this->conn, 'state', " where StateHead = $EmployeeID AND IsActive = 1");
	}

	public function getAllowedStateNamesForEmployee($EmployeeID)
	{
		$EmployeeID = (int) $EmployeeID;
		$names = array();
		if ($EmployeeID <= 0) {
			return $names;
		}
		foreach ($this->getStatesMapped_StateLead($EmployeeID) as $state) {
			if (!empty($state['StateName']) && !in_array($state['StateName'], $names, true)) {
				$names[] = $state['StateName'];
			}
		}
		return $names;
	}

	public function buildBranchStateInClause($stateNames)
	{
		if (!is_array($stateNames) || count($stateNames) === 0) {
			return '';
		}
		$escaped = array();
		foreach ($stateNames as $name) {
			$escaped[] = "'" . mysqli_real_escape_string($this->conn, (string) $name) . "'";
		}
		return implode(', ', $escaped);
	}

	public function resolveEmployeeIdFromSession($session)
	{
		if (isset($session['Roles']['EmployeeID']) && (int) $session['Roles']['EmployeeID'] > 0) {
			return (int) $session['Roles']['EmployeeID'];
		}
		if (empty($session['pb_username'])) {
			return 0;
		}
		$username = mysqli_real_escape_string($this->conn, trim((string) $session['pb_username']));
		$user = $this->_getTableDetails($this->conn, 'users', " WHERE UserName = '$username' AND IsActive = 1");
		if (is_array($user) && isset($user['EmployeeID']) && (int) $user['EmployeeID'] > 0) {
			return (int) $user['EmployeeID'];
		}
		$sql = "SELECT e.ID
		        FROM employees e
		        INNER JOIN users u ON u.EmployeeID = e.ID
		        WHERE u.UserName = '$username' AND u.IsActive = 1
		        LIMIT 1";
		$result = mysqli_query($this->conn, $sql);
		if ($result && ($row = mysqli_fetch_assoc($result)) && (int) $row['ID'] > 0) {
			return (int) $row['ID'];
		}
		return 0;
	}

	public function employeeHasStateScope($employeeId)
	{
		$employeeId = (int) $employeeId;
		if ($employeeId <= 0) {
			return false;
		}
		return count($this->getStatesMapped_StateLead($employeeId)) > 0;
	}

	public function branchStateMatchesAllowedStates($branchState, $allowedStateNames)
	{
		if (!is_array($allowedStateNames) || count($allowedStateNames) === 0) {
			return false;
		}
		$branchNorm = strtolower(trim((string) $branchState));
		foreach ($allowedStateNames as $stateName) {
			if ($branchNorm === strtolower(trim((string) $stateName))) {
				return true;
			}
		}
		return false;
	}

	public function buildBranchStateScopeSql($employeeId, $branchAlias = 'b')
	{
		$employeeId = (int) $employeeId;
		if ($employeeId <= 0) {
			return '';
		}
		$alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $branchAlias);
		if ($alias === '') {
			$alias = 'b';
		}
		return " AND EXISTS (
			SELECT 1 FROM state s
			WHERE s.IsActive = 1
			AND s.StateCorporateHead = $employeeId
			AND TRIM(LOWER($alias.BranchState)) = TRIM(LOWER(s.StateName))
		)";
	}
	

}