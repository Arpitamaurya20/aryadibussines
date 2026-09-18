<?php
function getAllBookingStatus($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'booking_status', $where);
	return $response;
}

function InsertBooking($conn,$data)
{
	$Status = $data['status_name'];
	$sql = "INSERT INTO booking_status(Status) VALUES ('$Status')";
	$response = _InsertTableRecords($conn, $sql);
	return $response;
}
function DeleteBookingStatus($conn,$data)
{
	$ID = $data['ID'];
	$query = " where ID = $ID";
	return delete_identity_filter($conn,'booking_status', $query);
}

function getTotalBookingStatus($conn)
{
    $sql = "Select COUNT(*) as booking_status_count from booking_status where IsActive = 1";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['booking_status_count'];
    }
    else
    {
        return 0;
    }
}

?>