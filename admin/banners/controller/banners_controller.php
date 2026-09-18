<?php
function getAllbanners($conn)
{
	$response = array();
	$sql = "Select * from  banners";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			extract($row);
			array_push($response, $row);
		}
	}
	return json_encode($response);
}
function getSinglebanners($conn, $id)
{
	$response = array();
	$sql = "Select * from  banners WHERE id='$id'";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			extract($row);
			array_push($response, $row);
		}
	}
	return json_encode($response);
}
function getSingleCFLVillage($conn, $BlockId)
{
	$response = array();
	$sql = "Select * from  villagedata WHERE BlockId='$BlockId' ORDER BY VillageId";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			extract($row);
			array_push($response, $row);
		}
	}
	return json_encode($response);
}
//Objective Create Block
function Createbanners($conn, $banner, $added_on)
{
	
	$response = array();
	// INSERT Project
	$sql = "INSERT INTO  banners (banner,added_on) VALUES ('$banner','$added_on')";
	//print_r($sql);


	$result_insert = mysqli_query($conn, $sql);
	

	if ($result_insert == true) {
		$response['error'] = false;
		$response['message'] = "Banners Created !";
	} else {
		echo $sql;
		$error = mysqli_error($conn);
		$response['error'] = true;
		$response['message'] = $error;
	}
	return $response;
}
function Updatebanners($conn, $bannersName, $current_date, $bannersId)
{
	$response = array();
	$sql = "Update bannersdata SET bannersName='$bannersName' where bannersId ='$bannersId'";
	$result = mysqli_query($conn, $sql);
	//echo $sql;
	if ($result == true) {
		$response['error'] = false;
		$response['message'] = "banners Updated!";
	} else {
		echo $sql;
		$error = mysqli_error($conn);
		$response['error'] = true;
		$response['message'] = $error;
	}
	return $response;
}
/*************************************************/
/*****************DELETE VILLAGE DATA****************/
/*************************************************/
function DeletetestimonialData($conn, $id)
{
	$sql = "Delete from banners where id = '$id'";
	$result_delete_data = mysqli_query($conn, $sql);
	//echo $sql;
	if (!$result_delete_data) {
		mysqli_error($conn, $sql);
		//echo $sql;
	} else {
		return false;
	}
}
