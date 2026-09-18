<?php
function getAllsite($conn)
{
	$response = array();
	$sql = "Select * from  site ORDER BY id DESC";
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

function deletesite($conn,$id)
{
	$sql = "Delete from site where id = ".$id;
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

function getTotalSite($conn)
{
    $sql = "Select COUNT(*) as site_count from site where 1";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['site_count'];
    }
    else
    {
        return 0;
    }
}

?>