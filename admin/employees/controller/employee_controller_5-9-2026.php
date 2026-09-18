<?php
function InsertEmployee($conn)
{
	$response = array();
	$employee_name = $_POST['employee_name'];
	$father_name=$_POST['father_name'];
	$designation = $_POST['designation'];
	$employee_department = "Vendor";
    if(isset($_POST['employee_department']))
    {
    	$employee_department = $_POST['employee_department'];
    }
    $basic = $_POST['basic'];
	$da = $_POST['da'];
    $hra = $_POST['hra'];
    $bonus = $_POST['bonus'];
	$health_insurance = $_POST['health_insurance'];
    $others = $_POST['others'];
    $employee_email = $_POST['employee_email'];
    $employee_contact = $_POST['employee_contact'];
	$bank_account_name = $_POST['bank_account_name'];
    $bank_account_number = $_POST['bank_account_number'];
    $employee_pan_number = $_POST['employee_pan_number'];
    $weekly_off = $_POST['weekly_off'];
	$employee_pan_image_path = '';
	$employee_aadhar_image_path = '';
	$employee_police_verify_image_path = '';
	$employee_profile_image_path = '';
	$EmpNo = GenerateEmployeeNumber($conn)['EmployeeNumber'];
	$DivisionSequence = GenerateEmployeeNumber($conn)['DivisionSequence'];
	$employee_id = $EmpNo;
	if (isset($_FILES['employee_pan_img']['name'])  && $_FILES['employee_pan_img']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["employee_pan_img"]["name"]);
        $employee_pan_image_path   = $EmpNo."_PAN.".$extn_pan[1];
        $path = "../media/".$employee_pan_image_path;
        move_uploaded_file($_FILES["employee_pan_img"]["tmp_name"], $path);
    }

	if (isset($_FILES['employee_addhar_img']['name'])  && $_FILES['employee_addhar_img']['name'] != '')
    {
        //echo $_FILES['employee_addhar_img']['name'];
        $extn_pan = explode('.', $_FILES["employee_addhar_img"]["name"]);
        $employee_aadhar_image_path   = $EmpNo."_ADH.".$extn_pan[1];
        $path = "../media/".$employee_aadhar_image_path;
       // echo "<br>".$path;
        move_uploaded_file($_FILES["employee_addhar_img"]["tmp_name"], $path);
    }

	if (isset($_FILES['employee_police_verification']['name'])  && $_FILES['employee_police_verification']['name'] != '')
    {
        //echo $_FILES['employee_police_verification']['name'];
        $extn_pan = explode('.', $_FILES["employee_police_verification"]["name"]);
        $employee_police_verify_image_path   = $EmpNo."_PV.".$extn_pan[1];
        $path = "../media/".$employee_police_verify_image_path;
        //echo "<br>".$path;
        move_uploaded_file($_FILES["employee_police_verification"]["tmp_name"], $path);
    }

	if (isset($_FILES['employee_profile_photo']['name'])  && $_FILES['employee_profile_photo']['name'] != '')
    {
        //echo $_FILES['employee_profile_photo']['name'];
        $extn_pan = explode('.', $_FILES["employee_profile_photo"]["name"]);
        $employee_profile_image_path   = $EmpNo."_PP.".$extn_pan[1];
        $path = "../media/".$employee_profile_image_path;
        //echo "<br>".$path;
        move_uploaded_file($_FILES["employee_profile_photo"]["tmp_name"], $path);
    }

    $employee_aadhar = $_POST['employee_aadhar'];
    $work_type = $_POST['work_type'];
    $username = $_POST['username'];
 	$CreatedDate = $_POST['CreatedDate'];
 	$CreatedTime = $_POST['CreatedTime'];
    $vendor = 0;
	$gender_type = $_POST['gender_type'];
	$uan_number = $_POST['uan_number'];
	$date_of_joining = $_POST['date_of_joining'];
	$employee_epf = $_POST['epf_number'];
	$employee_esic = $_POST['esic_number'];
	if(isset($_POST['employee_city']))
		$employee_city = $_POST['employee_city'];
	else
		$employee_city = "";
	if(isset($_POST['employee_state']))
		$employee_state = $_POST['employee_state'];
	else
		$employee_state = "";

    if($work_type == "Vendor")
    {
    	$vendor = 1;
    }

	$sql = "INSERT INTO employees(DivisionSequence,Name,FatherName,Designation,EmployeeNumber,Department,Basic,DA,HRA,Bonus,HealthInsurance,Others,Email,ContactNumber,BankAccountName,BankAccountNumber,PAN,PANImage,Aadhar,AadharImage,ProfileImage,PoliceVerificationImage,Vendor,CreatedBy,CreatedDate,CreatedTime,Gender,UANNumber,DateofJoining,Epf_number,Esic_number,WeeklyOff,City,State) VALUES ($DivisionSequence,'$employee_name','$father_name','$designation','$employee_id','$employee_department','$basic','$da','$hra','$bonus','$health_insurance','$others','$employee_email','$employee_contact','$bank_account_name','$bank_account_number','$employee_pan_number', '$employee_pan_image_path','$employee_aadhar', '$employee_aadhar_image_path', '$employee_profile_image_path', '$employee_police_verify_image_path', '$vendor','$username','$CreatedDate','$CreatedTime','$gender_type', '$uan_number', '$date_of_joining', '$employee_epf', '$employee_esic', '$weekly_off','$employee_city','$employee_state')";
	$response = _InsertTableRecords($conn, $sql);
	return $response;

}

