<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php'; 
include '../../controllers/common_controllers.php'; 
// Start MPDF
$html = "Test HTML";
$mpdf = new \Mpdf\Mpdf([
    'margin_left' => 12,
    'margin_right' => 12,
    'margin_top' => 12,
    'margin_bottom' => 20,
    'margin_footer' => 8,
]);
$conn = _connectodb();
$core = new Core();

// Escapes a value for the report; empty values render as a muted dash.
function sv_text($value)
{
    $text = trim((string)$value);
    if ($text === '') {
        return '<span class="muted">&mdash;</span>';
    }
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

function sv_priority_style($priority)
{
    $p = strtolower(trim((string)$priority));
    if (strpos($p, 'high') !== false || strpos($p, 'critical') !== false || strpos($p, 'urgent') !== false) {
        return 'background:#FDECEC; color:#B91C1C;';
    }
    if (strpos($p, 'medium') !== false || strpos($p, 'moderate') !== false) {
        return 'background:#FEF3C7; color:#92400E;';
    }
    if (strpos($p, 'low') !== false) {
        return 'background:#DCFCE7; color:#166534;';
    }
    return 'background:#E8F2FC; color:#0B356E;';
}

if(isset($_POST))
{
    $Action = $_POST['Action'];
    $SiteVisitID = $_POST['SiteVisitID'];
    //$Action = "Download";
    //$SiteVisitID = 12;
    $site_visits_obj = new Sitevisits($conn);
    $site_visit_details = $site_visits_obj->GetSiteVisitDetail($SiteVisitID);
    $VisitTitle = $site_visit_details['VisitTitle'];
    $ReportNumber = "ARYADI-SV-".sprintf("%05d", $site_visit_details['ID']);
    $ContactPerson = $site_visit_details['ContactPerson'];
    $ContactPersonPhone = $site_visit_details['ContactPersonPhone'];
    $ContactPersonEmail = $site_visit_details['ContactPersonEmail'];
    $ClientSignature = $site_visit_details['ClientSignature'];
    $Summary = $site_visit_details['Summary'];
    $CompletedDate = $site_visit_details['CompletedDate'];
    $CompletedTime = $site_visit_details['CompletedTime'];
    $VisitDate = isset($site_visit_details['CreatedDate']) ? $site_visit_details['CreatedDate'] : '';
    $VisitTime = isset($site_visit_details['CreatedTime']) ? $site_visit_details['CreatedTime'] : '';
    $VisitStatus = isset($site_visit_details['Status']) ? $site_visit_details['Status'] : '';

    $branch_obj = new Branch($conn);
    $branch_details = $branch_obj->getBranchDetailsbyID($site_visit_details);
    $BranchSite = $branch_details['BranchSite'];
    $CompanyID = $branch_details['CompanyID'];
    $BranchMobile = $branch_details['BranchMobile'];
    $BranchAddress1 = $branch_details['BranchAddress1'];
    $BranchEmail = $branch_details['BranchEmail'];
    $BranchAccountManager = $branch_details['AccountBranchManager'];

    $corporateticket_obj = new Corporateticket($conn);
    $Company_details=$corporateticket_obj->GetCompanyDetails($branch_details['CompanyID']);
    $company_name = $Company_details['CompanyName'];


    $employee_obj = new Employee($conn);
    $employee_array = $employee_obj->setEmployeeArray('All');
    $employee_details = $employee_obj->getEmployeeDetailsfromUserName($site_visit_details['CreatedBy']);
    $TechnicianName = "";
    $TechnicianPhoneNumber = "";
    if(isset($employee_details['Name']))
    {
        $TechnicianName = $employee_details['Name']; 
        $TechnicianPhoneNumber = $employee_details['ContactNumber'];
    }
    $CreatedByLabel = $TechnicianName != "" ? $TechnicianName : $site_visit_details['CreatedBy'];

    $BranchAccountManagerEmail = "";
    if($BranchAccountManager != -1 && $BranchAccountManager != "" && isset($employee_array[$BranchAccountManager]['Email']))
    {
        $BranchAccountManagerEmail = $employee_array[$BranchAccountManager]['Email']; 
    } 

    $CompanyEmail = "";
    if($Company_details['CompanyEmail']!="")
    {
        $CompanyEmail = $Company_details['CompanyEmail'];
    }

    $i = 1;
    $site_visit_observations = $site_visits_obj->GetSiteObservations($SiteVisitID);
    $observation_count = is_array($site_visit_observations) ? count($site_visit_observations) : 0;
    $observation_div = '';
    foreach($site_visit_observations as $site_visit_observation)
    {
        //Get Site Observation Media
        $observation_media_array = $site_visit_observation['observation_media'];
        $image_cells = array();
        foreach($observation_media_array as $observation_media)
        {
            $original_image_path = '../../media/site_visits/' . $observation_media['Image'];
            $compressed_image_path = '../../media/site_visits/compressed_' . $observation_media['Image'];

            $imageDate=$observation_media['CreatedDate'];
            $imageTime=$observation_media['CreatedTime'];
            
            // Compress the image
            $core->compressImage($original_image_path, $compressed_image_path, 50); // 75 is the quality percentage
            $image_cells[] = '<td class="img_cell">
                    <img class="obs_img" src="'.$compressed_image_path.'" alt="">
                    <div class="img_meta">'.htmlspecialchars($imageDate.' '.$imageTime, ENT_QUOTES, 'UTF-8').'</div>
                </td>';
        }

        if(count($image_cells) == 0)
        {
            $images_html = '<div class="block_text muted">No images attached.</div>';
        }
        else
        {
            $images_html = '';
            foreach(array_chunk($image_cells, 4) as $image_row)
            {
                while(count($image_row) < 4)
                {
                    $image_row[] = '<td class="img_cell"></td>';
                }
                $images_html .= '<tr>'.implode('', $image_row).'</tr>';
            }
            $images_html = '<table class="img_grid">'.$images_html.'</table>';
        }

        $observation_category = $site_visit_observation['Category'];
        $observation_priority = $site_visit_observation['Priority'];
        $observation_observation = $site_visit_observation['Observation'];
        $observation_companyrecommendation = $site_visit_observation['CompanyRecommendation'];
        $observation_clientrecommendation = $site_visit_observation['ClientRecommendation'];
        $Location = $site_visit_observation['Location'];
        $priority_label = trim((string)$observation_priority) != '' ? htmlspecialchars($observation_priority, ENT_QUOTES, 'UTF-8').' Priority' : 'Priority not set';
        $observation_div = $observation_div.'
        <div class="section">
            <table class="section_head">
                <tr>
                    <td class="section_title">Observation '.$i.'</td>
                    <td class="section_badge">
                        <table class="badge_table" align="right"><tr><td class="badge" style="'.sv_priority_style($observation_priority).'">'.$priority_label.'</td></tr></table>
                    </td>
                </tr>
            </table>
            <table class="kv">
                <tr>
                    <td class="k">Category</td>
                    <td class="v">'.sv_text($observation_category).'</td>
                    <td class="k">Location</td>
                    <td class="v">'.sv_text($Location).'</td>
                </tr>
            </table>
            <div class="block">
                <div class="block_label">Observation</div>
                <div class="block_text">'.sv_text($observation_observation).'</div>
            </div>
            <div class="block">
                <div class="block_label">Image(s)</div>
                '.$images_html.'
            </div>
            <table class="reco">
                <tr>
                    <td class="reco_cell reco_left">
                        <div class="block_label">Client Recommendation</div>
                        <div class="block_text">'.sv_text($observation_clientrecommendation).'</div>
                    </td>
                    <td class="reco_cell">
                        <div class="block_label">Aryadi Business Recommendation</div>
                        <div class="block_text">'.sv_text($observation_companyrecommendation).'</div>
                    </td>
                </tr>
            </table>
        </div>';
        $i++;
    }

    if($observation_div == '')
    {
        $observation_div = '
        <div class="section">
            <table class="section_head"><tr><td class="section_title">Observations</td></tr></table>
            <div class="block"><div class="block_text muted">No observations were recorded for this visit.</div></div>
        </div>';
    }
}
else
{
    die();
}
if($ClientSignature == "")
{
    $ClientSignature = '<div class="sign_line"></div><div class="sign_caption">Signature not captured</div>';
}
else
{
      $ClientSignatureDate = $site_visit_details['CreatedDate'];
      $ClientSignatureTime = $site_visit_details['CreatedTime'];
        $ClientSignature = '
        <img class="sign_img" src="../../media/signature/'.$ClientSignature.'">
        <div class="sign_caption">'.htmlspecialchars($ClientSignatureDate.' '.$ClientSignatureTime, ENT_QUOTES, 'UTF-8').'</div>';

}

$completed_on = trim($CompletedDate.' '.$CompletedTime);
$visit_on = trim($VisitDate.' '.$VisitTime);

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Visit Report '.$ReportNumber.'</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #0F172A; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #94A3B8; }

        .brand td { vertical-align: middle; }
        .brand_logo { width: 40%; }
        .brand_logo img { height: 21mm; }
        .brand_title { width: 60%; text-align: right; }
        .doc_title { font-size: 19pt; font-weight: bold; color: #0B356E; letter-spacing: 1px; }
        .doc_sub { font-size: 9pt; color: #1E8BE0; font-weight: bold; letter-spacing: 2px; margin-top: 1mm; }
        .doc_iso { font-size: 7.5pt; color: #64748B; margin-top: 1.5mm; }
        .bar_navy { height: 1.6mm; background: #0B356E; margin-top: 3mm; }
        .bar_sky { height: 0.8mm; background: #3AABF2; }

        .stats { margin-top: 4mm; border: 0.3mm solid #D6E4F2; }
        .stats td { width: 25%; padding: 3mm 3.5mm; background: #F3F8FD; border-right: 0.3mm solid #D6E4F2; vertical-align: top; }
        .stat_label { font-size: 7pt; color: #64748B; text-transform: uppercase; letter-spacing: 0.8px; }
        .stat_value { font-size: 10.5pt; font-weight: bold; color: #0B356E; margin-top: 1mm; }

        .section { margin-top: 4mm; border: 0.3mm solid #D6E4F2; }
        .section_head td { background: #0B356E; color: #FFFFFF; padding: 2.4mm 3.5mm; border: none; }
        .section_head td.section_title { font-size: 11pt; font-weight: bold; border-left: 1.2mm solid #3AABF2; }
        .section_head td.section_badge { text-align: right; padding: 1.6mm 3.5mm; border: none; }
        .badge_table { width: auto; border: none; }
        .section_head .badge_table td.badge { border: none; font-size: 8pt; font-weight: bold; padding: 1.2mm 3mm; text-transform: uppercase; letter-spacing: 0.5px; }

        .details_wrap td.col { width: 50%; vertical-align: top; padding: 0; }
        .details_wrap td.col_left { border-right: 0.3mm solid #D6E4F2; }
        td.col_head { background: #E8F2FC; color: #0B356E; font-weight: bold; font-size: 9.5pt; padding: 2.4mm 3.5mm; border-bottom: 0.3mm solid #D6E4F2; }

        .kv td { padding: 2.2mm 3.5mm; font-size: 9pt; border-bottom: 0.3mm solid #EEF3F9; vertical-align: top; }
        .kv td.k { width: 22%; color: #64748B; font-weight: bold; background: #FAFCFE; }
        .kv td.v { width: 28%; color: #0F172A; }
        .kv2 td { padding: 2.2mm 3.5mm; font-size: 9pt; border-bottom: 0.3mm solid #EEF3F9; vertical-align: top; }
        .kv2 td.k { width: 40%; color: #64748B; font-weight: bold; }
        .kv2 td.v { width: 60%; color: #0F172A; }

        .block { padding: 3mm 3.5mm; border-top: 0.3mm solid #EEF3F9; }
        .block_label { font-size: 8pt; font-weight: bold; color: #1E8BE0; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 1.5mm; }
        .block_text { font-size: 9.5pt; line-height: 1.5; color: #0F172A; }

        .img_grid td.img_cell { width: 25%; padding: 1.5mm; text-align: center; vertical-align: top; }
        .obs_img { width: 38mm; border: 0.3mm solid #D6E4F2; }
        .img_meta { font-size: 6.5pt; color: #64748B; margin-top: 1mm; }

        .reco { border-top: 0.3mm solid #EEF3F9; }
        .reco_cell { width: 50%; padding: 3mm 3.5mm; vertical-align: top; }
        .reco_left { border-right: 0.3mm solid #EEF3F9; }

        .sign_table td.sign_col { width: 50%; vertical-align: top; padding: 0; }
        .sign_table td.sign_left { border-right: 0.3mm solid #D6E4F2; }
        td.sign_body { padding: 2mm 3.5mm 2.5mm 3.5mm; height: 17mm; vertical-align: bottom; }
        .sign_img { height: 13mm; }
        .sign_line { border-bottom: 0.3mm solid #94A3B8; height: 10mm; width: 60mm; }
        .sign_caption { font-size: 7.5pt; color: #64748B; margin-top: 1mm; }

        .keep { page-break-inside: avoid; }
    </style>
</head>
<body>
    <table class="brand">
        <tr>
            <td class="brand_logo"><img src="../../media/pdf-assets/aryadi-logo.png"></td>
            <td class="brand_title">
                <div class="doc_title">SITE VISIT REPORT</div>
                <div class="doc_sub">FIELD SERVICE REPORT</div>
                <div class="doc_iso">ISO 9001:2015 &nbsp;|&nbsp; ISO 14001:2015 &nbsp;|&nbsp; ISO 45001:2018 Certified</div>
            </td>
        </tr>
    </table>
    <div class="bar_navy"></div>
    <div class="bar_sky"></div>

    <table class="stats">
        <tr>
            <td>
                <div class="stat_label">Report Number</div>
                <div class="stat_value">'.$ReportNumber.'</div>
            </td>
            <td>
                <div class="stat_label">Visit Date</div>
                <div class="stat_value">'.sv_text($visit_on).'</div>
            </td>
            <td>
                <div class="stat_label">Completed On</div>
                <div class="stat_value">'.sv_text($completed_on).'</div>
            </td>
            <td style="border-right:none;">
                <div class="stat_label">Observations</div>
                <div class="stat_value">'.$observation_count.'</div>
            </td>
        </tr>
    </table>

    <div class="section keep">
        <table class="details_wrap">
            <tr>
                <td class="col col_left">
                    <table><tr><td class="col_head">Customer Details</td></tr></table>
                    <table class="kv2">
                        <tr><td class="k">Customer Name</td><td class="v">'.sv_text($company_name).'</td></tr>
                        <tr><td class="k">Branch Name</td><td class="v">'.sv_text($BranchSite).'</td></tr>
                        <tr><td class="k">Address</td><td class="v">'.sv_text($BranchAddress1).'</td></tr>
                        <tr><td class="k">Contact Person</td><td class="v">'.sv_text($ContactPerson).'</td></tr>
                        <tr><td class="k">Contact Mobile</td><td class="v">'.sv_text($ContactPersonPhone).'</td></tr>
                    </table>
                </td>
                <td class="col">
                    <table><tr><td class="col_head">Site Visit Details</td></tr></table>
                    <table class="kv2">
                        <tr><td class="k">Visit Title</td><td class="v">'.sv_text($VisitTitle).'</td></tr>
                        <tr><td class="k">Report Number</td><td class="v">'.$ReportNumber.'</td></tr>
                        <tr><td class="k">Created By</td><td class="v">'.sv_text($CreatedByLabel).'</td></tr>
                        <tr><td class="k">Status</td><td class="v">'.sv_text($VisitStatus).'</td></tr>
                        <tr><td class="k">Completed On</td><td class="v">'.sv_text($completed_on).'</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    '.$observation_div.'

    <div class="section keep">
        <table class="section_head"><tr><td class="section_title">Site Visit Summary</td></tr></table>
        <div class="block" style="border-top:none;">
            <div class="block_text">'.sv_text($Summary).'</div>
        </div>
    </div>

    <div class="section keep">
        <table class="section_head"><tr><td class="section_title">Acknowledgement</td></tr></table>
        <table class="sign_table">
            <tr>
                <td class="sign_col sign_left">
                    <table><tr><td class="col_head">Aryadi Business Representative</td></tr></table>
                    <table class="kv2">
                        <tr><td class="k">Name</td><td class="v">'.sv_text($TechnicianName).'</td></tr>
                        <tr><td class="k">Phone Number</td><td class="v">'.sv_text($TechnicianPhoneNumber).'</td></tr>
                    </table>
                    <table><tr><td class="sign_body">
                        <div class="sign_line"></div>
                        <div class="sign_caption">Authorised Signatory</div>
                    </td></tr></table>
                </td>
                <td class="sign_col">
                    <table><tr><td class="col_head">Onsite Client Representative (Verified By)</td></tr></table>
                    <table class="kv2">
                        <tr><td class="k">Name</td><td class="v">'.sv_text($ContactPerson).'</td></tr>
                        <tr><td class="k">Contact Number</td><td class="v">'.sv_text($ContactPersonPhone).'</td></tr>
                    </table>
                    <table><tr><td class="sign_body">
                        '.$ClientSignature.'
                    </td></tr></table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>';

// if($Type == "hvac") {
//     $html .= generateHVACReportHTML($service_hvac_reports_details);
// }

$mpdf->SetTitle('Site Visit Report '.$ReportNumber);
$mpdf->SetAuthor('Aryadi Business Pvt. Ltd.');
$mpdf->SetHTMLFooter('
<table width="100%" style="border-top:0.3mm solid #D6E4F2; font-family:sans-serif; font-size:7.5pt; color:#64748B;">
    <tr>
        <td width="40%" style="padding-top:2mm; color:#0B356E; font-weight:bold;">Aryadi Business Pvt. Ltd.</td>
        <td width="35%" style="padding-top:2mm; text-align:center;">Site Visit Report &middot; '.$ReportNumber.'</td>
        <td width="25%" style="padding-top:2mm; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>
</table>');
$fullHTML = $html;
$mpdf->WriteHTML($html);
$pdf_name = "service-report-".$SiteVisitID.".pdf";
$pdfFilePath = '../reports/'.$pdf_name.'';
//$mpdf->Output();
$mpdf->Output($pdfFilePath, 'F');
//$mpdf->Output();
$response['pdfname'] = $pdf_name;
if($Action == "Send")
{
    $core = new Core();
    $mail_data['action'] = "Site Visit Report";
    $mail_data['SiteName'] = $BranchSite;
    //$mail_data['POCEmail'] = $SitePOCEmail;
    $mail_data['ClientRepresentativeEmails'] = $ContactPersonEmail;
    $mail_data['BranchEmail'] = $BranchEmail;
    $mail_data['CompanyEmail'] = $CompanyEmail;
    $mail_data['BranchAccountManagerEmail'] = $BranchAccountManagerEmail;
    //$mail_data['ZoneEmail'] = $ZoneEmail;
    $mail_data['POCName'] = $ContactPerson;
    $mail_data['ReportID'] = $SiteVisitID;
    $mail_data['FilePath'] = $pdf_name;

    $mail_data['ClientTicketID'] = $ReportNumber;
    $mail_data['report_images'] = $report_images;
    $url = "https://techxpertindia.in/admin/mail/send-service-report.php";
    $core->sendMailRequest($mail_data,$url);
}
echo json_encode($response);
?>
