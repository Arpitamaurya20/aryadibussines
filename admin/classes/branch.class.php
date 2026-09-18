<?php 
class Branch extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function setBranchArray($action)
	{
		$response = array();
		if($action == "Active")
			$branches_raw = $this->_getTableRecords($this->conn, 'branch', 'where IsActive = 1');
		else
			$branches_raw = $this->_getTableRecords($this->conn, 'branch', 'where 1');
		foreach($branches_raw as $branch)
		{
			$ID = $branch['ID'];
			$BranchSite = $branch['BranchSite'];
			$response[$ID]['BranchName'] = $BranchSite;
			$response[$ID]['BranchAddress1'] = $branch['BranchAddress1'];
			$response[$ID]['BranchCity'] = $branch['BranchCity'];
			$response[$ID]['BranchState'] = $branch['BranchState'];
			$response[$ID]['BranchAccountManager'] = $branch['AccountBranchManager'];
		}
		return $response;

	}

	public function setBranchesInCityArray($action,$filter)
	{
		$response = array();
		if($action == "Active")
		{
			$where = " where IsActive = 1 AND BranchCity IN (".$filter.")";
			$branches_raw = $this->_getTableRecords($this->conn, 'branch', $where);
		}
		else
		{
			$where = " where BranchCity IN (".$filter.")";
			$branches_raw = $this->_getTableRecords($this->conn, 'branch', $where);
		}
		foreach($branches_raw as $branch)
		{
			$ID = $branch['ID'];
			$BranchSite = $branch['BranchSite'];
			$response[$ID]['BranchName'] = $BranchSite;
			$response[$ID]['BranchCity'] = $branch['BranchCity'];
			$response[$ID]['BranchState'] = $branch['BranchState'];
		}
		return $response;

	}

	public function setBranchArrayByCorporateID($CorporateID,$action)
	{
		$response = array();
		if($action == "Active")
		{
			$where = " where CompanyID = $CorporateID AND IsActive = 1";
			$branches_raw = $this->_getTableRecords($this->conn, 'branch',$where);
		}
		else
		{
			$where = " where CompanyID = $CorporateID";
			$branches_raw = $this->_getTableRecords($this->conn, 'branch',$where);
		}
		foreach($branches_raw as $branch)
		{
			$ID = $branch['ID'];
			$BranchSite = $branch['BranchSite'];
			$response[$ID]['BranchName'] = $BranchSite;
			$response[$ID]['BranchCity'] = $branch['BranchCity'];
			$response[$ID]['BranchState'] = $branch['BranchState'];
			$response[$ID]['BranchCode'] = $branch['BranchCode'];
		}
		return $response;
	}

	public function setBranchArrayByCorporateIDv2($CorporateID,$action,$filter)
	{
		$response = array();
		if($action == "Active")
		{
			$where = " where CompanyID = $CorporateID AND IsActive = 1";
			if(isset($filter['branch']) && $filter['branch'] != "" && $filter['branch'] != -1)
			{
				$where = $where." AND ID = ".(int)$filter['branch'];
			}
			if(isset($filter['sql_in_state_string']))
			{
				if($filter['sql_in_state_string'] != "")
				{
					$where = $where." AND BranchState IN (".$filter['sql_in_state_string'].")";
				}
			}
			if(isset($filter['sql_in_branch_account_string']))
			{
				if($filter['sql_in_branch_account_string'] != "")
				{
					$where = $where." AND ID IN (".$filter['sql_in_branch_account_string'].")";
				}
			}
			$branches_raw = $this->_getTableRecords($this->conn, 'branch',$where);
		}
		else
		{
			$where = " where CompanyID = $CorporateID";
			if(isset($filter['branch']) && $filter['branch'] != "" && $filter['branch'] != -1)
			{
				$where = $where." AND ID = ".(int)$filter['branch'];
			}
			if(isset($filter['sql_in_state_string']))
			{
				if($filter['sql_in_state_string'] != "")
				{
					$where = $where." AND BranchState IN (".$filter['sql_in_state_string'].")";
				}
			}
			if(isset($filter['sql_in_branch_account_string']))
			{
				if($filter['sql_in_branch_account_string'] != "")
				{
					$where = $where." AND ID IN (".$filter['sql_in_branch_account_string'].")";
				}
			}
			$branches_raw = $this->_getTableRecords($this->conn, 'branch',$where);
		}
		foreach($branches_raw as $branch)
		{
			$ID = $branch['ID'];
			$BranchSite = $branch['BranchSite'];
			$response[$ID]['BranchName'] = $BranchSite;
			$response[$ID]['BranchCity'] = $branch['BranchCity'];
			$response[$ID]['BranchState'] = $branch['BranchState'];
			$response[$ID]['BranchCode'] = $branch['BranchCode'];
		}
		return $response;
	}

	public function setBranchArrayforAccountManager($sql_in_account)
	{
		$where = " where CompanyID IN (".$sql_in_account.") ORDER BY CompanyID ASC";
        $response = _getTableRecords($this->conn,'branch',$where);
        return $response;
	}
	
	public function MergeBranches($CurrentBranchID,$NewBranchID)
	{
		$response = array();
		$filter = "BranchID = $NewBranchID where BranchID = $CurrentBranchID";
		$this->_UpdateTableRecords($this->conn,'branch_arc',$filter);
		$this->_UpdateTableRecords($this->conn,'branch_assets',$filter);
		$this->_UpdateTableRecords($this->conn,'branch_spare_part',$filter);
		$this->_UpdateTableRecords($this->conn,'corporate_tickets',$filter);
		$this->_UpdateTableRecords($this->conn,'orders',$filter);
		$this->_UpdateTableRecords($this->conn,'order_item',$filter);
		$this->_UpdateTableRecords($this->conn,'ppm_tickets',$filter);
		$this->_UpdateTableRecords($this->conn,'temp_cart',$filter);
		$this->_UpdateTableRecords($this->conn,'ticket_quotation',$filter);
		$this->_UpdateTableRecords($this->conn,'users',$filter);
		$response['message'] = "All the data moved to new Branch";
		return $response;
	}

	public function getMappedAccountBranchesofAccountBranchManager($Employee_ID)
	{
		$where = " where AccountBranchManager = $Employee_ID";
		$branches_array = $this->_getTableRecords($this->conn,'branch', $where);
		return $branches_array;
	}

	public function getBranchDetailsbySite($BranchSite)
	{
		$where = " where BranchSite = '$BranchSite' ";
		$branch_details = $this->_getTableDetails($this->conn,'branch',$where);
		return $branch_details;
	}

	public function getBranchDetailsbyID($data)
	{
		$BranchID = $data['BranchID'];
		$where = " where ID = $BranchID ";
		$branch_details = $this->_getTableDetails($this->conn,'branch',$where);
		return $branch_details;
	}


	public function getMappedAccountsofBranchAccountManager($Employee_ID)
	{
		$where = " where AccountBranchManager = $Employee_ID";
		$company_array = $this->_getTableRecords($this->conn,'branch', $where);
		return $company_array;
	}

	public function getBranchesBySearchTeam($data)
	{
		$search_term = $data['search_term'];
		$corporate_filter = "";
		if(isset($data['CorporateID']))
		{
			$CorporateID = $data['CorporateID'];
			$corporate_filter = " AND CompanyID = $CorporateID";
		}
		if($search_term != -1)
		{
			$where = " where BranchSite like '%".$search_term."%' or BranchCode like '%".$search_term."%'  and IsActive = 1 $corporate_filter";
		}
		else
		{
			$where = " where IsActive = 1 $corporate_filter";
		}
		if(isset($data['filter_limit']))
		{
			$filter_limit = $data['filter_limit'];
			$where = $where." ORDER BY ID DESC ".$filter_limit;
		}
		$branches_array = $this->_getTableRecords($this->conn,'branch', $where);
		return $branches_array;

	}

}