<?php
function InsertEmployee($conn)
{
	$response = array();
	$employee_name = $_POST['employee_name'];
	$father_name = $_POST['father_name'];
	$designation = $_POST['designation'];
	$employee_department = "Vendor";
	if (isset($_POST['employee_department'])) {
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
	if (isset($_FILES['employee_pan_img']['name'])  && $_FILES['employee_pan_img']['name'] != '') {
		$extn_pan = explode('.', $_FILES["employee_pan_img"]["name"]);
		$employee_pan_image_path   = $EmpNo . "_PAN." . $extn_pan[1];
		$path = "../media/" . $employee_pan_image_path;
		move_uploaded_file($_FILES["employee_pan_img"]["tmp_name"], $path);
	}

	if (isset($_FILES['employee_addhar_img']['name'])  && $_FILES['employee_addhar_img']['name'] != '') {
		//echo $_FILES['employee_addhar_img']['name'];
		$extn_pan = explode('.', $_FILES["employee_addhar_img"]["name"]);
		$employee_aadhar_image_path   = $EmpNo . "_ADH." . $extn_pan[1];
		$path = "../media/" . $employee_aadhar_image_path;
		// echo "<br>".$path;
		move_uploaded_file($_FILES["employee_addhar_img"]["tmp_name"], $path);
	}

	if (isset($_FILES['employee_police_verification']['name'])  && $_FILES['employee_police_verification']['name'] != '') {
		//echo $_FILES['employee_police_verification']['name'];
		$extn_pan = explode('.', $_FILES["employee_police_verification"]["name"]);
		$employee_police_verify_image_path   = $EmpNo . "_PV." . $extn_pan[1];
		$path = "../media/" . $employee_police_verify_image_path;
		//echo "<br>".$path;
		move_uploaded_file($_FILES["employee_police_verification"]["tmp_name"], $path);
	}

	if (isset($_FILES['employee_profile_photo']['name'])  && $_FILES['employee_profile_photo']['name'] != '') {
		//echo $_FILES['employee_profile_photo']['name'];
		$extn_pan = explode('.', $_FILES["employee_profile_photo"]["name"]);
		$employee_profile_image_path   = $EmpNo . "_PP." . $extn_pan[1];
		$path = "../media/" . $employee_profile_image_path;
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
	if (isset($_POST['employee_city']))
		$employee_city = $_POST['employee_city'];
	else
		$employee_city = "";

	if (isset($_POST['employee_state']))
		$employee_state = $_POST['employee_state'];
	else
		$employee_state = "";

	if ($work_type == "Vendor") {
		$vendor = 1;
	}

	$sql = "INSERT INTO employees(DivisionSequence,Name,FatherName,Designation,EmployeeNumber,Department,Basic,DA,HRA,Bonus,HealthInsurance,Others,Email,ContactNumber,BankAccountName,BankAccountNumber,PAN,PANImage,Aadhar,AadharImage,ProfileImage,PoliceVerificationImage,Vendor,CreatedBy,CreatedDate,CreatedTime,Gender,UANNumber,DateofJoining,Epf_number,Esic_number,WeeklyOff,City,State) VALUES ($DivisionSequence,'$employee_name','$father_name', '$designation','$employee_id','$employee_department','$basic','$da','$hra','$bonus','$health_insurance','$others','$employee_email','$employee_contact','$bank_account_name','$bank_account_number','$employee_pan_number', '$employee_pan_image_path','$employee_aadhar', '$employee_aadhar_image_path', '$employee_profile_image_path', '$employee_police_verify_image_path', '$vendor','$username','$CreatedDate','$CreatedTime','$gender_type', '$uan_number', '$date_of_joining', '$employee_epf', '$employee_esic', '$weekly_off','$employee_city','$employee_state')";
	$response = _InsertTableRecords($conn, $sql);
	return $response;
}

function UpdateEmployee($conn, $data)
{
	$employee_gender = $data['employee_gender'];
	$employee_id = $data['employee_id'];
	$date_of_joining = $data['date_of_joining'];
	$employee_name = $data['employee_name'];
	$father_name = $data['father_name'];
	$designation = $data['designation'];
	$employee_email = $data['employee_email'];
	if (isset($data['employee_department']))
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

	if (isset($_FILES['employee_pan_img']['name'])  && $_FILES['employee_pan_img']['name'] != '') {
		$extn_pan = explode('.', $_FILES["employee_pan_img"]["name"]);
		$employee_pan_image_path   = $employee_id . "_PAN." . $extn_pan[1];
		$path = "../media/" . $employee_pan_image_path;
		move_uploaded_file($_FILES["employee_pan_img"]["tmp_name"], $path);

		$update_img = "UPDATE employees SET PANImage='$employee_pan_image_path' WHERE ID=$ID";
		$result = mysqli_query($conn, $update_img);
	}

	if (isset($_FILES['employee_addhar_img']['name'])  && $_FILES['employee_addhar_img']['name'] != '') {
		$extn_pan = explode('.', $_FILES["employee_addhar_img"]["name"]);
		$employee_aadhar_image_path   = $employee_id . "_ADH." . $extn_pan[1];
		$path = "../media/" . $employee_aadhar_image_path;
		move_uploaded_file($_FILES["employee_addhar_img"]["tmp_name"], $path);

		$update_img = "UPDATE employees SET AadharImage='$employee_aadhar_image_path' WHERE ID = $ID";
		$result 	= mysqli_query($conn, $update_img);
	}

	if (isset($_FILES['employee_police_verification']['name'])  && $_FILES['employee_police_verification']['name'] != '') {
		$extn_pan = explode('.', $_FILES["employee_police_verification"]["name"]);
		$employee_police_verify_image_path   = $employee_id . "_PV." . $extn_pan[1];
		$path = "../media/" . $employee_police_verify_image_path;
		move_uploaded_file($_FILES["employee_police_verification"]["tmp_name"], $path);

		$update_img = "UPDATE employees SET PoliceVerificationImage='$employee_police_verify_image_path' WHERE ID = $ID ";
		$result 	= mysqli_query($conn, $update_img);
	}

	if (isset($_FILES['employee_profile_photo']['name'])  && $_FILES['employee_profile_photo']['name'] != '') {
		$extn_pan = explode('.', $_FILES["employee_profile_photo"]["name"]);
		$employee_profile_image_path   = $employee_id . "_PP." . $extn_pan[1];
		$path = "../media/" . $employee_profile_image_path;
		move_uploaded_file($_FILES["employee_profile_photo"]["tmp_name"], $path);

		$update_img = "UPDATE employees SET ProfileImage='$employee_profile_image_path' WHERE ID = $ID";
		$result 	= mysqli_query($conn, $update_img);
	}

	$update_employee = " Name='$employee_name', FatherName='$father_name',designation='$designation',EmployeeNumber='$employee_id',Department='$employee_department',Email='$employee_email',ContactNumber='$employee_contact',BankAccountName='$bank_account_name',BankAccountNumber='$bank_account_number',PAN='$employee_pan_number',Aadhar='$employee_aadhar_number',Gender = '$employee_gender',UANNumber = '$employee_uan_number',DateofJoining = '$date_of_joining',WeeklyOff='$weekly_off',City='$employee_city',State='$employee_state' WHERE ID = '$ID'";

	$response = _UpdateTableRecords($conn, 'employees', $update_employee);
	return $response;
}


function UpdateSalaryEmployee($conn, $data)
{
	$basic = $data['basic'];
	$da = $data['da'];
	$hra = $data['hra'];
	$bonus = $data['bonus'];
	$convenience_allowance = $data['convenience_allowance'];
	$health_insurance = $data['health_insurance'];
	$others = $data['others'];
	$gross = (float)$basic + (float)$hra + (float)$convenience_allowance + (float)$others + (float)$bonus;
	$in_hand_salary = $data['in_hand_salary'];
	$employee_epf_number = $data['employee_epf_number'];
	$employee_esic_number = $data['employee_esic_number'];
	$ID = $data['EmployeeID'];

	$update_employee = "Basic='$basic',DA='$da',HRA='$hra',ConvenienceAllowance='$convenience_allowance',Bonus='$bonus',HealthInsurance='$health_insurance',Others='$others',Gross='$gross',InHandSalary='$in_hand_salary',Epf_number = '$employee_epf_number',Esic_number='$employee_esic_number' WHERE ID = '$ID'";
	$response = _UpdateTableRecords($conn, 'employees', $update_employee);
	return $response;
}


function CheckForDuplicateEmployeeDetails($conn, $data)
{
	$response = array();
	$response['error'] = true;
	$employee_email = $data['employee_email'];
	$employee_contact = $data['employee_contact'];
	$employee_pan_number = $data['employee_pan_number'];
	$employee_aadhar = $data['employee_aadhar'];
	$filter = " where Email = '$employee_email' and IsActive = 1";
	if (check_unique_identity_filter($conn, 'employees', $filter) === false) {
		$response['message'] = "Employee / Vendor with same Email is already registered";
		return $response;
	}
	$filter = " where ContactNumber = '$employee_contact' and IsActive = 1";
	if (check_unique_identity_filter($conn, 'employees', $filter) === false) {
		$response['message'] = "Employee / Vendor with same Contact number is already registered";
		return $response;
	}
	if ($employee_pan_number != "") {
		$filter = " where PAN = '$employee_pan_number' and IsActive = 1";
		if (check_unique_identity_filter($conn, 'employees', $filter) === false) {
			$response['message'] = "Employee / Vendor with same PAN is already registered";
			return $response;
		}
	}
	if ($employee_aadhar != "") {
		$filter = " where Aadhar = '$employee_aadhar' and IsActive = 1";
		if (check_unique_identity_filter($conn, 'employees', $filter) === false) {
			$response['message'] = "Employee / Vendor with same Aadhar is already registered";
			return $response;
		}
	}
	$response['error'] = false;
	$response['message'] = "No Duplicate Found";
	return $response;
}

function InsertUserRole($conn, $data)
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
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			extract($row);
			array_push($response, $row);
		}
	}
	return json_encode($response);
}

function getEmployeeData($conn, $ID)
{

	$ID = (int) $ID;
	if ($ID <= 0) {
		return array();
	}
	$where = " Where ID = $ID";
	$Emp_Details = _getTableDetails($conn, "employees", $where);
	return $Emp_Details;
}

function resolveViewEmployeeId($session, $get = array())
{
	if (isset($get['ID']) && is_numeric($get['ID']) && (int) $get['ID'] > 0) {
		return (int) $get['ID'];
	}
	if (isset($session['EmployeeID']) && is_numeric($session['EmployeeID']) && (int) $session['EmployeeID'] > 0) {
		return (int) $session['EmployeeID'];
	}
	if (isset($session['Roles']['EmployeeID']) && is_numeric($session['Roles']['EmployeeID']) && (int) $session['Roles']['EmployeeID'] > 0) {
		return (int) $session['Roles']['EmployeeID'];
	}
	return 0;
}

