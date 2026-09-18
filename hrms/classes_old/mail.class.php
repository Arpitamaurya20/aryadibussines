<?php 
class Mail extends Core
{
	private $mail;
	function __construct()
	{
		$this->mail = new PHPMailer\PHPMailer\PHPMailer();
		$this->mail->isSMTP();                      // Set mailer to use SMTP
		$this->mail->Host = 'smtp.gmail.com';
		$this->mail->SMTPAuth = true;                               // Enable SMTP authentication
		$this->mail->Username = 'contact@webomates.com';          // SMTP info@cyphertextsolutions.com username  
		$this->mail->Password = 'kecghqngbmqizlqj';                           // SMTP password
		$this->mail->SMTPSecure = 'ssl';                           // Enable TLS encryption, `ssl` also accepted
		$this->mail->Port = 465;       
		$this->mail->From = "contact@webomates.com";
		$this->mail->FromName = "Webomates Contact";                              // TCP port to connect to
		$this->mail->setFrom('contact@webomates.com', "Webomates Contact");
		$this->mail->isHTML(true);
		$this->mail->CharSet = "UTF-8";
	}
	public function sendemail($to,$cc,$bcc,$body,$Subject)
	{
		$this->mail->Body = $body;
		$this->mail->Subject  = $Subject;
		$this->mail->addAddress($to);
		if($cc!="")
		{
			$this->mail->AddCC($cc);	
		}
		if(!$this->mail->send())
		{
			$response['error'] = true;
		    $booking_response['message'] = "There is some technical error right now. Our technical team is working on it to get the services back. Thank you for your patience.";
		}
		else
		{
			$response['error'] = false;
		    $response['message'] = "Email Sent";
		}
		return $response;
	}
	
}