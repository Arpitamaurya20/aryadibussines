<?php
/**
 * Send email via PHPMailer with SMTP (Gmail).
 * Use this from API (OTP, forgot password) and from mail/sendMail.php so credentials stay in one place.
 * Credentials: update SMTP_* constants below for your portal.
 */

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $base = dirname(__DIR__);
    require_once $base . '/PHPMailer-master/src/Exception.php';
    require_once $base . '/PHPMailer-master/src/PHPMailer.php';
    require_once $base . '/PHPMailer-master/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

/** SMTP credentials – replace with your portal Gmail / App Password */
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_USERNAME', 'zeltologic@gmail.com');
    define('SMTP_PASSWORD', 'xxnkglywcnkkmejy');
    define('SMTP_FROM_EMAIL', 'zeltologic@gmail.com');
    define('SMTP_FROM_NAME', 'HealthX');
}

/**
 * Send an email via SMTP using PHPMailer.
 *
 * @param string      $toEmail   Recipient email
 * @param string      $subject   Subject
 * @param string      $bodyPlain Plain text body
 * @param string|null $bodyHtml  Optional HTML body (if null, plain only)
 * @param string      $toName    Optional recipient name
 * @param string|null $errorOut  If provided, will be set with error message on failure
 * @return bool True if sent, false on failure
 */
function sendMailViaSMTP($toEmail, $subject, $bodyPlain, $bodyHtml = null, $toName = '', &$errorOut = null) {
    $errorOut = null;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: '');

        $mail->Subject = $subject;
        if ($bodyHtml !== null && $bodyHtml !== '') {
            $mail->isHTML(true);
            $mail->Body    = $bodyHtml;
            $mail->AltBody = $bodyPlain;
        } else {
            $mail->isHTML(false);
            $mail->Body = $bodyPlain;
        }

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        $errorOut = $mail->ErrorInfo ?: $e->getMessage();
        return false;
    }
}