function canViewEmployeeProfile($conn, $employee_id, $session)
{
	$employee_id = (int) $employee_id;
	if ($employee_id <= 0) {
		return false;
	}

	$userType = isset($session['UserType']) ? (string) $session['UserType'] : '';
	if (in_array($userType, array('Admin', 'Super Admin'), true)) {
		return true;
	}
	if (CheckRole($session, 'HR')) {
		return true;
	}

	$logged_employee_id = 0;
	if (isset($session['Roles']['EmployeeID']) && is_numeric($session['Roles']['EmployeeID'])) {
		$logged_employee_id = (int) $session['Roles']['EmployeeID'];
	} elseif (isset($session['EmployeeID']) && is_numeric($session['EmployeeID'])) {
		$logged_employee_id = (int) $session['EmployeeID'];
	}

	if ($logged_employee_id > 0 && $logged_employee_id === $employee_id) {
		return true;
	}

	if ($logged_employee_id > 0) {
		$where = " WHERE ID = $employee_id AND Supervisor = $logged_employee_id AND IsActive = 1";
		$employee = _getTableDetails($conn, 'employees', $where);
		if (!empty($employee)) {
			return true;
		}
	}

	return false;
}

function getMonthlySalaryData($conn, $ID)
{
	$where = " Where ID = $ID";
	$Salary_Details = _getTableDetails($conn, "employee_monthly_salary", $where);
	return $Salary_Details;
}

function getEmployeeRole($conn, $ID)
{
	$where = " where EmployeeID = $ID";
	$e_roles = _getTableRecords($conn, 'user_roles', $where);
	return $e_roles;
}

function getEmployeeDivision($conn, $ID)
{
	$where = " where EmployeeID = $ID";
	$e_divisions = _getTableRecords($conn, 'user_divisions', $where);
	return $e_divisions;
}

function getEmployeeRolesArray($conn)
{
	$where = " where IsActive = 1";
	$roles_array = _getTableRecords($conn, 'employee_roles', $where);
	return $roles_array;
}
function getEmployeeDivisionArray($conn)
{
	$where = " where IsActive = 1";
	$divisions_array = _getTableRecords($conn, 'employee_divisions', $where);
	return $divisions_array;
}

