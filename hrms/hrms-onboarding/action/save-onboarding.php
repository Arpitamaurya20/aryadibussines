<?php
@session_start();
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();
$core = new Core();

function v($k, $default = '')
{
	return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default;
}

function numv($k)
{
	return floatval(v($k, '0'));
}

function uploadDoc($field, $employeeNo, $suffix)
{
	if (!isset($_FILES[$field]) || empty($_FILES[$field]['name'])) return '';
	$tmp = $_FILES[$field]['tmp_name'];
	$name = $_FILES[$field]['name'];
	$parts = explode('.', $name);
	$ext = strtolower(end($parts));
	if ($ext === '') $ext = 'jpg';

	$dir = dirname(__DIR__) . '/media';
	if (!is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}
	$file = preg_replace('/[^A-Za-z0-9_-]/', '', $employeeNo) . '_' . $suffix . '.' . $ext;
	$target = $dir . '/' . $file;
	if (@move_uploaded_file($tmp, $target)) {
		return $file;
	}
	return '';
}

$employeeNo = v('EmployeeNumber');
$name = v('Name');
$contact = v('ContactNumber');
$designation = v('Designation');
$department = v('Department');
$doj = v('DateofJoining');
$pan = v('PAN');
$aadhar = v('Aadhar');

if ($employeeNo === '' || $name === '' || $contact === '' || $designation === '' || $department === '' || $doj === '' || $pan === '' || $aadhar === '') {
	header('Location: ../view-onboarding.php?err=' . urlencode('Please fill all required fields.'));
	exit;
}

$employeeNoEsc = mysqli_real_escape_string($conn, $employeeNo);
$panEsc = mysqli_real_escape_string($conn, $pan);
$aadharEsc = mysqli_real_escape_string($conn, $aadhar);

if (!$core->check_unique_identity_filter($conn, 'employees', "WHERE EmployeeNumber = '$employeeNoEsc'")) {
	header('Location: ../view-onboarding.php?err=' . urlencode('Employee Number already exists.'));
	exit;
}
if (!$core->check_unique_identity_filter($conn, 'employees', "WHERE PAN = '$panEsc'")) {
	header('Location: ../view-onboarding.php?err=' . urlencode('PAN already exists.'));
	exit;
}
if (!$core->check_unique_identity_filter($conn, 'employees', "WHERE Aadhar = '$aadharEsc'")) {
	header('Location: ../view-onboarding.php?err=' . urlencode('Aadhar already exists.'));
	exit;
}

$profileImage = uploadDoc('ProfileImage', $employeeNo, 'PP');
$panImage = uploadDoc('PANImage', $employeeNo, 'PAN');
$aadharImage = uploadDoc('AadharImage', $employeeNo, 'ADH');
$policeImage = uploadDoc('PoliceVerificationImage', $employeeNo, 'PV');

$divisionSequence = intval($core->_getMaxIdentityValue($conn, 'employees', 'DivisionSequence')) + 1;
$vendor = (strtolower(v('WorkType', 'employee')) === 'vendor') ? 1 : 0;
$createdBy = isset($_SESSION['pp_email']) ? $_SESSION['pp_email'] : 'admin';
$createdDate = date('Y-m-d');
$createdTime = date('H:i:s');

$fields = [
	'DivisionSequence' => $divisionSequence,
	'Name' => v('Name'),
	'FatherName' => v('FatherName'),
	'Designation' => $designation,
	'EmployeeNumber' => $employeeNo,
	'Division' => v('Division'),
	'Department' => $department,
	'Basic' => numv('Basic'),
	'DA' => numv('DA'),
	'HRA' => numv('HRA'),
	'ConvenienceAllowance' => numv('ConvenienceAllowance'),
	'Bonus' => numv('Bonus'),
	'HealthInsurance' => numv('HealthInsurance'),
	'Others' => numv('Others'),
	'Email' => v('Email'),
	'PersonalEmail' => v('PersonalEmail'),
	'ContactNumber' => $contact,
	'BankAccountName' => v('BankAccountName'),
	'BankAccountNumber' => v('BankAccountNumber'),
	'PAN' => $pan,
	'Aadhar' => $aadhar,
	'Supervisor' => v('Supervisor'),
	'Vendor' => $vendor,
	'ProfileImage' => $profileImage,
	'AadharImage' => $aadharImage,
	'PANImage' => $panImage,
	'PoliceVerificationImage' => $policeImage,
	'Gender' => v('Gender'),
	'UANNumber' => v('UANNumber'),
	'DateofJoining' => $doj,
	'Epf_number' => v('Epf_number'),
	'Esic_number' => v('Esic_number'),
	'WeeklyOff' => v('WeeklyOff', 'Sunday'),
	'City' => v('City'),
	'State' => v('State'),
	'CreatedBy' => $createdBy,
	'CreatedDate' => $createdDate,
	'CreatedTime' => $createdTime,
	'IsActive' => 1
];

if (isset($_POST['ApplyEPF'])) $fields['ApplyEPF'] = intval(v('ApplyEPF', '1'));
if (isset($_POST['ApplyESI'])) $fields['ApplyESI'] = intval(v('ApplyESI', '1'));

$cols = [];
$vals = [];
foreach ($fields as $k => $val) {
	$cols[] = $k;
	if (is_numeric($val) && !in_array($k, ['EmployeeNumber', 'PAN', 'Aadhar', 'ContactNumber', 'BankAccountNumber', 'UANNumber', 'Epf_number', 'Esic_number'], true)) {
		$vals[] = $val;
	} else {
		$vals[] = "'" . mysqli_real_escape_string($conn, (string)$val) . "'";
	}
}

$sql = "INSERT INTO employees (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
$res = $core->_InsertTableRecords($conn, $sql);

if (!empty($res['error'])) {
	header('Location: ../view-onboarding.php?err=' . urlencode($res['message'] ?? 'Onboarding failed'));
	exit;
}

header('Location: ../view-onboarding.php?ok=' . urlencode('Employee onboarded successfully.'));
exit;
