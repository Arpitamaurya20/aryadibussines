<?php

function logServiceBookingMail($message)
{
    $logFile = dirname(__DIR__) . '/service-booking-mail.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL, FILE_APPEND);
}

function createServiceBookingMailer($mailDir)
{
    require_once $mailDir . '/PHPMailer-master/src/Exception.php';
    require_once $mailDir . '/PHPMailer-master/src/PHPMailer.php';
    require_once $mailDir . '/PHPMailer-master/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'mail.techxpertindia.in';
    $mail->SMTPAuth = true;
    $mail->Username = 'contact@techxpertindia.in';
    $mail->Password = 'Contact@123!';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ],
    ];
    $mail->setFrom('contact@techxpertindia.in', 'TechXpert India');
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}

function sendServiceBookingMail($data)
{
    if (empty($data) || ($data['action'] ?? '') !== 'New Service Booking') {
        return false;
    }

    $mailDir = dirname(__DIR__);
    require $mailDir . '/include/common-mail-design.php';

    $BookingCode   = $data['BookingCode'] ?? 'N/A';
    $CustomerName  = $data['CustomerName'] ?? 'N/A';
    $Phone         = $data['Phone'] ?? 'N/A';
    $Email         = $data['Email'] ?? 'N/A';
    $ServiceName   = $data['ServiceName'] ?? 'N/A';
    $SubService    = $data['SubService'] ?? 'N/A';
    $Price         = $data['Price'] ?? 'N/A';
    $Address       = $data['Address'] ?? 'N/A';
    $City          = $data['City'] ?? 'N/A';
    $State         = $data['State'] ?? 'N/A';
    $Landmark      = $data['Landmark'] ?? 'N/A';
    $SourceType    = $data['SourceType'] ?? 'N/A';
    $BookingDate   = $data['BookingDate'] ?? date('Y-m-d');
    $BookingTime   = $data['BookingTime'] ?? date('H:i:s');
    $StateHeadName = $data['StateHeadName'] ?? '';

    $recipients = [];
    if (!empty($data['StateHeadEmail']) && filter_var($data['StateHeadEmail'], FILTER_VALIDATE_EMAIL)) {
        $recipients[] = $data['StateHeadEmail'];
    }
    foreach (['rohittechxpert@gmail.com', 'info@techxpertindia.in'] as $centralEmail) {
        $recipients[] = $centralEmail;
    }
    $recipients = array_values(array_unique($recipients));

    if (empty($recipients)) {
        logServiceBookingMail("No recipients for booking $BookingCode");
        return false;
    }

    $greeting = $StateHeadName !== '' ? "Dear $StateHeadName," : 'Dear Team,';
    $subject = "New Service Booking - $BookingCode";
    $body_middle = "
<p style='margin-top:0px;'>$greeting</p>
<p>A new service booking has been received. Details are below:</p>
<table style='width:100%;border-collapse:collapse;font-size:14px;color:#444e61;'>
  <tr><td style='padding:6px 0;font-weight:bold;width:180px;'>Booking Code</td><td>$BookingCode</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Customer Name</td><td>$CustomerName</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Phone</td><td>$Phone</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Email</td><td>$Email</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Service</td><td>$ServiceName</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Sub Service</td><td>$SubService</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Price</td><td>$Price</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Address</td><td>$Address</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Landmark</td><td>$Landmark</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>City</td><td>$City</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>State</td><td>$State</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Source</td><td>$SourceType</td></tr>
  <tr><td style='padding:6px 0;font-weight:bold;'>Booking Date</td><td>$BookingDate $BookingTime</td></tr>
</table>
<p>Please review and take appropriate action.</p>
";
    $body = $mail_header . $body_middle . $mail_footer;

    $sentCount = 0;
    foreach ($recipients as $recipient) {
        try {
            $mail = createServiceBookingMailer($mailDir);
            $mail->addAddress($recipient);
            $mail->Subject = $subject;
            $mail->Body = $body;
            if ($mail->send()) {
                $sentCount++;
                logServiceBookingMail("Sent $BookingCode to $recipient");
            }
        } catch (Exception $e) {
            logServiceBookingMail("Failed $BookingCode to $recipient: " . $e->getMessage());
        }
    }

    return $sentCount > 0;
}
