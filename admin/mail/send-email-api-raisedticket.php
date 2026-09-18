<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if (ob_get_length()) {
    ob_clean();
}

require_once('../../api/common_api_header.php');
require_once('../controllers/common_controllers.php');

// ================= READ INPUT =================
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

// ================= LOG REQUEST =================
file_put_contents('api_innov.logs', $data_raw . PHP_EOL, FILE_APPEND);

// ================= RESPONSE FORMAT =================
$response = [
    'error' => true,
    'message' => 'Invalid request'
];

try {

    if (!$data) {
        throw new Exception("Invalid JSON input");
    }

    if (!isset($data['action'])) {
        throw new Exception("Action is required");
    }

    // ================= MAILER =================
    require 'PHPMailer-master/src/Exception.php';
    require 'PHPMailer-master/src/PHPMailer.php';
    require 'PHPMailer-master/src/SMTP.php';

    require ('include/mail-config-techxpertgroup.php');
    require ('include/common-mail-design.php');

    /* =========================================================
       CORPORATE TICKET RAISED
       ========================================================= */

    if ($data['action'] === "Corporate Ticket Raised") {

        $TicketID      = $data['TicketID'] ?? '';
        $CompanyName   = $data['CorporateName'] ?? '';
        $BranchSite    = $data['BranchSite'] ?? '';
        $BranchCity    = $data['BranchCity'] ?? '';
        $ServiceType   = $data['ServiceType'] ?? '';
        $ServiceName   = $data['ServiceName'] ?? '';
        $SubService    = $data['SubService'] ?? '';
        $Priority      = ucfirst($data['Priority'] ?? '');
        $Message       = nl2br(htmlspecialchars($data['Message'] ?? ''));
        $Status        = $data['Status'] ?? '';
        $RaisedBy      = $data['RaisedBy'] ?? '';
        $CreatedDate   = $data['CreatedDate'] ?? '';
        $CreatedTime   = $data['CreatedTime'] ?? '';
        $TechnicianName = $data['TechnicianName'] ?? 'Not Assigned';
        $AccountManagerName=$data['AccountManagerName']??' ';
        $AccountManagerNumber=$data['AccountManagerNumber']?? '';
        $AccountManagerEmail=$data['AccountManagerEmail']?? '';
        $QuationID=$data['QuationID']??'';

        if (empty($TicketID)) {
            throw new Exception("TicketID is required");
        }

        $quotationTableHtml = '';

            if (!empty($QuationID)) {

                $apiUrl = "https://techxpertindia.in/api/get_quotation_line_Items.php";
                $QuationID='27729';
                $payload = json_encode([
                    "QuotationID" => (int)$QuationID
                ]);

                $ch = curl_init($apiUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_TIMEOUT        => 5
                ]);

                $apiResponse = curl_exec($ch);
                curl_close($ch);

                $quotationData = json_decode($apiResponse, true);

                if (!empty($quotationData['data']) && $quotationData['error'] === false) {

                    // ================= BUILD QUOTATION TABLE =================
                    $quotationTableHtml .= "
                    <tr>
                        <td style='padding:0 30px 8px;
                                   font-size:13px;
                                   font-weight:600;
                                   color:#111827'>
                            Quotation Details
                        </td>
                    </tr>

                    <tr>
                        <td style='padding:0 30px 20px'>
                            <table width='100%' cellpadding='8' cellspacing='0'
                                   style='border-collapse:collapse;
                                          font-size:12px;
                                          color:#111827;
                                          border:1px solid #d1d5db'>
                                <tr style='background:#eef2ff; font-weight:600'>
                                    <td style='border:1px solid #d1d5db'>#</td>
                                    <td style='border:1px solid #d1d5db'>Item Description</td>
                                    <td style='border:1px solid #d1d5db'>Qty</td>
                                    <td style='border:1px solid #d1d5db'>UoM</td>
                                   
                                </tr>
                    ";

                    $i = 1;
                    $grandTotal = 0;

                    foreach ($quotationData['data'] as $item) {

                        $qty   = $item['Quantity'];
                        $price = $item['Price'];
                        $total = $item['TotalAmount'];
                        $grandTotal += $total;

                        $quotationTableHtml .= "
                            <tr>
                                <td style='border:1px solid #d1d5db'>{$i}</td>
                                <td style='border:1px solid #d1d5db'>{$item['LineItemName']}</td>
                                <td style='border:1px solid #d1d5db'>{$qty}</td>
                                <td style='border:1px solid #d1d5db'>{$item['UoM']}</td>
                                
                            </tr>
                        ";
                        $i++;
                    }

                    $quotationTableHtml .= "
                            
                            </table>
                        </td>
                    </tr>
                    ";
                }
            }


        $mail->Subject = "New Service Ticket Raised | $TicketID | $BranchSite";

$body_middle = "
<tr>
    <td style='padding:24px 30px 8px;
               font-size:20px;
               font-weight:600;
               color:#111827'>
        New Ticket Raised
    </td>
</tr>

<tr>
    <td style='padding:0 30px 16px;
               font-size:13px;
               color:#4b5563;
               line-height:1.6'>
        A new service ticket has been successfully raised in the Aryadibusiness system.
        Please review the details below and take action as per defined SLA.
    </td>
</tr>

<tr>
    <td style='padding:0 30px 20px'>
        <table width='100%' cellpadding='8' cellspacing='0'
               style='border:1px solid #d1d5db;
                      border-collapse:collapse;
                      font-size:12px;
                      color:#111827'>

            <!-- ROW 1 -->
            <tr style='background:#f9fafb'>
                <td style='border:1px solid #d1d5db; width:16%'><strong>Ticket ID</strong></td>
                <td style='border:1px solid #d1d5db; width:17%'>$TicketID</td>

                <td style='border:1px solid #d1d5db; width:16%'><strong>Priority</strong></td>
                <td style='border:1px solid #d1d5db; width:17%; font-weight:600; color:#b91c1c'>$Priority</td>

                <td style='border:1px solid #d1d5db; width:16%'><strong>Status</strong></td>
                <td style='border:1px solid #d1d5db; width:18%'>$Status</td>
            </tr>

            <!-- ROW 2 -->
            <tr>
                <td style='border:1px solid #d1d5db'><strong>Company Name</strong></td>
                <td style='border:1px solid #d1d5db'>$CompanyName</td>

                <td style='border:1px solid #d1d5db'><strong>Service Type</strong></td>
                <td style='border:1px solid #d1d5db'>$ServiceType</td>

                <td style='border:1px solid #d1d5db'><strong>Service</strong></td>
                <td style='border:1px solid #d1d5db'>$ServiceName</td>
            </tr>

            <!-- ROW 3 -->
            <tr style='background:#f9fafb'>
                <td style='border:1px solid #d1d5db'><strong>Sub Service</strong></td>
                <td style='border:1px solid #d1d5db'>$SubService</td>

                <td style='border:1px solid #d1d5db'><strong>Branch</strong></td>
                <td style='border:1px solid #d1d5db'>$BranchSite</td>

                <td style='border:1px solid #d1d5db'><strong>City</strong></td>
                <td style='border:1px solid #d1d5db'>$BranchCity</td>
            </tr>

            <!-- ROW 4 -->
            <tr>
                <td style='border:1px solid #d1d5db'><strong>Raised By</strong></td>
                <td style='border:1px solid #d1d5db'>$RaisedBy</td>

                <td style='border:1px solid #d1d5db'><strong>Technician</strong></td>
                <td style='border:1px solid #d1d5db; font-weight:600; color:#1d4ed8'>$TechnicianName</td>

                <td style='border:1px solid #d1d5db'><strong>Raised On</strong></td>
                <td style='border:1px solid #d1d5db'>$CreatedDate $CreatedTime</td>
            </tr>

        </table>
    </td>
</tr>

<tr>
    <td style='padding:0 30px 6px;
               font-size:13px;
               font-weight:600;
               color:#111827'>
        Issue Description
    </td>
</tr>

<tr>
    <td style='padding:0 30px 20px'>
        <div style='background:#f9fafb;
                    border:1px solid #d1d5db;
                    border-left:3px solid #2563eb;
                    padding:10px;
                    font-size:12px;
                    color:#374151;
                    line-height:1.6 ; color:red;'>
            $Message
        </div>
    </td>
</tr>


<tr>
    <td style='padding:0 30px 8px;
               font-size:13px;
               font-weight:600;
               color:#111827'>
        Contact Person
    </td>
</tr>

<tr>
    <td style='padding:0 30px 20px'>
        <table width='100%' cellpadding='0' cellspacing='0'
               style='border:1px solid #c7d2fe;
                      border-collapse:collapse;
                      background:#eef2ff;
                      font-size:12px;
                      color:#1f2937'>

            <!-- Header -->
            <tr>
                <td style='padding:8px 12px;
                           background:#e0e7ff;
                           font-weight:600;
                           border-bottom:1px solid #c7d2fe'>
                    Account Manager Details
                </td>
            </tr>

            <!-- Content -->
            <tr>
                <td style='padding:10px 12px; line-height:1.8'>
                    <strong>Name:</strong> $AccountManagerName<br>
                    <strong>Phone:</strong> $AccountManagerNumber<br>
                    <strong>Email:</strong> 
                    <a href='mailto:$AccountManagerEmail'
                       style='color:#1d4ed8;
                              text-decoration:none'>
                        $AccountManagerEmail
                    </a>
                </td>
            </tr>

        </table>
    </td>
</tr>


<tr>
    <td style='padding:0 30px 18px;
               font-size:11px;
               color:#6b7280'>
        This is a system-generated email. Please do not reply to this message.
    </td>
</tr>
";




        $mail->Body = $mail_header . $body_middle  . $quotationTableHtml . $mail_footer;

        if (!empty($data['ToEmail'])) {
            $mail->addAddress($data['ToEmail']);
        } else {
            throw new Exception("ToEmail is required");
        }

        if (!empty($data['CCEmail'])) {
            $mail->addCC($data['CCEmail']);
        }
    }

    // ================= SEND MAIL =================
    if (!$mail->send()) {
        throw new Exception($mail->ErrorInfo);
    }

    // ================= SUCCESS =================
    $response = [
        'error' => false,
        'message' => 'Mail sent successfully',
        'ticket_id' => $TicketID
    ];

} catch (Exception $e) {

    http_response_code(400);
    $response = [
        'error' => true,
        'message' => $e->getMessage()
    ];
}

// ================= OUTPUT =================
echo json_encode($response);
exit;
