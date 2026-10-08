<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php'; 
include '../../controllers/common_controllers.php';
require_once __DIR__ . '/../../includes/aryadi_pdf_theme.php'; 
// Start MPDF
$html = "Test HTML";
$mpdf = new \Mpdf\Mpdf();
$conn = _connectodb();
$core = new Core();
$category_obj = new Categories($conn);
$categories_array = $category_obj->setCategoriesArray();
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

if(isset($_POST))
{
    $Action = $_POST['Action'];
    $ServicereportID = $_POST['ServiceReportID'];
    //$ServicereportID = 202;
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
    

    $corporateticket_obj = new Corporateticket($conn);
    $ticket_details = $corporateticket_obj->GetTicketDetails($service_reports_details['TicketID']);
    $branch_details = $ticket_details['branch_details'];
    $BranchSite = $branch_details['BranchSite'];
    $CompanyID = $branch_details['CompanyID'];
    $BranchMobile = $branch_details['BranchMobile'];
    $BranchAddress1 = $branch_details['BranchAddress1'];
    $BranchEmail = $branch_details['BranchEmail'];
    $BranchAccountManager = $branch_details['AccountBranchManager'];
    $CorporateID = $ticket_details['CorporateID'];

    $Company_details=$corporateticket_obj->GetCompanyDetails($branch_details['CompanyID']);

    // $Customer=$Company['CompanyName']
    

    $where = "WHERE TicketID = " . $service_reports_details['TicketID'] . " ORDER BY CreatedDate DESC, CreatedTime DESC";
     $ticket_history_array = $core->_getTableRecords($conn,'corporate_ticket_status_history',$where);

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

     $time_section_html = apdf_turnaround_box($totalTime, $workAccept, $quotationTime, $workTime, $verifyTime);
     


   $company_name = $Company_details['CompanyName'];


    $Category = '';
    $BranchAssetID = '';
    $CategoryName = "N.A.";
    $SubCategoryName = "N.A.";
    $EquipmentName = "N.A.";
    $Make = "N.A.";
    $Model = "N.A.";
    $AssetLocation = "N.A.";
    $SerialNumber = "N.A.";
    
    // Check if 'Type' exists and its value
    if (isset($ticket_details['ticket_details']['Type'])) 
    {
        // Check if 'Type' is 'AMC'
        if ($ticket_details['ticket_details']['Type'] == 'AMC') 
        {
            $BranchAssetID = isset($ticket_details['ticket_details']['BranchAssetID']) ? $ticket_details['ticket_details']['BranchAssetID'] : '';
            // echo $BranchAssetID;
            if ($BranchAssetID != -1) {
                $where = "WHERE ID = $BranchAssetID";
                $BranchAssetDetails = _getTableDetails($conn, 'branch_assets', $where);
                $EquipmentName = $BranchAssetDetails['EquipmentName'];
                if($EquipmentName == "")
                    $EquipmentName = "N.A.";
                $Make = $BranchAssetDetails['Make'];
                if($Make == "")
                    $Make = "N.A.";
                $Model = $BranchAssetDetails['Model'];
                if($Model == "")
                    $Model = "N.A.";
                $AssetLocation = $BranchAssetDetails['EquipmentLocation'];
                if($AssetLocation == "")
                    $AssetLocation = "N.A.";
                $SerialNumber = $BranchAssetDetails['SNo'];
                if($SerialNumber == "")
                    $SerialNumber = "N.A.";
    
                if (isset($BranchAssetDetails['Category'])) 
                {
                    $Category = $BranchAssetDetails['Category'];
                   
                    if(isset($categories_array[$Category]))
                    {
                        $CategoryName = $categories_array[$Category]['CategoryName'];
                    }
                } 
                else 
                {
                }

                $SubCategory =  $BranchAssetDetails['SubCategory'];
                if($SubCategory != "")
                {
                    $subcategory_details = $core->_getTableDetails($conn,'manage_subcategories','where ID = '.$SubCategory);
                    if($subcategory_details != null)
                    {
                        $SubCategoryName = $subcategory_details['SubCategoriesName'];
                    }
                }
            }
        }
    } else {
        //echo 'Type key is not defined.';
    }


    $ticket_overview = $ticket_details['ticket_details'];
    $Type = $ticket_overview['Type'];
    $TicketID = $ticket_overview['TicketID'];
    $ClientTicketID = $ticket_overview['ClientTicketID'];
    if($ClientTicketID == "")
    {
        $ClientTicketID = "N.A.";
    }
    $RegisteredDate = $ticket_overview['CreatedDate'];
    $CloseDate = $ticket_overview['CloseDate'];
    $CloseTime = $ticket_overview['CloseTime'];
    $Service = $ticket_overview['Service'];
    $Subservice = $ticket_overview['Subservice'];
    $RegisteredTime = $ticket_overview['CreatedTime'];

    $report_images = $corporateticket_obj->GetTicketMedia($service_reports_details['TicketID']);
    $AttendedDate = $corporateticket_obj->GetAttendedDate($service_reports_details['TicketID']);
    $AttendedDate_html = "";
    if($AttendedDate != "")
    {
        $AttendedDate_html = '
        <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Attended Date</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$AttendedDate.'</td>
                                </tr>';
    }

    

    $employee_obj = new Employee($conn);
    $employee_array = $employee_obj->setEmployeeArray('All');
    $AssignedTo = $ticket_overview['AssignedTo'];
    $TechnicianName = isset($employee_array[$AssignedTo]['Name']) ? $employee_array[$AssignedTo]['Name'] : '';
    $TechnicianPhoneNumber = isset($employee_array[$AssignedTo]['ContactNumber']) ? $employee_array[$AssignedTo]['ContactNumber'] : '';

    $BranchAccountManagerEmail = "";
    if($BranchAccountManager != -1 && $BranchAccountManager != "" && isset($employee_array[$BranchAccountManager]['Email']))
    {
        $BranchAccountManagerEmail = $employee_array[$BranchAccountManager]['Email']; 
    } 

    $corporate_details = $ticket_details['corporate_details'];
    $CompanyEmail = "";
    if($corporate_details['CompanyEmail']!="")
    {
        $CompanyEmail = $corporate_details['CompanyEmail'];
    }

    // Quotation details
    $quotation_detail = $corporateticket_obj->GetQuotationDetail($service_reports_details['TicketID']);
    $quotation_html = "";
    if($quotation_detail != null)
    {
        $quotation_line_items = $corporateticket_obj->GetQuotationLineItems($quotation_detail['ID']);
        $line_item_html = '';
        $i = 1;
        $quotation_html = "";
        foreach($quotation_line_items as $line_item)
        {
            $line_item_html = $line_item_html.'<tr>';
            $line_item_html = $line_item_html.'<td style="padding:5px; width:5%;">'.$i.'</td>';
            $line_item_html = $line_item_html.'<td style="padding:5px; width:45%;">'.$line_item['LineItemName'].'</td>';
            $line_item_html = $line_item_html.'<td style="padding:5px; width:15%;">'.$line_item['ARCCode'].'</td>';
            $line_item_html = $line_item_html.'<td style="padding:5px; width:15%;">'.$line_item['HSN'].'</td>';
            $line_item_html = $line_item_html.'<td style="padding:5px; width:10%;">'.$line_item['UoM'].'</td>';
            $line_item_html = $line_item_html.'<td style="padding:5px; width:10%;">'.$line_item['Qty'].'</td>';
            $line_item_html = $line_item_html.'</tr>';
            $i++;
        }
        
        $line_item_table = '<table class="bottom_signature_table" style="border-top:none;">
                    <tbody>
                        <tr style="background:#f4f4f4;">
                            <td class="bottom_table_bold" style="width:5%;">#</td>
                            <td class="bottom_table_bold" style="width:45%;">Item & Description</td>
                            <td class="bottom_table_bold" style="width:15%;">ARC</td>
                            <td class="bottom_table_bold" style="width:15%;">HSN/SAC</td>
                            <td class="bottom_table_bold" style="width:10%;">UOM</td>
                            <td class="bottom_table_bold" style="width:10%;">Qty</td>
                           
                            
                        </tr>'.$line_item_html.'
                    </tbody>
                </table>';
        $quotation_html = 
        apdf_data_box('Line Items Used', array(
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
$ClientSignature = apdf_signature_html($ClientSignature, $service_reports_details['CreatedDate'], $service_reports_details['CreatedTime']);
$image_pre_td=[];
$image_post_td=[];
$image_service_report=[];
if (!is_array($report_images)) {
    $report_images = array();
}

foreach ($report_images as $report_image) 
{
    $original_image_path = '../../media/ticket_media/' . $report_image['Image'];
    $compressed_image_path = '../../media/ticket_media/compressed_' . $report_image['Image'];
    if (!is_file($original_image_path)) {
        continue;
    }

    // Compress the image
    if ($core->compressImage($original_image_path, $compressed_image_path, 50) === null) {
        $compressed_image_path = $original_image_path;
    }

    $image_cell = apdf_image_cell($compressed_image_path, apdf_join(array($report_image['CreatedDate'], $report_image['CreatedTime']), ' '));
    if($report_image['Action'] == "pre_img")
    {
        $image_pre_td[] = $image_cell;
    }
    if($report_image['Action'] == "Service_Report")
    {
        $image_service_report[] = $image_cell;
    }
    if($report_image['Action'] == "post_img")
    {
        $image_post_td[] = $image_cell;
    }
}

// Customer HVAC Code
$HVACReportHTML = "";
$HVACReportHTML_div = "";
$AssetCondition_html = "";
if($Category == 34)
{
    $where = "WHERE ServiceReportID = $ServicereportID";
    $response_hvac = _getTableDetails($conn, 'hvac_general_service_report', $where);
    if($response_hvac != null)
    {
        $TicketID_old = $TicketID;
        extract($response_hvac);
        $HVACReportHTML .= '
        <tr>
            <td class="custumer_right_border">
                <table class = "report_details_inside">
                    <tr>
                        <td style="width:60%;" class="">Grill Temperature (C/F)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($GrillTemperature) ? $GrillTemperature : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Ambient Temperature (C/F)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($AmbientTemperature) ? $AmbientTemperature : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Compressor current(AMPS)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($Compressor) ? $Compressor : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Voltage (Volts)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($Voltage) ? $Voltage : 'N/A').'</td>
                    </tr>
                     <tr>
                        <td style="width:60%;" class="">Room Temperature (C/F)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($RoomTemperature) ? $RoomTemperature : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Indoor Fan Motor Current (Amps)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($IndoorFan) ? $IndoorFan : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Compressor Discharge Pressure (PSI)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CompressorDischarge) ? $CompressorDischarge : 'N/A').'</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="report_details_inside">
                    <tr>
                        <td style="width:60%;" class="">Return Air Temperature (C/F)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($ReturnAirTemperature) ? $ReturnAirTemperature : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Supply Air Temperature (C/F)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($SupplyAirTemperature) ? $SupplyAirTemperature : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Total Current (Amps)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($TotalCurrent) ? $TotalCurrent : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Condenser Air Outlet (C/F)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CondenserAirOutlet) ? $CondenserAirOutlet : 'N/A').'</td>
                    </tr>
                     <tr>
                        <td style="width:60%;" class="">Outdoor Fan Motor Current (Amps)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($OutdoorFan) ? $OutdoorFan : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Compressor Suction Pressure (PSI)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CompressorSuction) ? $CompressorSuction : 'N/A').'</td>
                    </tr>
                      <tr>
                        <td style="width:60%;" class="">Condenser Air Inlet (C/F)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CondenserAirInlet) ? $CondenserAirInlet : 'N/A').'</td>
                    </tr>
                </table>
            </td>
        </tr>';


        if($AssetCondition != "")
        {
            $AssetCondition_html = '
            <tr>
                <td style="width:50%;" class="costumer_detail_bold">Asset Condition</td>
                <td style="width:10%;">:</td>
                <td style="width:40%;">'.$AssetCondition.'</td>
            </tr>';
        }

        $HVACReportHTML_div = apdf_box('Measured Parameters', '<table class="customer_details">'.$HVACReportHTML.'</table>');
        $TicketID = $TicketID_old;
    }

    
}

