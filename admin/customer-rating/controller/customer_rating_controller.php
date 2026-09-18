<?php
function getAllCustomerRating($conn)
{
	$where = " where ID = ID";
	$response = _getTableRecords($conn,'customer_rating', $where);
	return $response;
}


function DeleteCustomerRating($conn,$data)
{
	$ID = $data['ID'];
	$query = " where ID = $ID";
	return delete_identity_filter($conn,'customer_rating', $query);
}

function getTotalCustomerRating($conn)
{
    $sql = "Select COUNT(*) as rating_count from customer_rating";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['rating_count'];
    }
    else
    {
        return 0;
    }
}


?>