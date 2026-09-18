<?php
function getAllBranches($conn,$CompanyID)
{
	if($CompanyID == -1)
	{
		$where = " where IsActive = 1";
	}
	else
	{
		$where = " where CompanyID = $CompanyID";
	}
	$where = $where." ORDER BY ID DESC";
	$response = _getTableRecords($conn,'branch', $where);
	return $response;
}

function getAllBranchesWithName($conn)
{
	$where = " where IsActive = 1";
	$response = _getTableRecords($conn,'branch', $where);
	return $response;
}

function InsertBranch($conn,$data)
{
	$branch_company = $data["branch_company"];
    $branch_name = $data["branch_name"];
	$branch_email = $data["branch_email"];
    $branch_mobile = $data["branch_mobile"];
	$branch_landline = $data["branch_landline"];
	$branch_address_1 = $data["branch_address_1"];
    $branch_address_2 = $data["branch_address_2"];
	$branch_city = $data["branch_city"];
    $branch_state = $data["branch_state"];
    $branch_latitude = $data['branch_latitude'];
    $branch_longitude = $data['branch_longitude'];
	$branch_postal_code = $data["branch_postal_code"];
	$site_Incharge = $data["site_incharge"];
	$branch_code = $data["branch_code"];
	$account_branch_manager = -1;
	if(isset($data['account_branch_manager']))
	{
		$account_branch_manager = $data['account_branch_manager'];
	}
	if($account_branch_manager == "")
	{
		$account_branch_manager = -1;
	}

    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');




    // $company_query = "INSERT INTO branch (CompanyID,BranchSite,BranchEmail,BranchMobile,BranchLandline,BranchAddress1,BranchAddress2,BranchCity,BranchState,BranchPostalCode,SiteIncharge,BranchCode,CreatedBy,CreatedDate,CreatedTime ) VALUES('$branch_company','$branch_name','$branch_email','$branch_mobile','$branch_landline','$branch_address_1','$branch_address_2','$branch_city','$branch_state','$branch_postal_code','$site_Incharge','$branch_code','$CreatedBy','$CreatedDate','$CreatedTime')";
    // $response = _InsertTableRecords($conn, $company_query);

	// $BranchID = $response['last_insert_id'];

	// $branch_username = $data["branch_username"];
	// $branch_password = md5($data["branch_password"]);
	// $user_type = "Corporate Branch User";
	// $emp_Id = "-1";

	// $add_branch_user = "INSERT INTO users (UserName,Password,UserType,EmployeeID,CorporateID,BranchID,CreatedDate,CreatedTime) VALUES('$branch_username','$branch_password','$user_type','$emp_Id',$branch_company,$BranchID,'$CreatedDate','$CreatedTime')";
	// $response_add_company_user = _InsertTableRecords($conn, $add_branch_user);




	$not_duplicate = true;

	$branch_username = "Not Set";
	if ($data["branch_username"]) 
	{
		$branch_username = $data["branch_username"];
	}

	if($branch_username != "Not Set") {
	$where = " where UserName = '$branch_username'";
	$not_duplicate = check_unique_identity_filter($conn,'users',$where);
	}

	if($not_duplicate)
	{
    	$company_query = "INSERT INTO branch (CompanyID,BranchSite,BranchEmail,BranchMobile,BranchLandline,BranchAddress1,BranchAddress2,BranchCity,BranchState,Latitude,Longitude,BranchPostalCode,SiteIncharge,AccountBranchManager,BranchCode,CreatedBy,CreatedDate,CreatedTime ) VALUES('$branch_company','$branch_name','$branch_email','$branch_mobile','$branch_landline','$branch_address_1','$branch_address_2','$branch_city','$branch_state','$branch_latitude','$branch_longitude','$branch_postal_code','$site_Incharge',$account_branch_manager,'$branch_code','$CreatedBy','$CreatedDate','$CreatedTime')";
    	$response = _InsertTableRecords($conn, $company_query);


		$BranchID = $response['last_insert_id'];
		$raw_password = 'Not Set';
		if($branch_username != "")
		{
			if ($data["branch_password"]) {
				$raw_password = $data["branch_password"];

			}
			
		    $branch_password = md5($data["branch_password"]);
			$user_type = "Corporate Branch User";
			$emp_Id = "-1";

			$add_branch_user = "INSERT INTO users (UserName,Password,UserType,EmployeeID,CorporateID,BranchID,CreatedDate,CreatedTime) VALUES('$branch_username','$branch_password','$user_type','$emp_Id',$branch_company,$BranchID,'$CreatedDate','$CreatedTime')";
			$response_add_company_user = _InsertTableRecords($conn, $add_branch_user);

			$message = "Dear $site_Incharge,\n\nThis is to inform you that we have just created a new branch ( $branch_name ) with following details - \n\nYour Credential:\n\nUsername - $branch_username\n\nPassword - $raw_password\n\nWarm regards,\nTechXpert Team\n\n\nApplication Link - https://play.google.com/store/apps/details?id=io.ionic.techXpert\n\nWebsite Link - https://techxpertindia.in/admin/authentication/login\n\n";
		   	$phonenumber = "+91".$branch_mobile;
			$post_mail_data['action'] = "New Branch Account Register";
			$post_mail_data['branch_username'] = $branch_username;
			$post_mail_data['BranchEmail'] = $branch_email;
			$post_mail_data['BranchSite'] = $branch_name;
			$post_mail_data['password'] = $raw_password;
			if($branch_company == 183)
			{
				sendInnovMailRequest($post_mail_data);
			}
			else
			{
				sendMailRequest($post_mail_data);
				sendWhatsAppMessage($phonenumber,$message);
			}
			

		}
		if($account_branch_manager != -1)
		{
			// check if already account manager is added or not
			$filter = " where EmployeeID = $account_branch_manager and Role = 'Account Branch Manager'";
			if(check_unique_identity_filter($conn,'user_roles', $filter))
			{
				$sql_insert_account_branch_manager_role = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($account_branch_manager,'Branch Account Manager','$CreatedDate','$CreatedTime')";
				$response = _InsertTableRecords($conn, $sql_insert_account_branch_manager_role);
			}
			
		}
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Username is already associated with other user. Please use different username";
		return $response;
	}


    $response['message'] = "Branch Added to the System";
    return $response;
}

