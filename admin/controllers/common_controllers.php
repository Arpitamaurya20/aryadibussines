<?php
//$servername = "localhost";$dbusername = "root";$password = "";$dbname = "cypherte_aarzoo";
$servername = "";$dbusername = "";$password = "";$dbname = "";
$_URL = "";
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $servername = "localhost";$dbusername = "root";$password = "";$dbname = "aryadibussiness";
    $_URL = "http://localhost";
	define('FRONT_SITE_PATH','http://localhost/Projects/aryadibussines/');
}
else if (strpos($_SERVER['HTTP_HOST'], 'app.techxpertgroup.in') !== false) {
    $servername = "localhost";$dbusername = "aryadibussiness";$password = "NewTechXpert@123!";$dbname = "techxper_techxpertindia";
    $_URL = "http://localhost";
	define('FRONT_SITE_PATH','https://app.techxpertgroup.in/');
}
else
{
	//$servername = "localhost";$dbusername = "cypherte_onepoint";$password = "onepoint123";$dbname = "cypherte_onepoint";
	//$_URL = "https://onepoint.garyglobalsolutions.com/";
	$servername = "localhost";$dbusername = "root";$password = "NewTechXpert@123";$dbname = "aryadibussiness";
	$_URL = "https://techxpertindia.in/";
	define('FRONT_SITE_PATH','https://techxpertindia.in/');
}
//define('FRONT_SITE_PATH','https://onepoint.garyglobalsolutions.com/');
//$servername = "localhost";$dbusername = "rtfcsymy_novologic";$password = "Novologic@123!";$dbname = "rtfcsymy_novologic";
error_reporting(E_ALL);
//$servername = "localhost";$dbusername = "nobologic";$password = "Nobo@123";$dbname = "nobologic";
/*$myfile = fopen("../../logs/logs.txt", "a") or die("Unable to open file!");
$myfile_api = fopen("../../logs/logs_api.txt", "a") or die("Unable to open file!");
function WriteLog($txt)
{
	global $myfile;
	fwrite($myfile, "\n". $txt);
}
function WriteLog_API($txt)
{
	global $myfile_api;
	fwrite($myfile_api, "\n". $txt);
}*/
$_Nav_Dashboard = false;
$_Nav_Services = false;
$_Nav_Bookings = false;
$_Nav_Employees = false;
$_Nav_Configuration = false;
$_Nav_Contact = false;
$_Nav_Site_Setting = false;
$_Nav_main_Services = false;
$_Nav_LocationServices = false;
$_Nav_Corporate = false;
$_Nav_Customer = false;
$_Nav_Corporate_Tickets = false;
$_Nav_Corporate_Branches = false;
$_Nav_Corporate_dashboard = false;
$_Nav_Corporate_Profile = false;
$_Nav_Corporate_Raise_Ticket = false;
$_Nav_Corporate_approval = false;
$_Nav_Corporate_quotation_approval = false;
$_Nav_All_Order = false;
$_Nav_Attendance_List = false;
$_Nav_Attendance_Approval = false;
$_Nav_Attendance_HR_Approval = false;
$_Nav_Leave_HR_Approval = false;
$_Nav_Leave_Approval = false;
$_Nav_Convenience_Approval = false;
$_Nav_Convenience_HR_Approval = false;
$_Nav_Convenience_Finance_Payment = false;
$_Nav_Portal_Notifications = false;
$_Nav_Corporate_users = false;
$_Nav_Employee_Convenience = false;
$_Nav_Account_Tickets = false;
$_Nav_Account_Branch_Tickets = false;
$_Nav_Corporate_Finance_Tickets = false;
$_Nav_Projects = false;
$_Nav_Analytics_Dashboard = false;
$_Nav_PPM_Tickets = false;
$_Nav_Analytics_Daily_Tracker = false;
$_Nav_Corporate_Configuration = false;
$_Nav_Accounts_Dashboard = false;
$_Nav_My_Profile = false;
$_Nav_Site_Visits = false;
$_Nav_My_KPI=false;
$_Nav_PPM_SC=false;
$_Nav_Mail_SC=false;
$_Nav_Tender_RFQ=false;
$_Nav_Ticket_Billing=false;
$_Nav_Corporate_Audit=false;
$_Nav_Corporate_Audit_Portal=false;
$_Nav_Audit_Tickets=false;
$_Nav_HR_Tickets=false;
$_Nav_My_HR_Tickets=false;
$_Nav_My_Employee_Leave=false;
$_Nav_Leave_Mgmt_HR=false;
$_Nav_Employee_Asset_Acknowledgement=false;
$_Nav_My_State_Dashboard=false;
$_Nav_My_Branch_Dashboard=false;
$_Nav_MIS_Report=false;
function _connectodb()
{
	global $dbname;
	global $servername;
	global $dbusername;
	global $password;
	$connect = new mysqli($servername,$dbusername,$password,$dbname);
	if($connect->connect_error)
	{
		print_r("Connection Error: " . $connect->connect_error);
		return false;
	}
	else
	{
		return $connect;
	}
}

