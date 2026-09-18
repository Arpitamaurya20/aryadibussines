<?php 
class Partner extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function InsertPartner($data)
	{
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		$columns = ["Partner_Name","Partner_Email","Partner_Number","Partner_AM","CreatedBy","CreatedDate","CreatedTime"];
		$values = [$data['partner_company_name'],$data['partner_admin_email'],$data['partner_phone_number'],$data['partner_account_manager'],$data['CreatedBy'],$CreatedDate,$CreatedTime];
		$response = $this->_InsertPreparedData($this->conn,'partner', $columns, $values);

		if($response['error'] == false)
		{
			// Insert into users table
			$column_users = ["Email","Password","UserType","CreatedDate","CreatedTime","IsActive"];
			$value_users = [$data['pam_email'],"","PAM",$CreatedDate,$CreatedTime,-1];
			$response = $this->_InsertPreparedData($this->conn,'users', $column_users, $value_users);
		}
		return $response;
	}

	public function GetPartner($conn)
	{
		$where = " where 1 ORDER BY ID DESC";
        $PAM_Details = $this->_getTableRecords($conn, "partner", $where);
        return $PAM_Details;
	}
}
?>