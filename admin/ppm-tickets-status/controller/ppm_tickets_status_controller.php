<?php
function getAllPPMTicketStatus($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'ppm_ticket_status', $where);
	return $response;
}

function InsertPPMStatus($conn,$data)
{
	$Status = $data['status_name'];
	$sql = "INSERT INTO ppm_ticket_status(Status) VALUES ('$Status')";
	$response = _InsertTableRecords($conn, $sql);
	return $response;
}
function DeletePPMStatus($conn,$data)
{
	$ID = $data['ID'];
	$query = " where ID = $ID";
	return delete_identity_filter($conn,'ppm_ticket_status', $query);
}

function getTotalPPMTicketStatus($conn)
{
    $sql = "Select COUNT(*) as tickets_status_count from ppm_ticket_status where IsActive = 1";
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