<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
$mpdfLoaded = false;
foreach (array(
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../hrms/vendor/autoload.php',
) as $autoloadFile) {
    if (is_file($autoloadFile)) {
        require_once $autoloadFile;
        if (class_exists('\\Mpdf\\Mpdf')) {
            $mpdfLoaded = true;
            break;
        }
    }
}
if (!$mpdfLoaded) {
    echo json_encode(array(
        'error' => true,
        'message' => 'PDF library (mPDF) is not installed.'
    ));
    exit;
}
include '../../controllers/common_controllers.php';
require_once __DIR__ . '/../../includes/aryadi_pdf_theme.php';
$conn = _connectodb();
$core = new Core();
$category_obj = new Categories($conn);
$categories_array = $category_obj->setCategoriesArray();
$ppm_tickets_obj = new Ppmtickets($conn);
$BranchAccountManagerEmail='';

if(isset($_POST['ServiceReportID']))
{
    $Action = isset($_POST['Action']) ? $_POST['Action'] : 'Download';
    $ServicereportID = $_POST['ServiceReportID'];
    $service_report_obj = new Servicereport($conn);
    $service_reports_details = $service_report_obj->GetPPMServiceReportDetailsbyID($ServicereportID);
    if (!is_array($service_reports_details) || empty($service_reports_details['TicketID'])) {
        echo json_encode(array('error' => true, 'message' => 'Service report not found'));
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
$ClientSignature = apdf_signature_html($ClientSignature, $service_reports_details['CreatedDate'], $service_reports_details['CreatedTime']);
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
$EPReportHTML = "";
$EPChecklistHTML = "";
$EPReportHTML_div = "";
$AssetCondition_html = "";
if($Category == 23)
{
    $where = "WHERE ServiceReportID = $ServicereportID";
    $response_ep = _getTableDetails($conn, 'ppm_ep_service_report', $where);
    if($response_ep != null)
    {
        $TicketID_old = $TicketID;
        extract($response_ep);
       $EPReportHTML .= '
        <tr>
            <td class="custumer_right_border">
                <table class="report_details_inside">
                    <tr>
                        <td style="width:60%;">Remove Dust From Panel</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($RemoveDustFromPanel) ? $ppm_tickets_obj->returnChecklistValue($RemoveDustFromPanel) : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Check Indication Lamps</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CheckIndicationLamps) ? $ppm_tickets_obj->returnChecklistValue($CheckIndicationLamps)  : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Check Voltmeter & Ammeter</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CheckVoltmeterAmmeter) ? $ppm_tickets_obj->returnChecklistValue($CheckVoltmeterAmmeter)  : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Check Selector Switches</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CheckSelectorSwitches) ? $ppm_tickets_obj->returnChecklistValue($CheckSelectorSwitches)  : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Correct Loose Connection</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CorrectLooseConnection) ? $ppm_tickets_obj->returnChecklistValue($CorrectLooseConnection)  : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Check TPN</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($CheckTPN) ? $ppm_tickets_obj->returnChecklistValue($CheckTPN)  : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Multimeter</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($Multimeter) ? $ppm_tickets_obj->returnChecklistValue($Multimeter)  : 'N/A').'</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="report_details_inside">
                    <tr>
                        <td style="width:60%;">Remove Signboard</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($RemoveSignboard) ? $ppm_tickets_obj->returnChecklistValue($RemoveSignboard) : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Ensure No Tools Left</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($EnsureNoToolsLeft) ? $ppm_tickets_obj->returnChecklistValue($EnsureNoToolsLeft) : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Apply Lock Tag Notice</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($ApplyLockTagNotice) ? $ppm_tickets_obj->returnChecklistValue($ApplyLockTagNotice) : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Personal Protective Emergency</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($PersonalProtectiveEmergency) ? $ppm_tickets_obj->returnChecklistValue($PersonalProtectiveEmergency) : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Waste Clothes</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($WasteClothes) ? $ppm_tickets_obj->returnChecklistValue($WasteClothes) : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Rust Cleaning Agent</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($RustCleaningAgent) ? $ppm_tickets_obj->returnChecklistValue($RustCleaningAgent) : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Tool Set</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($ToolSet) ? $ppm_tickets_obj->returnChecklistValue($ToolSet) : 'N/A').'</td>
                    </tr>
                </table>
            </td>
        </tr>';


       $EPChecklistHTML .= '
        <tr>
            <td class="custumer_right_border">
                <table class="report_details_inside">
                    
                    <tr>
                        <td style="width:60%;">Earthing Resistance (Ohm)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($EarthingResistance) ? $EarthingResistance : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Frequency (Hz)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($Frequency) ? $Frequency : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Current (Amps)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($Current) ? $Current : 'N/A').'</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="report_details_inside">
                    <tr>
                        <td style="width:60%;">Power Factor (Watt/Cos)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($PowerFactor) ? $PowerFactor : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Supply 1 Phase Voltage (Volts)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($Supply1PhaseVoltage) ? $Supply1PhaseVoltage : 'N/A').'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;">Supply 3 Phase Voltage (Volts)</td>
                        <td style="width:10%;">:</td>
                        <td style="width:30%;">'.(!empty($Supply3PhaseVoltage) ? $Supply3PhaseVoltage : 'N/A').'</td>
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

        $EPReportHTML_div = apdf_box('Checklist', '<table class="customer_details">'.$EPReportHTML.'</table>');
        $EPReportHTML_div = $EPReportHTML_div.apdf_box('Measured Parameters', '<table class="customer_details">'.$EPChecklistHTML.'</table>');
        $TicketID = $TicketID_old;
    }

    
}

