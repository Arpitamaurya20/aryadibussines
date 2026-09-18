    <?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php'; 
include '../../controllers/common_controllers.php'; 
// Start MPDF
$html = "";
$mpdf = new \Mpdf\Mpdf();
$conn = _connectodb();
if(isset($_POST['QuotationID']))
{
    $QuotationID = $_POST['QuotationID'];
    $Action = $_POST['Action'];
}
else
{
   // die();
    if(isset($_GET['QuotationID']))
    {
       $QuotationID = $_GET['QuotationID'];
       $Action = ""; 
    }
}
$corporateticket_obj = new Corporateticket($conn);
$core = new Core();
$quotation_detail = $corporateticket_obj->GetQuotationDetailbyID($QuotationID); 
$Quotation_display_ID = "TECHX-EST-".sprintf('%05d', $QuotationID);
$QuotationDate = $quotation_detail['QuotationDate'];
$QuotationExpiryDate = $quotation_detail['QuotationExpiryDate'];
if($QuotationDate == "")
{
    $QuotationDate = $quotation_detail['CreatedDate'];
}
$q_line_items = $corporateticket_obj->GetQuotationLineItems($QuotationID);
$ticket_details = $corporateticket_obj->GetTicketDetails($quotation_detail['TicketID']);
$ticket_overview = $ticket_details['ticket_details'];
$TicketID = $ticket_overview['TicketID'];
$ClientTicketID = $ticket_overview['ClientTicketID'];
$branch_details = $ticket_details['branch_details'];
$BranchSite = $branch_details['BranchSite'];
$BranchState = $branch_details['BranchState'];
$BranchMobile = $branch_details['BranchMobile'];
$BranchEmail = $branch_details['BranchEmail'];

$AccountBranchManager = $branch_details['AccountBranchManager'];
$employee_obj = new Employee($conn);
$employee_array = $employee_obj->setEmployeeArray("All");
$sale_person_name = "";
if(isset($employee_array[$AccountBranchManager]))
{
    $sale_person_name = $employee_array[$AccountBranchManager]['Name'];
    $AccountBranchManagerEmail = $employee_array[$AccountBranchManager]['Email'];
}

$SiteIncharge = $branch_details['SiteIncharge'];
$BranchAddress1 = $branch_details['BranchAddress1'];

// Company Overview
$company_overview = $ticket_details['corporate_details'];
// Get Company Name
$CompanyName = "";
$company_head_details = null;

if(!empty($CompanyHeadID)) {
    $company_head_details = $core->_getTableDetails($conn, 'corporate', 'where ID = ' . intval($CompanyHeadID));
}

if($company_head_details != null) {
    $CompanyName = $company_head_details['CorporateName'];
} else {
    $CompanyName = "";
}


$CompanyGST = "";
$CompanyAddress = "";
$CompanyEmail = "";
if(isset($company_overview['CompanyName']))
{
    $CompanyEmail = $company_overview['CompanyEmail'];
    //$CompanyName = $company_overview['CompanyName'];
    // Get state gst details
    $CompanyID = $ticket_overview['CorporateID'];
    $gst_details_filter = " where CompanyID = $CompanyID and CompanyState = '$BranchState'";
    $gst_overview = $core->_getTableDetails($conn,'company_state_gst',$gst_details_filter);
    if($gst_overview != null)
    {
        $CompanyGST = $gst_overview['GST'];
        $CompanyAddress = $gst_overview['Address'];
    }
}

