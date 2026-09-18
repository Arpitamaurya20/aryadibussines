<?php
function InsertEmployee($conn)
{
	$response = array();
	$employee_name = $_POST['employee_name'];
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
	$employee_city = $_POST['employee_city'];


    if($work_type == "Vendor")
    {
    	$vendor = 1;
    }

	$sql = "INSERT INTO employees(DivisionSequence,Name,Designation,EmployeeNumber,Department,Basic,DA,HRA,Bonus,HealthInsurance,Others,Email,ContactNumber,BankAccountName,BankAccountNumber,PAN,PANImage,Aadhar,AadharImage,ProfileImage,PoliceVerificationImage,Vendor,CreatedBy,CreatedDate,CreatedTime,Gender,UANNumber,DateofJoining,Epf_number,Esic_number,City) VALUES ($DivisionSequence,'$employee_name','$designation','$employee_id','$employee_department','$basic','$da','$hra','$bonus','$health_insurance','$others','$employee_email','$employee_contact','$bank_account_name','$bank_account_number','$employee_pan_number', '$employee_pan_image_path','$employee_aadhar', '$employee_aadhar_image_path', '$employee_profile_image_path', '$employee_police_verify_image_path', '$vendor','$username','$CreatedDate','$CreatedTime','$gender_type', '$uan_number', '$date_of_joining', '$employee_epf', '$employee_esic', '$employee_city')";
	$response = _InsertTableRecords($conn, $sql);
	return $response;

}

function UpdateEmployee($conn,$data)
{
	$employee_gender = $data['employee_gender'];
	$employee_id = $data['employee_id'];
	$date_of_joining = $data['date_of_joining'];
    $employee_name = $data['employee_name'];
	$designation = $data['designation'];
    $employee_email = $data['employee_email'];
    if(isset($data['employee_department'])) 
	$employee_department = $data['employee_department'];
    $employee_contact = $data['employee_contact'];
	$bank_account_name = $data['bank_account_name'];
    $bank_account_number = $data['bank_account_number'];
    $employee_uan_number = $data['employee_uan_number'];
    $employee_pan_number = $data['employee_pan_number'];
    $employee_aadhar_number = $data['employee_aadhar_number'];
    $employee_city = $data['employee_city'];

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

    $update_employee = " Name='$employee_name', designation='$designation',EmployeeNumber='$employee_id',Department='$employee_department',Email='$employee_email',ContactNumber='$employee_contact',BankAccountName='$bank_account_name',BankAccountNumber='$bank_account_number',PAN='$employee_pan_number',Aadhar='$employee_aadhar_number',Gender = '$employee_gender',UANNumber = '$employee_uan_number',DateofJoining = '$date_of_joining',City='$employee_city' WHERE ID = '$ID'";

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
	$sql = "SELECT DISTINCT(a.Name),a.ID from employees a,user_roles b WHERE a.ID = b.EmployeeID and (b.Role = 'Vendor' or b.Role = 'Technician')";
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
		}
		else
		{
			$query_parameter = " UserName = '$username' where EmployeeID = $EmployeeID";
			$response = _UpdateTableRecords($conn,'users', $query_parameter);
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
	if($response['error'] == false)
		$response['message'] = "Password changed!";
	return $response;
}

function ManageRole_Supervisor($conn,$data)
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
			array_push($old_roles_array,$old_role['Role']);
		}
	}

	// find roles to be deleted
	// values which are in old roles array but not in new roles array
	$roles_to_be_deleted = array_diff($old_roles_array,$roles);
	foreach($roles_to_be_deleted as $role)
	{
		$query = " where EmployeeID = $EmployeeID and Role = '$role'";
		$response = delete_identity_filter($conn,"user_roles",$query);
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

function _api_login_user($conn,$data)
{
	$response = array();
	$UserName = $data['username'];
	$Password = md5($data['password']);
	$where = " where UserName = '$UserName'";
	$num_rows = _getTotalRows($conn,'users',$where);
	if(_getTotalRows($conn,'users',$where) > 0)
	{
		$AccessDetails = _getTableDetails($conn, "users", $where);
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
?>