<?php
if (empty($_DYNAMIC_PPM_PDF_SILENT)) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
require_once __DIR__ . '/../../includes/autoloader.inc.php';
require_once __DIR__ . '/../../vendor/autoload.php';
if (!function_exists('_connectodb')) {
    require_once __DIR__ . '/../../controllers/common_controllers.php';
}
require_once __DIR__ . '/../../includes/aryadi_pdf_theme.php';
// Start MPDF
$html = "Test HTML";
$conn = _connectodb();
$core = new Core();
$category_obj = new Categories($conn);
$categories_array = $category_obj->setCategoriesArray();

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
    //$Action = "Send";
    //$ServicereportID = 1;
    $service_report_obj = new Servicereport($conn);
    $service_reports_details = $service_report_obj->GetPPMServiceReportDetailsbyID($ServicereportID);
    if (!$service_reports_details || !is_array($service_reports_details)) {
        $response = array('error' => true, 'message' => 'Service report not found.');
        echo json_encode($response);
        exit;
    }
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
    $ticket_details = $corporateticket_obj->GetPPMTicketDetails($service_reports_details['TicketID']);
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
    if(1) 
    {
        // Check if 'Type' is 'AMC'
        if (1) 
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
    $ClientTicketID = "N.A.";
    if(isset($ticket_overview['ClientTicketID']))
    {
        $ClientTicketID = $ticket_overview['ClientTicketID'];
        if($ClientTicketID == "")
        {
            $ClientTicketID = "N.A.";
        }
    }
    $RegisteredDate = $ticket_overview['CreatedDate'];
    $CloseDate = $ticket_overview['CloseDate'];
    $CloseTime = $ticket_overview['CloseTime'];
    $Service = "N.A.";  
    $Subservice = "N.A.";  
    if(isset($ticket_overview['Service']))
    {
        $Service = $ticket_overview['Service'];
    }
    if(isset($ticket_overview['Subservice']))
    {
        $Subservice = $ticket_overview['Subservice'];
    }
    $RegisteredTime = $ticket_overview['CreatedTime'];
    $TicketStatus = isset($ticket_overview['Status']) ? $ticket_overview['Status'] : '';

    $report_images = $corporateticket_obj->GetPPMTicketMedia($service_reports_details['TicketID']);

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
    if(!empty($corporate_details['CompanyEmail']))
    {
        $CompanyEmail = $corporate_details['CompanyEmail'];
    }

    // Quotation line items are not printed on the PPM report.
    $quotation_html = "";
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
$image_pre_td=[];
$image_post_td=[];
$image_service_report=[];
if (!is_array($report_images)) {
    $report_images = array();
}

foreach ($report_images as $report_image) 
{
    $original_image_path = '../../media/ppm_ticket_media/' . $report_image['Image'];
    $compressed_image_path = '../../media/ppm_ticket_media/compressed_' . $report_image['Image'];
    $imageDate=$report_image['CreatedDate'];
    $imageTime=$report_image['CreatedTime'];
    if (!file_exists($original_image_path)) {
        continue;
    }

    // Compress the image
    if ($core->compressImage($original_image_path, $compressed_image_path, 50) === null) {
        $compressed_image_path = $original_image_path;
    }

    $image_cell = apdf_image_cell($compressed_image_path, apdf_join(array($imageDate, $imageTime), ' '));
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

$images_section_html =
    apdf_box('Pre Image(s)', '<div class="block block_first">'.apdf_image_grid($image_pre_td, 'No pre-work images captured.').'</div>', count($image_pre_td).' Photo(s)').
    apdf_box('Post Image(s)', '<div class="block block_first">'.apdf_image_grid($image_post_td, 'No post-work images captured.').'</div>', count($image_post_td).' Photo(s)');
if(!empty($image_service_report))
{
    $images_section_html .= apdf_box('Service Report', '<div class="block block_first">'.apdf_image_grid($image_service_report).'</div>', count($image_service_report).' Photo(s)');
}

// Customer HVAC Code
$HVACReportHTML_div = "";
$AssetConditionValue = "";
if($Category == 34)
{
    $where = "WHERE ServiceReportID = $ServicereportID";
    $response_hvac = _getTableDetails($conn, 'hvac_general_service_report', $where);
    if($response_hvac != null)
    {
        $TicketID_old = $TicketID;
        extract($response_hvac);
        $hvac_value = function ($value) {
            return apdf_text(!empty($value) ? $value : 'N/A');
        };
        $HVACReportHTML_div = apdf_box('Measured Parameters', '
        <table class="details_wrap">
            <tr>
                <td class="col col_left">
                    '.apdf_kv2(array(
                        array('Grill Temperature (C/F)', $hvac_value($GrillTemperature)),
                        array('Ambient Temperature (C/F)', $hvac_value($AmbientTemperature)),
                        array('Compressor Current (Amps)', $hvac_value($Compressor)),
                        array('Voltage (Volts)', $hvac_value($Voltage)),
                        array('Room Temperature (C/F)', $hvac_value($RoomTemperature)),
                        array('Indoor Fan Motor Current (Amps)', $hvac_value($IndoorFan)),
                        array('Compressor Discharge Pressure (PSI)', $hvac_value($CompressorDischarge)),
                    )).'
                </td>
                <td class="col">
                    '.apdf_kv2(array(
                        array('Return Air Temperature (C/F)', $hvac_value($ReturnAirTemperature)),
                        array('Supply Air Temperature (C/F)', $hvac_value($SupplyAirTemperature)),
                        array('Total Current (Amps)', $hvac_value($TotalCurrent)),
                        array('Condenser Air Outlet (C/F)', $hvac_value($CondenserAirOutlet)),
                        array('Outdoor Fan Motor Current (Amps)', $hvac_value($OutdoorFan)),
                        array('Compressor Suction Pressure (PSI)', $hvac_value($CompressorSuction)),
                        array('Condenser Air Inlet (C/F)', $hvac_value($CondenserAirInlet)),
                    )).'
                </td>
            </tr>
        </table>');

        if($AssetCondition != "")
        {
            $AssetConditionValue = $AssetCondition;
        }
        $TicketID = $TicketID_old;
    }

    
}

