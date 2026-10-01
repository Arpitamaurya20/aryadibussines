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

     $time_section_html = '
     <div style="width:100%; border:1px solid #ccc; border-radius:6px; margin-top:10px; background-color:#027dc1">
         <table>
             <tr>
                 <td style="color:white;">
                     '.$totalTime.'<br>
                     <small style="font-size:8px">Total Time<br>(Closed - Raised)</small>
                 </td>
                  <td style="color:#d52c27;">=</td>
                 <td style="color:white;">
                     '.$workAccept.'<br>
                     <small style="font-size:8px">Work Accept<br>(Work Start - Raised)</small>
                 </td>
                 <td style="color:#d52c27;">+</td>
                    <td style="color:white;">
                     '.$quotationTime.'<br>
                     <small style="font-size:8px">Quotation Time<br>(Approved - Sent)</small>
                 </td>
                 <td style="color:#d52c27;">+</td>
                 <td style="color:white;">
                     '.$workTime.'<br>
                     <small style="font-size:8px">Work Time<br>(Closed - Work Start)</small>
                 </td style="color:white;">
                 <td style="color:#d52c27;">+</td>
                 <td style="padding:10px; color:white;">
                     '.$verifyTime.'<br>
                     <small style="font-size:8px">Verify Time<br>(OTP - Closed)</small>
                 </td>
             </tr>
         </table>
         
     </div>';
     


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
    $TechnicianName = $employee_array[$AssignedTo]['Name']; 
    $TechnicianPhoneNumber = $employee_array[$AssignedTo]['ContactNumber'];

    if($BranchAccountManager != -1 && $BranchAccountManager != "")
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
        '<div class="quotation_div">   
                <table class="other_feedback">
                    <tr>
                        <td class="width_30"> Line Items Used: </td>
                    </tr>
                    <tr class="other_feedback_inside">
                    <td> '.$line_item_table.' </td>
                    </tr>
                </table>
            </div>';
    }
}
else
{
    die();
}
if($ClientSignature == ""){
    $ClientSignature = '';
}else{
      $ClientSignatureDate = $service_reports_details['CreatedDate'];
      $ClientSignatureTime = $service_reports_details['CreatedTime'];
    $ClientSignature = '
<div style="width:100px; height:200px;" class="client_signature">
    <img style="width:100px;" src="../../media/signature/'.$ClientSignature.'">
</div>
<div class="mt-2 d-flex" style="font-size:10px">
    <span class="badge bg-warning">'.$ClientSignatureDate.'</span>
    <span class="badge bg-info me-3">'.$ClientSignatureTime.'</span>
</div>';

}
$image_pre_td=[];
$image_post_td=[];



foreach ($report_images as $report_image) 
{
    $original_image_path = '../../media/ticket_media/' . $report_image['Image'];
    $compressed_image_path = '../../media/ticket_media/compressed_' . $report_image['Image'];
    $imageDate=$report_image['CreatedDate'];
    $imageTime=$report_image['CreatedTime'];
  
    
    // Compress the image
    $core->compressImage($original_image_path, $compressed_image_path, 50); // 75 is the quality percentage

    if($report_image['Action'] == "pre_img")
    {
        $image_pre_td[] = '<td style="width:25%; padding:20px;">
                    <div style="width:100px; height:100px;" class="product_attachment">
                        <a href="'.$compressed_image_path.'" target="_blank" title="Click to Open..."><img style="width:100px;"  class="mt-3"
                            src="'.$compressed_image_path.'" alt=""></a>
                    </div>
                    <div class="mt-2 d-flex"style="font-size: 10px">
                        <span class=""style="font-size: 5px;">'.$imageDate.'</span>
                        <span class=""style="font-size: 5px;">'.$imageTime.'</span>
                      </div>
                
                </td>';
    }
    if($report_image['Action'] == "Service_Report")
    {
        $image_service_report[] = '<td style="width:25%; padding:20px">
                    <div style="width:100px; height:100px;" class="product_attachment">
                        <a href="'.$compressed_image_path.'" target="_blank" title="Click to Open..."><img style="width:100px;"  class="mt-3"
                            src="'.$compressed_image_path.'" alt=""></a>
                    </div>
                    <div class="mt-2 d-flex" style="font-size: 10px">
                        <span class=""style="font-size: 5px;">'.$imageDate.'</span>
                        <span class=""style="font-size: 5px;">'.$imageTime.'</span>
                      </div>
                </td>';
    }
    if($report_image['Action'] == "post_img")
    {
        $image_post_td[] = '<td style="width:25%; padding:20px">
                    <div style="width:100px; height:100px;" class="product_attachment">
                        <a href="'.$compressed_image_path.'" target="_blank" title="Click to Open..."><img style="width:100px;"  class="mt-3"
                            src="'.$compressed_image_path.'" alt=""></a>
                    </div>
                     <div class="mt-2 d-flex" style="font-size: 10px">
                        <span class=""style="font-size: 5px;">'.$imageDate.'</span>
                        <span class=""style="font-size: 5px;">'.$imageTime.'</span>
                      </div>
                </td>';
    }
}

