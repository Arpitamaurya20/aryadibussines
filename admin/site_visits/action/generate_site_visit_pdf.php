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
if(isset($_POST))
{
    $Action = $_POST['Action'];
    $SiteVisitID = $_POST['SiteVisitID'];
    //$Action = "Download";
    //$SiteVisitID = 12;
    $site_visits_obj = new Sitevisits($conn);
    $site_visit_details = $site_visits_obj->GetSiteVisitDetail($SiteVisitID);
    $VisitTitle = $site_visit_details['VisitTitle'];
    $ReportNumber = "TECHX-SV-".sprintf("%05d", $site_visit_details['ID']);
    $ContactPerson = $site_visit_details['ContactPerson'];
    $ContactPersonPhone = $site_visit_details['ContactPersonPhone'];
    $ContactPersonEmail = $site_visit_details['ContactPersonEmail'];
    $ClientSignature = $site_visit_details['ClientSignature'];
    $Summary = $site_visit_details['Summary'];
    $CompletedDate = $site_visit_details['CompletedDate'];
    $CompletedTime = $site_visit_details['CompletedTime'];

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

    if($BranchAccountManager != -1 && $BranchAccountManager != "")
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
    $observation_div = '';
    foreach($site_visit_observations as $site_visit_observation)
    {
        //Get Site Observation Media
        $observation_media_array = $site_visit_observation['observation_media'];
        $image_observation_media = array();
        foreach($observation_media_array as $observation_media)
        {
            $original_image_path = '../../media/site_visits/' . $observation_media['Image'];
            $compressed_image_path = '../../media/site_visits/compressed_' . $observation_media['Image'];

            $imageDate=$observation_media['CreatedDate'];
            $imageTime=$observation_media['CreatedTime'];
            
            // Compress the image
            $core->compressImage($original_image_path, $compressed_image_path, 50); // 75 is the quality percentage
                $image_observation_media[] = '<td style="width:25%; padding:20px">
                    <div style="width:100px; height:100px;" class="product_attachment">
                        <img style="width:100px;"  class="mt-3"
                            src="'.$compressed_image_path.'" alt="">
                    </div>
                    <div class="mt-2 d-flex"style="font-size: 10px">
                        <span class=""style="font-size: 5px;">'.$imageDate.'</span>
                        <span class=""style="font-size: 5px;">'.$imageTime.'</span>
                      </div>
                </td>';
        }
        $observation_category = $site_visit_observation['Category'];
        $observation_priority = $site_visit_observation['Priority'];
        $observation_observation = $site_visit_observation['Observation'];
        $observation_companyrecommendation = $site_visit_observation['CompanyRecommendation'];
        $observation_clientrecommendation = $site_visit_observation['ClientRecommendation'];
        $Location = $site_visit_observation['Location'];
        $observation_div = $observation_div.'
        <div class="attacment_div" style="margin-top:5px;">
            <div class="attacment">
                Observation '.$i.'
            </div>
            <table  class="customer_details">
                <tr>
                    <td class="custumer_right_border">
                        <table class="customer_details_inside">
                           <tr>
                                <td style="width:50%;" class="costumer_detail_bold">Category</td>
                                <td style="width:10%;">:</td>
                                <td style="width:40%;">'.$observation_category.'</td>
                            </tr>
                        </table>
                    </td>
                    <td class="">
                        <table class="customer_details_inside">
                           <tr>
                                <td style="width:50%;" class="costumer_detail_bold">Priority</td>
                                <td style="width:10%;">:</td>
                                <td style="width:40%;">'.$observation_priority.'</td>
                            </tr>
                            <tr>
                                <td style="width:50%;" class="costumer_detail_bold">Location</td>
                                <td style="width:10%;">:</td>
                                <td style="width:40%;">'.$Location.'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table class="other_feedback">
                <tr> 
                    <td class="width_30"> Observation: </td>
                    
                </tr>
                <tr class="other_feedback_inside">
                <td> '.$observation_observation.' </td>
                </tr>
            </table>

            
            <table class="other_feedback">
                <tr> 
                    <td class="width_30"> Image(s): </td>
                    
                </tr>
                <tr>
                    '.implode("", $image_observation_media).'
                </tr>

            </table>
           
            <table class="other_feedback">
                <tr> 
                    <td class="width_30"> Client Recommendation: </td>
                    
                </tr>
                <tr class="other_feedback_inside">
                <td> '.$observation_clientrecommendation.' </td>
                </tr>
            </table>
            <table class="other_feedback">
                <tr> 
                    <td class="width_30"> Aryadibusiness Recommendation: </td>
                    
                </tr>
                <tr class="other_feedback_inside">
                <td> '.$observation_companyrecommendation.' </td>
                </tr>
            </table>
        </div>';
        $i++;
    }

    
}
else
{
    die();
}
if($ClientSignature == "")
{
    $ClientSignature = '';
}
else
{
      $ClientSignatureDate = $site_visit_details['CreatedDate'];
      $ClientSignatureTime = $site_visit_details['CreatedTime'];
        $ClientSignature = '
        <div style="width:100px; height:200px;" class="client_signature">
            <img style="width:100px;" src="../../media/signature/'.$ClientSignature.'">
        </div>
        <div class="mt-2 d-flex" style="font-size:10px">
            <span class="badge bg-warning">'.$ClientSignatureDate.'</span>
            <span class="badge bg-info me-3">'.$ClientSignatureTime.'</span>
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
            font-family: Inter;
            font-size:12pt;
        }  
        table {
            width: 100%;
        }
        .top_header{
           background:#08475e;
           border:1px solid #000;
        }
        .top_header tr td{
            width:50%;
         }
         .top_header_inside tr td {
            text-align:center;
            color:#fff;
            font-weight:bold;
            font-family: sans-serif;
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
             font-family: sans-serif;
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
            font-family: sans-serif;
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
        }
        .other_feedback tr .width_30{
            font-weight:bold;
            font-size:12px;
            font-family: sans-serif;
            height:50px;
        }
        .other_feedback_inside td{
            font-size:11px;
            font-family: sans-serif;
            margin-top:10px;
        }

        .attacment_div{
            border: 1px solid #000;
            
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
            background:#08475e;
            padding:6px 0px 6px 6px;
            color:#fff;
            font-size:14px;
            font-weight:bold;
            font-family: sans-serif;
            
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
            font-family: sans-serif;
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
            font-family: sans-serif;
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
            font-family: sans-serif;
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


    </style>
</head>
<body>
    <div class="container">
        <div class="page_1">
            <div class="pdf_header">
                <img src="../../media/pdf-assets/techx-1.png">
            </div>
            <div style="display:flex;">
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
                                <td>Site Visit Details</td>
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
                                    <td style="width:50%;" class="costumer_detail_bold">Contact Person</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$ContactPerson.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Contact Person Mobile</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$ContactPersonPhone.'</td>
                                </tr>
                                

                                
                            </table>
                        </td>
                        <td>
                            <table class="report_details_inside">
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Site Visit Title</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$VisitTitle.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Report Number</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$ReportNumber.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Created By</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$TechnicianName.'</td>
                                </tr>

                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Completed Date</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$CompletedDate.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Completed Time</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$CompletedTime.'</td>
                                </tr>
                               
                                
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            
        </div>


        <div class="page_2">
            
            '.$observation_div.'
            
            <div class="attacment_div" style="margin-top:10px;margin-bottom:10px;">
                <div class="attacment">
                    Site Visit Summary
                </div>
                <table class="other_feedback">
                    <tr class="other_feedback_inside">
                    <td> '.$Summary.' </td>
                    </tr>
                </table>
            </div>
            <div class="bottom_div">
                <table class="bottom_signature_table" style="border-bottom:none;">
                    <tbody>
                        <tr>
                            <td class="bottom_table_bold" colspan="2">Aryadibusiness Representative</td>
                        </tr>
                        <tr>
                            <td class="tech_bottom_table_bold"><span class="tech_value">Name:</span> <span>'.$TechnicianName.'</span></td>
                            <td class="tech_bottom_table_bold"><span class="tech_value">Phone Number:</span> <span>'.$TechnicianPhoneNumber.'</span></td>
                        </tr>
                        
                    </tbody>
                </table>
                <table class="bottom_signature_table" style="border-top:none;">
                    <tbody>
                        <tr>
                            <td class="bottom_table_bold" colspan="3">Onsite Client Representative Name (Verified By)</td>
                        </tr>
                        <tr>
                            <td class="bottom_table_bold">Name:</td>
                            <td class="bottom_table_bold">ContactNumber:</td>
                            <td class="bottom_table_bold">Signature:</td>
                            
                        </tr>
                        <tr>
                            <td style="padding:20px; 10px">'.$ContactPerson.'</td>
                            <td style="padding:20px; 10px">'.$ContactPersonPhone.'</td>
                            <td style="padding:20px; 10px">'.$ClientSignature.'</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
        </div>
        

        
    </div>
</body>
</html>';

// if($Type == "hvac") {
//     $html .= generateHVACReportHTML($service_hvac_reports_details);
// }

$mpdf->WriteHTML("");
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