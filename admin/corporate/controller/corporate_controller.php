<?php
function getAllCorporate($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'corporate', $where);
	return $response;
}

function InsertCorporate($conn,$data)
{
	$corporate_name = cleantext($data["corporate_name"]);
	$corporate_gst = $data["corporate_gst"];
    $corporate_address = cleantext($data["corporate_address"]);
    $corporate_username = cleantext($data["corporate_username"]);
    $corporate_password = md5(cleantext($data["corporate_password"]));
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $corporate_query = "INSERT INTO corporate (CorporateName,CorporateGST,CoporateAddress,CreatedBy,CreatedDate,CreatedTime ) VALUES('$corporate_name','$corporate_gst','$corporate_address','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $corporate_query);
    $response['message'] = "Company Added to the System";
    
    if($corporate_username != ""){
        $user_type = "Company Admin";
        $emp_ID="-1";
        $CompanyID = $response['last_insert_id'];
        $Branch_ID="-1";
        $Corporate_ID="-1";
        
        $add_company_user_query = "INSERT INTO users (UserName,Password,UserType,EmployeeID,CompanyID,CorporateID,BranchID,CreatedDate,CreatedTime) VALUES('$corporate_username','$corporate_password','$user_type','$emp_ID','$CompanyID','$Corporate_ID','$Branch_ID','$CreatedDate','$CreatedTime')";
        $response_add_corporate_user = _InsertTableRecords($conn, $add_company_user_query);
    }
    return $response;
}

function UpdateCorporate($conn,$data)
{	
	$corporate_name = $data["corporate_name"];
	$corporate_gst = $data["corporate_gst"];
    $corporate_address = cleantext($data["corporate_address"]);
    $corporate_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $old_corporate_details = GetCorporateDetailsbyID($conn,$corporate_id);
    if($corporate_name == $old_corporate_details['CorporateName']  && $corporate_gst == $old_corporate_details['CorporateGST'] && $corporate_address == $old_corporate_details['CoporateAddress'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	$update_param = " CorporateName = '$corporate_name', CorporateGST = '$corporate_gst',CoporateAddress='$corporate_address' where ID=$corporate_id";
    	$response = _UpdateTableRecords($conn,'corporate', $update_param);
		$response['message'] = "Company Data update";
    	
    }
    return $response;
}

function GetCorporateDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$corporate_details = _getTableDetails($conn,'corporate', $where);
	return $corporate_details;
}

function DeleteCorporate($conn,$data)
{
	// Get Corporate Details
	$ID = $data['ID'];
	$query = " IsActive = 0 where ID = $ID";
	$response = _UpdateTableRecords($conn,'corporate', $query);
	return $response;
}


function SetAccessCorporate($conn,$data)
{
	$admin_username = $data['set_access_username_company'];
	$password = $data['set_access_password_company'];
	$CreatedBy = $data['CreatedBy'];
	$CompanyId = $data['set_access_company_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $company_phone = $data['set_access_phonenumber'];
	$access = 1;
	$where = " where UserName = '$admin_username'";
	$not_duplicate = check_unique_identity_filter($conn,'users',$where);
	
	if($not_duplicate)
	{
		$raw_password = $data["set_access_password_company"];
	    $admin_password = md5($data["set_access_password_company"]);
	    $user_type = "Corporate User";
	    $emp_Id = "-1";
	    $CorporateID = "-1";
		$sql = "INSERT INTO users(UserName,Password,UserType,EmployeeID,CompanyID,CorporateID,CreatedDate,CreatedTime) VALUES ('$admin_username','$admin_password','$user_type','$emp_Id','$CompanyId','$CorporateID','$CreatedDate','$CreatedTime')";
		$response = _InsertTableRecords($conn, $sql);
		// Update Access to 1
		$sql_update = " Access = 1 where ID = $CompanyId";
		_UpdateTableRecords($conn,'corporate',$sql_update);
		$message = "Hello,\n\nThis is to inform you that we have just created a new corporate with following details - \n\nYour Credential:\n\nUsername - $admin_username\n\nPassword - $raw_password\n\nWarm regards,\nTechXpert Team";
		if($company_phone != "")
		{
   		 	$phonenumber = "+91".$company_phone;
		 	sendWhatsAppMessage($phonenumber,$message);
		}
		return $response;
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Username is already associated with other user. Please use different Username";
		return $response;
	}
}

function ResetPasswordCompany($conn,$data)
{
	$new_password = $data['reset_passsword_company'];
	$reset_company_id = $data['reset_company_id'];
	$password_md5 = md5($new_password);
	$query_parameter = " Password = '$password_md5' where UserType = 'Corporate User' and CorporateID = $reset_company_id";
	$response = _UpdateTableRecords($conn,'users', $query_parameter);
	if($response['error'] == false)
		$response['message'] = "Password changed!";
	return $response;
}

function getAllIndustryType($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'master_industry_type', $where);
	return $response;
}


function GetIndustryTypeNameByID($conn, $ID)
{
    $where = " WHERE ID = $ID";
    $industry_details = _getTableDetails($conn, 'master_industry_type', $where);
    return $industry_details;
}

?>