function SessionCheck()
{
	@session_start();
	if(isset($_SESSION['pb_username']))
	{
		return $_SESSION['UserType'];
	}
	else
	{
		if(isset($_COOKIE['pb_username']))
		{
			$_SESSION['pb_username'] = $_COOKIE['pb_username'];
			$_SESSION['UserType'] = $_COOKIE['UserType'];
			$roles_array = unserialize($_COOKIE['Roles']);
			$_SESSION['Roles'] = $roles_array;
			return $_SESSION['UserType'];
		}
		else
		{
			if (strpos($_SERVER['REQUEST_URI'],'login') !== false) 
			{
			    
			} 
			else 
			{
			    if(file_exists("../authentication/login.php"))
					header('Location: ../authentication/login.php');
				else
					header('Location:../../authentication/login.php');
			}
				
		}
		return "Error";
	}
}
function setTimeZone()
{
	date_default_timezone_set('Asia/Kolkata');
}

function _InsertTableRecords($conn, $sql)
{
	$response = array();
	$result = mysqli_query($conn, $sql);
	if ($result) {
		$response['message'] = "Data Inserted";
		$response['error'] = false;
		$lastId = mysqli_insert_id($conn);
		$response['last_insert_id'] = $lastId;
	} else {
		$response['sql'] = $sql;
		$response['error'] = true;
		$error = mysqli_error($conn);
		$response['message'] = $error;
		//echo $sql;
		//echo $error;
	}
	return $response;
}

function _debug_InsertTableRecords($conn, $sql)
{
	echo $sql;
	$response = array();
	$result = mysqli_query($conn, $sql);
	if ($result) {
		$response['message'] = "Data Inserted";
		$response['error'] = false;
		$lastId = mysqli_insert_id($conn);
		$response['last_insert_id'] = $lastId;
	} else {
		$response['sql'] = $sql;
		$response['error'] = true;
		$error = mysqli_error($conn);
		$response['message'] = $error;
		//echo $sql;
		//echo $error;
	}
	return $response;
}

function _UpdateTableRecords($conn, $table_name, $query_parameter)
{
	$response = array();
   	$sql = "UPDATE $table_name SET $query_parameter";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		$response['message'] = "Data Updated";
		$response['error'] = false;
	} else {
		$response['sql'] = $sql;
		$response['error'] = true;
		$error = mysqli_error($conn);
		$response['message'] = $error;
		echo $sql;
		echo $error;
	}
	return $response;
}

function delete_identity_filter($conn, $table, $query)
{
	$sql = "Delete from $table $query";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		return true;
	}
	return false;
}

function _getTableRecords($conn, $table_name, $where)
{
	$response = array();
	$sql = "Select * from $table_name $where";
	//echo $sql;
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				array_push($response, $row);
			}
		}
	} else {
		//echo $sql;
	}
	return $response;
}
function _debug_getTableRecords($conn, $table_name, $where)
{
	$response = array();
	$sql = "Select * from $table_name $where";
	echo $sql;
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				array_push($response, $row);
			}
		}
	} else {
		//echo $sql;
	}
	return $response;
}
function _getTableDetails($conn,$table_name, $where)
{
	$row = array();

	$sql = "Select * from $table_name $where";
	$result=mysqli_query($conn,$sql);
	if($result)
		$row = $result->fetch_assoc();
	else
	{
		$error = mysqli_error($conn);
		echo $sql;
		echo $error;
	}
	return $row;
}
function _getTableRecordsassoc($conn, $table_name, $where)
{
	$response = array();
	$sql = "Select * from $table_name $where";
	$result = mysqli_query($conn, $sql);
	if ($result)
	{
		if ($result->num_rows > 0)
		{
			$row = $result->fetch_assoc() ;
			return $row;
		}
	}
	else
	{
		//echo $sql;
	}
	//return $row;
}
function check_unique_identity_filter($conn, $table, $filter)
{
	$sql = "Select * from $table $filter";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			return false;
		}
	}
	return true;
}
function _getTotalRows($conn, $table, $filter)
{
	if ($filter == "") {
		$sql = "Select COUNT(*) as no_count from $table";
	} else {
		$sql = "Select COUNT(*) as no_count from $table $filter";
	}
	//echo $sql;
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return $row['no_count'];
	} else {
		return 0;
	}
}

function _getMaxIdentityValue($conn, $table, $column)
{
	$sql = "Select COALESCE(MAX($column),0) as max_value from $table";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return $row['max_value'];
	} else {
		return 0;
	}
}
function _getMaxIdentityValue_filter($conn, $table, $column, $where_query)
{
	$sql = "Select COALESCE(MAX($column),0) as max_value from $table $where_query";
	$result = mysqli_query($conn, $sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return $row['max_value'];
	} else {
		return 0;
	}
}

