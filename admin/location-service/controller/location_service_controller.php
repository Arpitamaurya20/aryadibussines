<?php
function getAlllocation_service($conn)
{
	$response = array();
	$sql = "select * from  location_services ORDER BY id DESC";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			extract($row);
			array_push($response, $row);
		}
	}
	return json_encode($response);
}

function getcityarray($conn)

{
	$cityarray=array();
	$sql = "select * from citydata";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			extract($row);
			$cityarray[$CityId]=$CityName;
			
		}
	}
	return $cityarray;
}

function getservicearray($conn)

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



function getSinglelocation_service($conn, $location_serviceId)
{
	$response = array();
	$sql = "Select * from  location_services WHERE id='$location_serviceId'";
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
function Createlocation_service($conn, $CityId, $service, $url, $detail, $MetaDescription,$MetaTitle,$title, $added_on)
{
	$response = array();
	// INSERT Project
	$sql = "INSERT INTO  location_services (CityID,service,url,detail,MetaDescription,MetaTitle,title,added_on) VALUES ('$CityId','$service','$url','$detail','$MetaDescription','$MetaTitle','$title','$added_on')";
	//print_r($sql);
	$result_insert = mysqli_query($conn, $sql);
	if ($result_insert == true) {
		$response['error'] = false;
		$response['message'] = "Location service Created !";
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
function Deletelocation_serviceData($conn, $location_serviceId)
{
	$sql = "delete from location_services where id = '$location_serviceId'";
	$result_delete_data = mysqli_query($conn, $sql);
	//echo $sql;
	if (!$result_delete_data) {
		mysqli_error($conn, $sql);
		//echo $sql;
	} else {
		return false;
	}
}
function update_locationService($conn, $CityId,$service,$url,$detail,$MetaDescription,$MetaTitle,$title,$added_on,$ID)
{
	$response = array();
	 $sql = "UPDATE location_services SET CityId='$CityId',service='$service',detail='$detail',  url='$url',title='$title',MetaDescription='$MetaDescription', MetaTitle='$MetaTitle' WHERE id =".$ID;
	$result = mysqli_query($conn, $sql);
	//echo $sql;
	if ($result == true) {
		$response['error'] = false;
		$response['message'] = "Location Service Updated!";
	} else {
		echo $sql;
		$error = mysqli_error($conn);
		$response['error'] = true;
		$response['message'] = $error;
	}
	return $response;
}