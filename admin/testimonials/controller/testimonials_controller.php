<?php
function getAlltestimonials($conn)
{
	$response = array();
	$sql = "Select * from  testimonials";
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

function getSingletestimonials($conn,$id)
{
	$response = array();
	$sql = "Select * from  testimonials WHERE id='$id'";
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




//Objective Create schemadata
function CreateStory($conn,$Name,$Title,$Location,$Story)
{
	$response = array();
	// INSERT Project
	$sql = "INSERT INTO `stories`(`Name`, `Title`, `Location`, `Story`)VALUES ('$Name','$Title','$Location','$Story')";
	//print_r($sql);
	$result_insert=mysqli_query($conn,$sql);
	if($result_insert==true)
	{
		$response['error'] = false;
		$response['message'] = "Schema Created !";
	}
	else
	{
		echo $sql;
		$error = mysqli_error($conn);
		$response['error'] = true;
		$response['message'] = $error;
	}
	return $response;
}

//Objective Create Block
function CreateCFLData($conn,$username,$password,$Role,$current_date)
{
	$response = array();
	// INSERT Project
	$sql = "INSERT INTO  users(UserName, Password, UserType, CreatedAt)VALUES ('$username','$password','$Role','$current_date')";
	//print_r($sql);
	$result_insert=mysqli_query($conn,$sql);
	if($result_insert==true)
	{
		$response['error'] = false;
		$response['message'] = "User Role Created !";
	}
	else
	{
		echo $sql;
		$error = mysqli_error($conn);
		$response['error'] = true;
		$response['message'] = $error;
	}
	return $response;
}



function  UpdateSchema($conn,$title,$description,$shortdescription,$schemaurl,$current_date,$Schemaid)
{
	$response = array();
	$sql = "Update schemadata SET title='$title', description='$description',shortdescription='$shortdescription',CreatedDate='$current_date' WHERE Schemaid='$Schemaid'";
	//print_r($sql);
	$result=mysqli_query($conn,$sql);
	//print_r($result);
	//echo $sql;
	if($result==true)
	{
		$response['error'] = false;
		$response['message'] = "Schema Updated!";
	}
	else
	{
		echo $sql;
		$error = mysqli_error($conn);
		$response['error'] = true;
		$response['message'] = "Schema Updated!";
	}
	return $response;
}

function DeletetestionialsData($conn,$id)
{
	$sql = "Delete from testimonials where id = ".$id;
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