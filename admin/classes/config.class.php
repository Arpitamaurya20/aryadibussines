<?php 
class Config extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function setSettingRoles()
	{
		$roles = array('City Corporate Lead','City Lead','Account Manager','Branch Account Manager');
		return $roles;
	}
	public function AddUpdateTatGroup($data)
	{
		$this->setTimeZone();
		$tat_group_name = $data['tat_group_name'];
		$tat_group_default_days = $data['tat_group_default_days'];
		$tat_group_default_hours = $data['tat_group_default_hours'];
		$UpdatedDate = date("Y-m-d");
		$UpdatedTime = date("H:i:s");
		$UpdatedBy = $data['UpdatedBy'];
		$form_action = $data['form_action'];
		if($form_action == "add")
		{
			$sql = "INSERT INTO tat_group(Name,DefaultTATDays,DefaultTATHours,UpdatedDate,UpdatedTime,UpdatedBy) VALUES('$tat_group_name','$tat_group_default_days','$tat_group_default_hours','$UpdatedDate','$UpdatedTime','$UpdatedBy')";
			$response = $this->_InsertTableRecords($this->conn,$sql);
		}
		else
		{
			$form_id = $data['form_id'];
			$update_sql = " Name = '$tat_group_name',DefaultTATDays = '$tat_group_default_days',DefaultTATHours = '$tat_group_default_hours' where ID = $form_id";
			$response = $this->_UpdateTableRecords($this->conn,'tat_group',$update_sql);
		}
		if($response['error'] == false)
		{
			$response['message'] = "Data Saved";
		} 
		else
		{
			$response['message'] = "Technical Error!";
		}
		return $response;
	}

	public function GetTATGroupDetails($TATGroupID)
	{
		$filter = " where ID = $TATGroupID";
		$response = $this->_getTableDetails($this->conn,'tat_group',$filter);
		return $response;
	}
	public function DeleteTATGroup($TATGroupID)
	{
		$update_sql = " IsActive = 0 where ID = $TATGroupID";
		$response = $this->_UpdateTableRecords($this->conn,'tat_group',$update_sql);
		return $response;
	}
	public function getAllConfigurableFields($data)
	{
		$CorporateID = $data['CorporateID'];
		$filter = " where CorporateID = $CorporateID ORDER BY ID DESC";
		$response = $this->_getTableRecords($this->conn,'raise_ticket_configuration',$filter);
		return $response;
	}
	public function GetFormConfigurationByID($data)
	{
		$form_id = $data['ID'];
		$filter = " where ID = $form_id";
		$response = $this->_getTableDetails($this->conn,'raise_ticket_configuration',$filter);
		return $response;
	}
	public function SaveFormConfiguration($data)
	{
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		$CorporateID = $data['CorporateID'];
		if($CorporateID != -1)
		{	
			if($data['form_action'] == "add")
			{
				$filter = " where CorporateID = $CorporateID";
				$param_number = $this->_getMaxIdentityValue_filter($this->conn,'raise_ticket_configuration','ParamNumber',$filter);
				if($param_number >= 5)
				{
					$response['error'] = true;
					$response['message'] = "Only 5 customizable fields are allowed";
				}
				else
				{
					$param_number = $param_number + 1;
					$rowData = [
	                'CorporateID' => $CorporateID,
	                'ParamNumber' => $param_number,
	                'Param' => 'param'.$param_number,
	                'Title' => $data['field_title'],
	                'Type' => $data['field_type'],
	                'Mandatory' => $data['field_mandatory'],
	                'CreatedBy' => $data['CreatedBy'],
	                'CreatedDate' => $CreatedDate,
	                'CreatedTime' => $CreatedTime
	            	];
	        		$response = $this->_InsertTableRecords_prepare($this->conn, 'raise_ticket_configuration', $rowData);
				}
			}
			else
			{
				$rowData = [
                'Title' => $data['field_title'],
                'Type' => $data['field_type'],
                'Mandatory' => $data['field_mandatory']
	            ];
	            $whereCondition = [
	                'ID' => $data['form_id']
	            ];
	            $response = $this->_UpdateTableRecords_prepare($this->conn, 'raise_ticket_configuration', $rowData, $whereCondition);
			}
			
		}
		else
		{
			$response['error'] = true;
			$response['message'] = "Invalid Corporate ID";
		}

		return $response;
		
	}

	public function GetConfigParametersfromURL($var)
	{
		$product_configuration = array();
		$file_name = $var.".json";
		$jsonFile = "../configuration/client/".$file_name;
		if(file_exists($jsonFile))
		{
			$jsonData = file_get_contents($jsonFile);
			$product_configuration = json_decode($jsonData,true);
		}
		return $product_configuration;

	}
	public function viewCircles($CorporateID)
	  {
	    $response = array();
			$response = $this->_getTableRecords($this->conn,'circles','where IsActive = 1 AND CorporateID = '.$CorporateID);
			return $response;
	  }
	public function SaveCircle($data)
	{
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		$CorporateID = $data['CorporateID'];
		if($data['form_action'] == "add")
		{	
			$rowData = [
	        'CorporateID' => $CorporateID,
	        'CircleName' => $data['circle_name'],
	        'CreatedBy' => $data['CreatedBy'],
	        'CreatedDate' => $CreatedDate,
	        'CreatedTime' => $CreatedTime
	    	];
			$response = $this->_InsertTableRecords_prepare($this->conn, 'circles', $rowData);
			
		}
		else
		{
			$rowData = [
            'CircleName' => $data['circle_name']
            ];
            $whereCondition = [
                'ID' => $data['form_id']
            ];
            $response = $this->_UpdateTableRecords_prepare($this->conn, 'circles', $rowData, $whereCondition);
		}
		return $response;
		
	}
}