function getAssignedList($conn)
{
	$response = array();
	$sql = "SELECT DISTINCT(a.Name),a.ID from employees a,user_roles b WHERE a.ID = b.EmployeeID and a.IsActive = 1 and (b.Role = 'Vendor' or b.Role = 'Technician')";
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

function getAssignedListforCities($conn, $CitiesArray)
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

function getAssignedListforCities_EmployeeTypeFilter($conn, $CitiesArray, $EmployeeType)
{
	$response = array();

	$CitiesList = implode("','", $CitiesArray);
	$CitiesList = "'" . $CitiesList . "'";
	$sql = "";
	if ($EmployeeType == "Vendor") {
		$sql = "SELECT DISTINCT(a.Name),a.ID from employees a,user_roles b WHERE a.ID = b.EmployeeID and (b.Role = 'Vendor') and a.City IN ($CitiesList)";
	} else {
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
	$employee_array = _getTableRecords($conn, 'employees', $where);
	return $employee_array;
}

function getFilteredEmployeeArray($conn, $data)
{
	$where = " where IsActive = 1";
	if ($data == "Employee")
		$where = " where Vendor = 0 and IsActive = 1";
	if ($data == "Vendor")
		$where = " where Vendor = 1 and IsActive = 1";
	$employee_array = _getTableRecords($conn, 'employees', $where);
	return $employee_array;
}

function getAccessDetails($conn, $ID)
{
	$where = " Where EmployeeID = $ID";
	$AccessDetails = _getTableDetails($conn, "users", $where);
	return $AccessDetails;
}

function ManageAccess($conn, $data)
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
	if (isset($data['password'])) {
		$raw_password = $data['password'];
	}
	// check for duplicate username
	$where = " where UserName = '$username'";
	$not_duplicate = check_unique_identity_filter($conn, 'users', $where);
	if ($not_duplicate) {
		if ($data['access'] == "Not Set") {
			$password = md5($data['password']);
			$sql = "INSERT INTO users(UserName,Password,UserType,EmployeeID,CreatedDate,CreatedTime) VALUES ('$username','$password','Employee',$EmployeeID,'$CreatedDate','$CreatedTime')";
			$response = _InsertTableRecords($conn, $sql);
			$message = "Dear $Name,\n\nThis is to inform you that we have just created a- $Email new Employee with following details - \n\nYour Credential:\n\nUsername - $username\n\nPassword - $raw_password\n\nWarm regards,\nTechXpert Team\n\n\nApplication Link - https://play.google.com/store/apps/details?id=io.ionic.techXpert\n\nWebsite Link - https://techxpertindia.in/admin/authentication/login\n\n";
			$phonenumber = "+91" . $Phone;
			$post_mail_data['action'] = "Employee Access";
			$post_mail_data['username'] = $username;
			$post_mail_data['Email'] = $Email;
			$post_mail_data['Name'] = $Name;
			$post_mail_data['password'] = $raw_password;
			sendMailRequest($post_mail_data);
			sendWhatsAppMessage($phonenumber, $message);
		} else {
			$query_parameter = " UserName = '$username' where EmployeeID = $EmployeeID";
			$response = _UpdateTableRecords($conn, 'users', $query_parameter);
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
	} else {
		$response['error'] = true;
		$response['message'] = "Username is already associated with other user. Please use different username";
	}
	return $response;
}

function ResetPassword($conn, $data)
{
	$new_password = $data['reset_passsword'];
	$username = $data['reset_password_username'];
	$password_md5 = md5($new_password);
	$query_parameter = " Password = '$password_md5' where UserName = '$username'";
	$response = _UpdateTableRecords($conn, 'users', $query_parameter);
	if ($response['error'] == false) {


		$response['message'] = "Password changed!";
	}

	return $response;
}

function ManageRole_Supervisor($conn, $data, $setting_roles)
{
	$response = array();
	$roles = array();
	$divisions = array();
	$response['error'] = false;
	if (isset($data['roles']))
		$roles = $data['roles'];
	if (isset($data['divisions']))
		$divisions = $data['divisions'];
	$EmployeeID = $data['EmployeeID'];
	$CreatedDate = $data['CreatedDate'];
	$CreatedTime = $data['CreatedTime'];
	$updation_message = "No Updation Done";

	// get old roles array
	$old_roles = getEmployeeRole($conn, $EmployeeID);
	$old_roles_array = array();
	if (sizeof($old_roles) > 0) {
		foreach ($old_roles as $old_role) {
			if (!(in_array($old_role['Role'], $setting_roles))) {
				array_push($old_roles_array, $old_role['Role']);
			}
		}
	}

	// find roles to be deleted
	// values which are in old roles array but not in new roles array
	$roles_to_be_deleted = array_diff($old_roles_array, $roles);
	foreach ($roles_to_be_deleted as $role) {
		$query = " where EmployeeID = $EmployeeID and Role = '$role'";
		delete_identity_filter($conn, "user_roles", $query);
		$updation_message = "Role Updated";
	}

	// find roles to be added
	// values which are in new roles array but not in old roles array
	$roles_to_be_added = array_diff($roles, $old_roles_array);

	foreach ($roles_to_be_added as $role) {
		$sql = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($EmployeeID,'$role','$CreatedDate','$CreatedTime')";
		$response = _InsertTableRecords($conn, $sql);
		$updation_message = "Role Updated";
	}

	// get old divisions array
	$old_divisions = getEmployeeDivision($conn, $EmployeeID);
	$old_divisions_array = array();
	if (sizeof($old_divisions) > 0) {
		foreach ($old_divisions as $old_division) {
			array_push($old_divisions_array, $old_division['Division']);
		}
	}

	// find divisions to be deleted
	// values which are in old divisions array but not in new divisions array
	$divisions_to_be_deleted = array_diff($old_divisions_array, $divisions);
	foreach ($divisions_to_be_deleted as $division) {
		$query = " where EmployeeID = $EmployeeID and Division = '$division'";
		$response = delete_identity_filter($conn, "user_divisions", $query);
		if ($updation_message == "No Updation Done") {
			$updation_message = "Division Updated";
		} else {
			$updation_message = $updation_message . "\n" . "Division Updated";
		}
	}

	// find divisions to be added
	// values which are in new divisions array but not in old divisions array
	$divisions_to_be_added = array_diff($divisions, $old_divisions_array);

	foreach ($divisions_to_be_added as $division) {
		$sql = "INSERT INTO user_divisions(EmployeeID,Division,CreatedDate,CreatedTime) VALUES ($EmployeeID,'$division','$CreatedDate','$CreatedTime')";
		$response = _InsertTableRecords($conn, $sql);
		if ($updation_message == "No Updation Done") {
			$updation_message = "Division Updated";
		} else {
			$updation_message = $updation_message . "\n" . "Division Updated";
		}
	}

	// Changing Supervisor
	if ($data['supervisor'] != $data['EmployeeCurrentSupervisor']) {
		$Supervisor = $data['supervisor'];
		$query_parameter = " Supervisor = $Supervisor where ID = $EmployeeID";
		_UpdateTableRecords($conn, 'employees', $query_parameter);
		if ($updation_message == "No Updation Done") {
			$updation_message = "Supervisor Updated";
		} else {
			$updation_message = $updation_message . "\n" . "Supervisor Updated";
		}
	}

	$response['message'] = $updation_message;
	return $response;
}

function _api_login_user_old($conn, $data)
{
	$response = array();
	$UserName = $data['username'];
	$Password = md5($data['password']);
	$where = " where UserName = '$UserName'";
	$num_rows = _getTotalRows($conn, 'users', $where);
	if (_getTotalRows($conn, 'users', $where) > 0) {
		$AccessDetails = _getTableDetails($conn, "users", $where);
		$response['error'] = false;
		$response['message'] = "Login Successful!";
		$response['data'] = $AccessDetails;
		if ($AccessDetails['UserType'] == "Employee") {
			$EmployeeID = $AccessDetails['EmployeeID'];
			$UserType = $AccessDetails['UserType'];
			$roles = getUserRole($conn, $EmployeeID, $UserType);
			$response['data']['role'] = $roles;
		}
	} else {
		$response['error'] = true;
		$response['message'] = "Unauthorized Access, Please try again!";
	}
	return $response;
}


function _api_login_user($conn, $data)
{
	$response = array();

	$UserName = $data['username'];
	$Password = $data['password'];

	// 🔐 Get user securely
	$stmt = $conn->prepare("SELECT * FROM users WHERE UserName=? AND IsActive=1");
	$stmt->bind_param("s", $UserName);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result->num_rows > 0) {
		$user = $result->fetch_assoc();
		$storedPassword = $user['Password'];

		$loginSuccess = false;
		$isEmployeeActive = 1;

		// =====================================================
		// ✅ CHECK EMPLOYEE ACTIVE STATUS
		// =====================================================

		if ($user['UserType'] === "Employee") {
			$EmployeeID = $user['EmployeeID'];

			$empStmt = $conn->prepare("
                SELECT IsActive 
                FROM employees 
                WHERE ID=?
            ");

			$empStmt->bind_param("s", $EmployeeID);
			$empStmt->execute();

			$empResult = $empStmt->get_result();

			if ($empResult->num_rows > 0) {
				$employee = $empResult->fetch_assoc();

				$isEmployeeActive = $employee['IsActive'];

				// ❌ Block inactive employee
				if ($isEmployeeActive == '0') {
					$response['error'] = true;
					$response['message'] = "Employee account is inactive!";
					$response['data'] = array(
						"isEmployeeActive" => 0
					);

					return $response;
				}
			} else {
				$response['error'] = true;
				$response['message'] = "Employee record not found!";
				$response['data'] = array(
					"isEmployeeActive" => 0
				);

				return $response;
			}
		}

		// =====================================================
		// ✅ PASSWORD CHECK
		// =====================================================

		// 🔹 Case 1: OLD MD5 PASSWORD
		if (strlen($storedPassword) == 32 && ctype_xdigit($storedPassword)) {
			if (md5($Password) === $storedPassword) {
				$loginSuccess = true;


				// $newHash = password_hash($Password, PASSWORD_BCRYPT);
				// $update = $conn->prepare("UPDATE users SET Password=? WHERE UserID=?");
				// $update->bind_param("si", $newHash, $user['UserID']);
				// $update->execute();


				// $user['Password'] = $newHash;
			}
		}
		// 🔹 Case 2: NEW PASSWORD (bcrypt)
		else {
			if (password_verify($Password, $storedPassword)) {
				$loginSuccess = true;
			}
		}

		if ($loginSuccess) {
			// 🔁 FORCE LOGOUT OLD SESSIONS
			$clear = $conn->prepare("UPDATE users SET AuthToken=NULL, TokenExpiry=NULL WHERE UserID=?");
			$clear->bind_param("i", $user['UserID']);
			$clear->execute();

			// 🔐 GENERATE NEW JWT TOKEN
			$issuedAt = time();
			$expirationTime = $issuedAt + 86400; // valid for 1 day
			$payload = array(
				'iat' => $issuedAt,
				'exp' => $expirationTime,
				'data' => array(
					'UserID' => $user['UserID'],
					'UserName' => $user['UserName'],
					'EmployeeID' => isset($user['EmployeeID']) ? $user['EmployeeID'] : null
				)
			);

			// Check if JWT_SECRET_KEY is defined, else use a fallback (in case this is called from somewhere else)
			$secretKey = defined('JWT_SECRET_KEY') ? JWT_SECRET_KEY : 'aryadibussines_super_secret_key_2026';
			$token = \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS256');
			$expiry = date("Y-m-d H:i:s", $expirationTime);

			$updateToken = $conn->prepare("UPDATE users SET AuthToken=?, TokenExpiry=? WHERE UserID=?");
			$updateToken->bind_param("ssi", $token, $expiry, $user['UserID']);
			$updateToken->execute();

			// 🔁 UPDATE USER ARRAY (IMPORTANT for response)
			$user['AuthToken'] = $token;
			$user['TokenExpiry'] = $expiry;

			// 🎯 ADD ROLE (your existing logic)
			if ($user['UserType'] == "Employee") {
				$EmployeeID = $user['EmployeeID'];
				$UserType = $user['UserType'];
				$roles = getUserRole($conn, $EmployeeID, $UserType);
				$user['role'] = $roles;
			}

			// ✅ FINAL RESPONSE (FULL DATA - APP COMPATIBLE)
			$response['error'] = false;
			$response['message'] = "Login Successful!";
			$response['token'] = $token;   // future-ready
			$response['data'] = $user;     // current app uses this
			$response['data']['IsEmployeeActive'] = $isEmployeeActive;
		} else {
			$response['error'] = true;
			$response['message'] = "Invalid Password";
		}
	} else {
		$response['error'] = true;
		$response['message'] = "User not found";
	}

	return $response;
}

function DeleteEmployee($conn, $data)
{
	$response = array();
	$EmployeeID = $data['EmployeeID'];
	// update isactive = 0 employees table
	$query_parameter = " IsActive = 0 where ID = $EmployeeID";
	_UpdateTableRecords($conn, 'employees', $query_parameter);

	// delete user_roles for Emloyee ID
	$query_parameter = " where EmployeeID = $EmployeeID";
	delete_identity_filter($conn, "user_roles", $query_parameter);

	// delete user_divisions for Emloyee ID
	$query_parameter = " where EmployeeID = $EmployeeID";
	delete_identity_filter($conn, "user_divisions", $query_parameter);

	$response['error'] = false;
	$response['message'] = "Employee Deleted from system";
	return $response;
}

function getDivisionArray($conn)
{
	$where = " where IsActive = 1";
	$employee_division_array = _getTableRecords($conn, 'employee_divisions', $where);
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
	$max_seq = _getMaxIdentityValue_filter($conn, 'employees', 'DivisionSequence', $where_query);
	$seq = $max_seq + 1;
	$formatted_seq = sprintf('%04d', $seq);
	//$currentDate = date("Ymd");
	$EmployeeNumber = $Initials . $formatted_seq;
	$response['EmployeeNumber'] = $EmployeeNumber;
	$response['DivisionSequence'] = $seq;
	return $response;
}

function InsertMonthlySalaryEmployee($conn, $salaryData)
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




function getEmployeeMonthlySalary($conn, $EmployeeID, $Year, $Month)
{

	$where = "WHERE EmployeeID='$EmployeeID' and Month='$Month' and Year='$Year'";
	// $where = "SELECT * FROM employee_monthly_salary where ID = $EmployeeID";
	$response = _getTableRecords($conn, 'employee_monthly_salary', $where);
	return $response;
}

function getEmployeeSupervisorData($conn, $EmployeeSupervisorId)
{
	$where = "WHERE ID='$EmployeeSupervisorId' ";
	$response = _getTableDetails($conn, 'employees', $where);
	return $response;
}

function getEmployeeDataByUserName($conn, $Username)
{
	$where = "WHERE UserName='$Username' ";
	$response = _getTableDetails($conn, 'users', $where);
	return $response;
}

function getTotalEmployee($conn)
{
	$sql = "Select COUNT(*) as employees_count from employees where 1 ORDER BY EmployeeNumber DESC";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return $row['employees_count'];
	} else {
		return 0;
	}
}

// ----------Leave Management-----------------------------

function hasHrLeaveApprovalAccess($roles)
{
	if (isset($_SESSION['UserType'])) {
		$userType = (string) $_SESSION['UserType'];
		if (in_array($userType, array('Admin', 'Super Admin'), true)) {
			return true;
		}
	}
	if (!isset($roles['EmployeeRoles']) || !is_array($roles['EmployeeRoles'])) {
		return false;
	}
	$hr_roles = array('HR', 'Super Admin', 'Admin');
	foreach ($roles['EmployeeRoles'] as $role) {
		if (in_array($role, $hr_roles, true)) {
			return true;
		}
	}
	return false;
}

function leaveApprovalColumnExists($conn, $column_name)
{
	$column_name = mysqli_real_escape_string($conn, $column_name);
	$result = mysqli_query($conn, "SHOW COLUMNS FROM `employee_leave` LIKE '$column_name'");
	return ($result && mysqli_num_rows($result) > 0);
}

function leaveUpdateSucceeded($conn, $update_result)
{
	if (!is_array($update_result) || !isset($update_result['error']) || $update_result['error'] === true) {
		return false;
	}
	return mysqli_affected_rows($conn) > 0;
}

function leaveUpdateErrorMessage($update_result)
{
	if (is_array($update_result) && !empty($update_result['message'])) {
		return $update_result['message'];
	}
	return 'Unable to update leave record.';
}

function computeEmployeeLeaveDays($leave_data)
{
	$from_date = (string) ($leave_data['FromDate'] ?? '');
	$to_date = (string) ($leave_data['ToDate'] ?? '');
	$duration = strtolower(trim((string) ($leave_data['Duration'] ?? '')));

	$leave_days = 1.0;
	try {
		if ($duration !== '' && strpos($duration, 'half') !== false) {
			$leave_days = 0.5;
		} else {
			$from_date_time = new DateTime($from_date);
			$to_date_time = new DateTime($to_date);
			$interval = $from_date_time->diff($to_date_time);
			$leave_days = (int) $interval->format('%a') + 1;
			if ($leave_days <= 0) {
				$leave_days = 1.0;
			}
		}
	} catch (Exception $e) {
		$leave_days = 1.0;
	}
	return (float) $leave_days;
}

function canSupervisorApproveLeave($conn, $leave_id, $approver_employee_id)
{
	$leave = getEmployeeLeaveDataByID($conn, array('ID' => (int) $leave_id));
	if (!$leave || !isset($leave['EmployeeID'])) {
		return false;
	}
	if (strcasecmp((string) ($leave['Status'] ?? ''), 'Pending') !== 0) {
		return false;
	}
	$employee_id = (int) $leave['EmployeeID'];
	$approver_employee_id = (int) $approver_employee_id;
	if ($approver_employee_id <= 0) {
		return false;
	}
	$where = " WHERE ID = $employee_id AND Supervisor = $approver_employee_id AND IsActive = 1";
	$employee = _getTableDetails($conn, 'employees', $where);
	return !empty($employee);
}

function canHrApproveLeave($conn, $leave_id, $roles)
{
	if (!hasHrLeaveApprovalAccess($roles)) {
		return false;
	}
	$leave = getEmployeeLeaveDataByID($conn, array('ID' => (int) $leave_id));
	if (!$leave) {
		return false;
	}
	return strcasecmp((string) ($leave['Status'] ?? ''), 'SupervisorApproved') === 0;
}

function supervisorApproveEmployeeLeave($conn, $leave_id, $approver_employee_id)
{
	$leave_id = (int) $leave_id;
	$approver_employee_id = (int) $approver_employee_id;
	$approved_at = date('Y-m-d H:i:s');

	$set_parts = array("Status = 'SupervisorApproved'");
	if (leaveApprovalColumnExists($conn, 'SupervisorApprovedBy')) {
		$set_parts[] = "SupervisorApprovedBy = $approver_employee_id";
	}
	if (leaveApprovalColumnExists($conn, 'SupervisorApprovedAt')) {
		$set_parts[] = "SupervisorApprovedAt = '$approved_at'";
	}
	if (leaveApprovalColumnExists($conn, 'ApprovedBy')) {
		$set_parts[] = "ApprovedBy = NULL";
	}
	if (leaveApprovalColumnExists($conn, 'ApprovedAt')) {
		$set_parts[] = "ApprovedAt = NULL";
	}
	if (leaveApprovalColumnExists($conn, 'RejectionReason')) {
		$set_parts[] = "RejectionReason = NULL";
	}

	$update_param = implode(', ', $set_parts) . " WHERE ID = $leave_id AND Status = 'Pending' AND IsActive = 1";
	$result = _UpdateTableRecords($conn, 'employee_leave', $update_param);
	$success = leaveUpdateSucceeded($conn, $result);
	if ($success) {
		require_once dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
		pnc_notifyLeaveDecision($conn, $leave_id, 'approved', 'supervisor');
	}
	return array('success' => $success, 'result' => $result);
}

function supervisorRejectEmployeeLeave($conn, $leave_id, $approver_employee_id, $reason = '')
{
	$leave_id = (int) $leave_id;
	$approver_employee_id = (int) $approver_employee_id;
	$approved_at = date('Y-m-d H:i:s');
	$reason = mysqli_real_escape_string($conn, trim($reason));

	$set_parts = array("Status = 'Rejected'", "Approved = '0'");
	if (leaveApprovalColumnExists($conn, 'ApprovedBy')) {
		$set_parts[] = "ApprovedBy = $approver_employee_id";
	}
	if (leaveApprovalColumnExists($conn, 'ApprovedAt')) {
		$set_parts[] = "ApprovedAt = '$approved_at'";
	}
	if (leaveApprovalColumnExists($conn, 'RejectionReason')) {
		$set_parts[] = "RejectionReason = '$reason'";
	}

	$update_param = implode(', ', $set_parts) . " WHERE ID = $leave_id AND Status = 'Pending' AND IsActive = 1";
	$result = _UpdateTableRecords($conn, 'employee_leave', $update_param);
	$success = leaveUpdateSucceeded($conn, $result);
	if ($success) {
		require_once dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
		pnc_notifyLeaveDecision($conn, $leave_id, 'rejected', 'supervisor');
	}
	return array('success' => $success, 'result' => $result);
}

function hrFinalApproveEmployeeLeave($conn, $leave_id, $approver_employee_id)
{
	$leave_id = (int) $leave_id;
	$leave = getEmployeeLeaveDataByID($conn, array('ID' => $leave_id));
	if (!$leave || strcasecmp((string) ($leave['Status'] ?? ''), 'SupervisorApproved') !== 0) {
		return array('success' => false, 'result' => array('error' => true, 'message' => 'Leave is not pending HR final approval.'));
	}

	$employee_id = (int) $leave['EmployeeID'];
	$leave_days = computeEmployeeLeaveDays($leave);
	$employee_data = getEmployeeData($conn, $employee_id);
	$current_allowed = (float) ($employee_data['EmployeeLeave'] ?? 0);
	$new_allowed = $current_allowed + $leave_days;

	$update_employee = " EmployeeLeave = '$new_allowed' where ID=$employee_id";
	$result_emp = _UpdateTableRecords($conn, 'employees', $update_employee);
	if (!empty($result_emp['error'])) {
		return array('success' => false, 'result' => $result_emp);
	}

	$approver_sql = 'NULL';
	if ((int) $approver_employee_id > 0) {
		$approver_sql = (int) $approver_employee_id;
	}
	$approved_at = date('Y-m-d H:i:s');

	$set_parts = array("Status = 'Approved'", "Approved = '1'");
	if (leaveApprovalColumnExists($conn, 'ApprovedBy')) {
		$set_parts[] = "ApprovedBy = $approver_sql";
	}
	if (leaveApprovalColumnExists($conn, 'ApprovedAt')) {
		$set_parts[] = "ApprovedAt = '$approved_at'";
	}
	if (leaveApprovalColumnExists($conn, 'RejectionReason')) {
		$set_parts[] = "RejectionReason = NULL";
	}

	$update_param = implode(', ', $set_parts) . " WHERE ID = $leave_id AND Status = 'SupervisorApproved' AND IsActive = 1";
	$result_leave = _UpdateTableRecords($conn, 'employee_leave', $update_param);
	$success = leaveUpdateSucceeded($conn, $result_leave);
	if ($success) {
		require_once dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
		pnc_notifyLeaveDecision($conn, $leave_id, 'approved', 'hr');
	}
	return array('success' => $success, 'result' => $result_leave);
}

function hrRejectEmployeeLeave($conn, $leave_id, $approver_employee_id, $reason = '')
{
	$leave_id = (int) $leave_id;
	$approver_sql = 'NULL';
	if ((int) $approver_employee_id > 0) {
		$approver_sql = (int) $approver_employee_id;
	}
	$approved_at = date('Y-m-d H:i:s');
	$reason = mysqli_real_escape_string($conn, trim($reason));

	$set_parts = array("Status = 'Rejected'", "Approved = '0'");
	if (leaveApprovalColumnExists($conn, 'ApprovedBy')) {
		$set_parts[] = "ApprovedBy = $approver_sql";
	}
	if (leaveApprovalColumnExists($conn, 'ApprovedAt')) {
		$set_parts[] = "ApprovedAt = '$approved_at'";
	}
	if (leaveApprovalColumnExists($conn, 'RejectionReason')) {
		$set_parts[] = "RejectionReason = '$reason'";
	}

	$update_param = implode(', ', $set_parts) . " WHERE ID = $leave_id AND Status = 'SupervisorApproved' AND IsActive = 1";
	$result = _UpdateTableRecords($conn, 'employee_leave', $update_param);
	$success = leaveUpdateSucceeded($conn, $result);
	if ($success) {
		require_once dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
		pnc_notifyLeaveDecision($conn, $leave_id, 'rejected', 'hr');
	}
	return array('success' => $success, 'result' => $result);
}

function getEmployeeLeaveRequestsForSupervisor($conn, $supervisor_employee_id, $filters = array())
{
	$supervisor_employee_id = (int) $supervisor_employee_id;
	if ($supervisor_employee_id <= 0) {
		return array();
	}

	$status = isset($filters['status']) ? trim((string) $filters['status']) : 'Pending';
	$employee_id = isset($filters['employee_id']) ? (int) $filters['employee_id'] : -1;

	$where = " WHERE el.IsActive = 1 AND el.EmployeeID IN (SELECT ID FROM employees WHERE Supervisor = $supervisor_employee_id)";
	if ($status !== '' && strcasecmp($status, 'all') !== 0 && strcasecmp($status, '-1') !== 0) {
		$status_esc = mysqli_real_escape_string($conn, $status);
		$where .= " AND el.Status = '$status_esc'";
	}
	if ($employee_id > 0) {
		$where .= " AND el.EmployeeID = $employee_id";
	}

	$sql = "
		SELECT
			el.ID,
			el.EmployeeID,
			e.Name AS EmployeeName,
			el.TypeOfLeave,
			el.ReasonOfLeave,
			el.FromDate,
			el.ToDate,
			el.Duration,
			el.Status,
			el.Approved,
			el.CreatedBy,
			el.CreatedDate,
			el.CreatedTime,
			el.IsActive
		FROM employee_leave el
		INNER JOIN employees e ON e.ID = el.EmployeeID
		$where
		ORDER BY el.ID DESC
	";
	return _getSQLRecords($conn, $sql);
}

function getEmployeeLeaveRequestsForHr($conn, $filters = array())
{
	$status = isset($filters['status']) ? trim((string) $filters['status']) : 'SupervisorApproved';
	$employee_id = isset($filters['employee_id']) ? (int) $filters['employee_id'] : -1;

	$where = " WHERE el.IsActive = 1";
	if ($status !== '' && strcasecmp($status, 'all') !== 0 && strcasecmp($status, '-1') !== 0) {
		$status_esc = mysqli_real_escape_string($conn, $status);
		$where .= " AND el.Status = '$status_esc'";
	}
	if ($employee_id > 0) {
		$where .= " AND el.EmployeeID = $employee_id";
	}

	$sql = "
		SELECT
			el.ID,
			el.EmployeeID,
			e.Name AS EmployeeName,
			el.TypeOfLeave,
			el.ReasonOfLeave,
			el.FromDate,
			el.ToDate,
			el.Duration,
			el.Status,
			el.Approved,
			el.CreatedBy,
			el.CreatedDate,
			el.CreatedTime,
			el.IsActive
		FROM employee_leave el
		INNER JOIN employees e ON e.ID = el.EmployeeID
		$where
		ORDER BY el.ID DESC
	";
	return _getSQLRecords($conn, $sql);
}

function buildEmployeeLeaveStatusBadge($status)
{
	$status = $status ? (string) $status : 'Pending';
	if (strcasecmp($status, 'Approved') === 0) {
		return '<span class="badge badge-success">Approved (HR Final)</span>';
	}
	if (strcasecmp($status, 'Rejected') === 0) {
		return '<span class="badge badge-danger">Rejected</span>';
	}
	if (strcasecmp($status, 'SupervisorApproved') === 0) {
		return '<span class="badge badge-info">Supervisor Approved - Pending HR</span>';
	}
	if (strcasecmp($status, 'Pending') === 0) {
		return '<span class="badge badge-warning">Pending Supervisor</span>';
	}
	return '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
}

function leaveParseMultiFilterValues($value)
{
	if (is_array($value)) {
		$items = $value;
	} else {
		$value = trim((string) $value);
		if ($value === '' || $value === '-1') {
			return array();
		}
		$items = preg_split('/\s*,\s*/', $value);
	}

	$result = array();
	foreach ($items as $item) {
		$item = trim((string) $item);
		if ($item !== '' && $item !== '-1') {
			$result[] = $item;
		}
	}

	return array_values(array_unique($result));
}

function leaveAppendSqlInClause($conn, $column, $values, $numeric = false)
{
	if (empty($values)) {
		return '';
	}

	if ($numeric) {
		$ids = array();
		foreach ($values as $value) {
			$id = (int) $value;
			if ($id > 0) {
				$ids[] = $id;
			}
		}
		$ids = array_values(array_unique($ids));
		if (empty($ids)) {
			return '';
		}
		return ' AND ' . $column . ' IN (' . implode(',', $ids) . ')';
	}

	$escaped = array();
	foreach ($values as $value) {
		$escaped[] = "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
	}
	if (empty($escaped)) {
		return '';
	}

	return ' AND ' . $column . ' IN (' . implode(',', $escaped) . ')';
}

function leaveHrActionableStatusSql()
{
	return "el.Status = 'SupervisorApproved'";
}

function leaveBuildStatusFilterSql($conn, $filters)
{
	$status_values = leaveParseMultiFilterValues(isset($filters['status']) ? $filters['status'] : '');
	if (empty($status_values)) {
		return '';
	}

	$or_parts = array();
	if (in_array('hr_actionable', $status_values, true)) {
		$or_parts[] = leaveHrActionableStatusSql();
	}
	if (in_array('pending_supervisor', $status_values, true)) {
		$or_parts[] = "el.Status = 'Pending'";
	}
	$status_values = array_values(array_diff($status_values, array('hr_actionable', 'pending_supervisor')));

	if (!empty($status_values)) {
		$escaped = array();
		foreach ($status_values as $value) {
			$escaped[] = "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
		}
		$or_parts[] = 'el.Status IN (' . implode(',', $escaped) . ')';
	}

	if (empty($or_parts)) {
		return '';
	}

	return ' AND (' . implode(' OR ', $or_parts) . ')';
}

function getSupervisedEmployeeIdsForLeave($conn, $supervisor_employee_id)
{
	$supervisor_employee_id = (int) $supervisor_employee_id;
	if ($supervisor_employee_id <= 0) {
		return array();
	}

	$ids = array();
	$rows = _getSQLRecords($conn, "SELECT ID FROM employees WHERE Supervisor = $supervisor_employee_id");
	foreach ($rows as $row) {
		if (!empty($row['ID'])) {
			$ids[] = (int) $row['ID'];
		}
	}
	return array_values(array_unique($ids));
}

function buildLeaveListJoinSql()
{
	return ' FROM employee_leave el INNER JOIN employees e ON e.ID = el.EmployeeID ';
}

function buildLeaveListFilterSql($conn, $filters = array(), $options = array())
{
	$scope = isset($options['scope']) ? (string) $options['scope'] : 'admin';
	$supervisor_employee_id = isset($options['supervisor_employee_id']) ? (int) $options['supervisor_employee_id'] : -1;

	$where = ' WHERE el.IsActive = 1 ';

	if ($scope === 'supervisor') {
		$supervised_ids = getSupervisedEmployeeIdsForLeave($conn, $supervisor_employee_id);
		if (empty($supervised_ids)) {
			return ' WHERE 1=0 ';
		}
		$where .= ' AND el.EmployeeID IN (' . implode(',', $supervised_ids) . ')';
	}

	$filter_date = isset($filters['filter_date']) ? trim((string) $filters['filter_date']) : '';
	if ($filter_date !== '' && strtolower($filter_date) !== 'all' && strpos($filter_date, ' - ') !== false) {
		$date_parts = explode(' - ', $filter_date, 2);
		if (count($date_parts) === 2) {
			$start_date = mysqli_real_escape_string($conn, trim($date_parts[0]));
			$end_date = mysqli_real_escape_string($conn, trim($date_parts[1]));
			$where .= " AND (el.FromDate >= '$start_date' AND el.FromDate <= '$end_date')";
		}
	}

	$employee_ids = leaveParseMultiFilterValues(isset($filters['employee_id']) ? $filters['employee_id'] : '');
	$where .= leaveAppendSqlInClause($conn, 'el.EmployeeID', $employee_ids, true);

	if (!empty($filters['employee_number'])) {
		$employee_number = mysqli_real_escape_string($conn, trim((string) $filters['employee_number']));
		$where .= " AND (e.EmployeeNumber LIKE '%$employee_number%' OR CAST(e.ID AS CHAR) LIKE '%$employee_number%')";
	}

	$where .= leaveBuildStatusFilterSql($conn, $filters);

	$state_values = leaveParseMultiFilterValues(isset($filters['state']) ? $filters['state'] : '');
	$where .= leaveAppendSqlInClause($conn, 'e.State', $state_values, false);

	$department_values = leaveParseMultiFilterValues(isset($filters['department']) ? $filters['department'] : '');
	$where .= leaveAppendSqlInClause($conn, 'e.Department', $department_values, false);

	$designation_values = leaveParseMultiFilterValues(isset($filters['designation']) ? $filters['designation'] : '');
	$where .= leaveAppendSqlInClause($conn, 'e.Designation', $designation_values, false);

	$leave_type_values = leaveParseMultiFilterValues(isset($filters['leave_type']) ? $filters['leave_type'] : '');
	$where .= leaveAppendSqlInClause($conn, 'el.TypeOfLeave', $leave_type_values, false);

	if (!empty($filters['search'])) {
		$search = mysqli_real_escape_string($conn, trim((string) $filters['search']));
		$where .= " AND (e.Name LIKE '%$search%' OR el.TypeOfLeave LIKE '%$search%' OR el.ReasonOfLeave LIKE '%$search%' OR el.Duration LIKE '%$search%')";
	}

	return $where;
}

function getLeaveFilterOptions($conn)
{
	$options = array(
		'states' => array(),
		'departments' => array(),
		'designations' => array(),
		'leave_types' => array(),
	);
	$state_rows = _getSQLRecords($conn, "SELECT DISTINCT State FROM employees WHERE IsActive = 1 AND Vendor = 0 AND State <> '' ORDER BY State ASC");
	foreach ($state_rows as $row) {
		$options['states'][] = $row['State'];
	}
	$department_rows = _getSQLRecords($conn, "SELECT DISTINCT Department FROM employees WHERE IsActive = 1 AND Vendor = 0 AND Department <> '' ORDER BY Department ASC");
	foreach ($department_rows as $row) {
		$options['departments'][] = $row['Department'];
	}
	$designation_rows = _getSQLRecords($conn, "SELECT DISTINCT Designation FROM employees WHERE IsActive = 1 AND Vendor = 0 AND Designation <> '' ORDER BY Designation ASC");
	foreach ($designation_rows as $row) {
		$options['designations'][] = $row['Designation'];
	}
	$leave_type_rows = _getSQLRecords($conn, "SELECT DISTINCT TypeOfLeave FROM employee_leave WHERE IsActive = 1 AND TypeOfLeave <> '' ORDER BY TypeOfLeave ASC");
	foreach ($leave_type_rows as $row) {
		$options['leave_types'][] = $row['TypeOfLeave'];
	}
	return $options;
}

function getLeaveApprovalSqlParts($conn)
{
	$parts = array(
		'supervisor_select' => '',
		'hr_select' => '',
		'supervisor_join' => '',
		'hr_join' => '',
	);
	if (leaveApprovalColumnExists($conn, 'SupervisorApprovedBy')) {
		$parts['supervisor_select'] = ', sup.Name AS SupervisorApproverName, el.SupervisorApprovedAt';
		$parts['supervisor_join'] = ' LEFT JOIN employees sup ON sup.ID = el.SupervisorApprovedBy ';
	}
	if (leaveApprovalColumnExists($conn, 'ApprovedBy')) {
		$parts['hr_select'] = ', hr.Name AS HrApproverName, el.ApprovedAt AS HrApprovedAt';
		$parts['hr_join'] = ' LEFT JOIN employees hr ON hr.ID = el.ApprovedBy ';
	}
	return $parts;
}

function sendLeaveDatatableJson($response)
{
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	header('Content-Type: application/json; charset=utf-8');
	$flags = 0;
	if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
		$flags = JSON_INVALID_UTF8_SUBSTITUTE;
	}
	$json = json_encode($response, $flags);
	if ($json === false) {
		$json = json_encode(array(
			'draw' => isset($response['draw']) ? (int) $response['draw'] : 0,
			'iTotalRecords' => 0,
			'iTotalDisplayRecords' => 0,
			'aaData' => array(),
		));
	}
	echo $json;
	exit;
}

