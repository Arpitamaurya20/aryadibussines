<?php
function getAllBranchARC($conn)
{
	$where = "where 1";
	$response = _getTableRecords($conn, 'branch_arc', $where);
	return $response;
}

function DeleteBranchARCItem($conn,$data)
{
	// Get Corporate Details
	$ID = $data['ID'];
	$query = " where ID = $ID";
	$response = delete_identity_filter($conn,'branch_arc', $query);
	return $response;
}


function getGlobalARCItems($conn,$CategoryID)
{
	$where = "where ItemCategories = $CategoryID";
	$response = _getTableRecords($conn, 'arc', $where);
	return $response;
}

function getAllARCItemsByBranchID($conn,$BranchID)
{
	$where = "where BranchID = $BranchID ORDER BY ID DESC";
	$response = _getTableRecords($conn, 'branch_arc', $where);
	return $response;
}

function getARCItemsByID($conn,$ID)
{
	$where = "where ID = $ID";
	$response = _getTableDetails($conn, 'branch_arc', $where);
	return $response;
}

function UpdateBranchARCItem($conn,$data){
	$Price = $data["arc_price"];
    $branch_arc_id = $data["branch_arc_id"];
    $old_branch_details = getARCItemsByID($conn,$branch_arc_id);
	
	if($Price == $old_branch_details['Price'])
	{
		$response['message'] = "No changes to update";
		$response['error'] = true;
 	}
 	else
	{
    	$update_param = " Price = '$Price' where ID= $branch_arc_id";
    	$response = _UpdateTableRecords($conn,'branch_arc', $update_param);
    	if($response['error'] == false)
    	{
    		$response['message'] = "Branch ARC Details Updated";
    	}
    	$branch_details_updated = true;
	}
	return $response;
}

function AddToCart($conn,$data)
{
	
	$ARC_ID = $data['add_arc_id'];
	$ARC_Category = $data['add_arc_category'];
	$ARC_Price = $data['add_arc_price'];
	$Product_QTY = $data['product_qty'];
	$BranchID = $data['add_branch_id'];
	// Find if the item is alreaded added to the cart
	$where = " where BranchID = $BranchID and ProductID = $ARC_ID and Status='Add to Cart'";
	$cart_details = _getTableDetails($conn,'temp_cart',$where);
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date("H:i:s");
    $Status = "Add to Cart";
	$CartID = $data['CartID'];
	$CreatedBy = $data['CreatedBy'];
	if(isset($cart_details['ID']))
	{
		$ID = $cart_details['ID'];
		$order_qty = $cart_details['OrderQTY'];
		$updated_order_qty = strval(intval($order_qty)+intval($Product_QTY));
		$update_param = " OrderQTY = '$updated_order_qty' where ID = $ID";
		_UpdateTableRecords($conn,'temp_cart',$update_param);
		$response['message'] = "ARC Item Updated in Cart";
	}
	else
	{
    	$arc_query = "INSERT INTO temp_cart(CartID,BranchID,ProductID,ProductCategory,Price,OrderQTY,Status,CreatedBy,CreatedDate,CreatedTime) VALUES ('$CartID','$BranchID','$ARC_ID','$ARC_Category','$ARC_Price','$Product_QTY','$Status','$CreatedBy','$CreatedDate','$CreatedTime')";
    	$response = _InsertTableRecords($conn, $arc_query);
    	$response['message'] = "ARC Item Added in Cart";
    }
    
    return $response;
}

// function GenerateAddtoCartCode($conn)
// {
// 	$response = array();
// 	$Initials = "CART-";

// 	$where_query = " where 1";
// 	$max_seq = _getMaxIdentityValue_filter($conn,'temp_cart','ID', $where_query);
// 	$seq = $max_seq+1;
// 	$formatted_seq = sprintf('%04d', $seq);

// 	$DisplayCartID = $Initials.$formatted_seq;
// 	$response['DisplayCartID'] = $DisplayCartID;
// 	$response['ID'] = $seq;
// 	return $response;
// }

function GenerateCartID($conn)
{
	$CartID = _getMaxIdentityValue($conn,'temp_cart','CartID');
	return $CartID;
}

?>