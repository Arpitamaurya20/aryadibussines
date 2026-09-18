<?php

function getAllOrderItemsByBranchID($conn,$BranchID)
{
	$where = " where BranchID = $BranchID ORDER BY ID DESC";
	$response = _getTableRecords($conn,'orders', $where);
	return $response;
}

function getAllOrderItemsDetails($conn,$OrderID)
{
	$where = " where Status = 'Ordered' and OrderID = $OrderID ORDER BY ID DESC";
	$response = _getTableRecords($conn,'order_item', $where);
	return $response;
}

function getAllCorporateOrderItems($conn,$CorporateID)
{
	$where = " where BranchID IN (Select ID from branch where CompanyID = $CorporateID ORDER BY ID DESC)";
	$response = _getTableRecords($conn,'orders', $where);
	return $response;
}

function getAllOrderItems($conn)
{
	$where = " where 1 ORDER BY ID DESC";
	$response = _getTableRecords($conn,'orders', $where);
	return $response;
}


?>