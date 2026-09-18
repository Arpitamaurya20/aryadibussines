<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/customer/controller/customer_controller.php');
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
include('mail_config.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
setTimeZone();
if(isset($data['action']))
{
	$action = $data['action'];
	if($action == "test_email")
	{
		$notice_title = "Test";
		$description = "Test";
		/*$CompanyAdminEmail = $data['CompanyAdminEmail'];
		$EmployeesEmailAddress = $data['employee_emails'];
		$CompanyAdminEmail = $data['CompanyAdminEmail'];*/
		$mail->Body = <<< EOF

	   <html><body>
		Dear Test,
		<br><br>
		A new Notice has been created. <br>
		Please go thorugh the same and contact Company Management in case of any queries
		<br><br>
			Notice Title : $notice_title
			<br>
			$description
		  <br><br>
		  Kindly login via using this link : <a href='digitalworkdesk.com/login.php'>Login Here</a>
		  <br><br>
		 Regards,
		 <br>
		 Digital WorkDesk Team
	   </body>
	</html>

EOF;
		$mail->Subject  = 'Test Email';
		$mail->addAddress("pgfiry@gmail.com");
		/*foreach($EmployeesEmailAddress as $EmployeeEmail)
		{
			$mail->addAddress($EmployeeEmail);
		}*/
		//$mail->AddBCC("pgfiry@gmail.com");
		if(!$mail->send()) 
		{
			$result_array["error"] = true;
			$result_array["message"] = "Error occured";   
			//echo "There is some technical error right now. Our technical team is working on it to get the services back. Thank you for your patience.";
		} 
		else 
		{
			$result_array["error"] = false;
			$result_array["message"] = "Mail Sent Successfully";
		}
	}
	
}
else
{
	$result_array["error"] = true;
	$result_array["message"] = "Missing User Fields";
}
echo json_encode($result_array);
?>