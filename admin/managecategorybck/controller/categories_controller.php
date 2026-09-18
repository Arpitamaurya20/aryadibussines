<?php
function getAllCategories($conn)
{
	$where = " where ID = ID";
	$response = _getTableRecords($conn,'manage_categories', $where);
	return $response;
}

function deletecategories($conn,$id)
{
	$query = " where ID = $id";
	return delete_identity_filter($conn,'manage_categories', $query);
}

function getTotalCategories($conn)
{
    $sql = "Select COUNT(*) as categories_count from manage_categories";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['categories_count'];
    }
    else
    {
        return 0;
    }
}

?>