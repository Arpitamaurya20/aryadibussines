<?php
function getAllCity($conn)
{
	$response = array();
	$sql = "Select * from  citydata ORDER BY CityId";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			extract($row);
			array_push($response, $row);
		}
	}
	return json_encode($response);
}
function getSingleCity($conn, $CityId)
{
	$response = array();
	$sql = "Select * from  citydata WHERE CityId='$CityId' ORDER BY CityId";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			array_push($response, $row);
		}
	}
	return json_encode($response);
}
function getSingleCityByID($conn, $CityId)
{
	$response = array();
	$where = " WHERE CityId='$CityId'";
	$response = _getTableDetails($conn,'citydata',$where);
	return $response;
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
function CreateCity($conn, $CityName,$url,$metaTitle,$metaDescription, $image,$featured, $current_date)
{
	
	$response = array();
	// INSERT Project
	$sql = "INSERT INTO  citydata (CityName,image,url,metaTitle,metaDescription,featured,CreatedDate) VALUES ('$CityName','$image','$url','$metaTitle','$metaDescription','$featured','$current_date')";
	//print_r($sql);
	$result_insert = mysqli_query($conn, $sql);
	if ($result_insert == true) {
		$response['error'] = false;
		$response['message'] = "City Created !";
	} else {
		echo $sql;
		$error = mysqli_error($conn);
		$response['error'] = true;
		$response['message'] = $error;
	}
	return $response;
}
function UpdateCity($conn, $CityName, $current_date, $CityId)
{
	$response = array();
	$sql = "Update citydata SET CityName='$CityName' where CityId ='$CityId'";
	$result = mysqli_query($conn, $sql);
	//echo $sql;
	if ($result == true) {
		$response['error'] = false;
		$response['message'] = "City Updated!";
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
function DeleteCityData($conn, $CityId,$image)
{
	$sql = "Delete from citydata where CityId = '$CityId'";
	$result_delete_data = mysqli_query($conn, $sql);
	//echo $sql;
	if (!$result_delete_data) {
		mysqli_error($conn, $sql);
		//echo $sql;
	} else {
		$path = "../../media/city/" . $image;
		unlink($path);
		return false;
	}
}

function getCityPromoBanners($conn,$CityId)
{
	$where = " where CityId = $CityId";
	$e_roles = _getTableRecords($conn,'citypromobanner',$where);
	return $e_roles;
}

function getTotalCity($conn)
{
	$sql = "Select COUNT(*) as city_count from citydata where CityId ORDER BY CityId";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		$row = $result->fetch_assoc();
		return $row['city_count'];
	}
	else
	{
		return 0;
	}
}