function UpdateEmployee($conn,$data)
{
	$employee_gender = $data['employee_gender'];
	$employee_id = $data['employee_id'];
	$date_of_joining = $data['date_of_joining'];
    $employee_name = $data['employee_name'];
	$father_name=$data['father_name'];
	$designation = $data['designation'];
    $employee_email = $data['employee_email'];
	$employee_personalemail = $data['PersonalEmail'];
    if(isset($data['employee_department'])) 
	$employee_department = $data['employee_department'];
    $employee_contact = $data['employee_contact'];
	$bank_account_name = $data['bank_account_name'];
    $bank_account_number = $data['bank_account_number'];
    $employee_uan_number = $data['employee_uan_number'];
    $employee_pan_number = $data['employee_pan_number'];
    $employee_aadhar_number = $data['employee_aadhar_number'];
    $employee_city = $data['employee_city'];
	$employee_state = $data['employee_state'];
    $weekly_off = $data['weekly_off'];


    $ID = $data['EmployeeID'];

	$employee_pan_image_path = '';
	$employee_aadhar_image_path = '';
	$employee_police_verify_image_path = '';
	$employee_profile_image_path = '';

	if (isset($_FILES['employee_pan_img']['name'])  && $_FILES['employee_pan_img']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["employee_pan_img"]["name"]);
        $employee_pan_image_path   = $employee_id."_PAN.".$extn_pan[1];
        $path = "../media/".$employee_pan_image_path;
        move_uploaded_file($_FILES["employee_pan_img"]["tmp_name"], $path);

		$update_img = "UPDATE employees SET PANImage='$employee_pan_image_path' WHERE ID=$ID";
	    $result = mysqli_query($conn, $update_img);
    }

	if (isset($_FILES['employee_addhar_img']['name'])  && $_FILES['employee_addhar_img']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["employee_addhar_img"]["name"]);
        $employee_aadhar_image_path   = $employee_id."_ADH.".$extn_pan[1];
        $path = "../media/".$employee_aadhar_image_path;
        move_uploaded_file($_FILES["employee_addhar_img"]["tmp_name"], $path);

		$update_img = "UPDATE employees SET AadharImage='$employee_aadhar_image_path' WHERE ID = $ID";
	    $result 	= mysqli_query($conn, $update_img);
    }

	if (isset($_FILES['employee_police_verification']['name'])  && $_FILES['employee_police_verification']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["employee_police_verification"]["name"]);
        $employee_police_verify_image_path   = $employee_id."_PV.".$extn_pan[1];
        $path = "../media/".$employee_police_verify_image_path;
        move_uploaded_file($_FILES["employee_police_verification"]["tmp_name"], $path);

		$update_img = "UPDATE employees SET PoliceVerificationImage='$employee_police_verify_image_path' WHERE ID = $ID ";
	    $result 	= mysqli_query($conn, $update_img);
    }

	if (isset($_FILES['employee_profile_photo']['name'])  && $_FILES['employee_profile_photo']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["employee_profile_photo"]["name"]);
        $employee_profile_image_path   = $employee_id."_PP.".$extn_pan[1];
        $path = "../media/".$employee_profile_image_path;
        move_uploaded_file($_FILES["employee_profile_photo"]["tmp_name"], $path);

        $update_img = "UPDATE employees SET ProfileImage='$employee_profile_image_path' WHERE ID = $ID";
	    $result 	= mysqli_query($conn, $update_img);
    }

    $update_employee = " Name='$employee_name',FatherName='$father_name', designation='$designation',EmployeeNumber='$employee_id',Department='$employee_department',Email='$employee_email',PersonalEmail='$employee_personalemail',ContactNumber='$employee_contact',BankAccountName='$bank_account_name',BankAccountNumber='$bank_account_number',PAN='$employee_pan_number',Aadhar='$employee_aadhar_number',Gender = '$employee_gender',UANNumber = '$employee_uan_number',DateofJoining = '$date_of_joining',WeeklyOff='$weekly_off',City='$employee_city' WHERE ID = '$ID'";

    $response = _UpdateTableRecords($conn,'employees', $update_employee);
    return $response;
}


function UpdateSalaryEmployee($conn,$data)
{
	$basic = $data['basic'];
	$da = $data['da'];
    $hra = $data['hra'];
    $bonus = $data['bonus'];
	$health_insurance = $data['health_insurance'];
    $others = $data['others'];
    $employee_epf_number = $data['employee_epf_number'];
    $employee_esic_number = $data['employee_esic_number'];
    $ID = $data['EmployeeID'];

    $update_employee = "Basic='$basic',DA='$da',HRA='$hra',Bonus='$bonus',HealthInsurance='$health_insurance',Others='$others',Epf_number = '$employee_epf_number',Esic_number='$employee_esic_number' WHERE ID = '$ID'";
    $response = _UpdateTableRecords($conn,'employees', $update_employee);
    return $response;
}