$i=1;
$SuperTotal = 0;
$total_tax = 0;
$line_item_html = '';
foreach($q_line_items as $line_item)
{
    $SuperTotal = $SuperTotal +  $line_item['TotalPrice'];
    $tax = $line_item['Tax'];
    $tax_html = "";
    if($tax == "" || $tax == "0")
    {
        $tax_html = "0";
    }
    if($tax != 0 && $tax != "")
    {
        $tax_html = $tax."%";
        $total_tax = $total_tax+round((($line_item['TotalPrice'] * $tax)/100),2);
    }
    $line_item_html = $line_item_html.'<tr>';
    $line_item_html = $line_item_html.'<td style="padding:5px; width:5%;">'.$i.'</td>';
    $line_item_html = $line_item_html.'<td style="padding:5px; width:25%;">'.$line_item['LineItemName'].'</td>';
     $line_item_html = $line_item_html.'<td style="padding:5px; width:10%;">'.$line_item['Make'].'</td>';
    $line_item_html = $line_item_html.'<td style="padding:5px; width:7%;">'.$line_item['ARCCode'].'</td>';
    $line_item_html = $line_item_html.'<td style="padding:5px; width:10%;">'.$line_item['HSN'].'</td>';
    $line_item_html = $line_item_html.'<td style="padding:5px; width:5%;">'.$line_item['UoM'].'</td>';
    $line_item_html = $line_item_html.'<td style="padding:5px; width:10%;">'.$line_item['Qty'].'</td>';
    $line_item_html = $line_item_html.'<td style="padding:5px; width:10%;">₹'.$core->formatIndianNumber($line_item['PerItemPrice']).'</td>';
    $line_item_html = $line_item_html.'<td style="padding:5px; width:8%;">'.$tax_html.'</td>';
    $line_item_html = $line_item_html.'<td style="padding:5px; width:10%;">₹'.$core->formatIndianNumber($line_item['TotalPrice']).'</td>';
    $line_item_html = $line_item_html.'</tr>';
    $i++;
}

$total_including_tax = $SuperTotal+$total_tax;
$total_including_tax_in_words = $core->numberToWordsIndian($total_including_tax); 

$SuperTotal_display = "₹".$core->formatIndianNumber($SuperTotal);
$total_tax_display = "₹".$core->formatIndianNumber($total_tax);
$total_including_tax_display = "₹".$core->formatIndianNumber($total_including_tax);

$tc_div = "";
if($quotation_detail['QuotationTC'] != "")
{
    $tc_div = '
    <tr>
        <td class="costumer_detail" style="width:70%;text-align:left;"> Terms & Conditions<br>'.$quotation_detail['QuotationTC'].'</td>
    </tr>';
}
else
{
    $tc_div = '
    <tr>
        <td class="costumer_detail" style="width:70%;text-align:left;"></td>
    </tr>';
}

$media_asset = "techx-quotation.png";
$header_company_name = "Techxpert Facilities India Private Limited";
$header_company_address = "451 - 452, First Floor, Leela Ram Market, <br>Masjid Moth, South Extension Part - 2,<br>New Delhi, Delhi - 110049, India";
$gst_pan_information = "<tr>
                                    
                                    <td style='width:40%;'> GSTIN - 07AAICT0561A1ZR</td>
                                </tr>
                                <tr>
                                    <td style='width:40%;'> PAN - AAICT0561A</td>
                                </tr>
                                <tr>
                                    <td style='width:40%;'>Phone - 9873669227</td>
                                </tr>";
$stamp_information = '<td class="costumer_detail_bold" style="width:70%;text-align:left;"> Note<br> Looking forward for your business</td>
                                    <td style="width:30%;" rowspan="2">
                                    Ceritified that particulars given above are true & correct<br><img src="../../media/pdf-assets/stamp.jpg" height="100px" style="margin-left:10px;"><br><span style="margin-left:10px;">Authorized Signature</span></td>';