function getEmployeeDetailsfromID($conn,$EmployeeID)
{
	$EmployeeID = (int) $EmployeeID;
	if ($EmployeeID <= 0) {
		return array();
	}
	$where = " where ID = $EmployeeID";
	$row = _getTableDetails($conn,'employees',$where);
	return is_array($row) ? $row : array();
}

function generateArraywithKey($data_array)
{
	$array_temp = array();
	foreach($data_array as $data)
	{
		$ID = $data['ID'];
		$array_temp[$ID] = $data;
	}
	return $array_temp;
}

function setNavigation($roles)
{
	global $_Nav_Dashboard;
	global $_Nav_Services;
	global $_Nav_Bookings;
	global $_Nav_Employees;
	global $_Nav_Configuration;
	global $_Nav_Contact;
	global $_Nav_Site_Setting;
	global $_Nav_Corporate;
	global $_Nav_Customer;
	global $_Nav_Corporate_Branches;
	global $_Nav_Corporate_Tickets;
	global $_Nav_Corporate_dashboard;
	global $_Nav_Corporate_Profile;
  global $_Nav_Corporate_Raise_Ticket;
  global $_Nav_Employees_details;
  global $_Nav_Corporate_approval;
  global $_Nav_Corporate_quotation_approval;
  global $_Nav_Corporate_users;
  global $_Nav_All_Order;
  global $_Nav_Attendance_List;
  global $_Nav_Attendance_Approval;
  global $_Nav_Attendance_HR_Approval;
  global $_Nav_Leave_HR_Approval;
  global $_Nav_Leave_Approval;
  global $_Nav_Convenience_Approval;
  global $_Nav_Convenience_HR_Approval;
  global $_Nav_Convenience_Finance_Payment;
  global $_Nav_Portal_Notifications;
  global $_Nav_Employee_Convenience;
  global $_Nav_Account_Tickets;
  global $_Nav_Account_Branch_Tickets;
  global $_Nav_Corporate_Finance_Tickets;
  global $_Nav_Projects;
  global $_Nav_Analytics_Dashboard;
  global $_Nav_PPM_Tickets;
  global $_Nav_Rate_Card;
  global $_Nav_Analytics_Daily_Tracker;
  global $_Nav_Corporate_Configuration;
  global $_Nav_Accounts_Dashboard;
  global $_Nav_All_Assets;
  global $_Nav_My_Profile;
  global $_Nav_Site_Visits;
  global $_Nav_My_KPI;
	global $_Nav_PPM_SC;
	global $_Nav_Mail_SC;
	global $_Nav_Tender_RFQ;
	global $_Nav_Ticket_Billing;
	global $_Nav_Corporate_Audit;
	global $_Nav_Corporate_Audit_Portal;
	global $_Nav_Audit_Tickets;
	global $_Nav_HR_Tickets;
	global $_Nav_My_HR_Tickets;
	global $_Nav_My_Employee_Leave;
	global $_Nav_Leave_Mgmt_HR;
	global $_Nav_Employee_Asset_Acknowledgement;
	global $_Nav_My_State_Dashboard;
	global $_Nav_My_Branch_Dashboard;
	global $_Nav_MIS_Report;
	if(!is_array($roles))
	{
		$roles = array();
	}
	$EmployeeRoles = isset($roles['EmployeeRoles']) && is_array($roles['EmployeeRoles']) ? $roles['EmployeeRoles'] : array();
	if(empty($EmployeeRoles) && isset($_SESSION['UserType']) && $_SESSION['UserType'] != "")
	{
		$EmployeeRoles = array($_SESSION['UserType']);
	}
	$navJsonFile = __DIR__ . '/../navigation/roles_navigation.json';
	$json = @file_get_contents($navJsonFile);
	$nav_root = json_decode($json);
	if (!is_object($nav_root)) {
		$nav_root = new stdClass();
	}
	$nav_array = get_object_vars($nav_root);
	foreach($EmployeeRoles as $role)
	{
		if ($role === 'State Corporate Lead') {
			$_Nav_My_State_Dashboard = true;
		}
		if ($role === 'Branch Account Manager') {
			$_Nav_My_Branch_Dashboard = true;
		}
		if (!isset($nav_array[$role]) || !is_array($nav_array[$role])) {
			continue;
		}
		$temp_nav_array = $nav_array[$role];
		//print_r($temp_nav_array);
		if(in_array("_Nav_Dashboard",$temp_nav_array))
			$_Nav_Dashboard = true;
		if(in_array("_Nav_Analytics_Dashboard",$temp_nav_array))
			$_Nav_Analytics_Dashboard = true;
		if(in_array("_Nav_MIS_Report",$temp_nav_array))
			$_Nav_MIS_Report = true;
		if(in_array("_Nav_Services",$temp_nav_array))
			$_Nav_Services = true;
		if(in_array("_Nav_Bookings",$temp_nav_array))
			$_Nav_Bookings = true;
		if(in_array("_Nav_Employees",$temp_nav_array))
			$_Nav_Employees = true;
		if(in_array("_Nav_Configuration",$temp_nav_array))
			$_Nav_Configuration = true;
		if(in_array("_Nav_Contact",$temp_nav_array))
			$_Nav_Contact = true;
		if(in_array("_Nav_Site_Setting",$temp_nav_array))
			$_Nav_Site_Setting = true;
		if(in_array("_Nav_Corporate",$temp_nav_array))
			$_Nav_Corporate = true;
		if(in_array("_Nav_Customer",$temp_nav_array))
			$_Nav_Customer = true;
		if(in_array("_Nav_Corporate_Branches",$temp_nav_array))
			$_Nav_Corporate_Branches = true;
		if(in_array("_Nav_Corporate_Tickets",$temp_nav_array))
			$_Nav_Corporate_Tickets = true;
		if(in_array("_Nav_Corporate_dashboard",$temp_nav_array))
			$_Nav_Corporate_dashboard = true;
		if(in_array("_Nav_Corporate_Profile",$temp_nav_array))
			$_Nav_Corporate_Profile = true;
		if(in_array("_Nav_Corporate_Raise_Ticket",$temp_nav_array))
			$_Nav_Corporate_Raise_Ticket = true;
		if(in_array("_Nav_Employees_details",$temp_nav_array))
			$_Nav_Employees_details = true;
		if(in_array("_Nav_Corporate_approval",$temp_nav_array))
			$_Nav_Corporate_approval = true;
		if(in_array("_Nav_Corporate_quotation_approval",$temp_nav_array))
			$_Nav_Corporate_quotation_approval = true;
		if(in_array("_Nav_All_Order",$temp_nav_array))
			$_Nav_All_Order = true;
		if(in_array("_Nav_Attendance_List",$temp_nav_array))
			$_Nav_Attendance_List = true;
		if(in_array("_Nav_Attendance_Approval",$temp_nav_array))
			$_Nav_Attendance_Approval = true;
		if(in_array("_Nav_Attendance_HR_Approval",$temp_nav_array))
			$_Nav_Attendance_HR_Approval = true;
		if(in_array("_Nav_Leave_HR_Approval",$temp_nav_array))
			$_Nav_Leave_HR_Approval = true;
		if(in_array("_Nav_Leave_Approval",$temp_nav_array))
			$_Nav_Leave_Approval = true;
		if(in_array("_Nav_Convenience_Approval",$temp_nav_array))
			$_Nav_Convenience_Approval = true;
		if(in_array("_Nav_Convenience_HR_Approval",$temp_nav_array))
			$_Nav_Convenience_HR_Approval = true;
		if(in_array("_Nav_Convenience_Finance_Payment",$temp_nav_array))
			$_Nav_Convenience_Finance_Payment = true;
		if(in_array("_Nav_Portal_Notifications",$temp_nav_array))
			$_Nav_Portal_Notifications = true;
		if(in_array("_Nav_Corporate_users",$temp_nav_array))
			$_Nav_Corporate_users = true;
		if(in_array("_Nav_Employee_Convenience",$temp_nav_array))
			$_Nav_Employee_Convenience = true;
		if(in_array("_Nav_Account_Tickets",$temp_nav_array))
			$_Nav_Account_Tickets = true;
		if(in_array("_Nav_Account_Branch_Tickets",$temp_nav_array))
			$_Nav_Account_Branch_Tickets = true;
		if(in_array("_Nav_Corporate_Finance_Tickets",$temp_nav_array))
			$_Nav_Corporate_Finance_Tickets = true;
		if(in_array("_Nav_Projects",$temp_nav_array))
			$_Nav_Projects = true;
		if(in_array("_Nav_PPM_Tickets",$temp_nav_array))
			$_Nav_PPM_Tickets = true;
		if(in_array("_Nav_Rate_Card",$temp_nav_array))
			$_Nav_Rate_Card = true;
		if(in_array("_Nav_Analytics_Daily_Tracker",$temp_nav_array))
			$_Nav_Analytics_Daily_Tracker = true;
		if(in_array("_Nav_Corporate_Configuration",$temp_nav_array))
			$_Nav_Corporate_Configuration = true;
		if(in_array("_Nav_Accounts_Dashboard",$temp_nav_array))
			$_Nav_Accounts_Dashboard = true;
		if(in_array("_Nav_All_Assets",$temp_nav_array))
			$_Nav_All_Assets = true;
		if(in_array("_Nav_My_Profile",$temp_nav_array))
			$_Nav_My_Profile = true;
		if(in_array("_Nav_Site_Visits",$temp_nav_array))
			$_Nav_Site_Visits = true;
		if(in_array("_Nav_My_KPI",$temp_nav_array))
			$_Nav_My_KPI = true;
		if(in_array("_Nav_PPM_SC",$temp_nav_array))
			$_Nav_PPM_SC = true;
		if(in_array("_Nav_Mail_SC",$temp_nav_array))
			$_Nav_Mail_SC = true;
		if(in_array("_Nav_Tender_RFQ",$temp_nav_array))
			$_Nav_Tender_RFQ = true;
		if(in_array("_Nav_Ticket_Billing",$temp_nav_array))
			$_Nav_Ticket_Billing = true;
		if(in_array("_Nav_Corporate_Audit",$temp_nav_array))
			$_Nav_Corporate_Audit = true;
		if(in_array("_Nav_Corporate_Audit_Portal",$temp_nav_array))
			$_Nav_Corporate_Audit_Portal = true;
		if(in_array("_Nav_Audit_Tickets",$temp_nav_array))
			$_Nav_Audit_Tickets = true;
		if(in_array("_Nav_HR_Tickets",$temp_nav_array))
			$_Nav_HR_Tickets = true;
		if(in_array("_Nav_My_HR_Tickets",$temp_nav_array))
			$_Nav_My_HR_Tickets = true;
		if(in_array("_Nav_My_Employee_Leave",$temp_nav_array))
			$_Nav_My_Employee_Leave = true;
		if(in_array("_Nav_Leave_Mgmt_HR",$temp_nav_array))
			$_Nav_Leave_Mgmt_HR = true;
		if(in_array("_Nav_Employee_Asset_Acknowledgement",$temp_nav_array))
			$_Nav_Employee_Asset_Acknowledgement = true;

	}

	enableAttendanceApprovalNavForSupervisor($roles);
	enableLeaveApprovalNavForSupervisor($roles);
	enableConvenienceApprovalNavForSupervisor($roles);
	enableConvenienceHrApprovalNavForHr($roles);
	enableConvenienceFinancePaymentNavForFinance($roles);
	enableEmployeeAssetAckNavForAccess($roles);
	enableMyHrTicketsNavForEmployee($roles);
	enableMyEmployeeLeaveNavForEmployee($roles);
	enableLeaveMgmtHrNavForHr($roles);

	if (!$_Nav_My_State_Dashboard && isset($roles['EmployeeID']) && (int)$roles['EmployeeID'] > 0) {
		$conn = _connectodb();
		if ($conn) {
			if (!class_exists('State')) {
				require_once __DIR__ . '/../includes/autoloader.inc.php';
			}
			$stateNavObj = new State($conn);
			if ($stateNavObj->employeeHasStateScope((int)$roles['EmployeeID'])) {
				$_Nav_My_State_Dashboard = true;
			}
		}
	}

	if ($_Nav_My_State_Dashboard) {
		$_Nav_My_Branch_Dashboard = false;
	} elseif (!$_Nav_My_Branch_Dashboard && isset($roles['EmployeeID']) && (int)$roles['EmployeeID'] > 0) {
		require_once __DIR__ . '/../dashboard/inc/state_dashboard_scope.php';
		$miniSession = ['Roles' => $roles];
		if (manager_dashboard_user_is_branch_account_manager($miniSession)) {
			$_Nav_My_Branch_Dashboard = true;
		}
	}
}