function buildLeaveApprovalInfoHtml($record)
{
	$lines = array();
	if (!empty($record['SupervisorApproverName']) && !empty($record['SupervisorApprovedAt'])) {
		$lines[] = '<strong>Supervisor:</strong> ' . htmlspecialchars($record['SupervisorApproverName']) . '<br><small>' . htmlspecialchars($record['SupervisorApprovedAt']) . '</small>';
	}
	if (!empty($record['HrApproverName']) && !empty($record['HrApprovedAt'])) {
		$lines[] = '<strong>HR:</strong> ' . htmlspecialchars($record['HrApproverName']) . '<br><small>' . htmlspecialchars($record['HrApprovedAt']) . '</small>';
	}
	if (!empty($record['RejectionReason'])) {
		$lines[] = '<small class="text-danger">' . htmlspecialchars((string) $record['RejectionReason']) . '</small>';
	}
	if (empty($lines)) {
		return '-';
	}
	return implode('<br>', $lines);
}

function leaveBulkProcessIds($conn, $leave_ids, $processor)
{
	$summary = array(
		'success' => 0,
		'failed' => 0,
	);

	foreach ($leave_ids as $leave_id) {
		$leave_id = (int) $leave_id;
		if ($leave_id <= 0) {
			continue;
		}

		$result = call_user_func($processor, $conn, $leave_id);
		if (!empty($result['success'])) {
			$summary['success']++;
		} else {
			$summary['failed']++;
		}
	}

	return $summary;
}

