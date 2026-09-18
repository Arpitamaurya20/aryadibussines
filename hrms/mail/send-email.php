<?php
require_once('../include/common-api-headers.php');
require_once('../include/autoloader.inc.php');
require '../project-assets/PHPMailer-master/src/Exception.php';
require '../project-assets/PHPMailer-master/src/PHPMailer.php';
require '../project-assets/PHPMailer-master/src/SMTP.php';
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$action = $data['action'];

require ('../include/common-mail-design.php');

$mail_html = "<tr bgcolor='#ffffff'>
	  <td align='center'>
		<table bgcolor='#ffffff' style='width:100%;line-height:20px;border:1px solid #f4f5f6;border-radius:5px' cellpadding='0'>
		  <tbody>
			<tr>
			  <td>
				<table style='width:92%;margin:0 auto' align='center'>
				  <tbody>
					<tr>
					  <td height='32' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi ,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Your account manager account is created on Webomates.</span></td>
					</tr>
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'></span></td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Click on the link to set your password and activate your account: </span></td>
					</tr>
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					";

if(isset($data['to_address']))
{
	$to_address = $data['to_address'];
}
if($action == "Add PAM")
{
	$body = "Test Email";
	$subject = "Test Subject";
}
$mail_obj = new Mail();
$mail_obj->sendemail($to_address,'','',$body,$subject);
?>