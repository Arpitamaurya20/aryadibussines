<?php 
class Company extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function setCompanyArray($action)
	{
		$response = array();
		if($action == "Active")
			$companies_raw = $this->_getTableRecords($this->conn, 'company', 'where IsActive = 1 ORDER BY CompanyName ASC');
		else
			$companies_raw = $this->_getTableRecords($this->conn, 'company', 'where 1 ORDER BY CompanyName ASC');
		foreach($companies_raw as $company)
		{
			$ID = $company['ID'];
			$CompanyName = $company['CompanyName'];
			$response[$ID]['CompanyName'] = $CompanyName;
		}
		return $response;

	}

	public function GetCompanyDetailsbyID($CompanyID)
	{
		$where = " where ID = $CompanyID";
		$company_details = $this->_getTableDetails($this->conn,'company', $where);
		// Get Admin username and password
		$where = " where CorporateID = $CompanyID and UserType = 'Corporate Admin'";
		$user_details = $this->_getTableDetails($this->conn,'users', $where);
		$company_details['UserName'] = "";
		if(isset($user_details['UserName']))
			$company_details['UserName'] = $user_details['UserName'];
		return $company_details;
	}

	public function getMappedAccountsofAccountManager($Employee_ID)
	{
		$where = " where AccountManager = $Employee_ID";
		$company_array = $this->_getTableRecords($this->conn,'company', $where);
		return $company_array;
	}

	public function GetCompanyConfiguration($data)
	{
		$response = array();
		$response['AdditionalPriorities'] = array();
		$CorporateID = $data['CorporateID'];
		$where = " where ID = $CorporateID";
		$company_details = $this->_getTableDetails($this->conn,'company',$where);
		$AdditionalPriorities = $company_details['AdditionalPriorities'];
		if($AdditionalPriorities != "")
		{
			$response['AdditionalPriorities'] = explode(',',$AdditionalPriorities);
		}
		return $response;
	}

}