// Dynamic PPM checklist for PDF (page 1)
$dynamicChecklistHTML_div = '';
$ppmTicketDbId = 0;
if (!empty($ticket_details['ticket_details']['ID'])) {
    $ppmTicketDbId = (int) $ticket_details['ticket_details']['ID'];
} elseif (!empty($service_reports_details['TicketID'])) {
    $ppmTicketDbId = (int) $service_reports_details['TicketID'];
}
if ($ppmTicketDbId > 0) {
    $dynamicPpmController = __DIR__ . '/../../dynamic-ppm/controller/dynamic_ppm_controller.php';
    if (is_file($dynamicPpmController) && !function_exists('dynamicPPMBuildChecklistPdfHtml')) {
        require_once $dynamicPpmController;
    }
    if (function_exists('dynamicPPMBuildChecklistPdfHtml')) {
        $dynamicChecklistHTML_div = dynamicPPMBuildChecklistPdfHtml($conn, $ppmTicketDbId);
        if ($dynamicChecklistHTML_div !== '') {
            $HVACReportHTML_div = '';
            $AssetConditionValue = '';
        }
    }
}

$header_logo = '../../media/pdf-assets/aryadi-logo.png';
if($CorporateID == 183)
{
    $header_logo = '../../img/innov_logo.jpg';
}

$ReportNumber = "ARYADI-PPM-".sprintf("%05d", (int) $ServicereportID);
$registered_on = apdf_join(array($RegisteredDate, $RegisteredTime), ' ');
$closed_on = apdf_join(array($CloseDate, $CloseTime), ' ');

$ticket_rows = array(
    array('Type', apdf_text($Type)),
    array('Category', apdf_text($CategoryName)),
    array('Sub Category', apdf_text($SubCategoryName)),
    array('Status', apdf_text($TicketStatus)),
);
if ($AssetConditionValue !== '') {
    $ticket_rows[] = array('Asset Condition', apdf_text($AssetConditionValue));
}
$ticket_rows[] = array('Complaint Registered', apdf_text($registered_on));

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PPM Service Report '.$ReportNumber.'</title>
    <style>'.apdf_css().'</style>
</head>
<body>
    '.apdf_header('PPM SERVICE REPORT', 'PREVENTIVE MAINTENANCE REPORT', $header_logo).'

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

    '.$dynamicChecklistHTML_div.'
    '.$quotation_html.'
    '.$images_section_html.'
    '.$HVACReportHTML_div.'

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
</body>
</html>';

$mpdf = new \Mpdf\Mpdf(apdf_mpdf_config());
$mpdf->SetTitle('PPM Service Report '.$ReportNumber);
$mpdf->SetAuthor('Aryadi Business Pvt. Ltd.');
apdf_footer($mpdf, 'PPM Service Report · Ticket '.$TicketID);
$fullHTML = $html;
$mpdf->WriteHTML($html);
$pdf_name = "service-report-".$TicketID.".pdf";
$reportsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0755, true);
}
$pdfFilePath = $reportsDir . $pdf_name;
$mpdf->Output($pdfFilePath, 'F');
if(isset($_GET['ServiceReportID']))
{
    $mpdf->Output();  
}
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
if (!empty($_DYNAMIC_PPM_PDF_SILENT)) {
    $GLOBALS['_DYNAMIC_PPM_PDF_RESULT'] = $response;
} else {
    echo json_encode($response);
}
?>
