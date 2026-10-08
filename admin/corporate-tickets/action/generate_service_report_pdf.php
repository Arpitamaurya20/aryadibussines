<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php'; 
include '../../controllers/common_controllers.php'; 
require_once '../../includes/aryadi_pdf_theme.php';
// Start MPDF
$html = "Test HTML";
$conn = _connectodb();
$core = new Core();
$api_key = "AIzaSyDU0suOSG-X34RvDzawjDGHbX1C5JrHHsw";

function getTimeDiff($start, $end) {
    if(!$start || !$end) return "-";

    $startTime = new DateTime($start);
    $endTime   = new DateTime($end);
    $diff      = $endTime->diff($startTime);

    $formatted = '';
    if ($diff->d >= 7) { 
        $weeks = floor($diff->d / 7);
        $days  = $diff->d % 7;
        $formatted .= $weeks . " w ";
        if ($days > 0) $formatted .= $days . " d ";
    } else {
        if ($diff->d > 0) $formatted .= $diff->d . " d ";
    }
    if ($diff->h > 0) $formatted .= $diff->h . " h ";
    if ($diff->i > 0) $formatted .= $diff->i . " m ";
    if ($diff->s > 0) $formatted .= $diff->s . " s";

    return trim($formatted);
}


if(isset($_POST) || isset($_GET))
{
    $Action = "";
    if(isset($_POST['Action']))
    {
        $Action = $_POST['Action'];
    }
    $ServicereportID = "";
    if(isset($_POST['ServiceReportID']))
    {
        $ServicereportID = $_POST['ServiceReportID'];
    }
    else
    {
        if(isset($_GET['ServiceReportID']))
        {
            $ServicereportID = $_GET['ServiceReportID'];
        } 
    }
    $service_report_obj = new Servicereport($conn);
    $service_reports_details = $service_report_obj->GetServiceReportDetailsbyID($ServicereportID);
    $ProblemReportedByClient = $service_reports_details['ProblemReportedByClient'];
    $Observation = $service_reports_details['Observation'];
    $ActionTaken = $service_reports_details['ActionTaken'];
    $Remarks = $service_reports_details['Remarks'];
    $ClientRepresentative = $service_reports_details['ClientRepresentative'];
    $ClientRepresentativeContact = $service_reports_details['ClientRepresentativeContact'];
    $ClientRepresentativeDesignation = $service_reports_details['ClientRepresentativeDesignation'];
    $ClientSignature = $service_reports_details['ClientSignature'];
    $ClientRepresentativeEmails = $service_reports_details['ClientRepresentativeEmails'];
    $address = $service_reports_details['Location'];
    $where = "WHERE TicketID = " . $service_reports_details['TicketID'] . " ORDER BY CreatedDate DESC, CreatedTime DESC";
     $ticket_history_array = $core->_getTableRecords($conn,'corporate_ticket_status_history',$where);
     if (!is_array($ticket_history_array)) {
         $ticket_history_array = array();
     }

     $ComplaintCreateTime   = null;
     $WorkStartTime         = null;
     $QuotationSendTime     = null;
     $QuotationApprovalTime = null;
     $WorkCompleteTime      = null;
     $VerifyTime            = null;
     
     foreach($ticket_history_array as $ticket_history) {
         $status    = strtolower($ticket_history['Status']);
         $timestamp = $ticket_history['CreatedDate']." ".$ticket_history['CreatedTime'];
     
         if (stripos($status, "raised") !== false) {
             $ComplaintCreateTime = $timestamp;
         }
         if (stripos($status, "assigned") !== false) {
             // optional step if you want to measure "Assign delay"
         }
         if (stripos($status, "quote sent") !== false) {
             $QuotationSendTime = $timestamp;
         }
         if (stripos($status, "quote approved") !== false) {
             $QuotationApprovalTime = $timestamp;
         }
         if (stripos($status, "work in progress") !== false) {
             $WorkStartTime = $timestamp;
         }
         if (stripos($status, "generate otp") !== false) {
             $VerifyTime = $timestamp;
         }
         if (stripos($status, "closed") !== false) {
             $WorkCompleteTime = $timestamp;
         }
     }
     

     $totalTime     = getTimeDiff($ComplaintCreateTime, $WorkCompleteTime);
     $workAccept    = getTimeDiff($ComplaintCreateTime, $WorkStartTime);
     $quotationTime = getTimeDiff($QuotationSendTime, $QuotationApprovalTime);
     $workTime      = getTimeDiff($WorkStartTime, $WorkCompleteTime);
     $verifyTime    = getTimeDiff($WorkCompleteTime, $VerifyTime);

     $tat_items = array(
         array('Total Time', $totalTime, 'Closed - Raised'),
         array('Work Accept', $workAccept, 'Work Start - Raised'),
         array('Quotation Time', $quotationTime, 'Approved - Sent'),
         array('Work Time', $workTime, 'Closed - Work Start'),
         array('Verify Time', $verifyTime, 'OTP - Closed'),
     );
     $tat_operators = array('=', '+', '+', '+');
     $tat_cells = '';
     foreach ($tat_items as $tat_index => $tat_item) {
         $last = $tat_index == count($tat_items) - 1 ? ' border-right:none;' : '';
         $tat_cells .= '
            <td style="width:17.6%;'.$last.'">
                <div class="stat_label">'.$tat_item[0].'</div>
                <div class="stat_value">'.apdf_text($tat_item[1] === '' ? '-' : $tat_item[1]).'</div>
                <div class="stat_hint">'.$tat_item[2].'</div>
            </td>';
         if (isset($tat_operators[$tat_index])) {
             $tat_cells .= '<td class="op">'.$tat_operators[$tat_index].'</td>';
         }
     }
     $time_section_html = apdf_box('Turnaround Time', '<table class="stats tat" width="100%" style="width:100%; margin-top:0; border:none;"><tr>'.$tat_cells.'</tr></table>');
     


    if($address == "")
    {
        if($service_reports_details['Latitude'] == "" && $service_reports_details['Longitude'] == "")
        {

        }
        else
        {
            // Example usage
            $latitude = $service_reports_details['Latitude'];
            $longitude = $service_reports_details['Longitude'];
            $address = $core->getAddress($latitude, $longitude, $api_key);

            $data = [
                'Location' => $address
            ];

            // Define the condition for the WHERE clause
            $where = [
                'ID' => $ServicereportID
            ];

            // Call the updated function
            $response = $core->_UpdateTableRecords_prepare($conn, 'corporate_ticket_general_service_report', $data, $where);
        }
    }
    

    $corporateticket_obj = new Corporateticket($conn);
    $ticket_details = $corporateticket_obj->GetTicketDetails($service_reports_details['TicketID']);
    $branch_details = $ticket_details['branch_details'];
    $BranchSite = $branch_details['BranchSite'];
    $BranchMobile = $branch_details['BranchMobile'];
    $BranchAddress1 = $branch_details['BranchAddress1'];
    $BranchEmail = $branch_details['BranchEmail'];
    $BranchAccountManager = $branch_details['AccountBranchManager'];
    $CorporateID = $ticket_details['CorporateID'];


    $ticket_overview = $ticket_details['ticket_details'];
    $Type = $ticket_overview['Type'];
    $TicketID = $ticket_overview['TicketID'];
    $ClientTicketID = $ticket_overview['ClientTicketID'];
    $RegisteredDate = $ticket_overview['CreatedDate'];
    $CloseDate = $ticket_overview['CloseDate'];
    $CloseTime = $ticket_overview['CloseTime'];
    $Service = $ticket_overview['Service'];
    $Subservice = $ticket_overview['Subservice'];
    $RegisteredTime = $ticket_overview['CreatedTime'];
    $TicketStatus = isset($ticket_overview['Status']) ? $ticket_overview['Status'] : '';

    $report_images = $corporateticket_obj->GetTicketMedia($service_reports_details['TicketID']);
    $AttendedDate = $corporateticket_obj->GetAttendedDate($service_reports_details['TicketID']);

    $employee_obj = new Employee($conn);
    $employee_array = $employee_obj->setEmployeeArray('All');
    if (!is_array($employee_array)) {
        $employee_array = array();
    }
    $AssignedTo = isset($ticket_overview['AssignedTo']) ? (int) $ticket_overview['AssignedTo'] : 0;
    $TechnicianName = "";
    $TechnicianPhoneNumber = "";
    if ($AssignedTo > 0 && isset($employee_array[$AssignedTo]['Name'])) {
        $TechnicianName = $employee_array[$AssignedTo]['Name'];
        $TechnicianPhoneNumber = isset($employee_array[$AssignedTo]['ContactNumber']) ? $employee_array[$AssignedTo]['ContactNumber'] : '';
    } elseif ($AssignedTo > 0) {
        $assigned_emp = getEmployeeDetailsfromID($conn, $AssignedTo);
        if (is_array($assigned_emp) && !empty($assigned_emp['Name'])) {
            $TechnicianName = $assigned_emp['Name'];
            $TechnicianPhoneNumber = isset($assigned_emp['ContactNumber']) ? $assigned_emp['ContactNumber'] : '';
        }
    }

    $BranchAccountManagerEmail = "";
    if($BranchAccountManager != -1 && $BranchAccountManager != "" && isset($employee_array[$BranchAccountManager]['Email']))
    {
        $BranchAccountManagerEmail = $employee_array[$BranchAccountManager]['Email']; 
    } 

    $corporate_details = isset($ticket_details['corporate_details']) && is_array($ticket_details['corporate_details']) ? $ticket_details['corporate_details'] : array();
    $CompanyEmail = "";
    if(!empty($corporate_details['CompanyEmail']))
    {
        $CompanyEmail = $corporate_details['CompanyEmail'];
    }
    $CompanyName = !empty($corporate_details['CompanyName']) ? $corporate_details['CompanyName'] : '';

    // Quotation details
    $quotation_detail = $corporateticket_obj->GetQuotationDetail($service_reports_details['TicketID']);
    $quotation_html = "";
    if($quotation_detail != null)
    {
        $quotation_line_items = $corporateticket_obj->GetQuotationLineItems($quotation_detail['ID']);
        if (!is_array($quotation_line_items)) {
            $quotation_line_items = array();
        }
        $line_item_html = '';
        $i = 1;
        foreach($quotation_line_items as $line_item)
        {
            $line_item_html .= '<tr'.($i % 2 == 0 ? ' class="alt"' : '').'>
                <td class="num">'.$i.'</td>
                <td>'.apdf_text($line_item['LineItemName']).'</td>
                <td>'.apdf_text($line_item['ARCCode']).'</td>
                <td>'.apdf_text($line_item['HSN']).'</td>
                <td>'.apdf_text($line_item['UoM']).'</td>
                <td>'.apdf_text($line_item['Qty']).'</td>
            </tr>';
            $i++;
        }
        if ($line_item_html == '') {
            $line_item_html = '<tr><td colspan="6" class="muted">No line items recorded.</td></tr>';
        }

        $quotation_html = apdf_data_box('Line Items Used', array(
            array('#', 6),
            array('Item &amp; Description', 44),
            array('ARC', 14),
            array('HSN/SAC', 14),
            array('UOM', 10),
            array('Qty', 12),
        ), $line_item_html);
    }
}
else
{
	die();
}
if($ClientSignature == ""){
    $ClientSignature = '<div class="sign_line"></div><div class="sign_caption">Signature not captured</div>';
}else{
    $ClientSignatureDate = $service_reports_details['CreatedDate'];
    $ClientSignatureTime = $service_reports_details['CreatedTime'];
    $ClientSignature = '
        <img class="sign_img" src="../../media/signature/'.$ClientSignature.'">
        <div class="sign_caption">'.htmlspecialchars(apdf_join(array($ClientSignatureDate, $ClientSignatureTime), ' '), ENT_QUOTES, 'UTF-8').'</div>';
}
$image_pre_td = array();
$image_post_td = array();
$image_service_report = array();
if (!is_array($report_images)) {
    $report_images = array();
}
foreach ($report_images as $report_image) 
{
    $original_image_path = '../../media/ticket_media/' . $report_image['Image'];
    $compressed_image_path = '../../media/ticket_media/compressed_' . $report_image['Image'];
    $imageDate=$report_image['CreatedDate'];
    $imageTime=$report_image['CreatedTime'];
    if (file_exists($original_image_path)) {
        if ($core->compressImage($original_image_path, $compressed_image_path, 50) === null) {
            $compressed_image_path = $original_image_path;
        }
    } else {
        continue;
    }

    $image_cell = apdf_image_cell($compressed_image_path, apdf_join(array($imageDate, $imageTime), ' '));
    if($report_image['Action'] == "pre_img")
    {
        $image_pre_td[] = $image_cell;
    }
    if($report_image['Action'] == "Service Report" || $report_image['Action'] == "Service_Report")
    {
        $image_service_report[] = $image_cell;
    }
    if($report_image['Action'] == "post_img")   
    {
        $image_post_td[] = $image_cell;
    }
}