$service_report_div = "";
if(!empty($image_service_report))
{

$service_report_div = '
<div class="attacment_div">
                <div class="attacment">
                    Service Report
                </div>
                <div class="product_attachment_box">
                    <table>
                    <tr>
                        '.implode("", $image_service_report).'
                    </tr>

                    </table>
                </div>
            </div>';
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

        $HVACReportHTML_div = '<div class="attacment_div">
                <div class="attacment">
                    Measured Parameters
                </div>
                <div class="product_attachment_box"> 
                    <table  class="customer_details">
                    '.$HVACReportHTML.'
                    </table>
                </div>
            </div>';
        $TicketID = $TicketID_old;
    }

    
}

// Build Ticket History Table
$ticket_history_html = '
<div class="attacment_div" style="margin-top:10px;">
    <div class="attacment">Activity Log</div>
    <div class="product_attachment_box">
        <table class="activity_table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Updated By</th>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>';
            
$count = 1;


foreach(array_reverse($ticket_history_array) as $ticket_history) {
    $EmployeeName = "";
    if($ticket_history['AssignedTo'] != "" && $ticket_history['AssignedTo'] != -1) {
        $EmployeeName = getEmployeeDetailsfromID($conn,$ticket_history['AssignedTo'])['Name'];
    }

    $ticket_history_html .= '
        <tr>
            <td>'.$count.'</td>
            <td>'.$ticket_history['Status'].'</td>
            <td>'.$EmployeeName.'</td>
           
            <td>'.$ticket_history['CreatedBy'].'</td>
            <td>'.$ticket_history['CreatedDate'].'</td>
            <td>'.$ticket_history['CreatedTime'].'</td>
        </tr>';
    $count++;
}

$ticket_history_html .= '
            </tbody>
        </table>
    </div>
</div>';






$Rating = $corporateticket_obj->GetRating($service_reports_details['TicketID']);
$ratingHtml = '';

if (!empty($Rating) && $Rating >= 1 && $Rating <= 5) {
    $starsHtml = '';
    for ($i = 0; $i < $Rating; $i++) {
        $starsHtml .= '<img src="https://techxpertindia.in/emoji/star.png" alt="Star Rating" style="width: 20px; height: 20px; margin-right: 2px;">';
    }

$ratingHtml = '
    <div class="rating-badge mt-2">
        <table style="border-collapse:collapse;">
            <tr>
                <td style="color:red; font-weight:600; font-size:16px; padding-right:8px;">
                    Customer Rating:
                </td>
                <td>
                    ' . $starsHtml . '
                </td>
            </tr>
        </table>
    </div>';

}


