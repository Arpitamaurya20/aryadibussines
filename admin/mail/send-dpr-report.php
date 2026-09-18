<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../api/common_api_header.php');
require_once('../controllers/common_controllers.php');

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require('include/mail-config-techxpertgroup.php');
require('include/common-mail-design.php');

// --- Log raw incoming request ---
$data_raw = file_get_contents('php://input');
file_put_contents('api.logs', $data_raw . "\n", FILE_APPEND);

$data = json_decode($data_raw, true);

// --- Extract JSON data safely ---
$SiteName   = $data['SiteName'] ?? "N.A";
$POCName    = $data['POCName'] ?? "Customer";
$ReportID   = $data['ReportID'] ?? "N.A";
$TicketID   = $data['TicketID'] ?? "";
$ClientTicketID = $data['ClientTicketID'] ?? "";
$ReportURL  = $data['ReportURL'] ?? "";
$Action     = $data['action'] ?? "Daily Progress Report";

$ProjectName = $data['ProjectName'] ?? "N.A";
$ProjectStartDate = $data['ProjectStartDate'] ?? "N.A";
$ProjectEndDate   = $data['ProjectEndDate'] ?? "N.A";
$CustomerManagerName   = $data['SupervisorName'] ?? "N.A";
$SupervisorEmail  = $data['SupervisorEmail'] ?? "N.A";
$CurrentDate      = date('d M Y');

$HelpdeskComplaintNumber = $TicketID;
if (!empty($ClientTicketID)) {
    $HelpdeskComplaintNumber .= "/" . $ClientTicketID;
}

// --- Handle recipients ---
if (!empty($data['BranchEmail'])) {
    $mail->addAddress($data['BranchEmail']);
}
if (!empty($data['CompanyEmail'])) {
    $mail->addAddress($data['CompanyEmail']);
}
if (!empty($data['BranchAccountManagerEmail'])) {
    $mail->addAddress($data['BranchAccountManagerEmail']);
}

// --- Handle client recipients ---
$ClientRepresentativeEmails = $data['ClientRepresentativeEmails'] ?? "";
$email_list = array_unique(array_filter(array_map('trim', explode(',', $ClientRepresentativeEmails))));
foreach ($email_list as $email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mail->addAddress($email);
    }
}

// --- Define subject ---
$mail->Subject = "[" . $HelpdeskComplaintNumber . "] Aryadibusiness Project Report for $SiteName";

// --- Modern paragraph-based professional email body ---
$body_middle = "
<div style='font-family:Segoe UI,Roboto,Arial,sans-serif;background-color:#f5f8fb;padding:40px 0;'>
    <div style='max-width:650px;margin:0 auto;background-color:#ffffff;border-radius:12px;
                box-shadow:0 4px 16px rgba(0,0,0,0.07);overflow:hidden;'>
        
        <div style='background-color:#0d6efd;padding:25px 35px;'>
            <h2 style='color:#ffffff;margin:0;font-weight:600;font-size:20px;'>
                Aryadibusiness Project Report
            </h2>
        </div>

        <div style='padding:35px 40px;color:#333;line-height:1.8;font-size:15px;'>
            <p style='margin-top:0;'>Dear Sir/Mam,</p>

            <p>We hope you are doing well.</p>

            <p>
                We are pleased to share the latest <b>Project Report</b> for your site.
                Please find the summary of your project details below:
            </p>

            <table style='width:100%;border-collapse:collapse;margin:25px 0;font-size:15px;'>
                <tr style='background-color:#f8f9fb;'>
                    <td style='width:40%;padding:8px 10px;font-weight:600;'>Site Name</td>
                    <td style='width:60%;padding:8px 10px;'>$SiteName</td>
                </tr>
                <tr>
                    <td style='padding:8px 10px;font-weight:600;'>Project Name</td>
                    <td style='padding:8px 10px;'>$ProjectName</td>
                </tr>
                <tr style='background-color:#f8f9fb;'>
                    <td style='padding:8px 10px;font-weight:600;'>Ticket ID</td>
                    <td style='padding:8px 10px;'>$TicketID</td>
                </tr>
                <tr>
                    <td style='padding:8px 10px;font-weight:600;'>Project Start Date</td>
                    <td style='padding:8px 10px;'>$ProjectStartDate</td>
                </tr>
                <tr style='background-color:#f8f9fb;'>
                    <td style='padding:8px 10px;font-weight:600;'>Project End Date</td>
                    <td style='padding:8px 10px;'>$ProjectEndDate</td>
                </tr>
                <tr>
                    <td style='padding:8px 10px;font-weight:600;'>Customer Manager Name</td>
                    <td style='padding:8px 10px;'>$CustomerManagerName</td>
                </tr>
                <tr style='background-color:#f8f9fb;'>
                    <td style='padding:8px 10px;font-weight:600;'>Supervisor Email</td>
                    <td style='padding:8px 10px;'>
                        <a href='mailto:$SupervisorEmail' style='color:#0d6efd;text-decoration:none;'>$SupervisorEmail</a>
                    </td>
                </tr>

                
            </table>

            <p>
                The report for <b>$CurrentDate</b> includes all key updates and progress metrics. 
                You can view or download the full report using the link below.
            </p>

            <div style='text-align:center;margin:40px 0;'>
                <a href='" . htmlspecialchars($ReportURL, ENT_QUOTES) . "'
                   style='background-color:#0d6efd;
                          color:#ffffff;
                          padding:14px 32px;
                          border-radius:6px;
                          text-decoration:none;
                          font-weight:600;
                          font-size:15px;
                          display:inline-block;
                          box-shadow:0 3px 8px rgba(13,110,253,0.25);'>
                    📄 View / Download Report
                </a>
            </div>

            <p style='font-size:13px;color:#666;'>
                If the above button doesn’t work, please copy and paste the following link into your browser:<br>
                <a href='" . htmlspecialchars($ReportURL, ENT_QUOTES) . "'
                   style='color:#0d6efd;word-break:break-all;'>
                   " . htmlspecialchars($ReportURL, ENT_QUOTES) . "
                </a>
            </p>

            <p>We appreciate your continued support and collaboration.</p>

            <p style='margin-bottom:0;'>Warm regards,<br>
            <b>Team Aryadibusiness</b><br>
            <span style='color:#777;'>Automated Project Reporting System</span>
            </p>
        </div>

        <div style='background-color:#f1f4f8;padding:15px 30px;text-align:center;
                    font-size:12px;color:#888;border-top:1px solid #e3e7ed;'>
            © " . date('Y') . " Aryadibusiness — All Rights Reserved
        </div>
    </div>
</div>
";

// --- Final mail body ---
$mail->Body = $mail_header . $body_middle;

// --- Send mail ---
if (!$mail->send()) {
    $response = [
        "status" => "error",
        "message" => "Mail not sent",
        "error_info" => $mail->ErrorInfo
    ];
} else {
    $response = [
        "status" => "success",
        "message" => "Mail sent successfully",
        "report_url" => $ReportURL
    ];
}

// --- JSON Response ---
header('Content-Type: application/json');
echo json_encode($response);
?>
