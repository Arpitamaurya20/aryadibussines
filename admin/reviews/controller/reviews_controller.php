<?php
function getAllreviews($conn)
{
	$response = array();
	$sql = "Select * from  reviews";
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

function getSinglereviews($conn,$id)
{
	$response = array();
	$sql = "Select * from  reviews WHERE id='$id'";
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

function getservicename($conn)

{
	$servicearray=array();
	$sql = "select * from services";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			extract($row);
			$servicearray[$ID]=$Name;
			
		}
	}
	return $servicearray;
}

?>