<?php
function getAllCorporateUsers($conn,$CorporateID)
{
	$where = " where CorporateID = $CorporateID and IsActive = 1";
	$coprorate_users = _getTableRecords($conn,'corporate_users',$where);
	return $coprorate_users;
}
function InsertCorporateUser($conn,$data)
{
	$CreatedDate = date("Y-m-d");
	$CreatedTime = date("H:i:s");
	extract($data);
	$insert_corporate_user_sql = "INSERT INTO corporate_users(Name,PhoneNumber,Email,CorporateID,CreatedBy,CreatedDate,CreatedTime) VALUES ('$corporate_user_name','$corporate_user_phonenumber','$corporate_user_email',$CorporateID,'$CreatedBy','$CreatedDate','$CreatedTime')";
	$response = _InsertTableRecords($conn,$insert_corporate_user_sql);

	if($corporate_user_phonenumber != "")
	{
		$message = "Dear $corporate_user_name,\n\n You have been added as a user to handle the Corporate Account !\n\nCredentials details:\n\nUsername - $corporate_user_email \n\n Password - $corporate_user_password\n\nWarm regards,\nTechXpert Team";
	    $CorporateUserphonenumber = "+91".$corporate_user_phonenumber;
		sendWhatsAppMessage($CorporateUserphonenumber,$message);
	}

    
	if($response['error'] == false)
	{
		$last_insert_id = $response['last_insert_id'];
		$password_md5 = md5($corporate_user_password);
		// insert into users
		$insert_user_sql = "INSERT INTO users(UserName,Password,UserType,EmployeeID,CorporateID,BranchID,CorporateUserID,CreatedDate,CreatedTime) VALUES ('$corporate_user_email','$password_md5','Corporate Admin',-1,$CorporateID,-1,$last_insert_id,'$CreatedDate','$CreatedTime')";
		$response = _InsertTableRecords($conn,$insert_user_sql);
	}
	return $response;
}

function UpdateCorporateUser($conn,$data)
{	
	$corporate_user_name = $data["corporate_user_name"];
	$corporate_user_phonenumber = $data["corporate_user_phonenumber"];
    $corporate_user_email = $data["corporate_user_email"];
    //$corporate_user_approval_minimum = $data["corporate_user_approval_minimum"];
    //$corporate_user_approval_maximum = $data["corporate_user_approval_maximum"];
    $corporate_user_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $old_corporate_user_details = GetCorporateUserDetailsbyID($conn,$corporate_user_id);
    if($corporate_user_name == $old_corporate_user_details['Name']  && $corporate_user_phonenumber == $old_corporate_user_details['Phonenumber'] && $corporate_user_email == $old_corporate_user_details['Email'] /*&& $corporate_user_approval_minimum == $old_corporate_user_details['ApprovalMinRange'] && $corporate_user_approval_maximum == $old_corporate_user_details['ApprovalMaxRange']*/)
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	$update_param = " Name = '$corporate_user_name', Phonenumber = '$corporate_user_phonenumber',Email='$corporate_user_email' where ID=$corporate_user_id";
    	$response = _UpdateTableRecords($conn,'corporate_users', $update_param);
		$response['message'] = "Corporate User Details Updated";
    	
    }
    return $response;
}

function GetCorporateUserDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$corporate_details = _getTableDetails($conn,'corporate_users', $where);
	return $corporate_details;
}

function DeleteCorporateUser($conn,$data)
{
	// Get Corporate Details
	$ID = $data['ID'];
	$query = "where ID = $ID";
	$response = delete_identity_filter($conn,'corporate_users', $query);
	$user_query = "where CorporateUserID = $ID";
	$response = delete_identity_filter($conn,'users', $user_query);
	return $response;
}

function CorporateUserResetPassword($conn,$data)
{
	$new_password = $data['reset_corporate_passsword'];
	$reset_corporate_id = $data['reset_corporate_id'];
	$password_md5 = md5($new_password);
	$query_parameter = " Password = '$password_md5' where CorporateUserID = $reset_corporate_id";
	$response = _UpdateTableRecords($conn,'users', $query_parameter);
	if($response['error'] == false)
		$response['message'] = "Password changed!";
	return $response;
}
function GetCorporateUserByCustomerPrice($conn,$price)
{
	$sql = " where $price Between ApprovalMinRange AND ApprovalMaxRange ";
	$corporate_user_details = _getTableDetails($conn,'corporate_users', $sql);
	return $corporate_user_details;
}
?>