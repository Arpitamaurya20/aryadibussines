<?php
set_time_limit(60);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

require ('include/mail-config-innov.php');
require ('include/common-mail-design.php');
$mail->SMTPDebug = 2;

$mail->Subject  = 'Aryadibusiness Sample email';
$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi Prateek,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>We're excited to confirm that your booking with us has been successfully processed. Here are the details:
						<br>
						<br>
						Company Name: [Insert Company Name] <br>
						Booking Date: [Insert Booking Date] <br>
						Booking Time: [Insert Booking Time] <br>
						Location: [Insert Location]</span>
						</td>
					</tr>
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Thank you for considering our AC Repairing Service for your needs. To book our services through WhatsApp, we look forward to serving you soon!</td>
					</tr>
					<br>
					";

$mail->Body = $mail_header.$body_middle.$mail_footer;
$mail->addAddress("pgfiry@gmail.com");
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