function buildLeaveBulkActionMessage($summary, $action_label)
{
	$success = (int) ($summary['success'] ?? 0);
	$failed = (int) ($summary['failed'] ?? 0);
	if ($success <= 0 && $failed <= 0) {
		return 'No leave records were updated.';
	}
	if ($success <= 0) {
		return 'Unable to ' . $action_label . ' selected record(s).';
	}
	return $success . ' record(s) ' . $action_label . '. ' . $failed . ' record(s) could not be updated.';
}

function supervisorBulkApproveEmployeeLeave($conn, $leave_ids, $approver_employee_id)
{
	$approver_employee_id = (int) $approver_employee_id;
	return leaveBulkProcessIds($conn, $leave_ids, function ($conn, $leave_id) use ($approver_employee_id) {
		if (!canSupervisorApproveLeave($conn, $leave_id, $approver_employee_id)) {
			return array('success' => false);
		}
		return supervisorApproveEmployeeLeave($conn, $leave_id, $approver_employee_id);
	});
}

function supervisorBulkRejectEmployeeLeave($conn, $leave_ids, $approver_employee_id, $reason = '')
{
	$approver_employee_id = (int) $approver_employee_id;
	return leaveBulkProcessIds($conn, $leave_ids, function ($conn, $leave_id) use ($approver_employee_id, $reason) {
		if (!canSupervisorApproveLeave($conn, $leave_id, $approver_employee_id)) {
			return array('success' => false);
		}
		return supervisorRejectEmployeeLeave($conn, $leave_id, $approver_employee_id, $reason);
	});
}

function hrBulkApproveEmployeeLeave($conn, $leave_ids, $approver_employee_id, $roles)
{
	$approver_employee_id = (int) $approver_employee_id;
	return leaveBulkProcessIds($conn, $leave_ids, function ($conn, $leave_id) use ($approver_employee_id, $roles) {
		if (!canHrApproveLeave($conn, $leave_id, $roles)) {
			return array('success' => false);
		}
		return hrFinalApproveEmployeeLeave($conn, $leave_id, $approver_employee_id);
	});
}

function hrBulkRejectEmployeeLeave($conn, $leave_ids, $approver_employee_id, $roles, $reason = '')
{
	$approver_employee_id = (int) $approver_employee_id;
	return leaveBulkProcessIds($conn, $leave_ids, function ($conn, $leave_id) use ($approver_employee_id, $roles, $reason) {
		if (!canHrApproveLeave($conn, $leave_id, $roles)) {
			return array('success' => false);
		}
		return hrRejectEmployeeLeave($conn, $leave_id, $approver_employee_id, $reason);
	});
}