$images_section_html =
    apdf_box('Pre Image(s)', '<div class="block block_first">'.apdf_image_grid($image_pre_td, 'No pre-work images captured.').'</div>', count($image_pre_td).' Photo(s)').
    apdf_box('Post Image(s)', '<div class="block block_first">'.apdf_image_grid($image_post_td, 'No post-work images captured.').'</div>', count($image_post_td).' Photo(s)');
if(!empty($image_service_report))
{
    $images_section_html .= apdf_box('Service Report', '<div class="block block_first">'.apdf_image_grid($image_service_report).'</div>', count($image_service_report).' Photo(s)');
}


$safety_check_div = "";
$TicketID_S = $service_reports_details['TicketID'];

$sql = "SELECT * FROM technician_safety_check WHERE TicketID = '$TicketID_S' AND IsActive = 1 LIMIT 1";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) > 0){
    $row = mysqli_fetch_assoc($result);
    $checklist = [
        "Is_Uniform"           => "Am i wearing the company uniform?",
        "Has_Jacket"           => "Am i wearing a safety jacket?",
        "Has_Toolkit_Isolated" => "Do i have an Insulated toolkit?",
        "Has_Safety_Shoes"     => "Am i wearing safety shoes?",
        "Has_Ppe_Kit"          => "Do i have the PPE kit?"
    ];

    $rows_html = "";
    $row_index = 1;
    foreach($checklist as $field => $label){
        
        $status_val = strtoupper(trim($row[$field]));
        $status = ($status_val == "YES") 
                    ? "<span class='pill_yes'>YES</span>" 
                    : "<span class='pill_no'>NO</span>";

        $images_html = "<span class='muted'>No image</span>";
        if($status_val == "YES"){
            $images_sql = "SELECT Image FROM ticket_media 
                           WHERE TicketID = '".$TicketID_S."' 
                           AND Action = '".$field."'";
            $images_res = mysqli_query($conn, $images_sql);

            if(mysqli_num_rows($images_res) > 0){
                $images_html = ""; // reset default
                while($img = mysqli_fetch_assoc($images_res)){
                    $images_html .= '<img class="check_img" src="../../media/ticket_media/'.$img['Image'].'" alt="'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'">';
                }
            }
        }

        $rows_html .= "
            <tr".($row_index % 2 == 0 ? " class='alt'" : "").">
                <td class='num'>$row_index</td>
                <td>".htmlspecialchars($label, ENT_QUOTES, 'UTF-8')."</td>
                <td>$status</td>
                <td>$images_html</td>
            </tr>
        ";
        $row_index++;
    }

    $safety_check_div = apdf_data_box('Technician Safety Checklist', array(
        array('#', 6),
        array('Checklist Item', 44),
        array('Status', 12),
        array('Image', 38),
    ), $rows_html);
}