function UpdateBranch($conn,$data)
{
	$branch_company = $data["branch_company"];
    $branch_name = $data["branch_name"];
	$branch_email = $data["branch_email"];
    $branch_mobile = $data["branch_mobile"];
	$branch_landline = $data["branch_landline"];
	$branch_address_1 = $data["branch_address_1"];
    $branch_address_2 = $data["branch_address_2"];
	$branch_city = $data["branch_city"];
    $branch_state = $data["branch_state"];
    $branch_latitude = $data['branch_latitude'];
    $branch_longitude = $data['branch_longitude'];
	$branch_postal_code = $data["branch_postal_code"];
	$site_Incharge = $data["site_incharge"];
	$branch_code = $data["branch_code"];
    $branch_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $branch_details_updated = false;
    $old_branch_details = GetBranchDetailsbyID($conn,$branch_id);
	$branch_username = $data["branch_username"];
	$account_branch_manager = $data['account_branch_manager'];
	
	if($branch_company == $old_branch_details['CompanyID'] && $branch_name == $old_branch_details['BranchSite'] && $branch_email == $old_branch_details['BranchEmail'] && $branch_mobile == $old_branch_details['BranchMobile'] && $branch_landline == $old_branch_details['BranchLandline']  && $branch_address_1 == $old_branch_details['BranchAddress1']  && $branch_address_2 == $old_branch_details['BranchAddress2']  && $branch_city == $old_branch_details['BranchCity']  && $branch_state == $old_branch_details['BranchState']  && $branch_postal_code == $old_branch_details['BranchPostalCode'] && $site_Incharge == $old_branch_details['SiteIncharge'] && $branch_code == $old_branch_details['BranchCode'] && $account_branch_manager == $old_branch_details['AccountBranchManager'] && $old_branch_details['Latitude'] == $branch_latitude && $old_branch_details['Longitude'] == $branch_longitude)
	{
		$response['message'] = "No changes to update";
		$response['error'] = true;
 	}
 	else
	{
    	$update_param = " CompanyID = '$branch_company',BranchSite='$branch_name', BranchEmail='$branch_email', BranchMobile='$branch_mobile', BranchLandline='$branch_landline', BranchAddress1 = '$branch_address_1',BranchAddress2='$branch_address_2', BranchCity='$branch_city', BranchState='$branch_state',Latitude = '$branch_latitude',Longitude = '$branch_longitude',BranchPostalCode='$branch_postal_code', SiteIncharge='$site_Incharge',AccountBranchManager = '$account_branch_manager',BranchCode='$branch_code'  where ID= $branch_id";
    	$response = _UpdateTableRecords($conn,'branch', $update_param);
    	if($response['error'] == false)
    	{
    		$response['message'] = "Branch Details Updated";
    	}
    	$branch_details_updated = true;

    	if($branch_company != $old_branch_details['CompanyID'])
    	{
    		// Update corporate tickets
    		$update_tickets = " CorporateID = $branch_company where BranchID = $branch_id";
    		_UpdateTableRecords($conn,'corporate_tickets', $update_tickets);

    		$update_users = " CorporateID = $branch_company where BranchID = $branch_id";
    		_UpdateTableRecords($conn,'users', $update_users);
    	}
	}
	if($branch_username != $old_branch_details['UserName'])
    {
    	// Validations
    	if($branch_username == "")
    	{
    		if($branch_details_updated)
    		{
    			$response['message'] = "Branch Details Updated, but you can't make username blank";
    			$response['error'] = false;
    		}
    		else
    		{
    			$response['message'] = "Username can't be blank";
    			$response['error'] = true;
    		}
    	}
    	else
    	{
    		$where = " where UserName = '$branch_username'";
			$not_duplicate = check_unique_identity_filter($conn,'users',$where);
			if($not_duplicate)
			{
				
				$response_update_username = UpdateUserName($conn,$branch_username,$old_branch_details['ID']);
				if(!$branch_details_updated)
	    		{
	    			$response['message'] = "Username updated";
	    			$response['error'] = false;
	    			
	    		}
			}
			else
			{
				if($branch_details_updated)
	    		{
	    			$response['message'] = "Branch Details Updated, but username can't be updated as it's already assigned to other user";
	    			$response['error'] = false;
	    		}
	    		else
	    		{
	    			$response['message'] = "Username can't be updated as it's already assigned to other user";
	    			$response['error'] = true;
	    		}
			}
    	}
    }
    // Update Account Manager
    if ($data['account_branch_manager'] != $old_branch_details['AccountBranchManager'])
{
    /* ---------- OLD ACCOUNT MANAGER ---------- */
    $old_account_branch_manager = $old_branch_details['AccountBranchManager'];

    if (!empty($old_account_branch_manager) && $old_account_branch_manager > 0)
    {
        $filter = " WHERE AccountBranchManager = $old_account_branch_manager";

        if (_getTotalRows($conn, 'branch', $filter) == 0)
        {
            $query_parameter =
                " WHERE Role='Branch Account Manager'
                  AND EmployeeID = $old_account_branch_manager";
            delete_identity_filter($conn, "user_roles", $query_parameter);
        }
    }

    /* ---------- NEW ACCOUNT MANAGER ---------- */
    if (!empty($account_branch_manager) && $account_branch_manager > 0)
    {
        $filter = " WHERE AccountBranchManager = $account_branch_manager";

        if (_getTotalRows($conn, 'branch', $filter) <= 1)
        {
            $sql_insert_account_branch_manager_role =
                "INSERT INTO user_roles(EmployeeID, Role, CreatedDate, CreatedTime)
                 VALUES ($account_branch_manager, 'Branch Account Manager',
                         '$CreatedDate', '$CreatedTime')";
            _InsertTableRecords($conn, $sql_insert_account_branch_manager_role);
        }
    }
}

    return $response;
}

function GetBranchDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	//echo $where;
	$branch_details = _getTableDetails($conn,'branch', $where);

	// Get Admin username and password
	$where = " where BranchID = $ID and UserType = 'Corporate Branch User'";
	$user_details = _getTableDetails($conn,'users', $where);
	$branch_details['UserName'] = "";
	if(isset($user_details['UserName']))
		$branch_details['UserName'] = $user_details['UserName'];
	return $branch_details;
}

function DeleteBranch($conn,$data)
{
	$BranchID = $data['ID'];
	$where = " where BranchID = $BranchID";
	// Delete branches
	delete_identity_filter($conn,"branch_assets",$where);

	// Delete all users
	if($BranchID != -1)
	{
		$where = " where BranchID = $BranchID";
		delete_identity_filter($conn,"users",$where);
	}

	// Delete company
	$where = " where ID = $BranchID";
	$response = delete_identity_filter($conn,"branch",$where);

	return $response;
}


function BranchResetPassword($conn,$data)
{
	$new_password = $data['reset_passsword'];
	$username = $data['reset_password_username'];
	$password_md5 = md5($new_password);
	$query_parameter = " Password = '$password_md5' where UserType = 'Corporate Branch User' and BranchID = '$username'";
	$response = _UpdateTableRecords($conn,'users', $query_parameter);
	if($response['error'] == false)
		$response['message'] = "Password changed!";
	return $response;
}

function getStateByCity($conn,$StateName)
{
	$StateID = '-1';
    $where = "where StateName = '$StateName'";
	$StateID = _getTableDetails($conn, 'state', $where)['ID'];

	$where = "where StateID = '$StateID'";
	$response = _getTableRecords($conn, 'citydata', $where);
	return $response;
}

function getTotalBranch($conn)
{
	$sql = "Select COUNT(*) as branch_count from branch where IsActive = 1";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		$row = $result->fetch_assoc();
		return $row['branch_count'];
	}
	else
	{
		return 0;
	}
}


?>