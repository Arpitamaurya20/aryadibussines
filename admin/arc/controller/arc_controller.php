<?php

function getAllARC($conn)
{
	$where = " where 1";
	$response = _getTableRecords($conn,'arc', $where);
	return $response;
}

function InsertARC($conn,$data)
{
	
	$Item_name = $data['item_name'];
	$Item_decs = $data['item_decs'];
	$Item_price = $data['item_price'];
	$Item_categories = $data['item_categories'];
	$Item_uom = $data['item_uom'];
	$Item_No = GenerateARCItemCode($conn)['ItemCode'];
	$Item_Code = $Item_No;
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');

	$Item_image = "";

	if (isset($_FILES['item_image']['name'])  && $_FILES['item_image']['name'] != '')
		{
			$extn_pan = explode('.', $_FILES["item_image"]["name"]);
			$Item_image   = $Item_name."_item.".$extn_pan[1];
			$path = "../media/".$Item_image;
			move_uploaded_file($_FILES["item_image"]["tmp_name"], $path);
		}


    $arc_query = "INSERT INTO arc(ItemName,ItemDescription,ItemCode,ItemCategories,ItemUOM,ItemPrice,ItemImage,CreatedBy,CreatedDate) VALUES ('$Item_name', '$Item_decs', '$Item_Code', '$Item_categories','$Item_uom', '$Item_price', '$Item_image', '$CreatedBy', '$CreatedDate')";
    $response = _InsertTableRecords($conn, $arc_query);
    $response['message'] = "ARC Item Added to the System";
    return $response;
}

function UpdateARC($conn,$data)
{
	$Item_name = $data['item_name'];
	$Item_decs = $data['item_decs'];
	$Item_price = $data['item_price'];
	$Item_categories = $data['item_categories'];
	$Item_uom = $data['item_uom'];

	$Item_image = "";

	$arc_id = $data['form_id'];
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $old_arc_details = GetARCbyID($conn,$arc_id);
	$Item_code = $old_arc_details['ItemCode'];
    if($Item_name == $old_arc_details['ItemName'] && $Item_decs == $old_arc_details['ItemDescription'] && $Item_code == $old_arc_details['ItemCode'] && $Item_categories == $old_arc_details['ItemCategories'] && $Item_uom == $old_arc_details['ItemUOM'] && $Item_price == $old_arc_details['ItemPrice'] && $Item_image == $old_arc_details['ItemImage'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {

    	$update_param = " ItemName = '$Item_name',ItemDescription='$Item_decs', ItemCode='$Item_code', ItemCategories='$Item_categories', ItemUOM='$Item_uom', ItemPrice='$Item_price'
		  where ID= $arc_id";
    	$response = _UpdateTableRecords($conn,'arc', $update_param);

    	if($response['error'] == false)
    	{
    		$response['message'] = "ARC Item Details Updated";
    	}
    }
	if (isset($_FILES['item_image']['name'])  && $_FILES['item_image']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["item_image"]["name"]);
        $Item_image   = $Item_name."_item.".$extn_pan[1];
        $path = "../media/".$Item_image;
        move_uploaded_file($_FILES["item_image"]["tmp_name"], $path);

		$update_img = "UPDATE arc SET ItemImage='$Item_image' WHERE ID=$arc_id";
	    $result = mysqli_query($conn, $update_img);
    }
    return $response;
}

function GetARCbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$company_details = _getTableDetails($conn,'arc', $where);
	return $company_details;
}

function DeleteARCAssets($conn,$data)
{
	// Get Region Details
	$where = " where ID = $data[ID]";

	$query = delete_identity_filter($conn,"arc",$where);
	return $query;
}


function GenerateARCItemCode($conn)
{
	$response = array();
	$Initials = "PRD";

	$where_query = " where 1";
	$max_seq = _getMaxIdentityValue_filter($conn,'arc','ID', $where_query);
	$seq = $max_seq+1;
	$formatted_seq = sprintf('%04d', $seq);

	$ItemCode = $Initials.$formatted_seq;
	$response['ItemCode'] = $ItemCode;
	$response['ID'] = $seq;
	return $response;
}

function getTotalARCItem($conn)
{
    $sql = "Select COUNT(*) as arc_count from arc where IsActive = 1";
    $result=mysqli_query($conn,$sql);
    if($result->num_rows>0) 
    {
        $row = $result->fetch_assoc();
        return $row['arc_count'];
    }
    else
    {
        return 0;
    }
}

?>