function CheckForDuplicateEmployeeDetails($conn,$data)
{
	$response = array();
	$response['error'] = true;
	$employee_email = $data['employee_email'];
	$employee_contact = $data['employee_contact'];
    $employee_pan_number = $data['employee_pan_number'];
    $employee_aadhar = $data['employee_aadhar'];
    $filter = " where Email = '$employee_email' and IsActive = 1";
   	if(check_unique_identity_filter($conn,'employees',$filter) === false)
   	{
   		$response['message'] = "Employee / Vendor with same Email is already registered";
   		return $response;
   	}
   	$filter = " where ContactNumber = '$employee_contact' and IsActive = 1";
   	if(check_unique_identity_filter($conn,'employees',$filter) === false)
   	{
   		$response['message'] = "Employee / Vendor with same Contact number is already registered";
   		return $response;
   	}
   	if($employee_pan_number != "")
   	{
	   	$filter = " where PAN = '$employee_pan_number' and IsActive = 1";
	   	if(check_unique_identity_filter($conn,'employees',$filter) === false)
	   	{
	   		$response['message'] = "Employee / Vendor with same PAN is already registered";
	   		return $response;
	   	}
	}
	if($employee_aadhar != "")
	{
	   	$filter = " where Aadhar = '$employee_aadhar' and IsActive = 1";
	   	if(check_unique_identity_filter($conn,'employees',$filter) === false)
	   	{
	   		$response['message'] = "Employee / Vendor with same Aadhar is already registered";
	   		return $response;
	   	}
	}
   	$response['error'] = false;
   	$response['message'] = "No Duplicate Found";
   	return $response;
}

function InsertUserRole($conn,$data)
{
	$EmployeeID = $data['EmployeeID'];
	$Role = $data['Role'];
	$CreatedDate = $data['CreatedDate'];
	$CreatedTime = $data['CreatedTime'];
	$sql = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($EmployeeID,'$Role','$CreatedDate','$CreatedTime')";
	$response = _InsertTableRecords($conn, $sql);
	return $response;
}

function getAllemployees($conn)
{
	$response = array();
	$sql = "Select * from  employees where 1 ORDER BY EmployeeNumber DESC";
	$result= mysqli_query($conn,$sql);
	if($result->num_rows>0)
	{
		while($row = $result->fetch_assoc())
		{
			extract($row);
			array_push($response,$row);
		}
	}
	return json_encode($response);
}

function getEmployeeData($conn, $ID){

    $where = " Where ID = $ID";
    $Emp_Details = _getTableDetails($conn, "employees", $where);
    return $Emp_Details;
}

function getMonthlySalaryData($conn, $ID) {
    $where = " Where ID = $ID";
    $Salary_Details = _getTableDetails($conn, "employee_monthly_salary", $where);
    return $Salary_Details;
}

function getEmployeeRole($conn,$ID)
{
	$where = " where EmployeeID = $ID";
	$e_roles = _getTableRecords($conn,'user_roles',$where);
	return $e_roles;
}

function getEmployeeDivision($conn,$ID)
{
	$where = " where EmployeeID = $ID";
	$e_divisions = _getTableRecords($conn,'user_divisions',$where);
	return $e_divisions;
}

function getEmployeeRolesArray($conn)
{
	$where = " where IsActive = 1";
	$roles_array = _getTableRecords($conn,'employee_roles',$where);
	return $roles_array;
}
function getEmployeeDivisionArray($conn)
{
	$where = " where IsActive = 1";
	$divisions_array = _getTableRecords($conn,'employee_divisions',$where);
	return $divisions_array;
}

function getAssignedList($conn)
{
	$response = array();
	$sql = "SELECT DISTINCT(a.Name),a.ID from employees a,user_roles b WHERE a.ID = b.EmployeeID and (b.Role = 'Vendor' or b.Role = 'Technician') And a.IsActive = 1";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				array_push($response, $row);
			}
		}
	} else {
		echo $sql;
	}
	return $response;
}

function getAssignedListforCities($conn,$CitiesArray)
{
	$response = array();
	
	$CitiesList = implode("','", $CitiesArray);
	$CitiesList = "'" . $CitiesList . "'";
	$sql = "SELECT DISTINCT(a.Name),a.ID from employees a,user_roles b WHERE a.ID = b.EmployeeID and (b.Role = 'Vendor' or b.Role = 'Technician') and a.City IN ($CitiesList)";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				array_push($response, $row);
			}
		}
	} else {
		echo $sql;
	}
	return $response;
}

function getAssignedListforCities_EmployeeTypeFilter($conn,$CitiesArray,$EmployeeType)
{
	$response = array();
	
	$CitiesList = implode("','", $CitiesArray);
	$CitiesList = "'" . $CitiesList . "'";
	$sql = "";
	if($EmployeeType == "Vendor")
	{
		$sql = "SELECT DISTINCT(a.Name),a.ID from employees a,user_roles b WHERE a.ID = b.EmployeeID and (b.Role = 'Vendor') and a.City IN ($CitiesList)";
	}
	else
	{
		$sql = "SELECT DISTINCT(a.Name),a.ID from employees a,user_roles b WHERE a.ID = b.EmployeeID and (b.Role = 'Technician') and a.City IN ($CitiesList)";
	}
	
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				array_push($response, $row);
			}
		}
	} else {
		echo $sql;
	}
	return $response;
}

function getEmployeeArray($conn)
{
	$where = " where IsActive = 1";
	$employee_array = _getTableRecords($conn,'employees',$where);
	return $employee_array;
}

