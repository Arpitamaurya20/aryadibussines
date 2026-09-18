<?php
function getAllEmployeesConvenience($conn)
{
	$where = " where 1 ORDER BY ID DESC";
	$response = _getTableRecords($conn,'employee_convenience', $where);
	return $response;
}

function DeleteEmployeeConvenience($conn,$data)
{
	$ID = $data['ID'];
	$query = "where ID = $ID";
	$response = delete_identity_filter($conn,'employee_convenience', $query);
	return $response;
}


?>