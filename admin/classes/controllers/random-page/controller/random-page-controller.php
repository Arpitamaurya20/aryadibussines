<?php
function getAllrandomPage($conn)
{
	$response = array();
	$sql = "Select * from  random_page ORDER BY ID desc";
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

function getSingleRandomPage($conn,$ID)
{
	$response = array();
	$sql = "Select * from  random_page WHERE ID='$ID'";
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

function DeleteRandomPageData($conn,$ID)
{
	$sql = "Delete from random_page where ID = ".$ID;
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

?>