$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .container{
            // border:1px solid #000;
        } 
        body{
             font-family: poppins, sans-serif;
            font-size:12pt;
        }  
        table {
            width: 100%;
        }
        .top_header{
           background:#027dc1;
           border:1px solid #000;
        }
        .top_header tr td{
            width:50%;
         }
         .top_header_inside tr td {
            text-align:center;
            color:#fff;
            font-weight:bold;
            // font-family: sans-serif;
            font-size:15px;
         }
         .top_right_border{
            border-right:1px solid #fff;
         } 

         
         .customer_details{
            border:1px solid #000;
         }
         .customer_details tr td{
            width:50%;
         }
         .customer_details_inside tr td{
             font-size:11px;
            //  font-family: sans-serif;
         }
         .customer_details_inside tr td{
            padding:3px 0px;
         }
         .costumer_detail_bold{
            font-weight:bold;
            width:100px;
            font-size:12px;
         }
         .custumer_right_border{
            border-right:1px solid #000;
         }
         .report_details_inside tr td{
            font-size:11px;
            // font-family: sans-serif;
        }
        .report_details_inside tr td{
           padding:3px 0px;
        }
        
        .customer_details tr {
            display: inline-flex !important;
        }
        .other_feedback{
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            // padding-bottom:50px;
        }
        .other_feedback tr .width_30{
            font-weight:bold;
            font-size:12px;
            // font-family: sans-serif;
            height:50px;
        }
        .other_feedback_inside td{
            font-size:11px;
            // font-family: sans-serif;
            margin-top:10px;
        }

        .attacment_div{
            border: 1px solid #000;
            page-break-inside: avoid;
        }   

        .action_taken_div{
            page-break-inside: avoid;
        }
        .remark_div{
            page-break-inside: avoid;
        }
        .observation_div{
            page-break-inside: avoid;
        }
        .problemreported_div{
            page-break-inside: avoid;
        }
        .quotation_div{
            page-break-inside: avoid;
        }

        .bottom_div{
            page-break-inside: avoid;
        }

        .attacment{
            background:#027dc1;
            padding:6px 0px 6px 6px;
            color:#fff;
            font-size:14px;
            font-weight:bold;
            // font-family: sans-serif;
            
        }
        .product_attachment_box{
            padding:20px;
        }
        

        .bottom_signature_table{
            border:1px solid #000;
            border-collapse: collapse;
        }

        .bottom_table_bold{
            font-weight:bold;
            font-size:13px;
            padding-left:10px !important;
        }

        .bottom_signature_table tbody tr td{
            // font-family: sans-serif;
            border:1px solid #000;
            font-size:12px;
            padding-top:15px;
            padding-bottom:15px;
            width:20%;
        }


        .natural_of_call_div{
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            font-weight:bold;
            font-size:12px;
            padding:4px 7px;
            // font-family: sans-serif;
        }
        .natural_table_bold{
            font-weight:bold;
            font-size:11px;
            padding-left:10px !important;
        }
        .natural_call_table{
            border:1px solid #000;
            border-collapse: collapse;
        }
        .natural_call_table tbody tr td{
            // font-family: sans-serif;
            border:1px solid #000;
            padding:10px 5px;
        }
        .pdf_header{
            width:100%;
        }
        .pdf_header img {
            width:100%;
        }

        .client_signature{
            width:100px;

        }
        .client_signature img{
            width:100%;
        }

        .tech_value{
          font-weight:bold;
        }   

        .tech_bottom_table_bold{
            font-size:13px;
            padding-left:10px !important;
        }

        .page_1 {
            // border:1px solid #000;
            // padding:30px;
            // height:88.8%;
        }
        .page_2 {
            // border:1px solid #000;
            // padding:30px;
            // height:100%;
        }


         .other_feedback{
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            // padding-bottom:50px;
        }
        .other_feedback tr .width_30{
            font-weight:bold;
            font-size:12px;
            // font-family: sans-serif;
        }
        .other_feedback_inside td{
            font-size:11px;
            // font-family: sans-serif;
            margin-top:10px;
        }

        .attacment_div{
            border: 1px solid #000;
            page-break-inside: avoid;
        }   

        .action_taken_div{
            page-break-inside: avoid;
        }
        .remark_div{
            page-break-inside: avoid;
        }
        .observation_div{
            page-break-inside: avoid;
        }
        .problemreported_div{
            page-break-inside: avoid;
        }
        .quotation_div{
            page-break-inside: avoid;
        }

        .bottom_div{
            page-break-inside: avoid;
        }

        .attacment{
            background:#027dc1;
            padding:6px 0px 6px 6px;
            color:#fff;
            font-size:14px;
            font-weight:bold;
            // font-family: sans-serif;
            
        }
        .product_attachment_box{
            padding:20px;
        }
        

        .bottom_signature_table{
            border:1px solid #000;
            border-collapse: collapse;
        }

        .bottom_table_bold{
            font-weight:bold;
            font-size:13px;
            padding-left:10px !important;
        }

        .bottom_signature_table tbody tr td{
            // font-family: sans-serif;
            border:1px solid #000;
            font-size:12px;
            padding-top:15px;
            padding-bottom:15px;
            width:20%;
        }


        .natural_of_call_div{
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            font-weight:bold;
            font-size:12px;
            padding:4px 7px;
            // font-family: sans-serif;
        }
        .natural_table_bold{
            font-weight:bold;
            font-size:11px;
            padding-left:10px !important;
        }
        .natural_call_table{
            border:1px solid #000;
            border-collapse: collapse;
        }
        .natural_call_table tbody tr td{
            // font-family: sans-serif;
            border:1px solid #000;
            padding:10px 5px;
        }
        .pdf_header{
            width:100%;
        }
        .pdf_header img {
            width:100%;
        }

        .client_signature{
            width:100px;

        }
        .client_signature img{
            width:100%;
        }

        .tech_value{
          font-weight:bold;
        }   

        .tech_bottom_table_bold{
            font-size:13px;
            padding-left:10px !important;
        }

        .page_1 {
            // border:1px solid #000;
            // padding:30px;
            // height:88.8%;
        }
        .page_2 {
            // border:1px solid #000;
            // padding:30px;
            // height:100%;
        }

        .activity_table {
    border-collapse: collapse;
    width: 100%;
    font-size: 11px;
    // font-family: sans-serif;
}

