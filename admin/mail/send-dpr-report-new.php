<?php
// send-dpr-report.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Adjust paths as required relative to this file
require_once('../../api/common_api_header.php');       // DB + helper (adjust if different)
require_once('../controllers/common_controllers.php'); // common controllers (adjust path)

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require('include/mail-config-techxpertgroup.php');     // must define $mail (PHPMailer configured)
require('include/common-mail-design.php');             // must define $mail_header and $mail_footer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log raw incoming request (append)
$data_raw = file_get_contents('php://input');
file_put_contents(__DIR__ . '/api.logs', date('Y-m-d H:i:s') . " " . $data_raw . PHP_EOL, FILE_APPEND);

// Accept JSON body or form-data / GET fallback
$data = json_decode($data_raw, true);
if (!$data) {
    // fallback to $_POST / $_GET
    $data = $_POST ?: $_GET ?: [];
}

// Validate TicketID
$TicketID = isset($data['TicketID']) ? intval($data['TicketID']) : 0;
if ($TicketID <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'TicketID required']);
    exit;
}

// DB connection helper assumed in common_controllers
$conn = _connectodb();
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    exit;
}

// Fetch ticket & branch & corporate & project-team details (safe)
$ticket_q = "SELECT * FROM corporate_tickets WHERE ID = '" . mysqli_real_escape_string($conn, $TicketID) . "' LIMIT 1";
$ticket_res = mysqli_query($conn, $ticket_q);
$ticket = $ticket_res && mysqli_num_rows($ticket_res) ? mysqli_fetch_assoc($ticket_res) : null;

if (!$ticket) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
    exit;
}

// Branch details
$BranchEmail = '';
$BranchSite = '';
$BranchAddress = '';
$BranchMobile = '';
$BranchAccountManager = null;
if (!empty($ticket['BranchID'])) {
    $bid = intval($ticket['BranchID']);
    $bq = "SELECT * FROM branch WHERE ID = $bid LIMIT 1";
    $br = mysqli_query($conn, $bq);
    if ($br && mysqli_num_rows($br)) {
        $branch = mysqli_fetch_assoc($br);
        $BranchEmail = trim($branch['BranchEmail'] ?? '');
        $BranchSite = $branch['BranchSite'] ?? '';
        $BranchAddress = $branch['BranchAddress1'] ?? '';
        $BranchMobile = $branch['BranchMobile'] ?? '';
        $BranchAccountManager = $branch['AccountBranchManager'] ?? null; // this may be employee id
    }
}

// Corporate details
$CompanyEmail = '';
if (!empty($ticket['CorporateID'])) {
    $cid = intval($ticket['CorporateID']);
    $cq = "SELECT * FROM company WHERE ID = $cid LIMIT 1";
    $cr = mysqli_query($conn, $cq);
    if ($cr && mysqli_num_rows($cr)) {
        $corp = mysqli_fetch_assoc($cr);
        $CompanyEmail = trim($corp['CompanyEmail'] ?? '');
    }
}

// If BranchAccountManager is an employee id, fetch their email
$BranchAccountManagerEmail = '';

// Project-team / project details (optional)
$ProjectName = '';
$ProjectQuery = "SELECT p.ProjectName, t.CustomerManagerEmail, t.CustomerSupervisorEmail, t.TechXpertManagerEmail
                 FROM projects p
                 LEFT JOIN project_team_details t ON p.ID = t.ProjectID
                 WHERE p.TicketID = '" . mysqli_real_escape_string($conn, $TicketID) . "' AND p.IsActive = 1 LIMIT 1";
$pr = mysqli_query($conn, $ProjectQuery);
if ($pr && mysqli_num_rows($pr)) {
    $prow = mysqli_fetch_assoc($pr);
    $ProjectName = $prow['ProjectName'] ?? '';
    $CustomerManagerEmail = trim($prow['CustomerManagerEmail'] ?? '');
    $CustomerSupervisorEmail = trim($prow['CustomerSupervisorEmail'] ?? '');
    $TechXpertManagerEmail = trim($prow['TechXpertManagerEmail'] ?? '');
} else {
    $CustomerManagerEmail = $CustomerSupervisorEmail = $TechXpertManagerEmail = '';
}

