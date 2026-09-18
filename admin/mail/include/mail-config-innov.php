<?php
$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->isSMTP();                      // Set mailer to use SMTP
$mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = 'innovtechnical@gmail.com';                  // SMTP info@cyphertextsolutions.com username
$mail->Password = 'eapupavklsqyexlj';                           // SMTP password
//$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 587;                                    // TCP port to connect to
$mail->setFrom('innovtechnical@gmail.com', "Innov Group");
$mail->isHTML(true);
?>