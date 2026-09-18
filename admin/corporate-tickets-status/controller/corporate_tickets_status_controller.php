<?php
function getAllCorporateTicketStatus($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'corporate_tickets_status', $where);
	return $response;
}

function InsertCorporateStatus($conn,$data)
{
	$Status = $data['status_name'];
	$sql = "INSERT INTO corporate_tickets_status(Status) VALUES ('$Status')";
	$response = _InsertTableRecords($conn, $sql);
	return $response;
}
function DeleteCorporateStatus($conn,$data)
{
	$ID = $data['ID'];
	$query = " where ID = $ID";
	return delete_identity_filter($conn,'corporate_tickets_status', $query);
}

function getTotalTicketStatus($conn)
{
    $sql = "Select COUNT(*) as tickets_status_count from corporate_tickets_status where IsActive = 1";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['tickets_status_count'];
    }
    else
    {
        return 0;
    }
}


?>