<?php
function getAllRegions($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'region', $where);
	return $response;
}

function InsertRegion($conn,$data)
{
	$region_name = $data["region_name"];
    $region_head = $data["region_head"];
	$region_corporate = $data["region_corporate"];
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $region_query = "INSERT INTO region (RegionName,RegionHead,RegionCorporateHead,CreatedBy,CreatedDate,CreatedTime ) VALUES('$region_name',$region_head,$region_corporate,'$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $region_query);
    if($response['error'] == false && $region_head != -1)
    {
    	$data['EmployeeID'] = $region_head;
		$data['Role'] = "Region Lead";
		$data['CreatedDate'] = $CreatedDate;
		$data['CreatedTime'] = $CreatedTime;
    	InsertUserRole($conn,$data);
    }
    if($response['error'] == false && $region_corporate != -1)
    {
    	$data['EmployeeID'] = $region_corporate;
		$data['Role'] = "Region Corporate Lead";
		$data['CreatedDate'] = $CreatedDate;
		$data['CreatedTime'] = $CreatedTime;
    	InsertUserRole($conn,$data);
    }
    $response['message'] = "Region Added to the System";
    return $response;
}

function UpdateRegion($conn,$data)
{
	$region_name = $data["region_name"];
    $region_head = $data["region_head"];
	$region_corporate = $data["region_corporate"];
    $region_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $old_region_details = GetRegionDetailsbyID($conn,$region_id);
    if($region_name == $old_region_details['RegionName'] && $region_head == $old_region_details['RegionHead'] && $region_corporate == $old_region_details['RegionCorporateHead'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	$update_param = " RegionName = '$region_name',RegionHead=$region_head, RegionCorporateHead=$region_corporate where ID=$region_id";
    	$response = _UpdateTableRecords($conn,'region', $update_param);
    	if($response['error'] == false)
    	{
    		if($region_head != $old_region_details['RegionHead'])
    		{
    			// Delete old role
    			$EmployeeID = $old_region_details['RegionHead'];
				$query_parameter = " where EmployeeID = $EmployeeID and Role = 'Region Lead'";
				delete_identity_filter($conn,"user_roles",$query_parameter);

				if($region_head != -1)
				{
					$data['EmployeeID'] = $region_head;
					$data['Role'] = "Region Lead";
					$data['CreatedDate'] = $CreatedDate;
					$data['CreatedTime'] = $CreatedTime;
			    	InsertUserRole($conn,$data);
				}
    		}
    		if($region_corporate != $old_region_details['RegionCorporateHead'])
    		{
    			// Delete old role
    			$EmployeeID = $old_region_details['RegionCorporateHead'];
				$query_parameter = " where EmployeeID = $EmployeeID and Role = 'Region Corporate Lead'";
				delete_identity_filter($conn,"user_roles",$query_parameter);

				if($region_corporate != -1)
				{
					$data['EmployeeID'] = $region_corporate;
					$data['Role'] = "Region Corporate Lead";
					$data['CreatedDate'] = $CreatedDate;
					$data['CreatedTime'] = $CreatedTime;
			    	InsertUserRole($conn,$data);
				}
    		}
    		$response['message'] = "Region Details Updated";
    	}
    }
    return $response;
}

function GetRegionDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$region_details = _getTableDetails($conn,'region', $where);
	return $region_details;
}

function DeleteRegion($conn,$data)
{
	// Get Region Details
	$ID = $data['ID'];
	$region = GetRegionDetailsbyID($conn,$ID);
	if($region['RegionHead'] != -1)
	{
		$EmployeeID = $region['RegionHead'];
		$query_parameter = " where EmployeeID = $EmployeeID and Role = 'Region Lead'";
		delete_identity_filter($conn,"user_roles",$query_parameter);
	}

	$query = " IsActive = 0 where ID = $ID";
	$response = _UpdateTableRecords($conn,'region', $query);
	return $response;
}

function getTotalRegion($conn)
{
	$sql = "Select COUNT(*) as region_count from region where IsActive = 1";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		$row = $result->fetch_assoc();
		return $row['region_count'];
	}
	else
	{
		return 0;
	}
}

?>