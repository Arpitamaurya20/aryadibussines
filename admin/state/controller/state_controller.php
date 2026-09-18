<?php
function getAllStates($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'state', $where);
	return $response;
}

function GetStateDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$state_details = _getTableDetails($conn,'state', $where);
	return $state_details;
}

function InsertState($conn,$data)
{
	$state_name = $data["state_name"];
    $state_head = $data["state_head"];
	$state_corporate_head = $data["state_corporate_head"];
	$region = $data["region"];
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $state_query = "INSERT INTO state (StateName,StateHead,StateCorporateHead,RegionID,CreatedBy,CreatedDate,CreatedTime) VALUES('$state_name',$state_head,$state_corporate_head,$region,'$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $state_query);
    if($response['error'] == false && $state_head != -1)
    {
    	$where = " where Role = 'State Lead' and EmployeeID = $state_head";
    	$rows_role = _getTotalRows($conn,'user_roles',$where);
    	if($rows_role == 0)
    	{
	    	$data['EmployeeID'] = $state_head;
			$data['Role'] = "State Lead";
			$data['CreatedDate'] = $CreatedDate;
			$data['CreatedTime'] = $CreatedTime;
	    	InsertUserRole($conn,$data);
	    }
    }
    if($response['error'] == false && $state_corporate_head != -1)
    {
    	$where = " where Role = 'State Corporate Lead' and EmployeeID = $state_corporate_head";
    	$rows_role = _getTotalRows($conn,'user_roles',$where);
    	if($rows_role == 0)
    	{
	    	$data['EmployeeID'] = $state_corporate_head;
			$data['Role'] = "State Corporate Lead";
			$data['CreatedDate'] = $CreatedDate;
			$data['CreatedTime'] = $CreatedTime;
    		InsertUserRole($conn,$data);
    	}
    }
    $response['message'] = "State Added to the System";
    return $response;
}

function UpdateState($conn,$data)
{
	$state_name = $data["state_name"];
    $state_head = $data["state_head"];
	$state_corporate_head = $data["state_corporate_head"];
	$region = $data['region'];
    $state_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $region_head=0;
    $old_state_details = GetStateDetailsbyID($conn,$state_id);
    if($state_name == $old_state_details['StateName'] && $state_head == $old_state_details['StateHead'] && $state_corporate_head == $old_state_details['StateCorporateHead'] && $old_state_details['RegionID'] == $region)
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	if ((int) $state_corporate_head > 0) {
    		$clear_other_states_sql = " StateCorporateHead = -1 WHERE ID <> " . (int) $state_id . " AND StateCorporateHead = " . (int) $state_corporate_head;
    		_UpdateTableRecords($conn, 'state', $clear_other_states_sql);
    	}
    	$update_param = " StateName = '$state_name',RegionID = $region,StateHead=$state_head, StateCorporateHead=$state_corporate_head where ID=$state_id";
    	$response = _UpdateTableRecords($conn,'state', $update_param);
    	if($response['error'] == false)
    	{
    		if($state_name != $old_state_details['StateName'])
    		{
    			$old_branch_state_name = $old_state_details['StateName'];
    			// Update Branch State
    			$sql_update_state_name = " BranchState = '$state_name' where BranchState = '$old_branch_state_name'";
    			_UpdateTableRecords($conn,'branch',$sql_update_state_name);	
    		}

    		if($state_head != $old_state_details['StateHead'])
    		{
    			// Delete old role
    			$EmployeeID = $old_state_details['StateHead'];
    			$where_roles_StateHead = " where StateHead = $EmployeeID";
    			$num_roles_StateHead = _getTotalRows($conn,'state',$where_roles_StateHead);
    			if($num_roles_StateHead == 0)
    			{
					$query_parameter = " where EmployeeID = $EmployeeID and Role = 'State Lead'";
					delete_identity_filter($conn,"user_roles",$query_parameter);
				}

				if($state_head != -1)
				{
					$where = " where Role = 'State Lead' and EmployeeID = $state_head";
			    	$rows_role = _getTotalRows($conn,'user_roles',$where);
			    	if($rows_role == 0)
			    	{
						$data['EmployeeID'] = $state_head;
						$data['Role'] = "State Lead";
						$data['CreatedDate'] = $CreatedDate;
						$data['CreatedTime'] = $CreatedTime;
				    	InsertUserRole($conn,$data);
				    }
				}
    		}

    		if($state_corporate_head != $old_state_details['StateCorporateHead'])
    		{
    			// Delete old role

    			$EmployeeID = $old_state_details['StateCorporateHead'];
    			$where_roles_StateCorporateHead = " where StateCorporateHead = $EmployeeID";
    			$num_roles_StateCorporateHead = _getTotalRows($conn,'state',$where_roles_StateCorporateHead);
    			if($num_roles_StateCorporateHead == 0)
    			{
					$query_parameter = " where EmployeeID = $EmployeeID and Role = 'State Corporate Lead'";
					delete_identity_filter($conn,"user_roles",$query_parameter);
				}

				if($state_corporate_head != -1)
				{
					$where = " where Role = 'State Corporate Lead' and EmployeeID = $state_corporate_head";
			    	$rows_role = _getTotalRows($conn,'user_roles',$where);
			    	if($rows_role == 0)
			    	{
						$data['EmployeeID'] = $state_corporate_head;
						$data['Role'] = "State Corporate Lead";
						$data['CreatedDate'] = $CreatedDate;
						$data['CreatedTime'] = $CreatedTime;
				    	InsertUserRole($conn,$data);
				    }
				}
    		}
    		$response['message'] = "State Details Updated";
    	}
    }
    return $response;
}

function DeleteState($conn,$data)
{
	// Get State Details
	$ID = $data['ID'];
	$state = GetStateDetailsbyID($conn,$ID);
	if($state['StateHead'] != -1)
	{
		$EmployeeID = $region['StateHead'];
		$query_parameter = " where EmployeeID = $EmployeeID and Role = 'State Lead'";
		delete_identity_filter($conn,"user_roles",$query_parameter);
	}

	if($region['StateCorporateHead'] != -1)
	{
		$EmployeeID = $region['StateCorporateHead'];
		$query_parameter = " where EmployeeID = $EmployeeID and Role = 'State Corporate Lead'";
		delete_identity_filter($conn,"user_roles",$query_parameter);
	}

	$query = " IsActive = 0 where ID = $ID";
	$response = _UpdateTableRecords($conn,'state', $query);
	return $response;
}


function getTotalState($conn)
{
	$sql = "Select COUNT(*) as state_count from state where IsActive = 1";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		$row = $result->fetch_assoc();
		return $row['state_count'];
	}
	else
	{
		return 0;
	}
}


?>