function getFilteredEmployeeArray($conn,$data)
{
	$where = " where IsActive = 1";
	if($data == "Employee")
		$where = " where Vendor = 0 and IsActive = 1";
	if($data == "Vendor")
		$where = " where Vendor = 1 and IsActive = 1";
	$employee_array = _getTableRecords($conn,'employees',$where);
	return $employee_array;
}

function getAccessDetails($conn,$ID)
{
	$where = " Where EmployeeID = $ID";
	$AccessDetails = _getTableDetails($conn, "users", $where);
    return $AccessDetails;
}

function ManageAccess($conn,$data)
{
	$username = $data['username'];
	$EmployeeID = $data['EmployeeID'];
	$CreatedDate = $data['CreatedDate'];
	$CreatedTime = $data['CreatedTime'];

	$where = " Where ID = $EmployeeID";
	$employeeData = _getTableDetails($conn, "employees", $where);
	$Email = $employeeData['Email'];
	$Phone = $employeeData['ContactNumber'];
	$Name = $employeeData['Name'];
	$raw_password = "";
	if(isset($data['password']))
	{
		$raw_password=$data['password'];
	}
	// check for duplicate username
	$where = " where UserName = '$username'";
	$not_duplicate = check_unique_identity_filter($conn,'users',$where);
	if($not_duplicate)
	{
		if($data['access'] == "Not Set")
		{
			$password = md5($data['password']);
			$sql = "INSERT INTO users(UserName,Password,UserType,EmployeeID,CreatedDate,CreatedTime) VALUES ('$username','$password','Employee',$EmployeeID,'$CreatedDate','$CreatedTime')";
			$response = _InsertTableRecords($conn, $sql);
			$message = "Dear $Name,\n\nThis is to inform you that we have just created a- $Email new Employee with following details - \n\nYour Credential:\n\nUsername - $username\n\nPassword - $raw_password\n\nWarm regards,\nTechXpert Team\n\n\nApplication Link - https://play.google.com/store/apps/details?id=io.ionic.techXpert\n\nWebsite Link - https://techxpertindia.in/admin/authentication/login\n\n";
	   		 $phonenumber = "+91".$Phone;
	   		 $post_mail_data['action'] = "Employee Access";
	   		 $post_mail_data['username'] = $username;
	   		 $post_mail_data['Email'] = $Email;
	   		 $post_mail_data['Name'] = $Name;
	   		 $post_mail_data['password'] = $raw_password;
	   		 sendMailRequest($post_mail_data);
			 sendWhatsAppMessage($phonenumber,$message);
		}
		else
		{
			$query_parameter = " UserName = '$username' where EmployeeID = $EmployeeID";
			$response = _UpdateTableRecords($conn,'users', $query_parameter);
			/*$message = "Dear $Name,\n\nThis is to inform you that we have just created a- $Email new Employee with following details - \n\nYour Credential:\n\nUsername - $username\n\nWarm regards,\nTechXpert Team\n\n\nApplication Link - https://play.google.com/store/apps/details?id=io.ionic.techXpert\n\nWebsite Link - https://techxpertindia.in/admin/authentication/login\n\n";
	   		 $phonenumber = "+91".$Phone;
	   		 $post_mail_data['action'] = "Employee Access";
	   		 $post_mail_data['username'] = $username;
	   		 $post_mail_data['Email'] = $Email;
	   		 $post_mail_data['Name'] = $Name;
	   		 $post_mail_data['password'] = $raw_password;
	   		 sendMailRequest($post_mail_data);
			 sendWhatsAppMessage($phonenumber,$message);*/
		}
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Username is already associated with other user. Please use different username";
	}
	return $response;
}

function ResetPassword($conn,$data)
{
	$new_password = $data['reset_passsword'];
	$username = $data['reset_password_username'];
	$password_md5 = md5($new_password);
	$query_parameter = " Password = '$password_md5' where UserName = '$username'";
	$response = _UpdateTableRecords($conn,'users', $query_parameter);
	if($response['error'] == false) {


		$response['message'] = "Password changed!";
	}

	return $response;
}

