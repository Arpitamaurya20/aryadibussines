<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/employees/controller/employee_controller.php');

$response = array();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
if (!is_array($data)) {
	$data = array();
}
if (!empty($_POST) && is_array($_POST)) {
	$data = array_merge($_POST, $data);
}
if (!empty($_GET) && is_array($_GET)) {
	$data = array_merge($_GET, $data);
}

$employeeId = 0;
foreach (array('EmployeeID', 'employee_id', 'employeeId', 'ID', 'id') as $key) {
	if (isset($data[$key]) && $data[$key] !== '' && is_numeric($data[$key])) {
		$employeeId = (int) $data[$key];
		break;
	}
}

if ($employeeId <= 0) {
	$response['error'] = true;
	$response['message'] = 'Missing User Fields';
	echo json_encode($response);
	exit;
}

$conn = _connectodb();
$employee = getEmployeeData($conn, $employeeId);
if (!is_array($employee) || empty($employee['ID'])) {
	$response['error'] = true;
	$response['message'] = 'Employee not found';
	echo json_encode($response);
	exit;
}

$supervisorName = '';
$supervisorId = isset($employee['Supervisor']) ? $employee['Supervisor'] : '';
if ($supervisorId !== '' && $supervisorId !== null && (int) $supervisorId > 0) {
	$supervisor = getEmployeeSupervisorData($conn, $supervisorId);
	if (is_array($supervisor) && !empty($supervisor['Name'])) {
		$supervisorName = $supervisor['Name'];
	}
}
$employee['SupervisorName'] = $supervisorName;

$roles = array();
$roleRows = getEmployeeRole($conn, $employeeId);
if (is_array($roleRows)) {
	foreach ($roleRows as $row) {
		if (is_array($row) && !empty($row['Role'])) {
			$roles[] = $row['Role'];
		}
	}
}

$divisions = array();
$divisionRows = getEmployeeDivision($conn, $employeeId);
if (is_array($divisionRows)) {
	foreach ($divisionRows as $row) {
		if (is_array($row) && !empty($row['Division'])) {
			$divisions[] = $row['Division'];
		}
	}
}

$username = '';
$access = getAccessDetails($conn, $employeeId);
if (is_array($access) && !empty($access['UserName'])) {
	$username = $access['UserName'];
}

$profileImage = profile_media_url(isset($employee['ProfileImage']) ? $employee['ProfileImage'] : '');
$panImage = profile_media_url(isset($employee['PANImage']) ? $employee['PANImage'] : '');
$aadharImage = profile_media_url(isset($employee['AadharImage']) ? $employee['AadharImage'] : '');
$policeImage = profile_media_url(isset($employee['PoliceVerificationImage']) ? $employee['PoliceVerificationImage'] : '');

$response['error'] = false;
$response['message'] = 'Profile loaded';
$response['data'] = $employee;
$response['profile'] = array(
	'employee_id' => (int) $employee['ID'],
	'username' => $username,
	'name' => profile_text(isset($employee['Name']) ? $employee['Name'] : ''),
	'designation' => profile_text(isset($employee['Designation']) ? $employee['Designation'] : ''),
	'photo_url' => $profileImage,
	'roles' => $roles,
	'divisions' => $divisions,
	'supervisor_id' => ($supervisorId !== '' && (int) $supervisorId > 0) ? (int) $supervisorId : 0,
	'supervisor_name' => $supervisorName,
	'personal' => array(
		'father_name' => profile_text(isset($employee['FatherName']) ? $employee['FatherName'] : ''),
		'gender' => profile_text(isset($employee['Gender']) ? $employee['Gender'] : ''),
	),
	'employment' => array(
		'employee_number' => profile_text(isset($employee['EmployeeNumber']) ? $employee['EmployeeNumber'] : ''),
		'date_of_joining' => profile_text(isset($employee['DateofJoining']) ? $employee['DateofJoining'] : ''),
		'department' => profile_text(isset($employee['Department']) ? $employee['Department'] : ''),
		'weekly_off' => profile_text(isset($employee['WeeklyOff']) ? $employee['WeeklyOff'] : ''),
		'city' => profile_text(isset($employee['City']) ? $employee['City'] : ''),
		'state' => profile_text(isset($employee['State']) ? $employee['State'] : ''),
	),
	'contact' => array(
		'official_email' => profile_text(isset($employee['Email']) ? $employee['Email'] : ''),
		'personal_email' => profile_text(isset($employee['PersonalEmail']) ? $employee['PersonalEmail'] : ''),
		'contact_number' => profile_text(isset($employee['ContactNumber']) ? $employee['ContactNumber'] : ''),
	),
	'documents' => array(
		'uan_number' => profile_text(isset($employee['UANNumber']) ? $employee['UANNumber'] : ''),
		'pan' => profile_text(isset($employee['PAN']) ? $employee['PAN'] : ''),
		'pan_image_url' => $panImage,
		'aadhar' => profile_text(isset($employee['Aadhar']) ? $employee['Aadhar'] : ''),
		'aadhar_image_url' => $aadharImage,
		'police_verification_image_url' => $policeImage,
	),
	'bank' => array(
		'account_name' => profile_text(isset($employee['BankAccountName']) ? $employee['BankAccountName'] : ''),
		'account_number' => profile_text(isset($employee['BankAccountNumber']) ? $employee['BankAccountNumber'] : ''),
	),
);

echo json_encode($response);

function profile_text($value)
{
	if ($value === null) {
		return '';
	}
	$value = trim((string) $value);
	if ($value === '' || $value === '-1') {
		return '';
	}
	return $value;
}

function profile_media_url($fileName)
{
	$fileName = profile_text($fileName);
	if ($fileName === '') {
		return '';
	}
	if (preg_match('#^https?://#i', $fileName)) {
		return $fileName;
	}
	$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
		|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
	$scheme = $https ? 'https' : 'http';
	$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'techxpertindia.in';
	$script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '/api/get_employee_info.php';
	$base = preg_replace('#/api/[^/]+$#', '', $script);
	return $scheme . '://' . $host . $base . '/admin/employees/media/' . rawurlencode($fileName);
}