function getEmployeeAllLeave($conn)
{
	$where = " where IsActive = 1 ORDER BY ID DESC";
	$response = _getTableRecords($conn, 'employee_leave', $where);
	return $response;
}

function InsertEmployeeLeave($conn, $data)
{
	$type_of_leave = "";
	$leave_reason = $data["leave_reason"];
	$from_date = $data["from_date"];
	$to_date = $data["to_date"];
	$EmployeeID = $data["EmployeeID"];
	$Duration = "Full Day";
	if (isset($data['half_day'])) {
		$Duration = "Half Day";
	}

	$CreatedBy = $data['CreatedBy'];
	$CreatedDate = date('Y-m-d');
	$CreatedTime = date('H:i:s');
	$leave_query = "INSERT INTO employee_leave (EmployeeID,TypeOfLeave,ReasonOfLeave,FromDate,ToDate,Duration,Status,Approved,CreatedBy,CreatedDate,CreatedTime ) VALUES('$EmployeeID','$type_of_leave','$leave_reason','$from_date','$to_date','$Duration','Pending','0','$CreatedBy','$CreatedDate','$CreatedTime')";
	$response = _InsertTableRecords($conn, $leave_query);

	$where = " where ID = $EmployeeID";

	$EmployeeDetailByID = _getTableDetails($conn, 'employees', $where);
	$SupervisorId = $EmployeeDetailByID['Supervisor'];

	if ($SupervisorId != -1 && $SupervisorId != "") {
		$EmployeeName = $EmployeeDetailByID['Name'];
		$EmployeeNumber = $EmployeeDetailByID['ContactNumber'];
		$where = " where ID = $SupervisorId";
		$SupervisorDetail = _getTableDetails($conn, 'employees', $where);
		if ($SupervisorDetail != null) {
			$EmployeeSupervisorEmail = $SupervisorDetail['Email'];
			$EmployeeSupervisorNumber = $SupervisorDetail['ContactNumber'];
			$message = "This is to inform you that $EmployeeName has applied the leave from $from_date to $to_date, Duration - $Duration for the Reason Mention : $leave_reason.\n\n Please approve it or reject it by going to web portal. \n\nRegard,\nTeam";


			//$message = "Dear Sir,\n\nI am writing to you to let you know that I have an important personal matter to attend at my hometown due to which I will not be able to come to the office from $from_date to $to_date.\n\nI shall be reachable on my mobile number $EmployeeNumber during the period.\n\nI will be thankful to you for considering my application.\n\n Yours Sincerely,\n $EmployeeName,\n\nWarm regards,\nTechXpert Team\n\n\Approval Link - https://app.techxpertgroup.in/admin/booking/view-booking-details \n\nWebsite Link - https://techxpertindia.in/admin/authentication/login\n\n";

			$phonenumber = "+91" . $EmployeeSupervisorNumber;
			$post_mail_data['action'] = "Employee Leave";
			$post_mail_data['Name'] = $EmployeeName;
			$post_mail_data['Email'] = $EmployeeSupervisorEmail;
			$post_mail_data['Number'] = $EmployeeNumber;
			$post_mail_data['FromDate'] = $from_date;
			$post_mail_data['ToDate'] = $to_date;
			sendWhatsAppMessage($phonenumber, $message);
		}
	}

	$response['message'] = "Leave Added to the System";
	return $response;
}

function UpdateEmployeeLeave($conn, $data)
{
	$type_of_leave = $data["type_of_leave"];
	$reason_of_leave = $data["reason_of_leave"];
	$from_date = $data["from_date"];
	$to_date = $data["to_date"];
	$leave_form_id = $data['form_id'];
	$CreatedDate = date('Y-m-d');
	$CreatedTime = date('H:i:s');
	$old_spare_part_details = GetEmployeeLeaveDetailsbyID($conn, $leave_form_id);
	if ($type_of_leave == $old_spare_part_details['TypeOfLeave'] && $reason_of_leave == $old_spare_part_details['ReasonOfLeave'] && $from_date == $old_spare_part_details['FromDate'] && $to_date == $old_spare_part_details['ToDate']) {
		$response['message'] = "No changes to update";
		$response['error'] = true;
	} else {
		$update_param = " TypeOfLeave = '$type_of_leave',ReasonOfLeave='$reason_of_leave',FromDate='$from_date',ToDate='$to_date' where ID=$leave_form_id";
		$response = _UpdateTableRecords($conn, 'employee_leave', $update_param);
		$response['message'] = "Leave Updated to the System";
	}
	return $response;
}

function GetEmployeeLeaveDetailsbyID($conn, $ID)
{
	$where = " where ID = $ID";
	$response = _getTableDetails($conn, 'employee_leave', $where);
	return $response;
}

function DeleteEmployeeLeave($conn, $data)
{
	$ID = $data['ID'];
	$query_parameter = " where ID = '$ID'";
	$response = delete_identity_filter($conn, "employee_leave", $query_parameter);
	return $response;
}

function getEmployeeLeaveDataByID($conn, $ID)
{
	$Id = $ID['ID'];
	$where = " where ID = $Id";
	$response = _getTableDetails($conn, 'employee_leave', $where);
	return $response;
}

function getLeaveDataByType($conn, $LeaveType)
{
	$where = " where TypeOfLeave = '$LeaveType'";
	$response = _getTableDetails($conn, 'leaveconfiguation', $where);
	return $response;
}

function GetAllEmployeeInArray($conn)
{
	$where = " where ID = ID";
	$response = _getTableRecords($conn, 'employees', $where);
	return $response;
}

function getEmployeeByPhone($conn, $phonenumber, $username)
{
	$response = array();

	// Security
	$phonenumber = mysqli_real_escape_string($conn, $phonenumber);

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
            WHERE e.ContactNumber = '$phonenumber' And u.UserName='$username'
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
function requestPasswordResetOtp($conn, $phonenumber, $username)
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

	$userId = (int) $employeeData['UserID'];
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
	if ($phonenumber === '') {
		$response['message'] = 'Missing phone number';
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

	$employeeData = getEmployeeByPhone($conn, $phonenumber);
	if (empty($employeeData)) {
		$response['message'] = 'No record found';
		return $response;
	}

	$userId = (int) $employeeData['UserID'];

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
	$upd = _UpdateTableRecords($conn, 'users', " Password = '$passwordMd5Esc' where UserID = $userId");
	if (!empty($upd['error'])) {
		$response['message'] = isset($upd['message']) ? $upd['message'] : 'Could not update password';
		return $response;
	}

	mysqli_query($conn, "UPDATE password_reset_otp SET verified = 1 WHERE id = $otpId");

	$response['error'] = false;
	$response['message'] = 'Password changed successfully';
	return $response;
}

function haversineDistanceMeters($lat1, $lon1, $lat2, $lon2)
{
	$earthRadius = 6371000;
	$lat1Rad = deg2rad((float) $lat1);
	$lat2Rad = deg2rad((float) $lat2);
	$deltaLat = deg2rad((float) $lat2 - (float) $lat1);
	$deltaLon = deg2rad((float) $lon2 - (float) $lon1);
	$a = sin($deltaLat / 2) * sin($deltaLat / 2)
		+ cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) * sin($deltaLon / 2);
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	return $earthRadius * $c;
}

function updateEmployeeAttendanceLocation($conn, $EmployeeID, $data)
{
	$response = ['error' => true, 'message' => 'Could not save location'];
	$EmployeeID = (int) $EmployeeID;
	if ($EmployeeID <= 0) {
		$response['message'] = 'Invalid employee';
		return $response;
	}

	$lat = trim((string) ($data['AttendanceLatitude'] ?? ''));
	$lng = trim((string) ($data['AttendanceLongitude'] ?? ''));
	$radius = (int) ($data['AttendanceRadiusMeters'] ?? 100);
	if ($radius < 10) {
		$radius = 100;
	}
	if ($radius > 50000) {
		$radius = 50000;
	}
	$isBoundary = !empty($data['IsAllowLocationBoundary']) ? 1 : 0;
	$updatedBy = mysqli_real_escape_string($conn, (string) ($data['UpdatedBy'] ?? ''));

	if ($isBoundary === 1) {
		if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
			if ((float) $lat < -90 || (float) $lat > 90 || (float) $lng < -180 || (float) $lng > 180) {
				$response['message'] = 'Invalid latitude or longitude';
				return $response;
			}
		} else {
			$lat = '';
			$lng = '';
		}
	} else {
		if ($lat !== '' && !is_numeric($lat)) {
			$lat = '';
		}
		if ($lng !== '' && !is_numeric($lng)) {
			$lng = '';
		}
	}

	$latSql = ($lat === '') ? 'NULL' : "'" . mysqli_real_escape_string($conn, $lat) . "'";
	$lngSql = ($lng === '') ? 'NULL' : "'" . mysqli_real_escape_string($conn, $lng) . "'";

	$update_param = "AttendanceLatitude = $latSql, AttendanceLongitude = $lngSql, "
		. "AttendanceRadiusMeters = $radius, IsAllowLocationBoundary = $isBoundary, "
		. "UpdatedBy = '$updatedBy', UpdatedDate = '" . date('Y-m-d') . "' "
		. "WHERE ID = $EmployeeID";

	$result = _UpdateTableRecords($conn, 'employees', $update_param);
	if (empty($result['error'])) {
		$response['error'] = false;
		$response['message'] = 'Location saved';
	} elseif (!empty($result['message'])) {
		$response['message'] = $result['message'];
	}
	return $response;
}

function getEmployeeAttendanceLocationPolicy($conn, $EmployeeID)
{
	$policy = [
		'boundaryEnabled' => false,
		'latitude' => null,
		'longitude' => null,
		'radiusMeters' => 100,
	];

	$employee = getEmployeeData($conn, $EmployeeID);
	if (empty($employee)) {
		return $policy;
	}

	if (!empty($employee['IsAllowLocationBoundary']) && (int) $employee['IsAllowLocationBoundary'] === 1) {
		$policy['boundaryEnabled'] = true;
		if (employeeHasConfiguredAttendanceLocation($employee)) {
			$policy['latitude'] = $employee['AttendanceLatitude'] ?? null;
			$policy['longitude'] = $employee['AttendanceLongitude'] ?? null;
		}
		$policy['radiusMeters'] = (int) ($employee['AttendanceRadiusMeters'] ?? 100);
		if ($policy['radiusMeters'] <= 0) {
			$policy['radiusMeters'] = 100;
		}
	}

	return $policy;
}

function getEmployeeAttendanceLocationPolicyApiData($conn, $EmployeeID)
{
	$EmployeeID = (int) $EmployeeID;
	$employee = getEmployeeData($conn, $EmployeeID);
	if (empty($employee)) {
		return null;
	}

	$policy = getEmployeeAttendanceLocationPolicy($conn, $EmployeeID);

	return [
		'EmployeeID' => $EmployeeID,
		'IsAllowLocationBoundary' => $policy['boundaryEnabled'] ? 1 : 0,
		'AttendanceLatitude' => $policy['latitude'],
		'AttendanceLongitude' => $policy['longitude'],
		'AttendanceRadiusMeters' => $policy['radiusMeters'],
		'boundaryEnabled' => $policy['boundaryEnabled'],
	];
}

