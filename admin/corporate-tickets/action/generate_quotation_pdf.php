<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php'; 
include '../../controllers/common_controllers.php';
require_once __DIR__ . '/../../includes/aryadi_pdf_theme.php'; 
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
$CompanyHeadID=$company_overview['CorporateName'];
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
$remarks_html = "";

if(!empty($quotation_detail['Remarks'])) {
    // Split by comma
    $remarks_array = explode(',', $quotation_detail['Remarks']);
    
    $remarks_html .= "<ul style='margin:5px 0; padding-left:15px;'>";
    foreach($remarks_array as $remark) {
        $remarks_html .= "<li>" . trim($remark) . "</li>";
    }
    $remarks_html .= "</ul>";
}

if($quotation_detail['QuotationTC'] != "")
{
    $tc_div = '
    <tr>
        <td class="costumer_detail" style="width:70%;text-align:left;">
            <strong>Terms & Conditions</strong><br>
            '.$quotation_detail['QuotationTC'].'<br><br>

            <strong>Remarks</strong><br>
            '.$remarks_html.'
        </td>
    </tr>';
}
else
{
    $tc_div = '
   <tr>
        <td class="costumer_detail" style="width:70%;text-align:left;">
            <strong>Remarks</strong><br>
            '.$remarks_html.'
        </td>
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


$QuoteCompanyDetailsID = isset($quotation_detail['QuoteCompanyDetailsID']) ? (int)$quotation_detail['QuoteCompanyDetailsID'] : 1;
if ($QuoteCompanyDetailsID > 0) {
    $quote_company_detail = $core->_getTableDetails($conn, 'quote_company_details', ' where ID = ' . $QuoteCompanyDetailsID);
    if (!empty($quote_company_detail) && isset($quote_company_detail['ID'])) {
        $header_company_name = trim((string)($quote_company_detail['CompanyName'] ?? '')) !== '' ? $quote_company_detail['CompanyName'] : $header_company_name;
        if (trim((string)($quote_company_detail['CompanyAddress'] ?? '')) !== '') {
            $header_company_address = nl2br($quote_company_detail['CompanyAddress']);
        }

        $gst_pan_information = '';
        if (trim((string)($quote_company_detail['GstNumber'] ?? '')) !== '') {
            $gst_pan_information .= "<tr><td style='width:40%;'> GSTIN - " . $quote_company_detail['GstNumber'] . "</td></tr>";
        }
        if (trim((string)($quote_company_detail['PanNumber'] ?? '')) !== '') {
            $gst_pan_information .= "<tr><td style='width:40%;'> PAN - " . $quote_company_detail['PanNumber'] . "</td></tr>";
        }
        if (trim((string)($quote_company_detail['Phone'] ?? '')) !== '') {
            $gst_pan_information .= "<tr><td style='width:40%;'>Phone - " . $quote_company_detail['Phone'] . "</td></tr>";
        }

        // Keep PDF layout stable if optional fields are not filled.
        if ($gst_pan_information === '') {
            $gst_pan_information = "<tr><td style='width:40%;'></td></tr>";
        }
    }
    
}
$media_asset = "techx-quotation.png";

if (!empty($quote_company_detail) && isset($quote_company_detail['ID'])) {
    $media_asset = !empty($quote_company_detail['HeaderImage']) 
        ? $quote_company_detail['HeaderImage'] 
        : $media_asset;
}

if (!empty($quote_company_detail) && isset($quote_company_detail['ID'])) {
    $stamp_image = !empty($quote_company_detail['StampImage'])
        ? '../../media/pdf-assets/' .$quote_company_detail['StampImage']
        : '../../media/pdf-assets/stamp.jpg'; // default fallback
} else {
    $stamp_image = '../../media/pdf-assets/stamp.jpg'; // safety fallback
}

$stamp_information = '
<td class="costumer_detail_bold" style="width:70%;text-align:left;">
    Note<br> Looking forward for your business
</td>
<td style="width:30%;" rowspan="2">
    Certified that particulars given above are true & correct<br>
    <img src="'.$stamp_image.'" height="100px" style="margin-left:10px;"><br>
    <span style="margin-left:10px;">Authorized Signature</span>
</td>';

// $stamp_information = '<td class="costumer_detail_bold" style="width:70%;text-align:left;"> Note<br> Looking forward for your business</td>
//                                     <td style="width:30%;" rowspan="2">
//                                     Certified that particulars given above are true & correct<br><img src="../../media/pdf-assets/stamp.jpg" height="100px" style="margin-left:10px;"><br><span style="margin-left:10px;">Authorized Signature</span></td>';
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
$issuer_name = trim(strip_tags($header_company_name));
$issuer_logo = '../../media/pdf-assets/tech-logo.jpg';
$issuer_logo_style = 'width:58mm; height:auto;';
$issuer_iso = true;
if (stripos($issuer_name, 'aryadi') !== false || stripos($issuer_name, 'arayadi') !== false) {
    $issuer_logo = '../../media/pdf-assets/aryadi-logo.png';
    $issuer_logo_style = '';
}
if($CompanyID == 183)
{
    $issuer_logo = '../../img/innov_logo.jpg';
    $issuer_logo_style = 'height:18mm; width:auto;';
    $issuer_iso = false;
}

$quotation_status = $quotation_detail['QuotationStatus'];

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation '.$Quotation_display_ID.'</title>
    <style>'.apdf_css().'
        .terms td { padding: 3mm 3.5mm; font-size: 8.5pt; line-height: 1.5; vertical-align: top; border-bottom: 0.3mm solid #EEF3F9; color: #0F172A; }
        .terms td.costumer_detail_bold { font-weight: bold; color: #0B356E; }
    </style>
</head>
<body>
    '.apdf_header('QUOTATION', 'ESTIMATE FOR SERVICES', $issuer_logo, $issuer_logo_style, $issuer_iso).'

    <div class="section keep">
        <table class="issuer">
            <tr>
                <td style="width:60%; border-right:0.3mm solid #D6E4F2;">
                    <div class="block_label">Issued By</div>
                    <table><tr><td class="issuer_name">'.$header_company_name.'</td></tr></table>
                    <div style="font-size:8.5pt; color:#334155; margin-top:1mm; line-height:1.45;">'.$header_company_address.'</div>
                </td>
                <td style="width:40%;">
                    <div class="block_label" style="text-align:right;">Registration</div>
                    <table class="issuer_meta">'.$gst_pan_information.'</table>
                </td>
            </tr>
        </table>
    </div>

    '.apdf_stats(array(
        array('Quotation No', $Quotation_display_ID),
        array('Quotation Date', apdf_text($QuotationDate)),
        array('Expiry Date', apdf_text($QuotationExpiryDate)),
        array('Status', apdf_text($quotation_status)),
    )).'

    '.apdf_two_col(
        'Quotation Details',
        array(
            array('Quotation', apdf_text($Quotation_display_ID)),
            array('Quotation Date', apdf_text($QuotationDate)),
            array('Expiry Date', apdf_text($QuotationExpiryDate)),
            array('Ticket Number', apdf_text($TicketID)),
            array('Client Reference Ticket', apdf_text($ClientTicketID)),
        ),
        'Customer Details',
        array(
            array('Place Of Supply', apdf_text($BranchState)),
            array('Sales Person', apdf_text($sale_person_name)),
            array('Customer Name', apdf_text($SiteIncharge)),
            array('Cust Contact No', apdf_text($BranchMobile)),
        )
    ).'

    '.apdf_two_col(
        'Bill To',
        array(
            array('Company', apdf_text($CompanyName)),
            array('Address', apdf_text($CompanyAddress)),
            array('GSTIN', apdf_text($CompanyGST)),
        ),
        'Ship To',
        array(
            array('Site', apdf_text($BranchSite)),
            array('Address', apdf_text($BranchAddress1)),
        )
    ).'

    '.apdf_data_box('Line Items', array(
        array('#', 5),
        array('Item &amp; Description', 25),
        array('Make/Model', 10),
        array('ARC', 7),
        array('HSN/SAC', 10),
        array('UOM', 5),
        array('Qty', 8),
        array('Rate', 11),
        array('Tax', 7),
        array('Amount', 12),
    ), $line_item_html).'

    <div class="section keep">
        <table>
            <tr>
                <td style="width:55%; vertical-align:top; padding:3mm 3.5mm; border-right:0.3mm solid #D6E4F2;">
                    <div class="block_label">Total In Words</div>
                    <div class="block_text" style="font-weight:bold; color:#0B356E;">'.ucwords($total_including_tax_in_words).'</div>
                </td>
                <td style="width:45%; vertical-align:top; padding:0;">
                    <table class="totals">
                        <tr><td class="t_label">Total</td><td>'.$SuperTotal_display.'</td></tr>
                        <tr><td class="t_label">Tax</td><td>'.$total_tax_display.'</td></tr>
                        <tr class="grand"><td>Total (Including Tax)</td><td>'.$total_including_tax_display.'</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    '.apdf_box('Notes, Terms & Authorisation', '<table class="terms" width="100%"><tr>'.$stamp_information.'</tr>'.$tc_div.'</table>').'
</body>
</html>';

$mpdf = apdf_create_mpdf('Quotation '.$Quotation_display_ID, 'Quotation · '.$Quotation_display_ID, array(), $issuer_name,
    'Head Off. - New Delhi Branch Off. - Noida , Gurgaon , Kolkata , Mumbai , Bangalore , Chennai , Baddi , Patna');

// Add watermark if not approved
if($quotation_status != "Quote Approved")
{
    $mpdf->SetWatermarkText('YET TO BE APPROVED');
    $mpdf->showWatermarkText = true;
    $mpdf->watermarkTextAlpha = 0.1;
}

$mpdf->WriteHTML($html);

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
if($Action == "Sendd")
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
    $core->sendMailRequest($mail_data,$url);
}
echo json_encode($response);
?>