$header_logo = '../../media/pdf-assets/aryadi-logo.png';
if($CorporateID == 183)
{
    $header_logo = '../../img/innov_logo.jpg';
}

$AssetConditionValue = ($AssetCondition_html !== '' && isset($AssetCondition)) ? $AssetCondition : '';
$ReportNumber = "ARYADI-PPM-".sprintf("%05d", (int) $ServicereportID);
$html = apdf_ppm_document(array(
    'title' => 'PPM SERVICE REPORT',
    'subtitle' => 'ELECTRICAL PANEL MAINTENANCE',
    'logo' => $header_logo,
    'report_number' => $ReportNumber,
    'ticket_id' => $TicketID,
    'type' => $Type,
    'category' => $CategoryName,
    'subcategory' => $SubCategoryName,
    'status' => isset($ticket_overview['Status']) ? $ticket_overview['Status'] : '',
    'asset_condition' => $AssetConditionValue,
    'registered' => apdf_join(array($RegisteredDate, $RegisteredTime), ' '),
    'closed' => apdf_join(array($CloseDate, $CloseTime), ' '),
    'company_name' => $company_name,
    'branch' => $BranchSite,
    'address' => $BranchAddress1,
    'mobile' => $BranchMobile,
    'client_ticket' => $ClientTicketID,
    'equipment' => $EquipmentName,
    'make' => $Make,
    'model' => $Model,
    'serial' => $SerialNumber,
    'asset_location' => $AssetLocation,
    'problem' => $ProblemReportedByClient,
    'observation' => $Observation,
    'action_taken' => $ActionTaken,
    'remarks' => $Remarks,
    'before_images_html' => $quotation_html,
    'after_images_html' => $EPReportHTML_div,
    'pre_images' => $image_pre_td,
    'post_images' => $image_post_td,
    'service_images' => $image_service_report,
    'tech_name' => $TechnicianName,
    'tech_phone' => $TechnicianPhoneNumber,
    'client_name' => $ClientRepresentative,
    'client_designation' => $ClientRepresentativeDesignation,
    'client_contact' => $ClientRepresentativeContact,
    'signature_html' => $ClientSignature,
));

$mpdf = apdf_create_mpdf('PPM Service Report '.$ReportNumber, 'PPM Service Report · Ticket '.$TicketID);
$fullHTML = $html;
$mpdf->WriteHTML($html);
$pdf_name = "service-report-".$TicketID.".pdf";
$reportsDir = __DIR__ . '/../reports';
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0777, true);
}
$pdfFilePath = $reportsDir . '/' . $pdf_name;
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