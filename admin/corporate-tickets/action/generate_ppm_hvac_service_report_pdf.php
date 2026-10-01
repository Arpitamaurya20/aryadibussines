<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php'; 
include '../../controllers/common_controllers.php'; 
// Start MPDF
$html = "Test HTML";
$mpdf = new \Mpdf\Mpdf();
$conn = _connectodb();
$core = new Core();
$category_obj = new Categories($conn);
$categories_array = $category_obj->setCategoriesArray();
$ppm_tickets_obj = new Ppmtickets($conn);
$BranchAccountManagerEmail='';

if(isset($_POST))
{
    $Action = isset($_POST['Action']) ? $_POST['Action'] : 'Download';
    $ServicereportID = $_POST['ServiceReportID'];
    $ServicereportID = $ServicereportID;
    $service_report_obj = new Servicereport($conn);
    $service_reports_details = $service_report_obj->GetPPMServiceReportDetailsbyID($ServicereportID);
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
    
    $RegisteredTime = $ticket_overview['CreatedTime'];



    $report_images = $corporateticket_obj->GetPPMTicketMedia($service_reports_details['TicketID']);
    $AttendedDate_html = "";
    /*$AttendedDate = $corporateticket_obj->GetAttendedDate($service_reports_details['TicketID']);
    
    if($AttendedDate != "")
    {
        $AttendedDate_html = '
        <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Attended Date</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$AttendedDate.'</td>
                                </tr>';
    }*/

    

    $employee_obj = new Employee($conn);
    $employee_array = $employee_obj->setEmployeeArray('All');
    if (!is_array($employee_array)) {
        $employee_array = array();
    }
    $AssignedTo = $ticket_overview['AssignedTo'];
    $TechnicianName = isset($employee_array[$AssignedTo]['Name']) ? $employee_array[$AssignedTo]['Name'] : 'N.A.';
    $TechnicianPhoneNumber = isset($employee_array[$AssignedTo]['ContactNumber']) ? $employee_array[$AssignedTo]['ContactNumber'] : '';

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
        /* $quotation_html = 
        '<div class="quotation_div">   
                <table class="other_feedback">
                    <tr>
                        <td class="width_30"> Line Items Used: </td>
                    </tr>
                    <tr class="other_feedback_inside">
                    <td> '.$line_item_table.' </td>
                    </tr>
                </table>
            </div>';*/
    }
}
else
{
    die();
}
if (!function_exists('ppmPdfText')) {
    function ppmPdfText($value, $fallback = 'N.A.') {
        $text = trim((string) $value);
        if ($text === '') {
            $text = $fallback;
        }
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('ppmPdfKv')) {
    function ppmPdfKv($label, $value) {
        return '<tr>
            <td class="kv-label">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</td>
            <td class="kv-value">'.ppmPdfText($value).'</td>
        </tr>';
    }
}

if($ClientSignature == ""){
    $ClientSignature = '<div class="sign-placeholder">Signature pending</div>';
}else{
      $ClientSignatureDate = $service_reports_details['CreatedDate'];
      $ClientSignatureTime = $service_reports_details['CreatedTime'];
    $ClientSignature = '
<div class="client_signature">
    <img src="../../media/signature/'.$ClientSignature.'">
</div>
<div class="sign-meta">'.ppmPdfText($ClientSignatureDate, '').' '.ppmPdfText($ClientSignatureTime, '').'</div>';

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
  
    
    // Compress the image
    $core->compressImage($original_image_path, $compressed_image_path, 50); // 75 is the quality percentage

    $imageCell = '<td class="photo-cell">
                    <div class="photo-frame">
                        <img src="'.$compressed_image_path.'" alt="">
                        <div class="photo-meta">'.ppmPdfText($imageDate, '').' '.ppmPdfText($imageTime, '').'</div>
                    </div>
                </td>';
    if($report_image['Action'] == "pre_img")
    {
        $image_pre_td[] = $imageCell;
    }
    if($report_image['Action'] == "Service_Report")
    {
        $image_service_report[] = $imageCell;
    }
    if($report_image['Action'] == "post_img")
    {
        $image_post_td[] = $imageCell;
    }
}

$service_report_div = "";
if(!empty($image_service_report))
{

$service_report_div = '
<table class="section-card" width="100%" cellpadding="0" cellspacing="0">
                <tr><td class="section-title">Service Report Images</td></tr>
                <tr><td class="section-body">
                    <table class="photo-table" width="100%" cellpadding="0" cellspacing="0"><tr>'.implode("", $image_service_report).'</tr></table>
                </td></tr>
            </table>';
}

// Customer HVAC Code
$HVACReportHTML = "";
$HVACChecklistHTML = "";
$HVACReportHTML_div = "";
$AssetCondition_html = "";
if($Category == 34)
{
    $where = "WHERE ServiceReportID = $ServicereportID";
    $response_hvac = _getTableDetails($conn, 'ppm_hvac_service_report', $where);
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
                        <td style="width:30%;">'.(!empty($CompressorCurrent) ? $CompressorCurrent : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Voltage (Volts)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($SupplyVoltage) ? $SupplyVoltage : 'N/A').'</td>
                    </tr>
                     <tr>
                        <td style="width:60%;" class="">Room Temperature (C/F)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($RoomTemperature) ? $RoomTemperature : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Indoor Fan Motor Current (Amps)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($IndoorFanMotorCurrent) ? $IndoorFanMotorCurrent : 'N/A').'</td>
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
                        <td style="width:30%;">'.(!empty($OutdoorFanMotorCurrent) ? $OutdoorFanMotorCurrent : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Compressor Suction Pressure (PSI)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CompressorSuctionPressure) ? $CompressorSuctionPressure : 'N/A').'</td>
                    </tr>
                      <tr>
                        <td style="width:60%;" class="">Condenser Air Inlet (C/F)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CondenserAirInlet) ? $CondenserAirInlet : 'N/A').'</td>
                    </tr>
                </table>
            </td>
        </tr>';

        $HVACChecklistHTML .= '
        <tr>
            <td class="custumer_right_border">
                <table class = "report_details_inside">
                    <tr>
                        <td style="width:60%;" class="">Cooling Coil Cleaning & Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($CoolingCoilCleaningCondition).'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Drain Pump Working Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($DrainPumpWorkingCondition).'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Drain Tray Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($DrainTrayCondition).'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Fan Motor Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($FanMotorCondition).'</td>
                    </tr>
                     <tr>
                        <td style="width:60%;" class="">Filter Cleaning Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($FilterCleaningCondition).'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">PCB Physical & Working Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($PCBPhysicalWorkingCondition).'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Cooling Loss Prevention Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($CoolingLossPreventionCondition).'</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="report_details_inside">
                    <tr>
                        <td style="width:60%;" class="">Condensor Coil Condition & Cleaning</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($CondesnorCoilConditionCleaning).'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Compressor Working Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($CompressorWorkingCondition).'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Outdoor Installation Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($OutdoorInstallationCondition).'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Fan Motor Working Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($FanMotorWorkingCondition).'</td>
                    </tr>
                     <tr>
                        <td style="width:60%;" class="">Electrical Terminal Condition</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($ElectricalTerminalCondition).'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;" class="">Surrounding Conditions to Work</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.$ppm_tickets_obj->returnHVACChecklistValue($SurroundingConditionsToWork).'</td>
                    </tr>
                      
                </table>
            </td>
        </tr>';


        if($AssetCondition != "")
        {
            $AssetCondition_html = ppmPdfKv('Asset Condition', $AssetCondition);
        }

        $HVACReportHTML_div = '<table class="section-card" width="100%" cellpadding="0" cellspacing="0">
                <tr><td class="section-title">Measured Parameters</td></tr>
                <tr><td class="section-body" style="padding:0;">
                    <table class="split-table" width="100%" cellpadding="0" cellspacing="0">'.$HVACReportHTML.'</table>
                </td></tr>
            </table>';
        $HVACReportHTML_div = $HVACReportHTML_div.'<table class="section-card" width="100%" cellpadding="0" cellspacing="0">
                <tr><td class="section-title">Checklist</td></tr>
                <tr><td class="section-body" style="padding:0;">
                    <table class="split-table" width="100%" cellpadding="0" cellspacing="0">'.$HVACChecklistHTML.'</table>
                </td></tr>
            </table>';
        $TicketID = $TicketID_old;
    }

    
}

