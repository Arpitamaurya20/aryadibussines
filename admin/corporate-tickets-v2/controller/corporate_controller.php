<?php
// function CreateCustomer($conn,$phonenumber)
// {
// 	//check for already existing customer
// 	$filter = " where PhoneNumber = '$phonenumber' and IsActive = 1";
// 	$response_check = check_unique_identity_filter($conn,'customers',$filter);
// 	if($response_check == true)
// 	{
// 		$CreatedDate = date('Y-m-d');
//     	$CreatedTime = date('H:i:s');
// 		$sql = "INSERT INTO customers(PhoneNumber,CreatedDate,CreatedTime) VALUES('$phonenumber','$CreatedDate','$CreatedTime')";
// 		_InsertTableRecords($conn,$sql);
// 	}
// }
// function getCustomerDetails($conn,$phonenumber)
// {
// 	$response = array();
// 	$where = " where PhoneNumber = '$phonenumber' and IsActive = 1";
// 	$data = _getTableDetails($conn,'customers', $where);
// 	$response['data'] = $data;
// 	if(sizeof($data) == 0)
// 	{
// 		$response['error'] = true;
// 		$response['message'] = "Technical problem, please try again later!";
// 	}
// 	else
// 	{
// 		$response['message'] = "Corporate Tickets fetched";
// 		$response['error'] = false;
// 	}
// 	return $response;
// }

// function updateCustomerDetails($conn,$data)
// {
// 	$phonenumber = $data['phonenumber'];
// 	$Name = $data['Name'];
// 	$Email = $data['Email'];
// 	$DefaultAddress = $data['DefaultAddress'];
// 	$City = $data['City'];
// 	$Landmark = $data['Landmark'];
// 	$not_duplicate = 1;
// 	$old_customer_details = getCustomerDetails($conn,$phonenumber);
// 	if($old_customer_details['data']['Email'] != $Email)
// 	{
// 		$filter = " where Email = '$Email'";
// 		if(check_unique_identity_filter($conn,'customers',$filter) == false)
// 		{
// 			$not_duplicate = 0;
// 			$response['error'] = true;
// 			$response['message'] = "Customer with same email is already registered";
// 		}
// 	}
// 	if($not_duplicate)
// 	{
// 		$query_parameter = " Name='$Name',Email='$Email',DefaultAddress = '$DefaultAddress',City='$City',Landmark='$Landmark' where PhoneNumber = '$phonenumber'";
// 		$response = _UpdateTableRecords($conn,'customers', $query_parameter);
// 		if($response['error'] == false)
// 		{
// 			$response['message'] = "Details updated";
// 		}
// 	}
// 	return $response;
// }


function getAllCorporateTickets($conn,$CorporateID,$BranchID)
{
	$corporate_check = "1";
	$branch_check = "1";
	if($CorporateID != -1)
	{
		$corporate_check = " CorporateID = $CorporateID ";
	}
	if($BranchID != -1)
	{
		$branch_check = " BranchID = $BranchID ";
	}
	$where = " where $corporate_check AND $branch_check AND IsActive = 1";
	$response = _getTableRecords($conn,'corporate_tickets', $where);
	return $response;
}

function DeleteCustomerDetails($conn,$data)
{
	$ID = $data['ID'];
	$query = " where ID = $ID";
	return delete_identity_filter($conn,'corporate_tickets', $query);
}
?>