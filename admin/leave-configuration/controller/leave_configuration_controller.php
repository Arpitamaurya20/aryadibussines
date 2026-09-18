<?php
function getAllLeave($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'leaveconfiguation', $where);
	return $response;
}

function InsertLeave($conn,$data)
{
	$number_of_leave = $data["number_of_leave"];
    $type_of_leave = $data["type_of_leave"];
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $leave_query = "INSERT INTO leaveconfiguation (TypeOfLeave,NumberOfLeave,CreatedBy,CreatedDate,CreatedTime ) VALUES('$type_of_leave','$number_of_leave','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $leave_query);
    $response['message'] = "Leave Added to the System";
    return $response;
}

function UpdateLeave($conn,$data)
{	
    $type_of_leave = $data["type_of_leave"];
	$number_of_leave = $data["number_of_leave"];
    $leave_form_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $old_spare_part_details = GetLeaveDetailsbyID($conn,$leave_form_id);
    if($type_of_leave == $old_spare_part_details['TypeOfLeave'] && $number_of_leave == $old_spare_part_details['NumberOfLeave'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	$update_param = " TypeOfLeave = '$type_of_leave',NumberOfLeave='$number_of_leave' where ID=$leave_form_id";
    	$response = _UpdateTableRecords($conn,'leaveconfiguation', $update_param);
        $response['message'] = "Leave Updated to the System";
    }
    return $response;
}

function GetLeaveDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$spare_part_details = _getTableDetails($conn,'leaveconfiguation', $where);
	return $spare_part_details;
}

function DeleteLeave($conn,$data)
{
	$ID = $data['ID'];

	$query_parameter = " where ID = '$ID'";
	$response = delete_identity_filter($conn,"leaveconfiguation",$query_parameter);
	return $response;
}

function getTotalLeave($conn)
{
    $sql = "Select COUNT(*) as leave_count from leaveconfiguation where IsActive = 1";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['leave_count'];
    }
    else
    {
        return 0;
    }
}

 ?>