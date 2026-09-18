<?php
function getAllUOM($conn)
{
	$where = " where ID = ID";
	$response = _getTableRecords($conn,'manage_uom', $where);
	return $response;
}

function deleteuom($conn,$id)
{
	$query = " where ID = $id";
	$response = delete_identity_filter($conn,'manage_uom', $query);
	return $response;
}

function getTotalUOM($conn)
{
    $sql = "Select COUNT(*) as uom_count from manage_uom";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['uom_count'];
    }
    else
    {
        return 0;
    }
}

?>