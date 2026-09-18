<?php

function getAllCartItems($conn,$BranchID)
{
	$where = " where Status = 'Add to Cart' and BranchID = $BranchID";
	$response = _getTableRecords($conn,'temp_cart', $where);
	return $response;
}

function InsertOrder($conn,$data)
{
	
	$response = array();

	$CreatedDate = date("Y-m-d");
	$CreatedTime = date("H:i:s");
	$ProductIDArr = $data['ProductID'];
	$CategoriesArr = $data['CategoriesID'];
	$BranchIDArr = $data['BranchID'];
	$PriceArrr = $data['Price'];
	$OrderQTYArr = $data['OrderQTY'];
	$Order_Code = $data['OrderID'];
	$CartIDArr = $data['CartID'];
	$CreatedBy = $data['CreatedBy'];

	foreach ($ProductIDArr as $key => $val) 
	{
		   
	   $ProductID = $val;
	   $CategoriesID = $CategoriesArr[$key];
	   $BranchID = $BranchIDArr[$key];
	   $Price = $PriceArrr[$key];
	   $Order = $OrderQTYArr[$key];
	   $CartID = $CartIDArr[$key];
	   $Status = "Ordered";
		$branch_arc_query = "INSERT INTO order_item (OrderID,BranchID,ProductID,ProductCategory,Price,OrderQTY,Status,CreatedBy,CreatedDate,CreatedTime) VALUES('$Order_Code','$BranchID','$ProductID','$CategoriesID','$Price','$Order','$Status','$CreatedBy','$CreatedDate','$CreatedTime')";
		// echo $branch_arc_query;
		$response = _InsertTableRecords($conn, $branch_arc_query);

		$update_param = " Status = 'Ordered' where CartID= $CartID";
	    $response = _UpdateTableRecords($conn,'temp_cart',$update_param);

	    
	}

	$orders_query = "INSERT INTO orders (OrderID,BranchID,CreatedBy,CreatedDate,CreatedTime) VALUES('$Order_Code','$BranchID','$CreatedBy','$CreatedDate','$CreatedTime')";
		// echo $branch_arc_query;
    $response = _InsertTableRecords($conn, $orders_query);

	$where = " where ID = $BranchID";
	$branch_Detail = _getTableDetails($conn,'branch',$where);
	$BranchPhone = $branch_Detail['BranchMobile'];
	$CityName = $branch_Detail['BranchCity'];
	$BranchSite = $branch_Detail['BranchSite'];
	$BranchSiteIncharge = $branch_Detail['SiteIncharge'];
	$BranchCode = $branch_Detail['BranchCode'];
	$BranchState = $branch_Detail['BranchState'];
	$BranchEmail = $branch_Detail['BranchEmail'];

	$message = "Dear $BranchSiteIncharge,\n\nThis is to inform you that your items has been successfully Ordered.\n\nWarm regards,\nTechXpert Team\n\n\nApplication Link - https://play.google.com/store/apps/details?id=io.ionic.techXpert\n\nWebsite Link - https://techxpertindia.in/admin/authentication/login\n\n";
   	$Branchphonenumber = "+91".$BranchPhone;
	sendWhatsAppMessage($Branchphonenumber,$message);

	$post_mail_data['action'] = "Branch ARC Order";
	$post_mail_data['Order_Code'] = $Order_Code;
	$post_mail_data['Branch_Site_Incharge'] = $BranchSiteIncharge;
	$post_mail_data['Branch_Site'] = $BranchSite;
	$post_mail_data['Branch_Code'] = $BranchCode;
	$post_mail_data['Branch_Phone'] = $BranchPhone;
	$post_mail_data['City_Name'] = $CityName;
	$post_mail_data['Branch_State'] = $BranchState;
	$post_mail_data['Branch_Email'] = $BranchEmail;
	sendMailRequest($post_mail_data);

	// send message to techxpert 
	$message = "Dear TechXpert,\n\nThis is to inform you that we have been recieved order from $BranchSiteIncharge.\n\nOrder Details:\n\nOrder Code - $Order_Code\nBranch Name/Code - $BranchSite/$BranchCode\nBranch Mobile Number - $BranchPhone\nBranch City/State - $CityName/$BranchState\n\nWarm regards,\nTechXpert Team";
   	$Branchphonenumber = "+91".$BranchPhone;
	sendWhatsAppMessage($Branchphonenumber,$message);

	$post_mail_data['action'] = "TechXpert ARC Order";
	sendMailRequest($post_mail_data);

	$response['error'] = false;
	$response['message'] = "Your Items Has been successfully Ordered";

	return $response;
}

function GenerateOrderCode($conn)
{
	$response = array();
	$Initials = "ORD-";

	$where_query = " where 1";
	$max_seq = _getMaxIdentityValue_filter($conn,'order','ID', $where_query);
	$seq = $max_seq+1;
	$formatted_seq = sprintf('%04d', $seq);

	$OrderID = $Initials.$formatted_seq;
	$response['OrderID'] = $OrderID;
	$response['ID'] = $seq;
	return $response;
}

function GenerateOrderID($conn)
{
	$OrderID = _getMaxIdentityValue($conn,'orders','OrderID');
	return $OrderID;
}



?>