function enableLeaveApprovalNavForSupervisor($roles)
{
	global $_Nav_Leave_Approval;
	if ($_Nav_Leave_Approval) {
		return;
	}
	if (!isset($roles['EmployeeID'])) {
		return;
	}
	$employee_id = (int) $roles['EmployeeID'];
	if ($employee_id <= 0) {
		return;
	}
	require_once __DIR__ . '/../attendance-list/controller/attendance_controller.php';
	$conn = _connectodb();
	if ($conn && employeeHasSupervisedTeam($conn, $employee_id)) {
		$_Nav_Leave_Approval = true;
	}
}

function enableAttendanceApprovalNavForSupervisor($roles)
{
	global $_Nav_Attendance_Approval;
	if ($_Nav_Attendance_Approval) {
		return;
	}
	if (!isset($roles['EmployeeID'])) {
		return;
	}
	$employee_id = (int) $roles['EmployeeID'];
	if ($employee_id <= 0) {
		return;
	}
	require_once __DIR__ . '/../attendance-list/controller/attendance_controller.php';
	$conn = _connectodb();
	if ($conn && employeeHasSupervisedTeam($conn, $employee_id)) {
		$_Nav_Attendance_Approval = true;
	}
}

function enableConvenienceApprovalNavForSupervisor($roles)
{
	global $_Nav_Convenience_Approval;
	if ($_Nav_Convenience_Approval) {
		return;
	}
	if (!isset($roles['EmployeeID'])) {
		return;
	}
	$employee_id = (int) $roles['EmployeeID'];
	if ($employee_id <= 0) {
		return;
	}
	require_once __DIR__ . '/../employees-convenience/controller/convenience_controller.php';
	$conn = _connectodb();
	if ($conn && employeeHasConvenienceTeam($conn, $employee_id)) {
		$_Nav_Convenience_Approval = true;
	}
}