$header_logo = '../../media/pdf-assets/aryadi-logo.png';
if($CorporateID == 183)
{
    $header_logo = '../../img/innov_logo.jpg';
}

// Build Ticket History Table
$skip_statuses = [
    'StateApproved',
    'FinanceApproved',
    'StateRejected',
    'FinanceRejected'
];

$history_rows = '';
$count = 1;
foreach(array_reverse($ticket_history_array) as $ticket_history) {
    if (in_array($ticket_history['Status'], $skip_statuses)) {
        continue;
    }
    $EmployeeName = "";
    if(isset($ticket_history['AssignedTo']) && $ticket_history['AssignedTo'] != "" && $ticket_history['AssignedTo'] != -1 && (int) $ticket_history['AssignedTo'] > 0) {
        $hist_emp = getEmployeeDetailsfromID($conn, $ticket_history['AssignedTo']);
        if (is_array($hist_emp) && !empty($hist_emp['Name'])) {
            $EmployeeName = $hist_emp['Name'];
        }
    }
    $history_rows .= '
            <tr'.($count % 2 == 0 ? ' class="alt"' : '').'>
                <td class="num">'.$count.'</td>
                <td style="color:#0B356E; font-weight:bold;">'.apdf_text($ticket_history['Status']).'</td>
                <td>'.apdf_text($EmployeeName).'</td>
                <td>'.apdf_text($ticket_history['CreatedBy']).'</td>
                <td>'.apdf_text($ticket_history['CreatedDate']).'</td>
                <td>'.apdf_text($ticket_history['CreatedTime']).'</td>
            </tr>';
    $count++;
}
if ($history_rows == '') {
    $history_rows = '<tr><td colspan="6" class="muted">No activity recorded.</td></tr>';
}

