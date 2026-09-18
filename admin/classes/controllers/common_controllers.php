<?php
//$servername = "localhost";$dbusername = "root";$password = "";$dbname = "cypherte_aarzoo";
$servername = "";$dbusername = "";$password = "";$dbname = "";
$_URL = "";
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $servername = "localhost";$dbusername = "root";$password = "";$dbname = "techxpertindia";
    $_URL = "http://localhost";
	define('FRONT_SITE_PATH','http://localhost/Projects/techxpertindia/');
}
else if (strpos($_SERVER['HTTP_HOST'], 'techxpertgroup.in') !== false) {
    $servername = "localhost";$dbusername = "techxper_techxpertindia";$password = "TechXpert@123!";$dbname = "techxper_techxpertindia";
    $_URL = "http://localhost";
	define('FRONT_SITE_PATH','https://app.techxpertgroup.in/');
}
else
{
	//$servername = "localhost";$dbusername = "cypherte_onepoint";$password = "onepoint123";$dbname = "cypherte_onepoint";
	//$_URL = "https://onepoint.garyglobalsolutions.com/";
	$servername = "localhost";$dbusername = "root";$password = "TechXpert@123";$dbname = "techxpertindia";
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
	$where = " where ID = $EmployeeID";
	return _getTableDetails($conn,'employees',$where);
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
	$EmployeeRoles = $roles['EmployeeRoles'];
	$json = file_get_contents('../navigation/roles_navigation.json');
	$nav_array = json_decode($json);
	$nav_array = get_object_vars($nav_array);
	foreach($EmployeeRoles as $role)
	{
		
		$temp_nav_array = $nav_array[$role];
		//print_r($temp_nav_array);
		if(in_array("_Nav_Dashboard",$temp_nav_array))
			$_Nav_Dashboard = true;
		if(in_array("_Nav_Analytics_Dashboard",$temp_nav_array))
			$_Nav_Analytics_Dashboard = true;
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

	}
}
function sendWhatsAppMessage($phonenumber,$message)
{
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
function UpdateUserName($conn,$newusername,$oldusername)
{
	$query_parameter = " UserName = '$newusername' where BranchID = '$oldusername'";
	return _UpdateTableRecords($conn, 'users', $query_parameter);
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
    if(isset($data['UserType']))
    {
        $UserType = $data['UserType'];
        if($UserType == "Employee")
        {
            if(isset($data['Roles']['EmployeeRoles']))
            {
                $EmployeeRoles = $data['Roles']['EmployeeRoles'];
                foreach($EmployeeRoles as $Role)
                {
                    if($Role == $Role_to_be_checked)
                    {
                        return true;
                    }
                }
            }
        }
    }
    return false;
}


function updatecaputesignature($conn,$ID,$TicketID)
{
	 $newvalue="DeleteImage";
	$query_parameter = "Action = '$newvalue' where ID = '$ID' And TicketID='$TicketID'";
	return _UpdateTableRecords($conn, 'ticket_media', $query_parameter);
}

function updatecaputesignatureppm($conn,$ID,$TicketID)
{
	 $newvalue="DeleteImage";
	$query_parameter = "TicketID = '-1' where ID = '$ID' And TicketID='$TicketID'";
	return _UpdateTableRecords($conn, 'ppm_ticket_media', $query_parameter);
}

?>