function enableConvenienceHrApprovalNavForHr($roles)
{
	global $_Nav_Convenience_HR_Approval;
	if ($_Nav_Convenience_HR_Approval) {
		return;
	}
	require_once __DIR__ . '/../employees-convenience/controller/convenience_controller.php';
	if (hasHrConvenienceApprovalAccess($roles)) {
		$_Nav_Convenience_HR_Approval = true;
	}
}

function enableConvenienceFinancePaymentNavForFinance($roles)
{
	global $_Nav_Convenience_Finance_Payment;
	if ($_Nav_Convenience_Finance_Payment) {
		return;
	}
	require_once __DIR__ . '/../employees-convenience/controller/convenience_controller.php';
	if (hasFinanceConveniencePaymentAccess($roles)) {
		$_Nav_Convenience_Finance_Payment = true;
	}
}

function enableEmployeeAssetAckNavForAccess($roles)
{
	global $_Nav_Employee_Asset_Acknowledgement;
	if ($_Nav_Employee_Asset_Acknowledgement) {
		return;
	}
	require_once __DIR__ . '/../employee-asset-acknowledgement/controller/employee_asset_acknowledgement_controller.php';
	if (hasEmployeeAssetAckAdminAccess($roles)) {
		$_Nav_Employee_Asset_Acknowledgement = true;
	}
}