function getEmployeeCheckoutEligibility($conn, $EmployeeID)
{
	$EmployeeID = (int) $EmployeeID;
	/** Minimum gap between punch-in and punch-out (hours). */
	$minHoursBeforeCheckout = 2;

	$response = [
		'canCheckout' => false,
		'hoursWorked' => 0,
		'minutesWorked' => 0,
		'remainingMinutes' => 0,
		'minHoursRequired' => $minHoursBeforeCheckout,
		'message' => '',
		'checkedIn' => false,
		'alreadyCheckedOut' => false,
		'checkInTime' => null,
	];

	$current_date = date('Y-m-d');
	$where = " WHERE EmployeeID = $EmployeeID AND RecordDate = '$current_date'";
	$attendance = _getTableDetails($conn, 'employee_attendance', $where);

	if (empty($attendance) || empty($attendance['InTime'])) {
		$response['message'] = 'You must check in before checking out.';
		return $response;
	}

	$response['checkedIn'] = true;
	$response['checkInTime'] = $attendance['InTime'];

	if (!empty($attendance['OutTime'])) {
		$response['alreadyCheckedOut'] = true;
		$response['message'] = 'You have already checked out for today.';
		return $response;
	}

	$inDateTime = $current_date . ' ' . $attendance['InTime'];
	$secondsWorked = max(0, time() - strtotime($inDateTime));
	$minSeconds = $minHoursBeforeCheckout * 3600;

	$response['hoursWorked'] = round($secondsWorked / 3600, 2);
	$response['minutesWorked'] = (int) floor($secondsWorked / 60);

	if ($secondsWorked < $minSeconds) {
		$remaining = $minSeconds - $secondsWorked;
		$response['remainingMinutes'] = (int) ceil($remaining / 60);
		$response['canCheckout'] = false;
		$response['message'] = 'Punch-out allowed only after ' . $minHoursBeforeCheckout
			. ' hours from punch-in. Please wait '
			. $response['remainingMinutes'] . ' more minute(s).';
		return $response;
	}

	$response['remainingMinutes'] = 0;
	$response['canCheckout'] = true;
	$response['message'] = 'You can check out. Hours so far: ' . $response['hoursWorked'];

	return $response;
}

function parseGpsAccuracyMeters($value)
{
	if ($value === '' || $value === null || !is_numeric($value)) {
		return 0;
	}

	return (float) $value;
}

function validateEmployeeCheckoutApi($conn, $EmployeeID, $Latitude = '', $Longitude = '', $useBranchLocations = true, $gpsAccuracyMeters = 0)
{
	$checkout = getEmployeeCheckoutEligibility($conn, $EmployeeID);
	$policyData = $useBranchLocations
		? getEmployeeAttendanceLocationPolicyWithBranchesApiData($conn, $EmployeeID)
		: getEmployeeAttendanceLocationPolicyApiData($conn, $EmployeeID);

	if ($policyData === null) {
		return [
			'error' => true,
			'message' => 'Employee not found',
			'data' => null,
		];
	}

	if (!$checkout['canCheckout']) {
		$data = array_merge($policyData, [
			'action' => 'checkout',
			'locationCheckRequired' => !empty($policyData['boundaryEnabled']),
			'allowed' => false,
			'canCheckout' => false,
			'hoursWorked' => $checkout['hoursWorked'],
			'minutesWorked' => $checkout['minutesWorked'],
			'checkedIn' => $checkout['checkedIn'],
			'checkInTime' => $checkout['checkInTime'],
		]);

		return [
			'error' => true,
			'message' => $checkout['message'],
			'data' => $data,
		];
	}

	$geofenceAllowed = true;
	$geofenceMessage = $checkout['message'];
	$matchedLocation = null;

	if (!empty($policyData['boundaryEnabled'])) {
		$geofence = $useBranchLocations
			? validateEmployeeAttendanceGeofenceWithBranches($conn, $EmployeeID, $Latitude, $Longitude, 'checkout', $gpsAccuracyMeters)
			: validateEmployeeAttendanceGeofence($conn, $EmployeeID, $Latitude, $Longitude);
		$geofenceAllowed = !empty($geofence['allowed']);
		$geofenceMessage = $geofence['message'] ?? $checkout['message'];
		$matchedLocation = $geofence['matchedLocation'] ?? null;
	}

	$allowed = $geofenceAllowed;

	$data = array_merge($policyData, [
		'action' => 'checkout',
		'locationCheckRequired' => !empty($policyData['boundaryEnabled']),
		'allowed' => $allowed,
		'canCheckout' => $checkout['canCheckout'] && $allowed,
		'hoursWorked' => $checkout['hoursWorked'],
		'minutesWorked' => $checkout['minutesWorked'],
		'checkedIn' => $checkout['checkedIn'],
		'checkInTime' => $checkout['checkInTime'],
		'currentLatitude' => $Latitude,
		'currentLongitude' => $Longitude,
	]);

	if ($matchedLocation !== null) {
		$data['matchedLocation'] = $matchedLocation;
		$data['distanceMeters'] = $matchedLocation['distanceMeters'];
	}

	if ($allowed) {
		$message = !empty($policyData['boundaryEnabled'])
			? ($geofenceMessage ?: 'Location verified. You can check out.')
			: $checkout['message'];
	} else {
		$message = $geofenceMessage ?: 'You are outside all allowed attendance locations.';
	}

	return [
		'error' => !$allowed,
		'message' => $message,
		'data' => $data,
	];
}

function validateEmployeeAttendanceLocationApi($conn, $EmployeeID, $Latitude, $Longitude, $action = 'checkin')
{
	$action = strtolower(trim((string) $action));
	if ($action === 'checkout') {
		return validateEmployeeCheckoutApi($conn, $EmployeeID, $Latitude, $Longitude, true);
	}

	$policyData = getEmployeeAttendanceLocationPolicyApiData($conn, $EmployeeID);
	if ($policyData === null) {
		return [
			'error' => true,
			'message' => 'Employee not found',
			'data' => null,
		];
	}

	$geofence = validateEmployeeAttendanceGeofence($conn, $EmployeeID, $Latitude, $Longitude);
	$allowed = !empty($geofence['allowed']);

	$data = array_merge($policyData, [
		'action' => 'checkin',
		'locationCheckRequired' => !empty($policyData['boundaryEnabled']),
		'allowed' => $allowed,
		'currentLatitude' => $Latitude,
		'currentLongitude' => $Longitude,
	]);

	if (
		$policyData['boundaryEnabled'] && $allowed
		&& is_numeric($Latitude) && is_numeric($Longitude)
		&& $policyData['AttendanceLatitude'] !== null && $policyData['AttendanceLongitude'] !== null
	) {
		$data['distanceMeters'] = round(haversineDistanceMeters(
			(float) $Latitude,
			(float) $Longitude,
			(float) $policyData['AttendanceLatitude'],
			(float) $policyData['AttendanceLongitude']
		));
	}

	if ($allowed) {
		$message = $policyData['boundaryEnabled']
			? 'Location verified. You are within the allowed attendance area.'
			: 'Location boundary is not enabled for this employee.';
		if (!empty($data['distanceMeters'])) {
			$message = 'Location verified (' . $data['distanceMeters'] . ' m from work location).';
		}
	} else {
		$message = $geofence['message'] ?? 'You are outside the allowed attendance area.';
	}

	return [
		'error' => !$allowed,
		'message' => $message,
		'data' => $data,
	];
}

function validateEmployeeAttendanceGeofence($conn, $EmployeeID, $Latitude, $Longitude)
{
	$response = ['allowed' => true, 'message' => ''];
	$employee = getEmployeeData($conn, $EmployeeID);
	if (empty($employee)) {
		$response['allowed'] = false;
		$response['message'] = 'Employee not found';
		return $response;
	}
	if (empty($employee['IsAllowLocationBoundary']) || (int) $employee['IsAllowLocationBoundary'] !== 1) {
		return $response;
	}

	$lat = trim((string) $Latitude);
	$lng = trim((string) $Longitude);
	if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
		$response['allowed'] = false;
		$response['message'] = 'GPS location is required for attendance';
		return $response;
	}

	$workLat = $employee['AttendanceLatitude'] ?? '';
	$workLng = $employee['AttendanceLongitude'] ?? '';
	if ($workLat === '' || $workLng === '' || !is_numeric($workLat) || !is_numeric($workLng)) {
		$response['allowed'] = false;
		$response['message'] = 'Attendance location is not configured for this employee';
		return $response;
	}

	$radius = (int) ($employee['AttendanceRadiusMeters'] ?? 100);
	if ($radius <= 0) {
		$radius = 100;
	}

	$distance = haversineDistanceMeters((float) $lat, (float) $lng, (float) $workLat, (float) $workLng);
	if ($distance > $radius) {
		$response['allowed'] = false;
		$response['message'] = 'You are outside your assigned work location ('
			. round($distance) . ' m away, maximum allowed ' . $radius . ' m)';
		return $response;
	}

	$response['matchedLocation'] = [
		'locationType' => 'employee',
		'BranchID' => null,
		'BranchSite' => 'Employee assigned location',
		'distanceMeters' => round($distance),
		'AttendanceRadiusMeters' => $radius,
		'Latitude' => $workLat,
		'Longitude' => $workLng,
	];
	$response['message'] = 'Location verified at assigned work location ('
		. round($distance) . ' m away).';

	return $response;
}

function getEmployeeAttendanceRadiusMeters($employee)
{
	$radius = (int) ($employee['AttendanceRadiusMeters'] ?? 100);
	if ($radius <= 0) {
		$radius = 100;
	}
	if ($radius > 50000) {
		$radius = 50000;
	}
	return $radius;
}

function getAttendanceGpsAccuracyBufferMeters()
{
	return 25;
}

function getEffectiveAttendanceRadiusMeters($employee, $gpsAccuracyMeters = 0)
{
	$radius = getEmployeeAttendanceRadiusMeters($employee);
	$buffer = getAttendanceGpsAccuracyBufferMeters();
	$accuracy = (int) round(max(0, (float) $gpsAccuracyMeters));
	if ($accuracy > 50) {
		$accuracy = 50;
	}

	return $radius + max($buffer, $accuracy);
}

function getActiveBranchAttendanceLocations($conn)
{
	$where = " WHERE IsActive = 1"
		. " AND Latitude IS NOT NULL AND TRIM(Latitude) != '' AND Latitude != '0'"
		. " AND Longitude IS NOT NULL AND TRIM(Longitude) != '' AND Longitude != '0'"
		. " ORDER BY BranchSite ASC";
	return _getTableRecords($conn, 'branch', $where);
}

