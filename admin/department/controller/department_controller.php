<?php
function getAllDepartments($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'department', $where);
	return $response;
}

function InsertDepartment($conn,$data)
{
	$department_name = $data["department_name"];
    $department_head = $data["department_head"];
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $department_query = "INSERT INTO department (DepartmentName,DepartmentHead,CreatedBy,CreatedDate,CreatedTime ) VALUES('$department_name',$department_head,'$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $department_query);
    if($response['error'] == false && $department_head != -1)
    {
    	$data['EmployeeID'] = $department_head;
		$data['Role'] = "Department Lead";
		$data['CreatedDate'] = $CreatedDate;
		$data['CreatedTime'] = $CreatedTime;
    	InsertUserRole($conn,$data);
    }
    $response['message'] = "Department Added to the System";
    return $response;
}

function UpdateDepartment($conn,$data)
{	
	$department_name = $data["department_name"];
    $department_head = $data["department_head"];
    $department_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $old_department_details = GetDepartmentDetailsbyID($conn,$department_id);
    if($department_name == $old_department_details['DepartmentName'] && $department_head == $old_department_details['DepartmentHead'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	$update_param = " DepartmentName = '$department_name',DepartmentHead=$department_head where ID=$department_id";
    	$response = _UpdateTableRecords($conn,'department', $update_param);
    	if($response['error'] == false)
    	{
    		if($department_head != $old_department_details['DepartmentHead'])
    		{
    			// Delete old role
    			$EmployeeID = $old_department_details['DepartmentHead'];
				$query_parameter = " where EmployeeID = $EmployeeID and Role = 'Department Lead'";
				delete_identity_filter($conn,"user_roles",$query_parameter);

				if($department_head != -1)
				{
					$data['EmployeeID'] = $department_head;
					$data['Role'] = "Department Lead";
					$data['CreatedDate'] = $CreatedDate;
					$data['CreatedTime'] = $CreatedTime;
			    	InsertUserRole($conn,$data);
				}
    		}
    		$response['message'] = "Department Details Updated";
    	}
    }
    return $response;
}

function GetDepartmentDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$department_details = _getTableDetails($conn,'department', $where);
	return $department_details;
}

function DeleteDepartment($conn,$data)
{
	// Get Department Details
	$ID = $data['ID'];
	$department = GetDepartmentDetailsbyID($conn,$ID);
	if($department['DepartmentHead'] != -1)
	{
		$EmployeeID = $department['DepartmentHead'];
		$query_parameter = " where EmployeeID = $EmployeeID and Role = 'Department Lead'";
		delete_identity_filter($conn,"user_roles",$query_parameter);
	}

	$query = " IsActive = 0 where ID = $ID";
	$response = _UpdateTableRecords($conn,'department', $query);
	return $response;
}


function getTotalDepartment($conn)
{
    $sql = "Select COUNT(*) as department_count from department where IsActive = 1";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['department_count'];
    }
    else
    {
        return 0;
    }
}

?>