function enableMyHrTicketsNavForEmployee($roles)
{
	global $_Nav_My_HR_Tickets;
	if ($_Nav_My_HR_Tickets) {
		return;
	}
	if (isset($roles['EmployeeID']) && (int) $roles['EmployeeID'] > 0) {
		$_Nav_My_HR_Tickets = true;
	}
}

function enableMyEmployeeLeaveNavForEmployee($roles)
{
	global $_Nav_My_Employee_Leave;
	if ($_Nav_My_Employee_Leave) {
		return;
	}
	if (isset($roles['EmployeeID']) && (int) $roles['EmployeeID'] > 0) {
		$_Nav_My_Employee_Leave = true;
	}
}

function enableLeaveMgmtHrNavForHr($roles)
{
	global $_Nav_Leave_Mgmt_HR;
	if ($_Nav_Leave_Mgmt_HR) {
		return;
	}
	require_once __DIR__ . '/../employee-leave-mgmt/controller/employee_leave_mgmt_controller.php';
	if (hasElmHrAccess($roles)) {
		$_Nav_Leave_Mgmt_HR = true;
	}
}

function sendWhatsAppMessage($phonenumber,$message)
{
  $phonenumber = preg_replace('/\D/', '', (string) $phonenumber);
  if (strlen($phonenumber) < 10) {
    return;
  }
  $params=array(
  'token' => '48y5d4l930we57ya',
  'to' => $phonenumber,
  //'to' => '+918826789578',
  'body' => $message
  );
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.ultramsg.com/instance32275/messages/chat",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 2,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => http_build_query($params),
    CURLOPT_HTTPHEADER => array(
      "content-type: application/x-www-form-urlencoded"
    ),
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);

  if ($err) {
    //echo "cURL Error #:" . $err;
  } else {
    //echo $response;
  }

  /*$params=array(
  'token' => '4om3scny22qoxw9e',
  'to' => "+919289787603",
  'body' => $message
  );
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.ultramsg.com/instance32275/messages/chat",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => http_build_query($params),
    CURLOPT_HTTPHEADER => array(
      "content-type: application/x-www-form-urlencoded"
    ),
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);

  if ($err) {
    //echo "cURL Error #:" . $err;
  } else {
    //echo $response;
  }*/
}
function _interakt_sendWhatsAppMessage_common($data)
{
  $curl = curl_init();
	$phonenumber = $data['phonenumber'];
	$template = $data['template'];
	$body_values = $data['body_values'];
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>'{
		"countryCode": "+91",
		"phoneNumber": "'.$phonenumber.'",
		"type": "Template",
		"template": {
		"name": "'.$template.'", 
		"languageCode": "en", 
		"bodyValues": '.$body_values.'
		}
	  }',
	  CURLOPT_HTTPHEADER => array(
	    'Authorization: Basic TVZPai1Sb3VRbE9fN3ltYWxNOTlRTWxFd0kwckt2NmFBak4zSUNEQnRaczo=',
	    'Content-Type: application/json'
	  ),
	));
	//echo $body_values;
	$response = curl_exec($curl);
	//var_dump($response);
	curl_close($curl);

}
function _interakt_sendWhatsAppMessage($data)
{
  $curl = curl_init();
	$phonenumber = $data['phonenumber'];
	$otp = $data['otp'];
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>'{
		"countryCode": "+91",
		"phoneNumber": "'.$phonenumber.'",
		"type": "Template",
		"template": {
		"name": "login_otp", 
		"languageCode": "en", 
		"bodyValues": [
		"'.$otp.'"
		]
		}
	  }',
	  CURLOPT_HTTPHEADER => array(
	    'Authorization: Basic TVZPai1Sb3VRbE9fN3ltYWxNOTlRTWxFd0kwckt2NmFBak4zSUNEQnRaczo=',
	    'Content-Type: application/json'
	  ),
	));

	$response = curl_exec($curl);

	curl_close($curl);

}