$dynamicChecklistHTML_div = '';
$ppmTicketDbId = 0;
if (!empty($ticket_details['ticket_details']['ID'])) {
    $ppmTicketDbId = (int) $ticket_details['ticket_details']['ID'];
} elseif (!empty($service_reports_details['TicketID'])) {
    $ppmTicketDbId = (int) $service_reports_details['TicketID'];
}
if ($ppmTicketDbId > 0) {
    $dynamicPpmController = __DIR__ . '/../../dynamic-ppm/controller/dynamic_ppm_controller.php';
    if (is_file($dynamicPpmController)) {
        require_once $dynamicPpmController;
        if (function_exists('dynamicPPMBuildChecklistPdfHtml')) {
            $dynamicChecklistHTML_div = dynamicPPMBuildChecklistPdfHtml($conn, $ppmTicketDbId);
            if ($dynamicChecklistHTML_div !== '') {
                $HVACReportHTML_div = '';
                $AssetCondition_html = '';
            }
        }
    }
}

$headerLogoPath = '../../img/aryadi.png';
$headerCompany = 'ARYADI BUSINESS PVT. LTD.';
$headerTagline = 'Technical Facility Management  |  AMC  |  Engineering Services';
if ((int) $CorporateID === 183) {
    $headerLogoPath = '../../img/innov_logo.jpg';
    $headerCompany = 'INNOV';
    $headerTagline = 'Your People Partner';
}
$preImagesRow = !empty($image_pre_td) ? implode('', $image_pre_td) : '<td class="empty-note">No pre images uploaded.</td>';
$postImagesRow = !empty($image_post_td) ? implode('', $image_post_td) : '<td class="empty-note">No post images uploaded.</td>';

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Field Service Report - '.ppmPdfText($TicketID).'</title>
    <style>
        body {
            font-family: poppins, sans-serif;
            color: #334155;
            font-size: 10pt;
            background: #ffffff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #2E7AAB;
            letter-spacing: 0.3px;
        }
        .company-tag {
            font-size: 7.5px;
            color: #64748B;
            padding-top: 2px;
        }
        .report-badge {
            background: #3D9FD6;
            color: #ffffff;
            text-align: right;
            padding: 10px 12px;
        }
        .report-badge-title {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #ffffff;
        }
        .report-badge-meta {
            font-size: 8px;
            color: #E8F4FD;
            padding-top: 3px;
        }
        .accent-line {
            height: 2px;
            background: #2E8FC4;
            font-size: 1px;
        }
        .section-card {
            margin-top: 8px;
            border: 1px solid #C5D9EA;
        }
        .section-title {
            background: #3D9FD6;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 6px 10px;
            letter-spacing: 0.3px;
        }
        .section-title-split {
            background: #3D9FD6;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 6px 10px;
            letter-spacing: 0.3px;
        }
        .section-body {
            padding: 8px 10px;
            background: #ffffff;
        }
        .kv-label {
            width: 42%;
            font-size: 8.5px;
            color: #64748B;
            padding: 3px 6px 3px 0;
            vertical-align: top;
        }
        .kv-value {
            width: 58%;
            font-size: 9.5px;
            color: #334155;
            font-weight: normal;
            padding: 3px 0;
            vertical-align: top;
        }
        .split-table td {
            width: 50%;
            vertical-align: top;
        }
        .col-pad {
            padding: 8px 10px;
            background: #ffffff;
        }
        .col-divider {
            border-right: 1px solid #C5D9EA;
        }
        .custumer_right_border {
            border-right: 1px solid #C5D9EA;
            padding: 8px 10px;
            vertical-align: top;
            width: 50%;
        }
        .report_details_inside td {
            font-size: 8.5px;
            padding: 3px 2px;
            color: #334155;
        }
        .narrative {
            font-size: 9.5px;
            color: #334155;
            line-height: 1.45;
            min-height: 20px;
        }
        .photo-cell {
            width: 25%;
            padding: 6px;
            text-align: center;
            vertical-align: top;
        }
        .photo-frame {
            border: 1px solid #C5D9EA;
            padding: 4px;
            background: #ffffff;
        }
        .photo-frame img {
            width: 118px;
        }
        .photo-meta {
            font-size: 7px;
            color: #64748B;
            padding-top: 3px;
        }
        .empty-note {
            font-size: 9px;
            color: #64748B;
            padding: 12px 8px;
        }
        .sign-head {
            background: #3D9FD6;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 6px 10px;
        }
        .sign-subhead {
            background: #D6EEF8;
            color: #2E7AAB;
            font-size: 8px;
            font-weight: bold;
            padding: 5px 10px;
            border-bottom: 1px solid #C5D9EA;
        }
        .sign-table td {
            border: 1px solid #C5D9EA;
            padding: 8px 10px;
            font-size: 9px;
            vertical-align: top;
        }
        .client_signature img {
            width: 90px;
        }
        .sign-placeholder {
            font-size: 8px;
            color: #94A3B8;
            padding: 16px 0;
        }
        .sign-meta {
            font-size: 7px;
            color: #64748B;
            padding-top: 3px;
        }
        .tech-label {
            font-weight: bold;
            color: #2E7AAB;
        }
        .attacment_div {
            margin-top: 8px;
            border: 1px solid #C5D9EA;
            page-break-inside: avoid;
        }
        .attacment {
            background: #3D9FD6;
            padding: 6px 10px;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .product_attachment_box {
            padding: 8px 10px;
        }
        .problemreported_div,
        .observation_div,
        .action_taken_div,
        .remark_div,
        .quotation_div,
        .bottom_div,
        .section-card {
            page-break-inside: avoid;
        }
        .bottom_signature_table {
            border-collapse: collapse;
            margin-top: 8px;
        }
        .bottom_signature_table td {
            border: 1px solid #C5D9EA;
            font-size: 9px;
            padding: 8px 10px;
        }
        .bottom_table_bold,
        .tech_bottom_table_bold {
            font-size: 9px;
        }
    </style>
</head>
<body>
    <table class="brand-bar" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="62%" style="padding:6px 8px 8px 0; vertical-align:middle;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="58" style="vertical-align:middle;">
                            <img src="'.$headerLogoPath.'" style="height:48px; width:auto;">
                        </td>
                        <td style="padding-left:8px; vertical-align:middle;">
                            <div class="company-name">'.$headerCompany.'</div>
                            <div class="company-tag">'.$headerTagline.'</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="38%" class="report-badge" style="vertical-align:middle;">
                <div class="report-badge-title">FIELD SERVICE REPORT</div>
                <div class="report-badge-meta">'.ppmPdfText($Type).'  |  '.ppmPdfText($TicketID).'</div>
            </td>
        </tr>
    </table>
    <div class="accent-line">&nbsp;</div>

    <table class="section-card" width="100%" cellpadding="0" cellspacing="0" style="margin-top:10px;">
        <tr>
            <td width="50%" class="section-title-split" style="border-right:1px solid #ffffff;">CUSTOMER DETAILS</td>
            <td width="50%" class="section-title-split">SERVICE TICKET DETAILS</td>
        </tr>
        <tr>
            <td class="col-pad col-divider" style="vertical-align:top;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    '.ppmPdfKv('Customer Name', $company_name).'
                    '.ppmPdfKv('Branch Name', $BranchSite).'
                    '.ppmPdfKv('Address', $BranchAddress1).'
                    '.ppmPdfKv('Mobile No.', $BranchMobile).'
                    '.ppmPdfKv('Equipment Name', $EquipmentName).'
                    '.ppmPdfKv('Make', $Make).'
                    '.ppmPdfKv('Model', $Model).'
                    '.ppmPdfKv('Serial Number', $SerialNumber).'
                    '.ppmPdfKv('Asset Location', $AssetLocation).'
                </table>
            </td>
            <td class="col-pad" style="vertical-align:top;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    '.ppmPdfKv('Type', $Type).'
                    '.ppmPdfKv('Ticket No', $TicketID).'
                    '.ppmPdfKv('Category', $CategoryName).'
                    '.ppmPdfKv('Sub Category', $SubCategoryName).'
                    '.ppmPdfKv('Complaint Registered', trim($RegisteredDate.' | '.$RegisteredTime)).'
                    '.$AssetCondition_html.'
                    '.ppmPdfKv('Close Date', trim($CloseDate.' | '.$CloseTime)).'
                    '.ppmPdfKv('Technician', $TechnicianName).'
                </table>
            </td>
        </tr>
    </table>

    <table class="section-card" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="section-title">Problem Statement</td></tr>
        <tr><td class="section-body"><div class="narrative">'.ppmPdfText($ProblemReportedByClient, '').'</div></td></tr>
    </table>
    <table class="section-card" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="section-title">Technician / Engineer Observation</td></tr>
        <tr><td class="section-body"><div class="narrative">'.ppmPdfText($Observation, '').'</div></td></tr>
    </table>
    <table class="section-card" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="section-title">Action Taken</td></tr>
        <tr><td class="section-body"><div class="narrative">'.ppmPdfText($ActionTaken, '').'</div></td></tr>
    </table>
    <table class="section-card" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="section-title">Technician / Engineer Remarks</td></tr>
        <tr><td class="section-body"><div class="narrative">'.ppmPdfText($Remarks, '').'</div></td></tr>
    </table>
    '.$dynamicChecklistHTML_div.'
    '.$quotation_html.'

    <table class="section-card" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="section-title">Pre Image(s)</td></tr>
        <tr><td class="section-body">
            <table class="photo-table" width="100%" cellpadding="0" cellspacing="0"><tr>'.$preImagesRow.'</tr></table>
        </td></tr>
    </table>
    <table class="section-card" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="section-title">Post Image(s)</td></tr>
        <tr><td class="section-body">
            <table class="photo-table" width="100%" cellpadding="0" cellspacing="0"><tr>'.$postImagesRow.'</tr></table>
        </td></tr>
    </table>
    '.$service_report_div.$HVACReportHTML_div.'

    <table class="section-card" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="sign-head" colspan="2">Technician</td></tr>
        <tr>
            <td class="col-pad col-divider" width="50%"><span class="tech-label">Name: </span>'.ppmPdfText($TechnicianName).'</td>
            <td class="col-pad" width="50%"><span class="tech-label">Phone: </span>'.ppmPdfText($TechnicianPhoneNumber, '').'</td>
        </tr>
    </table>
    <table class="section-card" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="sign-head" colspan="3">Onsite Client Representative (Verified By)</td></tr>
        <tr>
            <td class="sign-subhead">Name</td>
            <td class="sign-subhead">Designation</td>
            <td class="sign-subhead">Signature</td>
        </tr>
        <tr>
            <td style="padding:10px; border:1px solid #C5D9EA; font-size:9px; vertical-align:top;">'.ppmPdfText($ClientRepresentative, '').'<br>'.ppmPdfText($ClientRepresentativeContact, '').'</td>
            <td style="padding:10px; border:1px solid #C5D9EA; font-size:9px; vertical-align:top;">'.ppmPdfText($ClientRepresentativeDesignation, '').'</td>
            <td style="padding:10px; border:1px solid #C5D9EA; font-size:9px; vertical-align:top;">'.$ClientSignature.'</td>
        </tr>
    </table>
</body>
</html>';


 // if($Type == "hvac") {
 //     $html .= generateHVACReportHTML($service_hvac_reports_details);
 // }

 $default_logo = __DIR__ . '/../../img/aryadi.png';

if ($CorporateID == 183) {
    $default_logo = __DIR__ . '/../../img/innov_logo.jpg';
}
$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'fontDir' => array_merge($fontDirs, [__DIR__ . '/../../fonts']),
    'fontdata' => $fontData + [
        'poppins' => [
            'R' => 'Poppins-Regular.ttf',
            'B' => 'Poppins-Bold.ttf',
            'I' => 'Poppins-Italic.ttf'
        ]
    ],
    'default_font' => 'poppins',
    'format' => 'A4',
    'margin_left' => 12,
    'margin_right' => 12,
    'margin_top' => 10,
    'margin_bottom' => 16,
    'margin_header' => 0,
    'margin_footer' => 8,
]);
$mpdf->SetTitle('Field Service Report - ' . $TicketID);
$mpdf->SetAuthor('Aryadi Business Pvt. Ltd.');
$mpdf->SetCreator('Aryadi Business Pvt. Ltd.');
$mpdf->SetWatermarkImage($default_logo, 0.04, [42, 38], 'F');
$mpdf->showWatermarkImage = true;
$mpdf->SetFooter('<table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #C5D9EA;"><tr>
    <td style="font-size:7.5px; color:#64748B; padding-top:4px;">Aryadi Business Pvt. Ltd.  |  info@aryadibusiness.com  |  +91 8800904906</td>
    <td style="font-size:7.5px; color:#2E7AAB; text-align:right; padding-top:4px;">Page {PAGENO} of {nbpg}</td>
</tr></table>');
$mpdf->WriteHTML($html);
$pdf_name = "service-report-".$TicketID.".pdf";
$reportsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0755, true);
}
$pdfFilePath = $reportsDir . $pdf_name;
$mpdf->Output($pdfFilePath, 'F');
//$mpdf->Output();
$response = array();
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