<?php
function getAllSparePart($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'sparepartlist', $where);
	return $response;
}

function InsertSparePart($conn,$data)
{
    $SparePartCode_No = GenerateSparePartCode($conn)['SparePartCode'];
    $SparePartCode = $SparePartCode_No;
	$SparePart = $data["spare_part"];
    $Categories = $data["categories"];
    $UOM = $data["uom"];
	$Price = $data["price"];
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $spare_part_query = "INSERT INTO sparepartlist (SparePartCode,SparePart,Categories,UOM,Price,CreatedBy,CreatedDate,CreatedTime ) VALUES('$SparePartCode','$SparePart','$Categories','$UOM','$Price','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $spare_part_query);
    $response['message'] = "Spare Part Added to the System";
    return $response;
}

function UpdateSparePart($conn,$data)
{	
	$SparePart = $data["spare_part"];
    $Categories = $data["categories"];
    $UOM = $data["uom"];
    $Price = $data["price"];
    $spare_part_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $old_spare_part_details = GetSparePartDetailsbyID($conn,$spare_part_id);
    if($SparePart == $old_spare_part_details['SparePart'] && $Categories == $old_spare_part_details['Categories'] && $Price == $old_spare_part_details['Price'] && $UOM == $old_spare_part_details['UOM'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	$update_param = " SparePart = '$SparePart',Categories='$Categories',UOM='$UOM',Price='$Price' where ID=$spare_part_id";
    	$response = _UpdateTableRecords($conn,'sparepartlist', $update_param);
        $response['message'] = "Spare Part Updated to the System";
    }
    return $response;
}

function GetSparePartDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$spare_part_details = _getTableDetails($conn,'sparepartlist', $where);
	return $spare_part_details;
}

function DeleteSparePart($conn,$data)
{
	$ID = $data['ID'];

	$query_parameter = " where ID = '$ID'";
	$response = delete_identity_filter($conn,"sparepartlist",$query_parameter);
	return $response;
}

function GenerateSparePartCode($conn)
{
    $response = array();
    $Initials = "PRD";

    $where_query = " where 1";
    $max_seq = _getMaxIdentityValue_filter($conn,'sparepartlist','ID', $where_query);
    $seq = $max_seq+1;
    $formatted_seq = sprintf('%04d', $seq);

    $SparePartCode = $Initials.$formatted_seq;
    $response['SparePartCode'] = $SparePartCode;
    $response['ID'] = $seq;
    return $response;
}


function getTotalSparePart($conn)
{
    $sql = "Select COUNT(*) as spare_part_count from sparepartlist where IsActive = 1";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['spare_part_count'];
    }
    else
    {
        return 0;
    }
}

 ?>