function ManageRole_Supervisor($conn,$data,$setting_roles)
{
	$response = array();
	$roles = array();
	$divisions = array();
	$response['error'] = false;
	if(isset($data['roles']))
		$roles = $data['roles'];
	if(isset($data['divisions']))
		$divisions = $data['divisions'];
	$EmployeeID = $data['EmployeeID'];
	$CreatedDate = $data['CreatedDate'];
	$CreatedTime = $data['CreatedTime'];
	$updation_message = "No Updation Done";

	// get old roles array
	$old_roles = getEmployeeRole($conn,$EmployeeID);
	$old_roles_array = array();
	if(sizeof($old_roles) > 0)
	{
		foreach($old_roles as $old_role)
		{
			if(!(in_array($old_role['Role'],$setting_roles)))
			{
				array_push($old_roles_array,$old_role['Role']);
			}
		}
	}

	// find roles to be deleted
	// values which are in old roles array but not in new roles array
	$roles_to_be_deleted = array_diff($old_roles_array,$roles);
	foreach($roles_to_be_deleted as $role)
	{
		$query = " where EmployeeID = $EmployeeID and Role = '$role'";
		delete_identity_filter($conn,"user_roles",$query);
		$updation_message = "Role Updated";
	}

	// find roles to be added
	// values which are in new roles array but not in old roles array
	$roles_to_be_added = array_diff($roles,$old_roles_array);

	foreach($roles_to_be_added as $role)
	{
		$sql = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($EmployeeID,'$role','$CreatedDate','$CreatedTime')";
		$response = _InsertTableRecords($conn, $sql);
		$updation_message = "Role Updated";
	}

	// get old divisions array
	$old_divisions = getEmployeeDivision($conn,$EmployeeID);
	$old_divisions_array = array();
	if(sizeof($old_divisions) > 0)
	{
		foreach($old_divisions as $old_division)
		{
			array_push($old_divisions_array,$old_division['Division']);
		}
	}

	// find divisions to be deleted
	// values which are in old divisions array but not in new divisions array
	$divisions_to_be_deleted = array_diff($old_divisions_array,$divisions);
	foreach($divisions_to_be_deleted as $division)
	{
		$query = " where EmployeeID = $EmployeeID and Division = '$division'";
		$response = delete_identity_filter($conn,"user_divisions",$query);
		if($updation_message == "No Updation Done")
		{
			$updation_message = "Division Updated";
		}
		else
		{
			$updation_message = $updation_message."\n"."Division Updated";
		}
	}

	// find divisions to be added
	// values which are in new divisions array but not in old divisions array
	$divisions_to_be_added = array_diff($divisions,$old_divisions_array);

	foreach($divisions_to_be_added as $division)
	{
		$sql = "INSERT INTO user_divisions(EmployeeID,Division,CreatedDate,CreatedTime) VALUES ($EmployeeID,'$division','$CreatedDate','$CreatedTime')";
		$response = _InsertTableRecords($conn, $sql);
		if($updation_message == "No Updation Done")
		{
			$updation_message = "Division Updated";
		}
		else
		{
			$updation_message = $updation_message."\n"."Division Updated";
		}
	}

	// Changing Supervisor
	if($data['supervisor'] != $data['EmployeeCurrentSupervisor'])
	{
		$Supervisor = $data['supervisor'];
		$query_parameter = " Supervisor = $Supervisor where ID = $EmployeeID";
		_UpdateTableRecords($conn,'employees', $query_parameter);
		if($updation_message == "No Updation Done")
		{
			$updation_message = "Supervisor Updated";
		}
		else
		{
			$updation_message = $updation_message."\n"."Supervisor Updated";
		}

	}

	$response['message'] = $updation_message;
	return $response;
}

function _api_login_user_old($conn,$data)
{
	$response = array();
	$UserName = $data['username'];
	$Password = md5($data['password']);
	$where = " where UserName = '$UserName' And IsActive ='1'";
	$num_rows = _getTotalRows($conn,'users',$where);
	if(_getTotalRows($conn,'users',$where) > 0)
	{
		$AccessDetails = _getTableDetails($conn, "users", $where);
		  $EmployeeID = $AccessDetails['EmployeeID'];
		  if($EmployeeID !=-1)
			{
            $whereemp = "WHERE ID = '$EmployeeID'";
            $EmployeeDetails = _getTableDetails($conn, "employees", $whereemp);
				if ($EmployeeDetails['IsActive'] == 0)
				{
					$response['error'] = true;
					$response['message'] = "Unauthorized Access, Please try again!";
					return;
				}
			}
		$response['error'] = false;
		$response['message'] = "Login Successful!";
		$response['data'] = $AccessDetails;
		if($AccessDetails['UserType'] == "Employee")
		{
			$EmployeeID = $AccessDetails['EmployeeID'];
			$UserType = $AccessDetails['UserType'];
			$roles = getUserRole($conn,$EmployeeID,$UserType);
			$response['data']['role'] = $roles;
		}
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Unauthorized Access, Please try again!";
	}
    return $response;
}


function _api_login_user($conn,$data)
{
    $response = array();

    $UserName = $data['username'];
    $Password = $data['password'];

    // 🔐 Get user securely
    $stmt = $conn->prepare("SELECT * FROM users WHERE UserName=? AND IsActive=1");
    $stmt->bind_param("s", $UserName);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0)
    {
        $user = $result->fetch_assoc();
        $storedPassword = $user['Password'];

        $loginSuccess = false;

        // 🔹 Case 1: OLD MD5 PASSWORD
        if(strlen($storedPassword) == 32 && ctype_xdigit($storedPassword))
        {
            if(md5($Password) === $storedPassword)
            {
                $loginSuccess = true;

               
                // $newHash = password_hash($Password, PASSWORD_BCRYPT);
                // $update = $conn->prepare("UPDATE users SET Password=? WHERE UserID=?");
                // $update->bind_param("si", $newHash, $user['UserID']);
                // $update->execute();

               
                // $user['Password'] = $newHash;
            }
        }
        // 🔹 Case 2: NEW PASSWORD (bcrypt)
        else
        {
            if(password_verify($Password, $storedPassword))
            {
                $loginSuccess = true;
            }
        }

        if($loginSuccess)
        {
            // 🔁 FORCE LOGOUT OLD SESSIONS
            $clear = $conn->prepare("UPDATE users SET AuthToken=NULL, TokenExpiry=NULL WHERE UserID=?");
            $clear->bind_param("i", $user['UserID']);
            $clear->execute();

            // 🔐 GENERATE NEW TOKEN
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 day"));

            $updateToken = $conn->prepare("UPDATE users SET AuthToken=?, TokenExpiry=? WHERE UserID=?");
            $updateToken->bind_param("ssi", $token, $expiry, $user['UserID']);
            $updateToken->execute();

            // 🔁 UPDATE USER ARRAY (IMPORTANT for response)
            $user['AuthToken'] = $token;
            $user['TokenExpiry'] = $expiry;

            // 🎯 ADD ROLE (your existing logic)
            if($user['UserType'] == "Employee")
            {
                $EmployeeID = $user['EmployeeID'];
                $UserType = $user['UserType'];
                $roles = getUserRole($conn,$EmployeeID,$UserType);
                $user['role'] = $roles;
            }

            // ✅ FINAL RESPONSE (FULL DATA - APP COMPATIBLE)
            $response['error'] = false;
            $response['message'] = "Login Successful!";
            $response['token'] = $token;   // future-ready
            $response['data'] = $user;     // current app uses this
        }
        else
        {
            $response['error'] = true;
            $response['message'] = "Invalid Password";
        }
    }
    else
    {
        $response['error'] = true;
        $response['message'] = "User not found";
    }

    return $response;
}

