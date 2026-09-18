<?php
function getAllSubCategories($conn)
{
	$where = " where ID = ID";
	$response = _getTableRecords($conn,'manage_subcategories', $where);
	return $response;
}

function deleteSubCategories($conn,$id)
{
	$query = " where ID = $id";
	$response = delete_identity_filter($conn,'manage_subcategories', $query);
	return $response;
}

function getSubCategories($conn,$CategoryID)
{
	$where = "where Categories = $CategoryID";
	$response = _getTableRecords($conn, 'manage_subcategories', $where);
	return $response;
}

function getTotalSubCategories($conn)
{
    $sql = "Select COUNT(*) as subcategories_count from manage_subcategories";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['subcategories_count'];
    }
    else
    {
        return 0;
    }
}

?>