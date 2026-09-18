<?php
@session_start();
require_once('../include/autoloader.inc.php');
$conf = new Conf();
$_ProductName = $conf->_ProductName;
$_MailName = $conf->_MailName;
$_ProductLogo = $conf->_ProductLogo;
$_WebsiteLink = $conf->_WebsiteLink;
$_MailSignature = $conf->_MailSignature;
$_DomainName = $conf->_DomainName;
$currentYear = date("Y");
header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers');
header('Content-Type: text/html; charset=utf-8');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';


$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->isSMTP();                      // Set mailer to use SMTP
$mail->Host = 'mail.digitalworkdesk.com';  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = 'contact@digitalworkdesk.com';                  // SMTP info@cyphertextsolutions.com username
$mail->Password = 'Contact@123!';                           // SMTP password
$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 465;                                    // TCP port to connect to
$mail->setFrom('contact@digitalworkdesk.com',  $_MailName);
$mail->isHTML(true);

?>