if($CompanyID == 183)
{
    $media_asset = "innov-quotation.jpg";
    $header_company_name = "Innovsource Facilities Pvt. Ltd.";
    $header_company_address = "Unit No 401, Om Sadan, Mehra Industrial Estate, <br>Lal Bahadur Shastri Marg, Vikroli West,<br>Mumbai, Maharashtra - 400079, India";
    $gst_pan_information = "<tr>
                                    
                                    <td style='width:40%;'> GSTIN - 27AAECI4015J1ZO</td>
                                </tr>
                               
                                <tr>
                                    <td style='width:40%;'>Phone - 8826789578</td>
                                </tr>";
    $stamp_information = '<td class="costumer_detail_bold" style="width:70%;text-align:left;"> Note<br> Looking forward for your business</td>
                                    <td style="width:30%;" rowspan="2">
                                    Ceritified that particulars given above are true & correct<br><br><span style="margin-left:10px;">Generates Document, Does not require signature</span></td>';
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
            // padding-bottom:50px;
        }
        .other_feedback tr .width_30{
            font-weight:bold;
            font-size:12px;
            font-family: sans-serif;
        }
        .other_feedback_inside td{
            font-size:11px;
            font-family: sans-serif;
            margin-top:10px;
        }

        // .bottom_div{
        //     page-break-inside: avoid;
        // }

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
            padding-left:5px !important;
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
        .right-column {
            width: 20%;
            vertical-align: top;
            height: 100px; /* Match the height of the image */
        }
        .left-column {
            width: 80%;
            text-align: left;
            vertical-align: top;
        }
        .stamp {
            height: 100px;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="page_1">
            <div class="pdf_header">
                <img src="../../media/pdf-assets/'.$media_asset.'">
            </div>
            <div>
                <table  class="customer_details">
                    <tr>
                        <td class="custumer_right_border">
                            <table class="customer_details_inside">
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">'.$header_company_name.'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;">'.$header_company_address.'</td>
                                </tr>
                                
                                
                               
                            </table>
                        </td>
                        <td>
                            <table class="report_details_inside">
                                '.$gst_pan_information.'
                                
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div style="display:flex;">
                <table class="top_header">
                    <tr>
                        <td class="top_right_border">
                            <table class="top_header_inside">
                                <tr>
                                <td>Quotation Details</td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table class="top_header_inside">
                                <tr>
                                <td>Customer Details</td>
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
                                    <td style="width:50%;" class="costumer_detail_bold">Quotation</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$Quotation_display_ID.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Quotation Date</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$QuotationDate.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Expiry Date</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$QuotationExpiryDate.'</td>
                                </tr>
                                
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Ticket Number</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$TicketID.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Client Reference Ticket</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$ClientTicketID.'</td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table class="report_details_inside">
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Place Of Supply</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$BranchState.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Sales Person</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$sale_person_name.'</td>
                                </tr>

                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Customer Name</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$SiteIncharge.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Cust Contact NO</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$BranchMobile.'</td>
                                </tr>
                                
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div style="display:flex;">
                <table class="top_header">
                    <tr>
                        <td class="top_right_border">
                            <table class="top_header_inside">
                                <tr>
                                <td> Bill To</td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table class="top_header_inside">
                                <tr>
                                <td>Ship To</td>
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
                                    <td style="width:50%;" class="costumer_detail_bold">.'.$CompanyName.'.</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;">'.$CompanyAddress.'</td>
                                </tr>
                                
                                <tr>
                                    
                                    <td style="width:40%;"> GSTIN - '.$CompanyGST.'</td>
                                </tr>
                                <tr>
                                    
                                    <td style="width:40%;"></td>
                                </tr>
                               
                            </table>
                        </td>
                        <td>
                            <table class="report_details_inside">
                                <tr>
                                    <td style="width:40%;">'.$BranchSite.'</td>
                                </tr>
                                <tr>
                                    
                                    <td style="width:40%;">'.$BranchAddress1.'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"></td>
                                </tr>
                                
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>


        <div class="page_2">
            <div class="bottom_div">   
                <table class="bottom_signature_table" style="border-top:none;">
                    <tbody>
                        <tr style="background:#f4f4f4;">
                            <td class="bottom_table_bold" style="width:5%;">#</td>
                            <td class="bottom_table_bold" style="width:25%;">Item & Description</td>
                             <td class="bottom_table_bold" style="width:10%;">Make/Model</td>
                            <td class="bottom_table_bold" style="width:7%;">ARC</td>
                            <td class="bottom_table_bold" style="width:10%;">HSN/SAC</td>
                            <td class="bottom_table_bold" style="width:5%;">UOM</td>
                            <td class="bottom_table_bold" style="width:10%;">Qty</td>
                            <td class="bottom_table_bold" style="width:10%;">Rate</td>
                            <td class="bottom_table_bold" style="width:8%;">Tax</td>
                            <td class="bottom_table_bold" style="width:10%;">Amount</td>
                            
                        </tr>'.$line_item_html.'
                    </tbody>
                </table>
            </div>
            <div>
                <table  class="customer_details">
                    <tr>
                        <td class="custumer_right_border">
                            <table class="customer_details_inside">
                                <tr>
                                  <td style="width:40%;"> Total In Words</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Rupees '.ucwords($total_including_tax_in_words).'</td>
                                </tr>
                               
                               
                            </table>
                        </td>
                        <td>
                            <table class="report_details_inside">
                                <tr>
                                    <td style="width:50%; text-align:right;">Total</td>
                                    <td style="width:10%; text-align:right;"></td>
                                    <td style="width:40%; text-align:right;">'.$SuperTotal_display.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%; text-align:right;">Tax</td>
                                    <td style="width:10%; text-align:right;"></td>
                                    <td style="width:40%; text-align:right;">'.$total_tax_display.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%; text-align:right;" class="costumer_detail_bold">Total(Including Tax)</td>
                                    <td style="width:10%; text-align:right;"></td>
                                    <td style="width:40%; text-align:right;">'.$total_including_tax.'</td>
                                </tr>
                                
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div>
                <table  class="customer_details">
                    <tr>
                        <td class="">
                            <table class="customer_details_inside">
                               
                                <tr>
                                    '.$stamp_information.'
                                </tr>'.$tc_div.'
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            
            
        </div>
        

        
    </div>
</body>
</html>';

$mpdf->WriteHTML("");
$mpdf->WriteHTML($html);
$mpdf->SetFooter('<div style="text-align: center; font-size: 10pt;">Head Off. - New Delhi Branch Off. - Noida , Gurgaon , Kolkata , Mumbai , Bangalore , Chennai , Baddi , Patna</div>');

/*$mpdf->SetWatermarkImage('../../media/pdf-assets/tech-logo.jpg');
$mpdf->showWatermarkImage = true;
$mpdf->watermarkImgAlpha = 0.1; // Set transparency
$mpdf->watermarkImgBehind = true; // Place the watermark behind the content
$mpdf->showWatermarkImage = true;*/


$pdf_name = "$TicketID-quotation-pdf.pdf";
$pdfFilePath = '../quotations/'.$pdf_name.'';
$mpdf->Output($pdfFilePath, 'F');
if(isset($_GET['QuotationID']))
{
    $mpdf->Output();
}

$response['pdfname'] = $pdf_name;
if($Action == "Senddd")
{
    $core = new Core();
    $mail_data['action'] = "Send Quotation";
    $mail_data['SiteName'] = $BranchSite;
    //$mail_data['POCEmail'] = $SitePOCEmail;
    $mail_data['BranchEmail'] = $BranchEmail;
    $mail_data['CorporateAccountEmail'] = $CompanyEmail;
    $mail_data['AccountBranchManagerEmail'] = $AccountBranchManagerEmail;
    //$mail_data['ZoneEmail'] = $ZoneEmail;
    $mail_data['POCName'] = $SiteIncharge;
    $mail_data['FilePath'] = $pdf_name;
    $mail_data['TicketID'] = $TicketID;
    $mail_data['QuotationID'] = $QuotationID;
    $mail_data['ClientTicketID'] = $ClientTicketID;
    $mail_data['CompanyID'] = $CompanyID;
    // $url = "https://techxpertindia.in/admin/mail/send-quotation.php";
    // $core->sendMailRequest($mail_data,$url);
}
echo json_encode($response);
?>