<?php
function getAllBranchSparePart($conn)
{
	$response = array();
	$sql = "Select * from  branch_spare_part";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		while($row = $result->fetch_assoc())
		{
			extract($row);
			array_push($response,$row);
		}
	}
	return json_encode($response);
}


function DeleteBranchSparePart($conn,$data)
{
	// Get Corporate Details
	$ID = $data['ID'];
	$query = " where ID = $ID";
	$response = delete_identity_filter($conn,'branch_spare_part', $query);
	return $response;
}



function getGlobalARCItems($conn,$CategoryID)
{
	$where = "where Categories = $CategoryID";
	$response = _getTableRecords($conn, 'sparepartlist', $where);
	return $response;
}

function getAllSparePartsByBranchID($conn,$BranchID)
{
	$where = "where BranchID = $BranchID";
	$response = _getTableRecords($conn, 'branch_spare_part', $where);
	return $response;
}

function getSparePartByID($conn,$ID)
{
	$where = "where ID = $ID";
	$response = _getTableDetails($conn, 'branch_spare_part', $where);
	return $response;
}

function UpdateBranchSparePart($conn,$data){
	$Price = $data["spare_price"];
    $branch_spare_id = $data["branch_spare_id"];
    $old_branch_details = getSparePartByID($conn,$branch_spare_id);
	
	if($Price == $old_branch_details['Price'])
	{
		$response['message'] = "No changes to update";
		$response['error'] = true;
 	}
 	else
	{
    	$update_param = " Price = '$Price' where ID= $branch_spare_id";
    	$response = _UpdateTableRecords($conn,'branch_spare_part', $update_param);
    	if($response['error'] == false)
    	{
    		$response['message'] = "Branch Spare Part Details Updated";
    	}
    	$branch_details_updated = true;
	}
	return $response;
}

?>