$ticket_history_html = apdf_data_box('Activity Log', array(
    array('#', 6),
    array('Status', 24),
    array('Assigned To', 20),
    array('Updated By', 20),
    array('Date', 16),
    array('Time', 14),
), $history_rows);



$Rating = $corporateticket_obj->GetRating($service_reports_details['TicketID']);
$ratingHtml = '';

if (!empty($Rating) && $Rating >= 1 && $Rating <= 5) {
    $Rating = (int) $Rating;
    $ratingHtml = '
        <div class="rating">Customer Rating &nbsp;
            <span class="rating_stars">'.str_repeat('&#9733;', $Rating).'</span><span class="rating_off">'.str_repeat('&#9733;', 5 - $Rating).'</span>
            &nbsp;<b style="color:#0B356E;">'.$Rating.'/5</b>
        </div>';
}

$ReportNumber = "ARYADI-SR-".sprintf("%05d", (int) $ServicereportID);
$registered_on = apdf_join(array($RegisteredDate, $RegisteredTime), ' ');
$closed_on = apdf_join(array($CloseDate, $CloseTime), ' ');

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Report '.$ReportNumber.'</title>
    <style>'.apdf_css().'</style>
</head>
<body>
    '.apdf_header('SERVICE REPORT', 'FIELD SERVICE REPORT', $header_logo).'

    '.apdf_stats(array(
        array('Report Number', $ReportNumber),
        array('Ticket No', apdf_text($TicketID)),
        array('Registered On', apdf_text($registered_on)),
        array('Closed On', apdf_text($closed_on)),
    )).'

    '.apdf_two_col(
        'Customer Details',
        array(
            array('Customer Name', apdf_text($CompanyName)),
            array('Branch Name', apdf_text($BranchSite)),
            array('Address', apdf_text($BranchAddress1)),
            array('Mobile No.', apdf_text($BranchMobile)),
            array('Client Ticket No.', apdf_text($ClientTicketID)),
        ),
        'Service Ticket Details',
        array(
            array('Type', apdf_text($Type)),
            array('Service', apdf_text($Service)),
            array('Sub Service', apdf_text($Subservice)),
            array('Status', apdf_text($TicketStatus)),
            array('Attended Date', apdf_text($AttendedDate)),
            array('Attended Location', apdf_text($address)),
        )
    ).'

    <div class="section">
        '.apdf_section_head('Service Details').'
        <div class="block block_first keep">
            <div class="block_label">Problem Statement</div>
            <div class="block_text">'.apdf_text($ProblemReportedByClient).'</div>
        </div>
        <div class="block keep">
            <div class="block_label">Technician / Engineer Observation</div>
            <div class="block_text">'.apdf_text($Observation).'</div>
        </div>
        <div class="block keep">
            <div class="block_label">Action Taken</div>
            <div class="block_text">'.apdf_text($ActionTaken).'</div>
        </div>
        <div class="block keep">
            <div class="block_label">Technician / Engineer Remarks</div>
            <div class="block_text">'.apdf_text($Remarks).'</div>
        </div>
    </div>

    '.$quotation_html.'
    '.$time_section_html.'
    '.$images_section_html.'
    '.$ticket_history_html.'
    '.$safety_check_div.'

    '.apdf_acknowledgement(
        'Technician Details',
        array(
            array('Name', apdf_text($TechnicianName)),
            array('Phone Number', apdf_text($TechnicianPhoneNumber)),
        ),
        'Onsite Client Representative (Verified By)',
        array(
            array('Name', apdf_text($ClientRepresentative)),
            array('Designation', apdf_text($ClientRepresentativeDesignation)),
            array('Contact Number', apdf_text($ClientRepresentativeContact)),
        ),
        $ClientSignature
    ).'
    '.$ratingHtml.'
