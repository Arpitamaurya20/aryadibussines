<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);

require ('include/mail-config.php');
require ('include/common-mail-design.php');

if($data['action'] == "New Corporate Account Register")
{
	$admin_usename = $data['admin_username'];
	$admin_password = $data['password'];
	$CompanyName = $data['CompanyName'];
	$CompanyEmail = $data['CompanyEmail'];

	$mail->Subject  = 'Welcome to Aruadibusiness - Save your credentials';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi $Name,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>A new company account is registered. Here are the details to login into app and portal:
						<br>
						<br>
						Company Account Name: ".$CompanyName." <br>
						Username: ".$admin_usename." <br>
						Password: ".$admin_password." <br>
						</td>
					</tr>
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>In case of any issues please let us know</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>App link - https://play.google.com/store/apps/details?id=io.ionic.techXpert</td>
					</tr>
					<br>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	$mail->addAddress($CompanyEmail);
	$mail->addBCC("pgfiry@gmail.com");
}
if(!$mail->send())
{
	echo "Error";
	//$result_array["error"] = true;
	//$result_array["message"] = "Error occured";
	//echo "There is some technical error right now. Our technical team is working on it to get the services back. Thank you for your patience.";
}
else
{
	echo "Mail Sent";
}
?>