<?php
function getAllCompanies($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'company', $where);
	return $response;
}
function getAllCompaniesbyCompanyHQ($conn,$CorporateHQID)
{
	$where = " where IsActive = 1";
	if($CorporateHQID != -1)
	{
		$where = $where." AND CorporateName = $CorporateHQID";
	}
	$where = $where." ORDER BY ID DESC";
	
	$response = _getTableRecords($conn,'company', $where);
	return $response;
}
function InsertCompany($conn,$data)
{
	$corporate_name = $data["corporate_name"];
	$company_name = $data["company_name"];
    $company_email = $data["company_email"];
	$company_phone = $data["company_phone"];
    $company_mobile = $data["company_mobile"];
	//$company_branches = $data["company_branches"];
	$company_tendor_raw = $data["company_tendor"];
	$company_tendor = implode(',',$company_tendor_raw);
	$company_tat = $data["company_tat"];
	$company_po_wo = $data["company_po_wo"];
	$company_po_wo_date = $data["company_po_wo_date"];
	$account_manager = $data['account_manager'];
	$CompanyIndustryType=$data['corporate_industry'];
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
	$not_duplicate = true;
	$access = 0;
	$admin_username = $data["admin_username"];
	$TicketsNeedApproval = 0;
	if(isset($_POST['need_approval_by_company_admin']))
    {
    	$TicketsNeedApproval = 1;
    }
    $AdditionalPriorities = $data['additional_priorities'];
    $TicketsNeedsWAMessage = 0;
	if(isset($_POST['need_wa_message']))
    {
    	$TicketsNeedsWAMessage = 1;
    }
	if($admin_username != "")
	{
		$access = 1;
		$where = " where UserName = '$admin_username'";
		$not_duplicate = check_unique_identity_filter($conn,'users',$where);
	}
	if($not_duplicate)
	{
		
    	$company_query = "INSERT INTO company (CorporateName,CompanyName,CompanyEmail,CompanyPhone,CompanyMobile,CompanyTendor,CompanyTAT,CompanyPOWO,CompanyPOWODate,Access,TicketsNeedApproval,TicketNeedsWAMessage,AdditionalPriorities,AccountManager,CompanyIndustryType,CreatedBy,CreatedDate,CreatedTime) VALUES('$corporate_name','$company_name','$company_email','$company_phone','$company_mobile','$company_tendor','$company_tat','$company_po_wo','$company_po_wo_date',$access,$TicketsNeedApproval,$TicketsNeedsWAMessage,'$AdditionalPriorities',$account_manager,'$CompanyIndustryType','$CreatedBy','$CreatedDate','$CreatedTime')";
    	$response = _InsertTableRecords($conn, $company_query);
		$lastId = $response['last_insert_id'];
		if($admin_username != "")
		{
			$raw_password = $data["admin_password"];
		    $admin_password = md5($data["admin_password"]);
		    $user_type = "Corporate Admin";
		    $emp_Id = "-1";
			$sql = "INSERT INTO users(UserName,Password,UserType,EmployeeID,CorporateID,CreatedDate,CreatedTime) VALUES ('$admin_username','$admin_password','$user_type','$emp_Id','$lastId','$CreatedDate','$CreatedTime')";
			$response = _InsertTableRecords($conn, $sql);
			$message = "Dear $company_name,\n\nThis is to inform you that we have just created a new corporate with following details - \n\nYour Credential:\n\nUsername - $admin_username\n\nPassword - $raw_password\n\nWarm regards,\nTechXpert Team\n\n\nApplication Link - https://play.google.com/store/apps/details?id=io.ionic.techXpert\n\nWebsite Link - https://techxpertindia.in/admin/authentication/login\n\n";
	   		 $phonenumber = "+91".$company_phone;
	   		 $post_mail_data['action'] = "New Corporate Account Register";
	   		 $post_mail_data['admin_username'] = $admin_username;
	   		 $post_mail_data['CompanyEmail'] = $company_email;
	   		 $post_mail_data['CompanyName'] = $company_name;
	   		 $post_mail_data['password'] = $raw_password;
	   		 sendMailRequest($post_mail_data);
			 sendWhatsAppMessage($phonenumber,$message);
		}
		if($account_manager != -1)
		{
			// check if already account manager is added or not
			$filter = " where EmployeeID = $account_manager and Role = 'Account Manager'";
			if(check_unique_identity_filter($conn,'user_roles', $filter))
			{
				$sql_insert_account_manager_role = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($account_manager,'Account Manager','$CreatedDate','$CreatedTime')";
				$response = _InsertTableRecords($conn, $sql_insert_account_manager_role);
			}
			
		}
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Username is already associated with other user. Please use different username";
		return $response;
	}
	// if($admin_username != "")
	// {
	// 	$admin_password = md5($data["admin_password"]);
	// 	$user_type = "Corporate Admin";
	// 	$emp_Id = "-1";
	// 	$add_corporate_user = "INSERT INTO users (UserName,Password,UserType,EmployeeID,CorporateID,CreatedDate,CreatedTime) VALUES('$admin_username','$admin_password','$user_type','$emp_Id','$lastId','$CreatedDate','$CreatedTime')";
	// 	$response_add_company_user = _InsertTableRecords($conn, $add_corporate_user);
	// }
    $response['message'] = "Company Added to the System";
    return $response;
}
function CorporateSetAccess($conn,$data)
{
	$admin_username = $data['set_access_username'];
	$password = $data['set_access_password'];
	$CreatedBy = $data['CreatedBy'];
	$CorporateID = $data['set_access_corporate_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $company_phone = $data['set_access_phonenumber'];
	$access = 1;
	$where = " where UserName = '$admin_username'";
	$not_duplicate = check_unique_identity_filter($conn,'users',$where);
	
	if($not_duplicate)
	{
		$raw_password = $data["set_access_password"];
	    $admin_password = md5($data["set_access_password"]);
	    $user_type = "Corporate Admin";
	    $emp_Id = "-1";
		$sql = "INSERT INTO users(UserName,Password,UserType,EmployeeID,CorporateID,CreatedDate,CreatedTime) VALUES ('$admin_username','$admin_password','$user_type','$emp_Id','$CorporateID','$CreatedDate','$CreatedTime')";
		$response = _InsertTableRecords($conn, $sql);
		// Update Access to 1
		$sql_update = " Access = 1 where ID = $CorporateID";
		_UpdateTableRecords($conn,'company',$sql_update);
		$message = "Hello,\n\nThis is to inform you that we have just created a new corporate with following details - \n\nYour Credential:\n\nUsername - $admin_username\n\nPassword - $raw_password\n\nWarm regards,\nTechXpert Team\n\n\nApplication Link - https://play.google.com/store/apps/details?id=io.ionic.techXpert\n\nWebsite Link - https://techxpertindia.in/admin/authentication/login\n\n";
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
function UpdateCompany($conn,$data)
{
	$corporate_name = $data["corporate_name"];
	$company_name = $data["company_name"];
    $company_email = $data["company_email"];
	$company_phone = $data["company_phone"];
    $company_mobile = $data["company_mobile"];
	$company_tendor_raw = $data["company_tendor"];
	$company_tendor = implode(',',$company_tendor_raw);
	$company_tat = $data["company_tat"];
	$company_po_wo = $data["company_po_wo"];
	$company_po_wo_date = $data["company_po_wo_date"];
    $company_id = $data['form_id'];
    $account_manager = $data['account_manager'];
	$AdditionalPriorities=$data['additional_priorities'];
	$CompanyIndustryType=$data['corporate_industry'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    if(isset($data['need_approval_by_company_admin']))
    	$TicketsNeedApproval = 1;
    else
    	$TicketsNeedApproval = 0;
    if(isset($data['need_wa_message']))
    	$TicketNeedsWAMessage = 1;
    else
    	$TicketNeedsWAMessage = 0;
    $company_details_updated = false;
    $old_company_details = GetCompanyDetailsbyID($conn,$company_id);
    if($corporate_name == $old_company_details['CorporateName'] && $company_name == $old_company_details['CompanyName'] && $company_email == $old_company_details['CompanyEmail'] && $company_phone == $old_company_details['CompanyPhone'] && $company_mobile == $old_company_details['CompanyMobile'] && $company_tendor == $old_company_details['CompanyTendor'] && $company_tat == $old_company_details['CompanyTAT'] && $company_po_wo == $old_company_details['CompanyPOWO'] && $company_po_wo_date == $old_company_details['CompanyPOWODate'] && $TicketsNeedApproval == $old_company_details['TicketsNeedApproval'] && $TicketNeedsWAMessage == $old_company_details['TicketNeedsWAMessage'] && $account_manager == $old_company_details['AccountManager'] && $AdditionalPriorities == $old_company_details['AdditionalPriorities'] && $CompanyIndustryType == $old_company_details['CompanyIndustryType'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }

    else
    {
    	$update_param = "CorporateName = '$corporate_name', CompanyName = '$company_name',CompanyEmail='$company_email', CompanyPhone='$company_phone', CompanyMobile='$company_mobile',CompanyTendor='$company_tendor',CompanyTAT='$company_tat',CompanyPOWO='$company_po_wo',CompanyPOWODate='$company_po_wo_date',TicketsNeedApproval=$TicketsNeedApproval,TicketNeedsWAMessage=$TicketNeedsWAMessage,AdditionalPriorities='$AdditionalPriorities',CompanyIndustryType='$CompanyIndustryType',AccountManager=$account_manager  where ID= $company_id";
    	$response = _UpdateTableRecords($conn,'company', $update_param);
    	if($response['error'] == false)
    	{
    		$response['message'] = "Company Details Updated";
    		$company_details_updated = false;
    	}
    }

    if($data['admin_username'] != $old_company_details['UserName'])
    {
    	// Validations
    	if($data['admin_username'] == "")
    	{
    		if($company_details_updated)
    		{
    			$response['message'] = "Company Details Updated, but you can't make username blank";
    		}
    		else
    		{
    			$response['message'] = "Username can't be blank";
    		}
    	}
    	else
    	{
    		$admin_username = $data['admin_username'];
    		$where = " where UserName = '$admin_username'";
			$not_duplicate = check_unique_identity_filter($conn,'users',$where);
			if($not_duplicate)
			{
				$response_update_username = UpdateUserName($conn,$admin_username,$old_company_details['UserName']);
				if(!$company_details_updated)
	    		{
	    			$response['message'] = "Username updated";
	    			$response['error'] = false;
	    		}
			}
			else
			{
				if($company_details_updated)
	    		{
	    			$response['message'] = "Company Details Updated, but username can't be updated as it's already assigned to other user";
	    		}
	    		else
	    		{
	    			$response['message'] = "Username can't be updated as it's already assigned to other user";
	    		}
			}
    	}
    }

    // Update Account Manager
    if($data['account_manager'] != $old_company_details['AccountManager'])
    {
    	$old_account_manager = $old_company_details['AccountManager'];
    	$filter = " where AccountManager = $old_account_manager";
    	// check if the old member is already account manager
    	// To check the old member already account manager
    	if(_getTotalRows($conn,'company',$filter) > 0)
    	{
    		// do nothing for old
    	}
    	else
    	{
    		// Remove role of the old one
    		$query_parameter = " where Role='Account Manager' and EmployeeID = $old_account_manager";
    		delete_identity_filter($conn,"user_roles",$query_parameter);
    	}

    	// Check if new one is already Account Manager of any other Account
    	if($account_manager != -1)
    	{
	    	$filter = " where AccountManager = $account_manager";
	    	if(_getTotalRows($conn,'company',$filter) > 1)
	    	{
	    		// do nothing for new
	    	}
	    	else
	    	{
	    		// Insert new role
	    		$sql_insert_account_manager_role = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($account_manager,'Account Manager','$CreatedDate','$CreatedTime')";
				_InsertTableRecords($conn, $sql_insert_account_manager_role);
	    	}
    	}		
    }
    return $response;
}
function GetCompanyDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$company_details = _getTableDetails($conn,'company', $where);
	// Get Admin username and password
	$where = " where CorporateID = $ID and UserType = 'Corporate Admin'";
	$user_details = _getTableDetails($conn,'users', $where);
	$company_details['UserName'] = "";
	if(isset($user_details['UserName']))
		$company_details['UserName'] = $user_details['UserName'];
	return $company_details;
}
function DeleteCompany($conn,$data)
{
	$CompanyID = $data['ID'];
	$where = " where CompanyID = $CompanyID";
	// Delete branches
	delete_identity_filter($conn,"branch",$where);
	// Delete all users
	if($CompanyID != -1)
	{
		$where = " where CorporateID = $CompanyID";
		delete_identity_filter($conn,"users",$where);
	}
	//Get All branches
	$where = " where CompanyID = $CompanyID and IsActive = 1";
	$branches = _getTableRecords($conn,'branch', $where);
	// Deactivate branches
	$query_parameter = " IsActive = 0 where CompanyID = $CompanyID";
	_UpdateTableRecords($conn, 'branch', $query_parameter);
	// Deactivate branch assets
	foreach($branches as $branch)
	{
		$BranchID = $branch['ID'];
		$query_parameter = " IsActive = 0 where BranchID = $BranchID";
		_UpdateTableRecords($conn, 'branch_assets', $query_parameter);
	}
	// Deactivate Tickets
	$query_parameter = " IsActive = 0 where CorporateID = $CompanyID";
	_UpdateTableRecords($conn, 'corporate_tickets', $query_parameter);
	// Delete company
	$where = " where ID = $CompanyID";
	$response = delete_identity_filter($conn,"company",$where);
	return $response;
}
function CorporateResetPassword($conn,$data)
{
	$new_password = $data['reset_passsword'];
	$reset_corporate_id = $data['reset_corporate_id'];
	$password_md5 = md5($new_password);
	$query_parameter = " Password = '$password_md5' where UserType = 'Corporate Admin' and CorporateID = $reset_corporate_id";
	$response = _UpdateTableRecords($conn,'users', $query_parameter);
	if($response['error'] == false)
		$response['message'] = "Password changed!";
	return $response;
}

function setActiveDeactive($conn, $data){
	$CompanyID = $data['ID'];
	$Isactive = $data['status'];
	$company_query = " IsActive = '$Isactive' where ID = $CompanyID";
	$response = _UpdateTableRecords($conn,'company', $company_query);
    return $response;
}

function ExportCompanyData($conn){

	$query = "SELECT * FROM company";
	$result = mysqli_query($conn, $query);

	// Create a new PHPExcel object
	$objPHPExcel = new PHPExcel();

	// Set the active sheet
	$objPHPExcel->setActiveSheetIndex(0);

	// Write data to the active sheet
	$row = 1;
	while ($data = mysqli_fetch_array($result)) {
	    $objPHPExcel->getActiveSheet()->setCellValue('A'.$row, $data['column_1']);
	    $objPHPExcel->getActiveSheet()->setCellValue('B'.$row, $data['column_2']);
	    $objPHPExcel->getActiveSheet()->setCellValue('C'.$row, $data['column_3']);
	    $row++;
	}

	// Download the file
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="data.xlsx"');
	header('Cache-Control: max-age=0');
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$objWriter->save('php://output');
    exit;
}
?>