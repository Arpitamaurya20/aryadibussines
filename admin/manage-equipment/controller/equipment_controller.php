<?php
function getAllEquipment($conn)
{
	$where = " where ID = ID";
	$response = _getTableRecords($conn,'manage_equipment', $where);
	return $response;
}

function deleteEquipment($conn,$data)
{
	$id = $data['ID'];
	$query = " where ID = $id";
	return delete_identity_filter($conn,'manage_equipment', $query);
}

?>