function sendMailRequest($postdata)
{
	$resource = "https://techxpertindia.in/admin/mail/send-email-api.php";


	$postdata = json_encode($postdata);
	$ch = curl_init($resource);
	curl_setopt($ch, CURLOPT_URL, $resource);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_USERAGENT, 'api');
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch,  CURLOPT_RETURNTRANSFER, false);
	curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
	curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	curl_exec($ch);
	curl_close($ch);
}
function sendServiceBookingMailRequest($postdata)
{
	$corePath = dirname(__FILE__) . '/../mail/include/service-booking-mail-core.php';
	if (!is_readable($corePath)) {
		return;
	}
	require_once $corePath;
	@sendServiceBookingMail($postdata);
}

function sendInnovMailRequest($postdata)
{
	$resource = "https://techxpertindia.in/admin/mail/send-email-api-innov.php";

	// $resource = "http://localhost/Projects/techxpertindia/admin/mail/send-email-api-innov.php";
	$postdata = json_encode($postdata);
	
	$ch = curl_init($resource);
	curl_setopt($ch, CURLOPT_URL, $resource);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_USERAGENT, 'api');
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch,  CURLOPT_RETURNTRANSFER, false);
	curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
	curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	curl_exec($ch);
	curl_close($ch);

	
}
function getDivisionInitials($conn,$data)
{
    $Division = $data;
	// Get initials
	$where = " where Division = '$Division' and IsActive = 1";
	$division_row = _getTableDetails($conn,'employee_divisions', $where);
	$Initials = $division_row['Initials'];
}
/** Corporate company admin login — match by current UserName. */
function UpdateUserName($conn,$newusername,$oldusername)
{
	$oldusername = mysqli_real_escape_string($conn, trim((string) $oldusername));
	$newusername = mysqli_real_escape_string($conn, trim((string) $newusername));
	$query_parameter = " UserName = '$newusername' where UserName = '$oldusername'";
	return _UpdateTableRecords($conn, 'users', $query_parameter);
}

/**
 * Branch site login: UPDATE if Corporate Branch User exists for BranchID, else INSERT.
 * Optional $passwordPlain updates password on update or sets it on insert.
 */
function UpdateCorporateBranchSiteUser($conn, $newusername, $branchId, $corporateId, $passwordPlain = '')
{
	$branchId = (int) $branchId;
	$corporateId = (int) $corporateId;
	$newusername = mysqli_real_escape_string($conn, trim((string) $newusername));
	$CreatedDate = date('Y-m-d');
	$CreatedTime = date('H:i:s');
	$user_type = 'Corporate Branch User';

	$where = " where BranchID = $branchId and UserType = '$user_type'";
	$existing = _getTableDetails($conn, 'users', $where);

	if (!empty($existing) && isset($existing['UserID'])) {
		$set = " UserName = '$newusername'";
		if ((string) $passwordPlain !== '') {
			$pwd = mysqli_real_escape_string($conn, md5((string) $passwordPlain));
			$set .= ", Password = '$pwd'";
		}
		return _UpdateTableRecords($conn, 'users', $set . " WHERE BranchID = $branchId AND UserType = '$user_type'");
	}

	$pwd_plain = (string) $passwordPlain !== '' ? (string) $passwordPlain : bin2hex(random_bytes(8));
	$pwd = mysqli_real_escape_string($conn, md5($pwd_plain));
	$sql = "INSERT INTO users (UserName,Password,UserType,EmployeeID,CorporateID,BranchID,CreatedDate,CreatedTime) VALUES('$newusername','$pwd','$user_type','-1',$corporateId,$branchId,'$CreatedDate','$CreatedTime')";
	return _InsertTableRecords($conn, $sql);
}

function cleantext($str)
{
	$str = addslashes($str);
	$str = trim($str);
	return $str;
}
function clean_datatable_text($str)
{
    // Define a regular expression pattern to match non-alphanumeric characters
    $pattern = '/[^a-zA-Z0-9& ]/';
    // Use the preg_replace function to remove illegal characters
    $cleanString = preg_replace($pattern, '', $str);
    return $cleanString;
}
function echoValue($val)
{
	if(isset($val))
		echo $val;
	else
		echo "Not Set";
}

function GenerateTempImageID($conn)
{
	$TempImageID = _getMaxIdentityValue($conn,'temp_capture_image','TempImageID');
	return $TempImageID;
}

function CheckRole($data,$Role_to_be_checked)
{
    if(isset($data['Roles']['EmployeeRoles']) && is_array($data['Roles']['EmployeeRoles']))
    {
        foreach($data['Roles']['EmployeeRoles'] as $Role)
        {
            if($Role == $Role_to_be_checked)
            {
                return true;
            }
        }
    }
    return false;
}

/**
 * State manager access: State Corporate Lead role or mapped as StateCorporateHead on a state.
 */
