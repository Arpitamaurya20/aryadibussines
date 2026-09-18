<?php
function getAllHolidays($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'listofholidays', $where);
	return $response;
}

function InsertHolidays($conn,$data)
{
	$region_name = $data["region_name"];
    $holidays_name = $data["holidays_name"];
	$holidays_date = $data["holidays_date"];
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $holidays_query = "INSERT INTO listofholidays (RegionName,HolidaysName,HolidaysDate,CreatedBy,CreatedDate,CreatedTime ) VALUES('$region_name','$holidays_name','$holidays_date','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $holidays_query);
    $response['message'] = "Holiday Added to the System";
    return $response;
}

function UpdateHolidays($conn,$data)
{	
	$region_name = $data["region_name"];
    $holidays_name = $data["holidays_name"];
    $holidays_date = $data["holidays_date"];
    $holidays_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $old_holidays_details = GetHolidaysDetailsbyID($conn,$holidays_id);
    if($region_name == $old_holidays_details['RegionName'] && $holidays_name == $old_holidays_details['HolidaysName'] && $holidays_date == $old_holidays_details['HolidaysDate'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	$update_param = " RegionName = '$region_name',HolidaysName='$holidays_name',HolidaysDate='$holidays_date' where ID=$holidays_id";
    	$response = _UpdateTableRecords($conn,'listofholidays', $update_param);
        $response['message'] = "Holiday Updated to the System";
    }
    return $response;
}

function GetHolidaysDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$holidays_details = _getTableDetails($conn,'listofholidays', $where);
	return $holidays_details;
}

function DeleteHolidays($conn,$data)
{
	$ID = $data['ID'];

	$query_parameter = " where ID = '$ID'";
	$response = delete_identity_filter($conn,"listofholidays",$query_parameter);
	return $response;
}

// function GetRegionID($conn,$ID)
// {
// 	$where = " where ID = $ID";
// 	$holidays_details = _getTableDetails($conn,'region', $where);
// 	return $holidays_details;
// }
function GetAllRegion($conn)
{
	$where = " where ID = ID";
	$response = _getTableRecords($conn,'region', $where);
	return $response;
}

function getTotalHolidays($conn)
{
    $sql = "Select COUNT(*) as holidays_count from listofholidays where IsActive = 1";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['holidays_count'];
    }
    else
    {
        return 0;
    }
}

 ?>