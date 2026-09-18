<?php
	$mail = new PHPMailer\PHPMailer\PHPMailer();
	$mail->isSMTP();                      // Set mailer to use SMTP
	$mail->Host = 'mail.techxpertindia.in';  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = 'contact@techxpertindia.in';                 // SMTP info@cyphertextsolutions.com username
	$mail->Password = 'Contact@123!';                           // SMTP password
	$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 465;                                    // TCP port to connect to
	$mail->setFrom('contact@techxpertindia.in', "TechXpert Team");
	$mail->isHTML(true);
?>