</body>
</html>';

$mpdf = new \Mpdf\Mpdf(apdf_mpdf_config());
$mpdf->SetTitle('Service Report '.$ReportNumber);
$mpdf->SetAuthor('Aryadi Business Pvt. Ltd.');
apdf_footer($mpdf, 'Service Report · Ticket '.$TicketID);
$mpdf->WriteHTML($html);
$pdf_name = "service-report-".$TicketID.".pdf";
$pdfFilePath = '../reports/'.$pdf_name.'';
$mpdf->Output($pdfFilePath, 'F');
if(isset($_GET['ServiceReportID']))
{
    $mpdf->Output();  
}
$response['pdfname'] = $pdf_name;
if($Action == "Send")
{
    $url = "https://techxpertindia.in/admin/mail/send-service-report.php";
    if($CorporateID == 183)
    {
        $url = "https://techxpertindia.in/admin/mail/send-email-api-innov.php";
    }
    $core = new Core();
    $mail_data['action'] = "General Service Report";
    $mail_data['SiteName'] = $BranchSite;
    //$mail_data['POCEmail'] = $SitePOCEmail;
    $mail_data['ClientRepresentativeEmails'] = $ClientRepresentativeEmails;
    $mail_data['BranchEmail'] = $BranchEmail;
    $mail_data['CompanyEmail'] = $CompanyEmail;
    $mail_data['BranchAccountManagerEmail'] = $BranchAccountManagerEmail;
    //$mail_data['ZoneEmail'] = $ZoneEmail;
    $mail_data['POCName'] = $ClientRepresentative;
    $mail_data['ReportID'] = $ServicereportID;
    $mail_data['FilePath'] = $pdf_name;
    $mail_data['TicketID'] = $TicketID;
    $mail_data['ClientTicketID'] = $ClientTicketID;
    $mail_data['report_images'] = $report_images;
    
    $core->sendMailRequest($mail_data,$url);
}



echo json_encode($response);
?>
