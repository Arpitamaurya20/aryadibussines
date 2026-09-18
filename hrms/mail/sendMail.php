<?php
/**
 * Contact form – sends to portal email using same SMTP credentials as API (PHPMailer).
 * Credentials are in include/send_mail_phpmailer.php (SMTP_* constants).
 */
require_once __DIR__ . '/../include/send_mail_phpmailer.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = isset($_POST['name']) ? trim($_POST['name']) : '';
    $number   = isset($_POST['number']) ? trim($_POST['number']) : '';
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $people   = isset($_POST['people']) ? trim($_POST['people']) : '';
    $services = isset($_POST['services']) ? trim($_POST['services']) : '';
    $date     = isset($_POST['date']) ? trim($_POST['date']) : '';
    $message  = isset($_POST['message']) ? trim($_POST['message']) : '';

    $subject = "New Contact Form Submission";
    $plainBody = "Name: $name\nMobile: $number\nEmail: $email\nPeople: $people\nService: $services\nDate: $date\nMessage: $message";
    $htmlBody = "
        <h2>Contact Form Details</h2>
        <p><b>Name:</b> " . htmlspecialchars($name) . "</p>
        <p><b>Mobile:</b> " . htmlspecialchars($number) . "</p>
        <p><b>Email:</b> " . htmlspecialchars($email) . "</p>
        <p><b>People:</b> " . htmlspecialchars($people) . "</p>
        <p><b>Service:</b> " . htmlspecialchars($services) . "</p>
        <p><b>Date:</b> " . htmlspecialchars($date) . "</p>
        <p><b>Message:</b> " . nl2br(htmlspecialchars($message)) . "</p>
    ";

    $toEmail = 'zeltologic@gmail.com'; // your receiving email
    $err = null;
    if (sendMailViaSMTP($toEmail, $subject, $plainBody, $htmlBody, '', $err)) {
        echo "success";
    } else {
        echo "Mailer Error: " . (strlen($err) ? $err : 'Failed to send.');
    }
}