function userHasStateCorporateLeadAccess($session)
{
    if (CheckRole($session, 'State Corporate Lead')) {
        return true;
    }
    if (!isset($session['Roles']['EmployeeID'])) {
        return false;
    }
    $employeeId = (int)$session['Roles']['EmployeeID'];
    if ($employeeId <= 0) {
        return false;
    }
    static $scopeCache = array();
    if (isset($scopeCache[$employeeId])) {
        return $scopeCache[$employeeId];
    }
    $conn = _connectodb();
    if (!$conn) {
        return false;
    }
    if (!class_exists('State')) {
        require_once __DIR__ . '/../includes/autoloader.inc.php';
    }
    $stateObject = new State($conn);
    $scopeCache[$employeeId] = $stateObject->employeeHasStateScope($employeeId);
    return $scopeCache[$employeeId];
}


 function _getSQLDetails($conn, $sql)
	{
		$response = array();
		$result = mysqli_query($conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				$response = $row = $result->fetch_assoc();
			}
		} else {
			//echo $sql;
		}
		return $response;
	}

	function _getSQLRecords($conn, $sql)
	{
		$response = array();
		$result = mysqli_query($conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			// echo $sql;
		}
		return $response;
	}


	function updatecaputesignature($conn,$ID,$TicketID)
   {
	 $newvalue="DeleteImage";
	$query_parameter = "Action = '$newvalue', IsActive='0' where ID = '$ID' And TicketID='$TicketID'";
	return _UpdateTableRecords($conn, 'ticket_media', $query_parameter);
   }

  function updatecaputesignatureppm($conn, $ID, $TicketID)
{
    // Mark TicketID as deleted
    $newTicketID = '-1' . $TicketID;

    $query_parameter = "
        TicketID = '$newTicketID'
        WHERE ID = '$ID'
        AND TicketID = '$TicketID'
    ";

    return _UpdateTableRecords($conn, 'ppm_ticket_media', $query_parameter);
}

  function _InsertTableRecords_prepare($conn, $tableName, $data)
	{
		$response = array();
		 // Build the columns and placeholders strings dynamically
	    $columns = implode(", ", array_keys($data));
	    $placeholders = implode(", ", array_fill(0, count($data), '?'));

	    // Prepare the SQL statement
	    $sql = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
	    $stmt = $conn->prepare($sql);

	    if ($stmt === false) {
	        die("Error preparing statement: " . $conn->error);
	    }

	    // Bind the parameters dynamically
	    $types = str_repeat('s', count($data)); // Assuming all parameters are strings; adjust as needed
	    $stmt->bind_param($types, ...array_values($data));

	    // Execute the statement
	    if (!$stmt->execute()) 
	    {
	    	$response['error'] = true;
	    	$response['message'] = $stmt->error;
	    }
	    else
	    {
	    	$response['error'] = false;
	    	$response['message'] = "Data Inserted";
	    	$response['last_insert_id'] = $conn->insert_id;
	    }

	    $stmt->close();
	    return $response;
	}


	function _UpdateTableRecords_prepare($conn, $tableName, $data, $where)
	{
	    $response = array();

	    // Build the columns and placeholders strings dynamically for SET clause
	    $setParts = [];
	    foreach ($data as $column => $value) {
	        $setParts[] = "$column = ?";
	    }
	    $setClause = implode(", ", $setParts);

	    // Build the WHERE clause dynamically
	    $whereParts = [];
	    foreach ($where as $column => $value) {
	        $whereParts[] = "$column = ?";
	    }
	    $whereClause = implode(" AND ", $whereParts);

	    // Prepare the SQL statement
	    $sql = "UPDATE $tableName SET $setClause WHERE $whereClause";
	    $stmt = $conn->prepare($sql);

	    if ($stmt === false) {
	        die("Error preparing statement: " . $conn->error);
	    }

	    // Bind the parameters dynamically
	    $types = str_repeat('s', count($data) + count($where)); // Assuming all parameters are strings; adjust as needed
	    $params = array_merge(array_values($data), array_values($where));
	    $stmt->bind_param($types, ...$params);

	    // Execute the statement
	    if (!$stmt->execute()) 
	    {
	        $response['error'] = true;
	        $response['message'] = $stmt->error;
	    }
	    else
	    {
	        $response['error'] = false;
	        $response['message'] = "Data Updated";
	        $response['affected_rows'] = $stmt->affected_rows;
	    }

	    $stmt->close();
	    return $response;
	}


	function sendMailRequestRaisedTicket($postdata)
{
    $resource = "https://techxpertindia.in/admin/mail/send-email-api-raisedticket.php";

    $jsonData = json_encode($postdata);

    $ch = curl_init($resource);

    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => $jsonData,
        CURLOPT_HTTPHEADER      => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 10,
        CURLOPT_CONNECTTIMEOUT  => 5,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'status' => false,
            'error'  => $error
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status'    => true,
        'httpCode'  => $httpCode,
        'response'  => json_decode($response, true)
    ];
}

?>