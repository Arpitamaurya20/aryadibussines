<?php

function get_bookings($conn,$data)
{
    $response = array();
    $response['error'] = true;
    $response['data']  = array();
	$sql = "Select * From confirm_booking";
    $book_result = mysqli_query($conn, $book_query);
	$result=mysqli_query($conn,$sql);
    if($result->num_rows>0)
	{
        $response['error'] = false;
		while($row = $result->fetch_assoc())
		{
			extract($row);
			array_push($response['data'],$row);
		}
        $response['message'] = "Bookings Fetched";
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "No bookings found";
	}
	return $response;

}


?>