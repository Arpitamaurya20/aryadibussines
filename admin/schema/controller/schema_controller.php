<?php
function getAllSchema($conn)
{
	$response = array();
	$sql = "Select * from  schemadata ORDER BY SchemaId DESC";
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



function getSingleSchema($conn,$SchemaId)
{
	$response = array();
	$sql = "Select * from  schemadata WHERE SchemaId='$SchemaId' ORDER BY SchemaId";
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
function CreateSchema($conn,$title,$description,$shortdescription,$schemaurl,$current_date)
{
	$response = array();
	$password=md5($password);
	// INSERT Project
	$sql = "INSERT INTO `schemadata`(`title`, `description`, `shortdescription`, `schemaurl`,`CreatedDate`)VALUES ('$title','$description','$shortdescription','$schemaurl','$current_date')";
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



function  UpdateSchema($conn,$title,$description,$shortdescription,$schemaurl,$current_date,$SchemaId)
{
	$response = array();
	$sql = "Update schemadata SET title='$title', description='$description',shortdescription='$shortdescription',CreatedDate='$current_date' WHERE SchemaId='$SchemaId'";
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




/*************************************************/
/*****************DELETE Block DATA****************/
/*************************************************/
function DeleteSchemaData($conn,$ID)
{
	$sql = "Delete from schemadata where SchemaId = '$ID'";
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




/*************************************************/
/*************CHECK DUPLICATE USERNAME************/
/*************************************************/
function CheckUsername($conn,$username)
{
	$sql = "SELECT * FROM users where UserName = '$username'";
	
	if ($result = mysqli_query($conn,$sql))
	{
		$rowcount = mysqli_num_rows($result);
		
		if($rowcount > 0){
			return "Duplicate";
		}
		else{
			return false;
	}
	return false;
	}
}
?>