function DeleteEmployee($conn,$data)
{
	$response = array();
	$EmployeeID = $data['EmployeeID'];
	// update isactive = 0 employees table
	$query_parameter = " IsActive = 0 where ID = $EmployeeID";
	_UpdateTableRecords($conn,'employees', $query_parameter);

	// delete user_roles for Emloyee ID
	$query_parameter = " where EmployeeID = $EmployeeID";
	delete_identity_filter($conn,"user_roles",$query_parameter);

	// delete user_divisions for Emloyee ID
	$query_parameter = " where EmployeeID = $EmployeeID";
	delete_identity_filter($conn,"user_divisions",$query_parameter);

	$response['error'] = false;
	$response['message'] = "Employee Deleted from system";
	return $response;
}

function getDivisionArray($conn)
{
	$where = " where IsActive = 1";
	$employee_division_array = _getTableRecords($conn,'employee_divisions',$where);
	return $employee_division_array;
}

function GenerateEmployeeNumber($conn)
{
	$response = array();
	//$Division = $data;
	// Get initials
	//$where = " where Division = '$Division' and IsActive = 1";
	//$division_row = _getTableDetails($conn,'employee_divisions', $where);
	//$Initials = $division_row['Initials'];
	$Initials = "TECHX";

	// Get Max sequence number for that division
	//$where_query = " where Division = '$Division'";
	$where_query = " where 1";
	$max_seq = _getMaxIdentityValue_filter($conn,'employees','DivisionSequence', $where_query);
	$seq = $max_seq+1;
	$formatted_seq = sprintf('%04d', $seq);
	//$currentDate = date("Ymd");
	$EmployeeNumber = $Initials.$formatted_seq;
	$response['EmployeeNumber'] = $EmployeeNumber;
	$response['DivisionSequence'] = $seq;
	return $response;
}

function InsertMonthlySalaryEmployee($conn,$salaryData)
{ 

	$employeeID = $salaryData['ID'];
	$basic = $salaryData['Basic'];
	$da = $salaryData['DA'];
    $hra = $salaryData['HRA'];
    $bonus = $salaryData['Bonus'];
	$year = $_POST['year'];
	$month = $_POST['month'];
	$health_insurance = $salaryData['HealthInsurance'];
    $others = $salaryData['Others'];
    $employee_epf_number = $salaryData['Epf_number'];
    $employee_esic_number = $salaryData['Esic_number'];
	// $CreatedDate = $_POST['CreatedDate'];
	// $CreatedTime = $_POST['CreatedTime'];

	$sql = "INSERT INTO employee_monthly_salary(EmployeeID,Year,Month,Basic,DA,HRA,Bonus,HealthInsurance,Epf_number,Esic_number,Others) VALUES ('$employeeID','$year','$month','$basic','$da','$hra','$bonus','$health_insurance','$employee_epf_number', '$employee_esic_number','$others')";
}




function getEmployeeMonthlySalary($conn,$EmployeeID,$Year,$Month)
{
	
	$where = "WHERE EmployeeID='$EmployeeID' and Month='$Month' and Year='$Year'";
	// $where = "SELECT * FROM employee_monthly_salary where ID = $EmployeeID";
	$response = _getTableRecords($conn, 'employee_monthly_salary', $where);
	return $response;
}

function getEmployeeSupervisorData($conn,$EmployeeSupervisorId)
{
	$where = "WHERE ID='$EmployeeSupervisorId' ";
	$response = _getTableDetails($conn, 'employees', $where);
	return $response;
}

function getEmployeeDataByUserName($conn,$Username)
{
	$where = "WHERE UserName='$Username' ";
	$response = _getTableDetails($conn, 'users', $where);
	return $response;
}

function getTotalEmployee($conn)
{
	$sql = "Select COUNT(*) as employees_count from employees where 1 ORDER BY EmployeeNumber DESC";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		$row = $result->fetch_assoc();
		return $row['employees_count'];
	}
	else
	{
		return 0;
	}
}

// ----------Leave Management-----------------------------

function getEmployeeAllLeave($conn)
{
	$where = " where IsActive = 1 ORDER BY ID DESC";
	$response = _getTableRecords($conn,'employee_leave', $where);
	return $response;
}