function formatBranchAttendanceLocationApiData($branch, $radiusMeters = 100)
{
	$radiusMeters = (int) $radiusMeters;
	if ($radiusMeters <= 0) {
		$radiusMeters = 100;
	}

	return [
		'BranchID' => (int) ($branch['ID'] ?? 0),
		'BranchSite' => $branch['BranchSite'] ?? '',
		'BranchCode' => $branch['BranchCode'] ?? '',
		'Latitude' => $branch['Latitude'] ?? null,
		'Longitude' => $branch['Longitude'] ?? null,
		'BranchCity' => $branch['BranchCity'] ?? '',
		'BranchState' => $branch['BranchState'] ?? '',
		'AttendanceRadiusMeters' => $radiusMeters,
	];
}

function getBranchAttendanceLocationsApiData($conn, $EmployeeID)
{
	$employee = getEmployeeData($conn, $EmployeeID);
	if (empty($employee)) {
		return [];
	}

	$radiusMeters = getEmployeeAttendanceRadiusMeters($employee);
	$branches = getActiveBranchAttendanceLocations($conn);
	$locations = [];

	foreach ($branches as $branch) {
		$lat = trim((string) ($branch['Latitude'] ?? ''));
		$lng = trim((string) ($branch['Longitude'] ?? ''));
		if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
			continue;
		}
		$locations[] = formatBranchAttendanceLocationApiData($branch, $radiusMeters);
	}

	return $locations;
}

function getEmployeeAttendanceLocationPolicyWithBranchesApiData($conn, $EmployeeID)
{
	$policyData = getEmployeeAttendanceLocationPolicyApiData($conn, $EmployeeID);
	if ($policyData === null) {
		return null;
	}

	$employee = getEmployeeData($conn, $EmployeeID);
	if (empty($employee)) {
		return null;
	}

	$policyData['branchLocations'] = getBranchAttendanceLocationsApiData($conn, $EmployeeID);
	$policyData['allowBranchLocations'] = true;
	$hasEmployeeLocation = employeeHasConfiguredAttendanceLocation($employee);
	$policyData['checkinLocationRule'] = $hasEmployeeLocation ? 'employee_or_branches' : 'branches_only';
	$policyData['checkoutLocationRule'] = 'employee_and_branches';
	$policyData['locationSource'] = $hasEmployeeLocation ? 'employee_or_branches' : 'branches_only';

	return $policyData;
}

function employeeHasConfiguredAttendanceLocation($employee)
{
	$workLat = trim((string) ($employee['AttendanceLatitude'] ?? ''));
	$workLng = trim((string) ($employee['AttendanceLongitude'] ?? ''));

	if ($workLat === '' || $workLng === '' || !is_numeric($workLat) || !is_numeric($workLng)) {
		return false;
	}
	if ($workLat === '0' || $workLng === '0' || (float) $workLat == 0.0 || (float) $workLng == 0.0) {
		return false;
	}

	return true;
}

function findNearestAllowedAttendanceLocation($conn, $EmployeeID, $Latitude, $Longitude, $action = 'checkout', $gpsAccuracyMeters = 0)
{
	$employee = getEmployeeData($conn, $EmployeeID);
	if (empty($employee)) {
		return null;
	}

	$hasEmployeeLocation = employeeHasConfiguredAttendanceLocation($employee);
	$includeEmployeeLocation = $hasEmployeeLocation;
	$includeBranchLocations = true;

	$lat = (float) $Latitude;
	$lng = (float) $Longitude;
	$radiusMeters = getEmployeeAttendanceRadiusMeters($employee);
	$effectiveRadiusMeters = getEffectiveAttendanceRadiusMeters($employee, $gpsAccuracyMeters);
	$nearest = null;

	$workLat = trim((string) ($employee['AttendanceLatitude'] ?? ''));
	$workLng = trim((string) ($employee['AttendanceLongitude'] ?? ''));
	if ($includeEmployeeLocation && $hasEmployeeLocation) {
		$distance = haversineDistanceMeters($lat, $lng, (float) $workLat, (float) $workLng);
		if ($distance <= $effectiveRadiusMeters) {
			$nearest = [
				'locationType' => 'employee',
				'BranchID' => null,
				'BranchSite' => 'Employee assigned location',
				'distanceMeters' => round($distance),
				'AttendanceRadiusMeters' => $radiusMeters,
				'Latitude' => $workLat,
				'Longitude' => $workLng,
			];
		}
	}

	if (!$includeBranchLocations) {
		return $nearest;
	}

	$branches = getActiveBranchAttendanceLocations($conn);
	foreach ($branches as $branch) {
		$branchLat = trim((string) ($branch['Latitude'] ?? ''));
		$branchLng = trim((string) ($branch['Longitude'] ?? ''));
		if ($branchLat === '' || $branchLng === '' || !is_numeric($branchLat) || !is_numeric($branchLng)) {
			continue;
		}

		$distance = haversineDistanceMeters($lat, $lng, (float) $branchLat, (float) $branchLng);
		if ($distance > $effectiveRadiusMeters) {
			continue;
		}

		if ($nearest === null || $distance < $nearest['distanceMeters']) {
			$nearest = [
				'locationType' => 'branch',
				'BranchID' => (int) ($branch['ID'] ?? 0),
				'BranchSite' => $branch['BranchSite'] ?? '',
				'distanceMeters' => round($distance),
				'AttendanceRadiusMeters' => $radiusMeters,
				'Latitude' => $branchLat,
				'Longitude' => $branchLng,
			];
		}
	}

	return $nearest;
}

function validateEmployeeAttendanceGeofenceWithBranches($conn, $EmployeeID, $Latitude, $Longitude, $action = 'checkout', $gpsAccuracyMeters = 0)
{
	$response = ['allowed' => true, 'message' => '', 'matchedLocation' => null];
	$employee = getEmployeeData($conn, $EmployeeID);
	if (empty($employee)) {
		$response['allowed'] = false;
		$response['message'] = 'Employee not found';
		return $response;
	}

	if (empty($employee['IsAllowLocationBoundary']) || (int) $employee['IsAllowLocationBoundary'] !== 1) {
		return $response;
	}

	$action = strtolower(trim((string) $action));
	$lat = trim((string) $Latitude);
	$lng = trim((string) $Longitude);
	if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
		$response['allowed'] = false;
		$response['message'] = 'GPS location is required for attendance';
		return $response;
	}

	$hasEmployeeLocation = employeeHasConfiguredAttendanceLocation($employee);
	$includeEmployeeLocation = $hasEmployeeLocation;
	$includeBranchLocations = true;

	$radiusMeters = getEmployeeAttendanceRadiusMeters($employee);
	$matchedLocation = findNearestAllowedAttendanceLocation($conn, $EmployeeID, $lat, $lng, $action, $gpsAccuracyMeters);
	if ($matchedLocation !== null) {
		$response['matchedLocation'] = $matchedLocation;
		if ($matchedLocation['locationType'] === 'branch') {
			$response['message'] = 'Location verified at branch ' . $matchedLocation['BranchSite']
				. ' (' . $matchedLocation['distanceMeters'] . ' m away).';
		} else {
			$response['message'] = 'Location verified at assigned work location ('
				. $matchedLocation['distanceMeters'] . ' m away).';
		}
		return $response;
	}

	$branchCount = count(getActiveBranchAttendanceLocations($conn));
	$workLat = trim((string) ($employee['AttendanceLatitude'] ?? ''));
	$workLng = trim((string) ($employee['AttendanceLongitude'] ?? ''));

	if ($action === 'checkin' && !$hasEmployeeLocation && $branchCount === 0) {
		$response['allowed'] = false;
		$response['message'] = 'No branch attendance locations are configured';
		return $response;
	}

	if (!$hasEmployeeLocation && $branchCount === 0) {
		$response['allowed'] = false;
		$response['message'] = 'No attendance locations are configured for this employee or branches';
		return $response;
	}

	$nearestDistance = null;
	if ($includeEmployeeLocation && $hasEmployeeLocation) {
		$nearestDistance = haversineDistanceMeters((float) $lat, (float) $lng, (float) $workLat, (float) $workLng);
	}

	if ($includeBranchLocations) {
		$branches = getActiveBranchAttendanceLocations($conn);
		foreach ($branches as $branch) {
			$branchLat = trim((string) ($branch['Latitude'] ?? ''));
			$branchLng = trim((string) ($branch['Longitude'] ?? ''));
			if ($branchLat === '' || $branchLng === '' || !is_numeric($branchLat) || !is_numeric($branchLng)) {
				continue;
			}
			$distance = haversineDistanceMeters((float) $lat, (float) $lng, (float) $branchLat, (float) $branchLng);
			if ($nearestDistance === null || $distance < $nearestDistance) {
				$nearestDistance = $distance;
			}
		}
	}

	$nearestDistance = $nearestDistance !== null ? round($nearestDistance) : 0;
	$response['allowed'] = false;
	if ($action === 'checkin' && !$hasEmployeeLocation) {
		$response['message'] = 'You are outside all allowed branch locations ('
			. $nearestDistance . ' m from nearest branch, maximum allowed ' . $radiusMeters . ' m)';
	} else {
		$response['message'] = 'You are outside all allowed attendance locations ('
			. $nearestDistance . ' m from nearest location, maximum allowed ' . $radiusMeters . ' m)';
	}
	return $response;
}

function validateEmployeeAttendanceLocationWithBranchesApi($conn, $EmployeeID, $Latitude, $Longitude, $action = 'checkin', $gpsAccuracyMeters = 0)
{
	$action = strtolower(trim((string) $action));
	if ($action === 'checkout') {
		return validateEmployeeCheckoutApi($conn, $EmployeeID, $Latitude, $Longitude, true, $gpsAccuracyMeters);
	}

	$policyData = getEmployeeAttendanceLocationPolicyWithBranchesApiData($conn, $EmployeeID);
	if ($policyData === null) {
		return [
			'error' => true,
			'message' => 'Employee not found',
			'data' => null,
		];
	}

	$geofence = validateEmployeeAttendanceGeofenceWithBranches($conn, $EmployeeID, $Latitude, $Longitude, 'checkin', $gpsAccuracyMeters);
	$allowed = !empty($geofence['allowed']);

	$data = array_merge($policyData, [
		'action' => 'checkin',
		'locationCheckRequired' => !empty($policyData['boundaryEnabled']),
		'allowed' => $allowed,
		'currentLatitude' => $Latitude,
		'currentLongitude' => $Longitude,
	]);

	if (!empty($geofence['matchedLocation'])) {
		$data['matchedLocation'] = $geofence['matchedLocation'];
		$data['distanceMeters'] = $geofence['matchedLocation']['distanceMeters'];
	}

	if ($allowed) {
		$message = $policyData['boundaryEnabled']
			? ($geofence['message'] ?? 'Location verified. You can check in.')
			: 'Location boundary is not enabled for this employee.';
	} else {
		$message = $geofence['message'] ?? 'You are outside all allowed attendance locations.';
	}

	return [
		'error' => !$allowed,
		'message' => $message,
		'data' => $data,
	];
}
