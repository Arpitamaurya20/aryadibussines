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

if($data['action'] == "Send Quotation")
{
	$SiteName = "N.A";
    if(isset($data['SiteName'])){
		$SiteName = $data['SiteName'];
	}
	if(isset($data['CompanyID']))
	{
		if($data['CompanyID'] == 183)
		{
			require ('include/mail-config-innov.php');
			require ('include/common-mail-design-innov.php');
		}
	}
	

	$BranchEmail = "";
	if(isset($data['BranchEmail']))
	{
		$BranchEmail = $data['BranchEmail'];
	}
	$CorporateAccountEmail = "";
	if(isset($data['CorporateAccountEmail'])){
		$CorporateAccountEmail = $data['CorporateAccountEmail'];
	}
	$AccountBranchManagerEmail = "";
	if(isset($data['AccountBranchManagerEmail']))
	{
		$AccountBranchManagerEmail = $data['AccountBranchManagerEmail'];
	}

    $POCName = "N.A";
    if(isset($data['POCName'])){
		$POCName = $data['POCName'];
	}

   
    $File_Path = $data['FilePath'];
    $Main_File_Path = "../corporate-tickets/quotations/$File_Path";
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

	$mail->Subject  = "[".$HelpdeskComplaintNumber."]Aryadibusiness Quotation Generated - Take Action";
	$body_middle = "
	<p style='margin-top:0px;'>Dear $POCName,</p>

	<p>Quotation has been generated for $HelpdeskComplaintNumber. Please find attached and take action</p>
	";
	$mail->Body = $mail_header.$body_middle.$mail_footer;
	$approval_link_main = "https://techxpertindia.in/admin/corporate-tickets/view-web-quotation.php";
	/*if(strpos($ClientRepresentativeEmails, ',') !== false) 
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
	}*/

	$approval_link_parameter = "?QuotationID=".base64_encode($data['QuotationID'])."&By=".base64_encode($BranchEmail);
	$approval_link = $approval_link_main.$approval_link_parameter;
	$button = '<a href="'.$approval_link.'" style="display: inline-block; padding: 10px 20px; font-size: 16px; color: white; background-color: #007bff; text-decoration: none; border-radius: 5px;">
    Take Action
	</a><br>';
	$body_approval = "<p>Kindly Approve / Reject the Quotation</p>".$button;
	$mail->Body = $mail_header.$body_middle.$body_approval.$mail_footer;
	$mail->addAttachment($Main_File_Path, $File_Path);
	$mail->clearAddresses();      // Clears all 'To' addresses
	$mail->clearCCs();            // Clears all 'CC' addresses (optional)
	$mail->clearBCCs();  
	$mail->addAddress($BranchEmail);
	
	if(!$mail->send())
	{
		//echo "error";
	}
	else
	{
		//var_dump($mail);
		//echo "Mail Sent";
	}
	if($CorporateAccountEmail != "")
	{
		$approval_link_parameter = "?QuotationID=".base64_encode($data['QuotationID'])."&By=".base64_encode($CorporateAccountEmail);
		$approval_link = $approval_link_main.$approval_link_parameter;
		$button = '<a href="'.$approval_link.'" style="display: inline-block; padding: 10px 20px; font-size: 16px; color: white; background-color: #007bff; text-decoration: none; border-radius: 5px;">
    Take Action
	</a><br>';
		$body_approval = "<p>Kindly Approve / Reject the Quotation</p>".$button;
		$mail->Body = $mail_header.$body_middle.$body_approval.$mail_footer;
		$mail->clearAddresses();      // Clears all 'To' addresses
		$mail->clearCCs();            // Clears all 'CC' addresses (optional)
		$mail->clearBCCs();  
		$mail->addAddress($CorporateAccountEmail);
		
		if(!$mail->send())
		{
			//echo "error";
		}
		else
		{
			//var_dump($mail);
			//echo "Mail Sent";
		}
	}
	if($AccountBranchManagerEmail != "")
	{
		$mail->Body = $mail_header.$body_middle.$mail_footer;
		$mail->clearAddresses();      // Clears all 'To' addresses
		$mail->clearCCs();            // Clears all 'CC' addresses (optional)
		$mail->clearBCCs();  
		$mail->addAddress($AccountBranchManagerEmail);
		
		if(!$mail->send())
		{
			//echo "error";
		}
		else
		{
			//var_dump($mail);
			//echo "Mail Sent";
		}
	}
	
    
}

?>