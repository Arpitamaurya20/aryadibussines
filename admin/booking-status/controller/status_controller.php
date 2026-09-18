<?php
function getAllstatus($conn)
{
	$response = array();
	$sql = "Select * from status ORDER BY id DESC";
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

function deletestatus($conn,$id)
{
	$sql = "Delete from status where id = ".$id;
	$result_delete_data=mysqli_query($conn,$sql);

	if(!$result_delete_data)
	{
		mysqli_error($conn,$sql);
	}
	else
	{

		return false;
	}
}

?>