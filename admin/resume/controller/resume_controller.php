<?php
function getAllresume($conn)
{
	$response = array();
	$sql = "Select * from  resume ORDER BY id DESC";
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

function deleteresume($conn,$id)
{
	$sql = "Delete from resume where id = ".$id;
	$result_delete_data=mysqli_query($conn,$sql);
	//echo $sql;
	if(!$result_delete_data)
	{
		mysqli_error($conn,$sql);
		//echo $sql;
	}
	else
	{

		return false;
	}
}

function getTotalResume($conn)
{
	$sql = "Select COUNT(*) as resume_count from resume where id ORDER BY id DESC";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		$row = $result->fetch_assoc();
		return $row['resume_count'];
	}
	else
	{
		return 0;
	}
}

?>