function InsertEmployeeLeave($conn,$data)
{
	$type_of_leave = "";
    $leave_reason = $data["leave_reason"];
    $from_date = $data["from_date"];
    $to_date = $data["to_date"];
    $EmployeeID = $data["EmployeeID"];
    $Duration = "Full Day";
    if(isset($data['half_day']))
    {
    	$Duration = "Half Day";
    }

    $CreatedBy = $data['CreatedBy'];
	$CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $leave_query = "INSERT INTO employee_leave (EmployeeID,TypeOfLeave,ReasonOfLeave,FromDate,ToDate,Duration,CreatedBy,CreatedDate,CreatedTime ) VALUES('$EmployeeID','$type_of_leave','$leave_reason','$from_date','$to_date','$Duration','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $leave_query);

	$where = " where ID = $EmployeeID";

	$EmployeeDetailByID = _getTableDetails($conn,'employees', $where);
	$SupervisorId = $EmployeeDetailByID['Supervisor'];

	if($SupervisorId != -1 && $SupervisorId != "")
	{
		$EmployeeName = $EmployeeDetailByID['Name'];
		$EmployeeNumber = $EmployeeDetailByID['ContactNumber'];
		$where = " where ID = $SupervisorId";
		$SupervisorDetail = _getTableDetails($conn,'employees', $where);
		if($SupervisorDetail != null)
		{
			$EmployeeSupervisorEmail = $SupervisorDetail['Email'];
			$EmployeeSupervisorNumber = $SupervisorDetail['ContactNumber'];
			$message = "This is to inform you that $EmployeeName has applied the leave from $from_date to $to_date, Duration - $Duration for the Reason Mention : $leave_reason.\n\n Please approve it or reject it by going to web portal. \n\nRegard,\nTeam";
    	

    		//$message = "Dear Sir,\n\nI am writing to you to let you know that I have an important personal matter to attend at my hometown due to which I will not be able to come to the office from $from_date to $to_date.\n\nI shall be reachable on my mobile number $EmployeeNumber during the period.\n\nI will be thankful to you for considering my application.\n\n Yours Sincerely,\n $EmployeeName,\n\nWarm regards,\nTechXpert Team\n\n\Approval Link - https://app.techxpertgroup.in/admin/booking/view-booking-details \n\nWebsite Link - https://techxpertindia.in/admin/authentication/login\n\n";

	 		$phonenumber = "+91".$EmployeeSupervisorNumber;
	 		$post_mail_data['action'] = "Employee Leave";
			 $post_mail_data['Name'] = $EmployeeName;
			 $post_mail_data['Email'] = $EmployeeSupervisorEmail;
			 $post_mail_data['Number'] = $EmployeeNumber;
			 $post_mail_data['FromDate'] = $from_date;
			 $post_mail_data['ToDate'] = $to_date;
			 sendWhatsAppMessage($phonenumber,$message);
		}
	}

    $response['message'] = "Leave Added to the System";
    return $response;
}