.activity_table th {
    background-color: #027dc1;
    color: #fff;
    padding: 8px;
    text-align: left;
    border: 1px solid #000;
}

.activity_table td {
    border: 1px solid #000;
    padding: 6px;
}

.activity_table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.rating-badge {
   margin-top:10px;
  display: inline-block;
  padding: 6px 14px;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  border-radius: 25px;
  letter-spacing: 0.3px;
  transition: all 0.3s ease;
  text-align: right;
}

.rating-badge:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}



    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body>
    <div class="container">
        <div class="page_1">
            <div class="pdf_header">
                <img src="../../media/pdf-assets/aryadi-sr.png">
            </div>
            <div style="display:flex;  margin-top:10px;">
                <table class="top_header">
                    <tr>
                        <td class="top_right_border">
                            <table class="top_header_inside">
                                <tr>
                                <td>Customer Details</td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table class="top_header_inside">
                                <tr>
                                <td>Service Ticket Details</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div>
                <table  class="customer_details">
                    <tr>
                        <td class="custumer_right_border">
                            <table class="customer_details_inside">
                               <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Customer Name</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$company_name.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Branch Name</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$BranchSite.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Address</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$BranchAddress1.'</td>
                                </tr>
                                
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Mobile No.</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$BranchMobile.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Client Ticket Number</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$ClientTicketID.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Equipment Name</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$EquipmentName.'</td>
                                </tr>
                                
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Make</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$Make.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Model</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$Model.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Serial Number</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$SerialNumber.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Asset Location</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$AssetLocation.'</td>
                                </tr>
                                
                            </table>
                        </td>
                        <td>
                            <table class="report_details_inside">
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Type</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$Type.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Ticket No</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$TicketID.'</td>
                                </tr>

                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Category</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$CategoryName.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Sub Category</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$SubCategoryName.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold"> Complaint Registered</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$RegisteredDate.' | '.$RegisteredTime.'</td>
                                </tr>'.$AttendedDate_html.$AssetCondition_html.'
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Close Date</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$CloseDate.' | '.$CloseTime.'</td>
                                </tr>
                               
                                
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="problemreported_div">
                <table class="other_feedback">
                    <tr> 
                        <td class="width_30"> Problem Statement: </td>
                        
                    </tr>
                    <tr class="other_feedback_inside">
                    <td> '.$ProblemReportedByClient.' </td>
                    </tr>
                </table>
            </div>
            <div class="observation_div">
                <table class="other_feedback">
                    <tr>
                        <td class="width_30"> Technician/Engineer Observation: </td>
                        <tr class="other_feedback_inside">
                        <td> '.$Observation.'</td>
                        </tr>
                    </tr>
                </table>
            </div>
            <div class="action_taken_div">
                <table class="other_feedback" style="">
                    <tr>
                        <td class="width_30"> Action Taken: </td>
                    </tr>
                    <tr class="other_feedback_inside">
                    <td> '.$ActionTaken.'</td>
                    </tr>
                </table>
            </div>

            <div class="remark_div">
                <table class="other_feedback">
                    <tr>
                        <td class="width_30"> Technician/Engineer Remarks: </td>
                    </tr>
                    <tr class="other_feedback_inside">
                    <td> '.$Remarks.' </td>
                    </tr>
                </table>
            </div>
            '.$quotation_html.'

            '.$ticket_history_html.'
        </div>


        <div class="page_2">
            <div class="attacment_div" style="margin-top:10px;">
                <div class="attacment">
                    Pre Image(s)
                </div>
                <div class="product_attachment_box">
                    <table>
                    <tr>
                        '.implode("", $image_pre_td).'
                    </tr>

                    </table>
                     
                </div>
            </div>

             <div class="attacment_div">
                <div class="attacment">
                    Post Image(s)
                </div>
                <div class="product_attachment_box">
                    <table>
                    <tr>
                        '.implode("", $image_post_td).'
                    </tr>

                    </table>
                     
                </div>
            </div>'.$service_report_div .$HVACReportHTML_div.'

            '.$time_section_html.'
            

            <div class="bottom_div">
                <table class="bottom_signature_table" style="border-bottom:none;  margin-top:10px;">
                    <tbody>
                        <tr style="background-color:#027dc1; color:white">
                            <td class="bottom_table_bold" colspan="2">Technician</td>
                        </tr>
                        <tr>
                            <td class="tech_bottom_table_bold"><span class="tech_value">Name:</span> <span>'.$TechnicianName.'</span></td>
                            <td class="tech_bottom_table_bold"><span class="tech_value">Phone Number:</span> <span>'.$TechnicianPhoneNumber.'</span></td>
                        </tr>
                        
                    </tbody>
                </table>
                <table class="bottom_signature_table" style="border-top:none; margin-top:10px;">
                    <tbody>
                        <tr style="background-color:#027dc1; color:white">
                            <td class="bottom_table_bold" colspan="3">Onsite Client Representative Name (Verified By)</td>
                        </tr>
                        <tr>
                            <td class="bottom_table_bold">Name:</td>
                            <td class="bottom_table_bold">Designation:</td>
                            <td class="bottom_table_bold">Signature:</td>
                            
                        </tr>
                        <tr>
                            <td style="padding:20px; 10px">'.$ClientRepresentative.'<br>'.$ClientRepresentativeContact.'</td>
                            <td style="padding:20px; 10px">'.$ClientRepresentativeDesignation.'</td>
                            <td style="padding:20px; 10px">'.$ClientSignature.'</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
        </div>
        
         '.$ratingHtml.'
        
    </div>
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
    'default_font' => 'poppins'
]);
$mpdf->WriteHTML("");
$mpdf->SetWatermarkImage(
    $default_logo,
    0.08,         // opacity (keep subtle)
    [50, 20],    // size (width x height in mm) - adjust as needed
    'F',          // behind content (background)
    true,         // keep aspect ratio
    45            // rotation angle (45 = diagonal)
);
$mpdf->showWatermarkImage = true;
$mpdf->SetFooter('<div style="font-size:9px; text-align:right; color:#555;">Page {PAGENO} of {nbpg}</div>');

$mpdf->WriteHTML("");
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