// Build Ticket History Table
if (!is_array($ticket_history_array)) {
    $ticket_history_array = array();
}
$history_rows = '';
$count = 1;
foreach(array_reverse($ticket_history_array) as $ticket_history) {
    $EmployeeName = "";
    if($ticket_history['AssignedTo'] != "" && $ticket_history['AssignedTo'] != -1) {
        $hist_emp = getEmployeeDetailsfromID($conn,$ticket_history['AssignedTo']);
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
$ratingHtml = apdf_rating_html($Rating);

$header_logo = '../../media/pdf-assets/aryadi-logo.png';
if($CorporateID == 183)
{
    $header_logo = '../../img/innov_logo.jpg';
}

$AssetConditionValue = ($AssetCondition_html !== '' && isset($AssetCondition)) ? $AssetCondition : '';
$ReportNumber = "ARYADI-AMC-".sprintf("%05d", (int) $ServicereportID);
$registered_on = apdf_join(array($RegisteredDate, $RegisteredTime), ' ');
$closed_on = apdf_join(array($CloseDate, $CloseTime), ' ');

$ticket_rows = array(
    array('Type', apdf_text($Type)),
    array('Category', apdf_text($CategoryName)),
    array('Sub Category', apdf_text($SubCategoryName)),
    array('Status', apdf_text(isset($ticket_overview['Status']) ? $ticket_overview['Status'] : '')),
    array('Attended Date', apdf_text($AttendedDate)),
);
if ($AssetConditionValue !== '') {
    $ticket_rows[] = array('Asset Condition', apdf_text($AssetConditionValue));
}

$images_html =
    apdf_box('Pre Image(s)', '<div class="block block_first">'.apdf_image_grid($image_pre_td, 'No pre-work images captured.').'</div>', count($image_pre_td).' Photo(s)').
    apdf_box('Post Image(s)', '<div class="block block_first">'.apdf_image_grid($image_post_td, 'No post-work images captured.').'</div>', count($image_post_td).' Photo(s)');
if(!empty($image_service_report))
{
    $images_html .= apdf_box('Service Report', '<div class="block block_first">'.apdf_image_grid($image_service_report).'</div>', count($image_service_report).' Photo(s)');
}

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AMC Service Report '.$ReportNumber.'</title>
    <style>'.apdf_css().'</style>
</head>
<body>
    '.apdf_header('AMC SERVICE REPORT', 'ANNUAL MAINTENANCE CONTRACT', $header_logo).'

    '.apdf_stats(array(
        array('Report Number', $ReportNumber),
        array('Ticket No', apdf_text($TicketID)),
        array('Registered On', apdf_text($registered_on)),
        array('Closed On', apdf_text($closed_on)),
    )).'

    '.apdf_two_col(
        'Customer Details',
        array(
            array('Customer Name', apdf_text($company_name)),
            array('Branch Name', apdf_text($BranchSite)),
            array('Address', apdf_text($BranchAddress1)),
            array('Mobile No.', apdf_text($BranchMobile)),
            array('Client Ticket No.', apdf_text($ClientTicketID)),
        ),
        'Service Ticket Details',
        $ticket_rows
    ).'

    '.apdf_asset_section($EquipmentName, $Make, $Model, $SerialNumber, $AssetLocation).'

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
    '.$images_html.'
    '.$HVACReportHTML_div.'
    '.$ticket_history_html.'

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

$mpdf = apdf_create_mpdf('AMC Service Report '.$ReportNumber, 'AMC Service Report · Ticket '.$TicketID);
$fullHTML = $html;
$mpdf->WriteHTML($html);
$pdf_name = "service-report-".$TicketID.".pdf";
$pdfFilePath = '../reports/'.$pdf_name.'';
$mpdf->Output($pdfFilePath, 'F');
//$mpdf->Output();
$response['pdfname'] = $pdf_name;
if($Action == "Send")
{
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
    $url = "https://techxpertindia.in/admin/mail/send-service-report.php";
    $core->sendMailRequest($mail_data,$url);
}
echo json_encode($response);
?>