function UpdateEmployeeLeave($conn,$data)
{	
    $type_of_leave = $data["type_of_leave"];
	$reason_of_leave = $data["reason_of_leave"];
    $from_date = $data["from_date"];
    $to_date = $data["to_date"];
    $leave_form_id = $data['form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $old_spare_part_details = GetEmployeeLeaveDetailsbyID($conn,$leave_form_id);
    if($type_of_leave == $old_spare_part_details['TypeOfLeave'] && $reason_of_leave == $old_spare_part_details['ReasonOfLeave'] && $from_date == $old_spare_part_details['FromDate'] && $to_date == $old_spare_part_details['ToDate'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	$update_param = " TypeOfLeave = '$type_of_leave',ReasonOfLeave='$reason_of_leave',FromDate='$from_date',ToDate='$to_date' where ID=$leave_form_id";
    	$response = _UpdateTableRecords($conn,'employee_leave', $update_param);
        $response['message'] = "Leave Updated to the System";
    }
    return $response;
}

function GetEmployeeLeaveDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$response = _getTableDetails($conn,'employee_leave', $where);
	return $response;
}

function DeleteEmployeeLeave($conn,$data)
{
	$ID = $data['ID'];
	$query_parameter = " where ID = '$ID'";
	$response = delete_identity_filter($conn,"employee_leave",$query_parameter);
	return $response;
}

function getEmployeeLeaveDataByID($conn,$ID)
{
	$Id = $ID['ID'];
	$where = " where ID = $Id";
	$response = _getTableDetails($conn,'employee_leave', $where);
	return $response;
}

function getLeaveDataByType($conn,$LeaveType)
{
	$where = " where TypeOfLeave = '$LeaveType'";
	$response = _getTableDetails($conn,'leaveconfiguation', $where);
	return $response;
}

function GetAllEmployeeInArray($conn)
{
	$where = " where ID = ID";
	$response = _getTableRecords($conn,'employees', $where);
	return $response;
}

function getEmployeeByPhone($conn, $phonenumber, $username)
{
    $response = array();

    // Security
    $phonenumber = mysqli_real_escape_string($conn, $phonenumber);
    $username = mysqli_real_escape_string($conn, $username);

    $sql = "SELECT 
                u.UserID,
                u.UserName,
                u.EmployeeID,
                u.UserType,
                u.IsActive AS UserActive,
                e.ID,
                e.Name,
                e.ContactNumber,
                e.Email,
                e.IsActive AS EmployeeActive
            FROM users u
            INNER JOIN employees e ON u.EmployeeID = e.ID
            WHERE e.ContactNumber = '$phonenumber' AND u.UserName='$username'
            LIMIT 1";

    $result = _getSQLDetails($conn, $sql);

    if (!empty($result)) {
        $response = $result;
    }

    return $response;
}


/**
 * Create OTP row for employee user matched by phone; returns mail_* keys for API to send email (not exposed if mail fails).
 */
function requestPasswordResetOtp($conn, $phonenumber , $username)
{
    $response = array('error' => true, 'message' => '');

    $employeeData = getEmployeeByPhone($conn, $phonenumber, $username);
    if (empty($employeeData)) {
        $response['message'] = 'No record found';
        return $response;
    }
    if ($employeeData['UserActive'] != 1 || $employeeData['EmployeeActive'] != 1) {
        $response['message'] = 'User or Employee is inactive';
        return $response;
    }

    $email = isset($employeeData['Email']) ? trim($employeeData['Email']) : '';
    if ($email === '') {
        $response['message'] = 'No email registered for this account';
        return $response;
    }

    $userId = (int) $employeeData['EmployeeID'];
    $otp = (string) random_int(1000, 9999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $otpHashEsc = mysqli_real_escape_string($conn, $otpHash);
    $emailEsc = mysqli_real_escape_string($conn, $email);
    $expiresAt = date('Y-m-d H:i:s', time() + 600);
    $createdAt = date('Y-m-d H:i:s');

    mysqli_query($conn, "DELETE FROM password_reset_otp WHERE UserID = $userId AND verified = 0");

    $sql = "INSERT INTO password_reset_otp (UserID, otp_hash, email, expires_at, verified, attempts, created_at) VALUES ($userId, '$otpHashEsc', '$emailEsc', '$expiresAt', 0, 0, '$createdAt')";
    $ins = _InsertTableRecords($conn, $sql);
    if (!empty($ins['error'])) {
        $response['message'] = isset($ins['message']) ? $ins['message'] : 'Could not create reset request';
        return $response;
    }

    $response['error'] = false;
    $response['message'] = 'OTP sent to registered email';
    $response['otp_record_id'] = isset($ins['last_insert_id']) ? (int) $ins['last_insert_id'] : 0;
    $response['mail_to'] = $email;
    $response['mail_name'] = isset($employeeData['Name']) ? $employeeData['Name'] : '';
    $response['mail_otp'] = $otp;
    return $response;
}

/**
 * Verify OTP and set users.Password (md5) for the user linked to the phone.
 */
function verifyOtpAndResetPassword($conn, $data)
{
    $response = array('error' => true, 'message' => '');

    $phonenumber = isset($data['phonenumber']) ? $data['phonenumber'] : '';
	$username = isset($data['username']) ? $data['username'] : '';
    if ($phonenumber === '') {
        $response['message'] = 'Missing phone number';
        return $response;
    }

	if ($username === '') {
		$response['message'] = 'Missing username';
		return $response;
	}

    $otp = isset($data['otp']) ? trim((string) $data['otp']) : '';
    $newPassword = '';
    if (isset($data['new_password']) && $data['new_password'] !== '') {
        $newPassword = $data['new_password'];
    } elseif (isset($data['reset_passsword']) && $data['reset_passsword'] !== '') {
        $newPassword = $data['reset_passsword'];
    }

    if ($otp === '' || $newPassword === '') {
        $response['message'] = 'Missing OTP or new password';
        return $response;
    }

    if (strlen($newPassword) < 6) {
        $response['message'] = 'Password must be at least 6 characters';
        return $response;
    }

    $employeeData = getEmployeeByPhone($conn, $phonenumber, $username);
    if (empty($employeeData)) {
        $response['message'] = 'No record found';
        return $response;
    }

    $userId = (int) $employeeData['EmployeeID'];

    $sql = "SELECT id, otp_hash, expires_at, attempts FROM password_reset_otp WHERE UserID = $userId AND verified = 0 ORDER BY id DESC LIMIT 1";
    $row = _getSQLDetails($conn, $sql);
    if (empty($row) || !isset($row['id'])) {
        $response['message'] = 'No pending OTP. Request a new code.';
        return $response;
    }

    if (strtotime($row['expires_at']) < time()) {
        $response['message'] = 'OTP expired. Request a new code.';
        return $response;
    }

    $attempts = (int) $row['attempts'];
    if ($attempts >= 5) {
        $response['message'] = 'Too many failed attempts. Request a new OTP.';
        return $response;
    }

    $otpId = (int) $row['id'];
    if (!password_verify($otp, $row['otp_hash'])) {
        $attempts++;
        mysqli_query($conn, "UPDATE password_reset_otp SET attempts = $attempts WHERE id = $otpId");
        $response['message'] = 'Invalid OTP';
        return $response;
    }

    $passwordMd5 = md5($newPassword);
    $passwordMd5Esc = mysqli_real_escape_string($conn, $passwordMd5);
    $upd = _UpdateTableRecords($conn, 'users', " Password = '$passwordMd5Esc' where EmployeeID = $userId");
    if (!empty($upd['error'])) {
        $response['message'] = isset($upd['message']) ? $upd['message'] : 'Could not update password';
        return $response;
    }

    mysqli_query($conn, "UPDATE password_reset_otp SET verified = 1 WHERE id = $otpId");

    $response['error'] = false;
    $response['message'] = 'Password changed successfully';
    return $response;
}

?>