// Build recipients list: combine from DB + payload
$recipients = [];

// DB recipients (if valid)
foreach ([$BranchEmail, $CompanyEmail, $BranchAccountManagerEmail, $CustomerManagerEmail, $CustomerSupervisorEmail, $TechXpertManagerEmail] as $em) {
    if (!empty($em) && filter_var($em, FILTER_VALIDATE_EMAIL)) $recipients[] = $em;
}

// From payload: client representative emails (comma separated)
$ClientRepresentativeEmails = trim($data['ClientRepresentativeEmails'] ?? '');
if (!empty($ClientRepresentativeEmails)) {
    $split = array_map('trim', explode(',', $ClientRepresentativeEmails));
    foreach ($split as $em) {
        if (!empty($em) && filter_var($em, FILTER_VALIDATE_EMAIL)) $recipients[] = $em;
    }
}

// Deduplicate recipients
$recipients = array_values(array_unique($recipients));

// If no recipients, exit
if (empty($recipients)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No valid recipient emails found']);
    exit;
}

// Build report URL (the link you asked to send)
$report_url = "https://techxpertindia.in/admin/corporate-tickets/action/generate-dpr-report?TicketID=" . urlencode($TicketID);

// Compose email subject and body (action may influence subject)
$action = $data['action'] ?? 'Daily Progress Report';
$ClientTicketID = trim($ticket['ClientTicketID'] ?? '');
$HelpdeskComplaintNumber = $ticket['TicketID'] . (!empty($ClientTicketID) ? "/{$ClientTicketID}" : '');

$SiteName = $BranchSite ?: ($ticket['SiteName'] ?? 'Site');

// Create mail (use the $mail object from mail-config — if not, create new)
if (!isset($mail) || !($mail instanceof PHPMailer)) {
    // If include/mail-config-techxpertgroup.php did not create $mail, create minimal PHPMailer
    $mail = new PHPMailer(true);
    // try to reuse your config file settings if it sets variables — otherwise configure simple sendmail
    $mail->isMail();
    $mail->setFrom('no-reply@techxpertindia.in', 'Aryadibusiness');
}

// Clear previous recipients/attachments (safety)
$mail->clearAddresses();
$mail->clearCCs();
$mail->clearBCCs();
$mail->clearAttachments();

// Add recipients
foreach ($recipients as $r) {
    $mail->addAddress($r);
}

// Subject
$mail->Subject = "[" . $HelpdeskComplaintNumber . "] Aryadibusiness - {$action} for {$SiteName}";

// Build HTML body (using your mail header/footer if present)
$POCName = $data['POCName'] ?? ($ticket['ContactPerson'] ?? 'Customer');
$body_middle = "
    <p>Dear {$POCName},</p>
    <p>Your {$action} for site <b>{$SiteName}</b> has been generated.</p>
    <p style='margin:14px 0;'>
        <a href='{$report_url}' target='_blank' style='display:inline-block;padding:10px 18px;background:#027dc1;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;'>
            VIEW REPORT
        </a>
    </p>
    <p style='font-size:12px;color:#666;'>If you cannot click the button, open this link in your browser:<br><a href='{$report_url}' target='_blank'>{$report_url}</a></p>
";

// Use header/footer if available
$body = (isset($mail_header) ? $mail_header : '') . $body_middle . (isset($mail_footer) ? $mail_footer : '');

// HTML body
$mail->isHTML(true);
$mail->Body = $body;

// Optionally set AltBody
$mail->AltBody = "Report URL: {$report_url}";

// Send mail
try {
    $sent = $mail->send();
    $result = ['status' => 'success', 'message' => 'Mail sent', 'recipients' => $recipients];
} catch (Exception $e) {
    $result = ['status' => 'error', 'message' => 'Mail error: ' . $mail->ErrorInfo, 'exception' => $e->getMessage()];
}

// Log result
file_put_contents(__DIR__ . '/api.logs', date('Y-m-d H:i:s') . " SEND RESULT: " . json_encode($result) . PHP_EOL, FILE_APPEND);

// Return JSON
header('Content-Type: application/json');
echo json_encode($result);
exit;
