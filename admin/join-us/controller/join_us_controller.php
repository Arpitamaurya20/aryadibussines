<?php
function getAllJoinUsData($conn)
{
	$response = array();
	$sql = "select * from  join_us ORDER BY id DESC";
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
?>