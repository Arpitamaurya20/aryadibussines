<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../api/common_api_header.php');
require_once('../controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
$data_raw = file_get_contents('php://input');

$filename = 'api.logs';

// Open the file in append mode
$file = fopen($filename, 'a');

// Write the content to the file
fwrite($file, $data_raw);

// Close the file
fclose($file);


$data = json_decode($data_raw,true);

require ('include/mail-config-techxpertgroup.php');
require ('include/common-mail-design.php');

if($data['action'] == "General Service Report")
{
	$SiteName = "N.A";
    if(isset($data['SiteName'])){
		$SiteName = $data['SiteName'];
	}
	/*
	$POCEmail = "";
	if(isset($data['POCEmail'])){
		$POCEmail = $data['POCEmail'];
	}*/

	$ClientRepresentativeEmails = "";
	if(isset($data['ClientRepresentativeEmails']))
	{
		$ClientRepresentativeEmails = $data['ClientRepresentativeEmails'];
	}
	/*$ZoneEmail = "";
	if(isset($data['ZoneEmail']))
	{
		$ZoneEmail = $data['ZoneEmail'];
	}*/

    $POCName = "N.A";
    if(isset($data['POCName'])){
		$POCName = $data['POCName'];
	}

    $ReportID = "N.A";
    if(isset($data['ReportID'])){
		$ReportID = $data['ReportID'];
	}

    $File_Path = $data['FilePath'];
    $Main_File_Path = "../corporate-tickets/reports/$File_Path";
	$HelpdeskComplaintNumber = "";
	if(isset($data['TicketID']))
	{
		$HelpdeskComplaintNumber = $data['TicketID'];
	}
	if(isset($data['ClientTicketID']))
	{
		if($data['ClientTicketID'] != "")
		{
			$HelpdeskComplaintNumber = $HelpdeskComplaintNumber."/".$data['ClientTicketID'];
		}
	}

	$BranchEmail = "";
	if(isset($data['BranchEmail']))
	{
		$BranchEmail = $data['BranchEmail'];
		$mail->addAddress($BranchEmail);
	}

	$CompanyEmail = "";
	if(isset($data['CompanyEmail']))
	{
		$CompanyEmail = $data['CompanyEmail'];
		$mail->addAddress($CompanyEmail);
	}

	$BranchAccountManagerEmail = "";
	if(isset($data['BranchAccountManagerEmail']))
	{
		$BranchAccountManagerEmail = $data['BranchAccountManagerEmail'];
		$mail->addAddress($BranchAccountManagerEmail);
	}


	$mail->Subject  = "[".$HelpdeskComplaintNumber."]Aryadibusiness Service Report Generated for $SiteName";
	$body_middle = "
	<p style='margin-top:0px;'>Dear $POCName,</p>

	<p>Field service report has been generated. Please find attached the service report.</p>
	";
	$mail->Body = $mail_header.$body_middle.$mail_footer;
	if(strpos($ClientRepresentativeEmails, ',') !== false) 
	{
	 	$cr_emails_array = explode(",", $ClientRepresentativeEmails); 
	 	foreach($cr_emails_array as $cr_email)
	 	{
	 		$mail->addAddress($cr_email);
	 	}
	} 
	else 
	{
	    $mail->addAddress($ClientRepresentativeEmails);
	}
	$mail->addAddress($ClientRepresentativeEmails);
    $mail->addAttachment($Main_File_Path, $File_Path); 
    // if(isset($data['report_images']))
    // {
    // 	foreach($data['report_images'] as $report_image)
    // 	{
    // 		$Report_Image_Path = "../media/ticket_media/".$report_image['Image'];
    // 		$mail->addAttachment($Report_Image_Path, $report_image['Image']); 
    // 	}
    // }
}
if($data['action'] == "Site Visit Report")
{
	$SiteName = "N.A";
    if(isset($data['SiteName'])){
		$SiteName = $data['SiteName'];
	}
	/*
	$POCEmail = "";
	if(isset($data['POCEmail'])){
		$POCEmail = $data['POCEmail'];
	}*/

	$ClientRepresentativeEmails = "";
	if(isset($data['ClientRepresentativeEmails']))
	{
		$ClientRepresentativeEmails = $data['ClientRepresentativeEmails'];
	}
	/*$ZoneEmail = "";
	if(isset($data['ZoneEmail']))
	{
		$ZoneEmail = $data['ZoneEmail'];
	}*/

    $POCName = "N.A";
    if(isset($data['POCName'])){
		$POCName = $data['POCName'];
	}

    $ReportID = "N.A";
    if(isset($data['ReportID'])){
		$ReportID = $data['ReportID'];
	}

    $File_Path = $data['FilePath'];
    $Main_File_Path = "../site_visits/reports/$File_Path";
	$HelpdeskComplaintNumber = "";
	
	if(isset($data['ClientTicketID']))
	{
		if($data['ClientTicketID'] != "")
		{
			$HelpdeskComplaintNumber = $data['ClientTicketID'];
		}
	}

	$BranchEmail = "";
	if(isset($data['BranchEmail']))
	{
		$BranchEmail = $data['BranchEmail'];
		$mail->addAddress($BranchEmail);
	}

	$CompanyEmail = "";
	if(isset($data['CompanyEmail']))
	{
		$CompanyEmail = $data['CompanyEmail'];
		$mail->addAddress($CompanyEmail);
	}

	$BranchAccountManagerEmail = "";
	if(isset($data['BranchAccountManagerEmail']))
	{
		$BranchAccountManagerEmail = $data['BranchAccountManagerEmail'];
		$mail->addAddress($BranchAccountManagerEmail);
	}


	$mail->Subject  = "[".$HelpdeskComplaintNumber."]Aryadibusiness Site Visit Report Generated for $SiteName";
	$body_middle = "
	<p style='margin-top:0px;'>Dear $POCName,</p>

	<p>Site Visit report has been generated. Please find attached the service report.</p>
	";
	$mail->Body = $mail_header.$body_middle.$mail_footer;
	if(strpos($ClientRepresentativeEmails, ',') !== false) 
	{
	 	$cr_emails_array = explode(",", $ClientRepresentativeEmails); 
	 	foreach($cr_emails_array as $cr_email)
	 	{
	 		$mail->addAddress($cr_email);
	 	}
	} 
	else 
	{
	    $mail->addAddress($ClientRepresentativeEmails);
	}
	$mail->addAddress($ClientRepresentativeEmails);
    $mail->addAttachment($Main_File_Path, $File_Path); 
    // if(isset($data['report_images']))
    // {
    // 	foreach($data['report_images'] as $report_image)
    // 	{
    // 		$Report_Image_Path = "../media/ticket_media/".$report_image['Image'];
    // 		$mail->addAttachment($Report_Image_Path, $report_image['Image']); 
    // 	}
    // }
}
if(!$mail->send())
{
	//echo "error";
}
else
{
	//echo "Mail Sent";
}
?>