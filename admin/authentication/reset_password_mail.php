<?php
require '../mail/PHPMailer-master/src/Exception.php';
require '../mail/PHPMailer-master/src/PHPMailer.php';
require '../mail/PHPMailer-master/src/SMTP.php';

require ('include/mail-config.php');
require ('include/common-mail-design.php');

include('../controllers/common_controllers.php');
include('auth_controller/authentication_controller.php');

$UserType = SessionCheck();
$conn = _connectodb();

function getEmployeeDatafromMail($conn,$User_ID)
{
	$where = "WHERE Email='$User_ID' ";
	$response = _getTableDetails($conn, 'employees', $where);
	return $response;
}

function getCompanyDatafromMail($conn,$User_ID)
{
	$where = "WHERE CompanyEmail='$User_ID' ";
	$response = _getTableDetails($conn, 'company', $where);
	return $response;
}

function getBranchDatafromMail($conn,$User_ID)
{
	$where = "WHERE BranchEmail='$User_ID' ";
	$response = _getTableDetails($conn, 'branch', $where);
	return $response;
}

function getEmployeeUserNameFromId($conn,$EmployeeID)
{
	$where = "WHERE EmployeeID='$EmployeeID'";
	$response = _getTableDetails($conn, 'users', $where);
	return $response;
}

function getCompanyUserNameFromId($conn,$CompanyID)
{
	$where = "WHERE CorporateID='$CompanyID'";
	$response = _getTableDetails($conn, 'users', $where);
	return $response;
}

function getBranchUserNameFromId($conn,$BranchID)
{
	$where = "WHERE BranchID='$BranchID' ";
	$response = _getTableDetails($conn, 'users', $where);
	return $response;
}

// get mail from user

function getIDFromUserName($conn,$User_ID)
{
	$where = "WHERE UserName='$User_ID'";
	$response = _getTableDetails($conn, 'users', $where);
	return $response;
}

function GetMailFromEmployeeID($conn,$EmployeeIDFromUserName)
{
	$where = "WHERE ID='$EmployeeIDFromUserName'";
	$response = _getTableDetails($conn, 'employees', $where);
	return $response;
}
function GetMailFromCorporateID($conn,$CorporateIDFromUserName)
{
	$where = "WHERE ID='$CorporateIDFromUserName'";
	$response = _getTableDetails($conn, 'company', $where);
	return $response;
}

function GetMailFromBranchID($conn,$BranchIDFromUserName)
{
	$where = "WHERE ID='$BranchIDFromUserName'";
	$response = _getTableDetails($conn, 'branch', $where);
	return $response;
}


$User_ID=$_POST['user_id'];
// $UserID=null;

$AllUserData = getIDFromUserName($conn,$User_ID);
$UName = isset($AllUserData['UserName']);
if ($UName) {
	$EmployeeIDFromUserName = $AllUserData['EmployeeID'];
	if ($EmployeeIDFromUserName != -1) {
		$GetDataFromEmployee = GetMailFromEmployeeID($conn,$EmployeeIDFromUserName);
		$User_ID =  $GetDataFromEmployee['Email'];
	}

	$CorporateIDFromUserName = $AllUserData['CorporateID'];
	if ($CorporateIDFromUserName != -1) {
		$GetDataFromCorporate = GetMailFromCorporateID($conn,$CorporateIDFromUserName);
		$User_ID =  $GetDataFromCorporate['CompanyEmail'];
	}

	$BranchIDFromUserName = $AllUserData['BranchID'];
	if ($BranchIDFromUserName != -1) {
		$GetDataFromBranch = GetMailFromBranchID($conn,$BranchIDFromUserName);
		$User_ID =  $GetDataFromBranch['BranchEmail'];
	}
}

$CompanyID='';
$Username='';
$UserID='';

$EmployeeData = getEmployeeDatafromMail($conn,$User_ID);
if($EmployeeData) {
	$EmployeeID = $EmployeeData['ID'];
	$UserData = getEmployeeUserNameFromId($conn,$EmployeeID);
	$Username = isset($UserData['UserName']);
	$UserID = $UserData['UserID'];
}


if(empty($EmployeeData))
{
	$CompanyData = getCompanyDatafromMail($conn,$User_ID);
	if ($CompanyData) {
	$CompanyID = $CompanyData['ID'];
	$UserData = getCompanyUserNameFromId($conn,$CompanyID);
	$Username = $UserData['UserName'];
	$UserID = $UserData['UserID'];
	}
	
}
elseif(empty($EmployeeData) && empty($CompanyData))
{
	$BranchData = getBranchDatafromMail($conn,$User_ID);
	$BranchID = $BranchData['ID'];
	$UserData = getBranchUserNameFromId($conn,$BranchID);
	$Username = $UserData['UserName'];
	$UserID = $UserData['UserID'];
}

// $encryption_key = "your_secret_key";
// $encrypted_unique_id = urlencode(base64_encode(openssl_encrypt($UserID, "AES-256-CBC", $encryption_key)));



$token = bin2hex(random_bytes(32)); 



$url = "http://localhost/projects/techxpertindia/admin/authentication/new_password.php?secret=$UserID";


$encrypted_url = base64_encode($url);
// $decrypted_url = base64_decode($encrypted_url);
$mail->Subject  = 'Reset Password';
$body_middle = "<tr>
		  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi : $Username; </td>
		</tr>

		<tr>
		  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Please click the following link to reset your password: <a href='http://localhost/projects/techxpertindia/admin/authentication/new_password.php?secret=$UserID' > $encrypted_url</a></td>
		</tr>
	
		<br>
		";
		$response=array();
if ($UserID) 
{
	$sql = " INSERT INTO temp_table(UserName,UserId,Token) VALUES ('$Username','$UserID','$token')";
	_InsertTableRecords($conn, $sql);


	$mail->Body = $mail_header.$body_middle;
	$mail->addAddress("$User_ID");
	if(!$mail->send())
	{ 
		$response['error'] = true;
    	$response['message'] = "Invalid Username or Email Address, Please try again later !";
	}
	else
	{
		$response['error'] = false;
    	$response['message'] = "link generate successfully please check your mail";
	}
}
else 
{
	$response['error'] = true;
    $response['message'] = "Invalid Username or Email Address, Please try again later !";
}
echo json_encode($response);

?>