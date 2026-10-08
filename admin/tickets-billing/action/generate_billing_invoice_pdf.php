<?php
session_start();
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php';
include('../../controllers/common_controllers.php');
require_once('../controller/ticket_billing_controller.php');
require_once __DIR__ . '/../../includes/aryadi_pdf_theme.php';

$conn = _connectodb();
SessionCheck();

$billingNumber = isset($_GET['BillingNumber']) ? trim((string)$_GET['BillingNumber']) : '';
$mode = isset($_GET['Mode']) ? strtolower(trim((string)$_GET['Mode'])) : 'preview';

if ($billingNumber === '') {
    http_response_code(400);
    echo 'Billing number is required.';
    exit;
}

$result = GetBillingInvoiceDetail($conn, $billingNumber);
if (!isset($result['error']) || $result['error'] === true || !isset($result['data'])) {
    http_response_code(404);
    echo isset($result['message']) ? $result['message'] : 'Billing invoice not found.';
    exit;
}

$data = $result['data'];
$header = isset($data['header']) ? $data['header'] : array();
$issuer = isset($data['issuer']) ? $data['issuer'] : array();
$tickets = isset($data['tickets']) && is_array($data['tickets']) ? $data['tickets'] : array();
$billToCompanies = isset($data['bill_to_companies']) && is_array($data['bill_to_companies']) ? $data['bill_to_companies'] : array();

function tb_pdf_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function tb_pdf_amt($value)
{
    return 'Rs ' . number_format((float)$value, 2);
}

$billToDisplay = count($billToCompanies) > 0 ? implode(', ', $billToCompanies) : '-';
$remarks = isset($header['Remarks']) ? trim((string)$header['Remarks']) : '';
$invoiceNo = isset($header['BillingNumber']) ? (string)$header['BillingNumber'] : $billingNumber;
$invoiceDate = isset($header['BilledDate']) ? (string)$header['BilledDate'] : '';
$paymentStatus = isset($header['PaymentStatus']) ? (string)$header['PaymentStatus'] : 'Pending';
$ticketCount = isset($header['TicketCount']) ? (int)$header['TicketCount'] : count($tickets);
$totalAmount = isset($header['TotalAmount']) ? (float)$header['TotalAmount'] : 0.0;

$issuerLogoPath = '';
if (!empty($issuer['HeaderImage'])) {
    $possible = realpath(__DIR__ . '/../../media/pdf-assets/' . basename((string)$issuer['HeaderImage']));
    if ($possible && file_exists($possible)) {
        $issuerLogoPath = str_replace('\\', '/', $possible);
    }
}

$issuerName = isset($issuer['CompanyName']) ? (string)$issuer['CompanyName'] : 'TechXpert';
$headerLogo = '../../media/pdf-assets/aryadi-logo.png';
$headerLogoStyle = '';
if (stripos($issuerName, 'aryadi') === false && stripos($issuerName, 'arayadi') === false) {
    $headerLogo = $issuerLogoPath !== '' ? $issuerLogoPath : '../../media/pdf-assets/tech-logo.jpg';
    $headerLogoStyle = 'width:58mm; height:auto;';
}

$issuerMeta = '';
if (!empty($issuer['GstNumber'])) { $issuerMeta .= '<tr><td>GSTIN - ' . tb_pdf_h($issuer['GstNumber']) . '</td></tr>'; }
if (!empty($issuer['PanNumber'])) { $issuerMeta .= '<tr><td>PAN - ' . tb_pdf_h($issuer['PanNumber']) . '</td></tr>'; }
if (!empty($issuer['Phone'])) { $issuerMeta .= '<tr><td>Phone - ' . tb_pdf_h($issuer['Phone']) . '</td></tr>'; }
if (!empty($issuer['Email'])) { $issuerMeta .= '<tr><td>Email - ' . tb_pdf_h($issuer['Email']) . '</td></tr>'; }

$ticketSections = '';
foreach ($tickets as $ticket) {
    $rows = '';
    $lineIndex = 0;
    $lines = isset($ticket['line_items']) && is_array($ticket['line_items']) ? $ticket['line_items'] : array();
    foreach ($lines as $line) {
        $lineIndex++;
        $rows .= '
        <tr' . ($lineIndex % 2 == 0 ? ' class="alt"' : '') . '>
            <td class="num" style="text-align:center;">' . $lineIndex . '</td>
            <td>' . tb_pdf_h(isset($line['LineItemName']) ? $line['LineItemName'] : '') . '</td>
            <td style="text-align:right;">' . number_format((float)(isset($line['BilledQty']) ? $line['BilledQty'] : 0), 2) . '</td>
            <td style="text-align:right;">' . tb_pdf_amt(isset($line['PerItemPrice']) ? $line['PerItemPrice'] : 0) . '</td>
            <td style="text-align:right;">' . tb_pdf_amt(isset($line['BilledLineAmount']) ? $line['BilledLineAmount'] : 0) . '</td>
        </tr>';
    }
    $rows .= '
        <tr class="subtotal">
            <td colspan="4" style="text-align:right;">Ticket Subtotal</td>
            <td style="text-align:right;">' . tb_pdf_amt(isset($ticket['TicketBilledAmount']) ? $ticket['TicketBilledAmount'] : 0) . '</td>
        </tr>';

    $ticketSections .= apdf_data_box(
        'Ticket: ' . (isset($ticket['TicketID']) ? $ticket['TicketID'] : ''),
        array(
            array('#', 6),
            array('Description', 54),
            array('Qty', 12),
            array('Rate', 14),
            array('Amount', 14),
        ),
        $rows,
        apdf_join(array(
            isset($ticket['TicketType']) ? $ticket['TicketType'] : '',
            isset($ticket['CompanyName']) ? $ticket['CompanyName'] : '',
            isset($ticket['BranchSite']) ? $ticket['BranchSite'] : '',
        ))
    );
}

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>' . apdf_css() . '</style>
</head>
<body>
    ' . apdf_header('TAX INVOICE', 'TICKET BILLING INVOICE', $headerLogo, $headerLogoStyle) . '

    <div class="section keep">
        <table class="issuer">
            <tr>
                <td style="width:60%; border-right:0.3mm solid #D6E4F2;">
                    <div class="block_label">Issued By</div>
                    <table><tr><td class="issuer_name">' . tb_pdf_h($issuerName) . '</td></tr></table>
                    ' . (!empty($issuer['CompanyAddress']) ? '<div style="font-size:8.5pt; color:#334155; margin-top:1mm; line-height:1.45;">' . nl2br(tb_pdf_h($issuer['CompanyAddress'])) . '</div>' : '') . '
                </td>
                <td style="width:40%;">
                    <div class="block_label" style="text-align:right;">Registration</div>
                    <table class="issuer_meta">' . ($issuerMeta !== '' ? $issuerMeta : '<tr><td>' . apdf_text('') . '</td></tr>') . '</table>
                </td>
            </tr>
        </table>
    </div>

    ' . apdf_stats(array(
        array('Invoice No.', tb_pdf_h($invoiceNo)),
        array('Invoice Date', apdf_text($invoiceDate)),
        array('Payment Status', tb_pdf_h($paymentStatus)),
        array('Tickets', (int)$ticketCount),
    )) . '

    ' . apdf_box('Bill To', apdf_box_text(tb_pdf_h($billToDisplay))) . '

    ' . $ticketSections . '

    <div class="section keep">
        <table>
            <tr>
                <td style="width:55%; vertical-align:middle; padding:3mm 3.5mm; border-right:0.3mm solid #D6E4F2;">
                    <div class="block_label">Note</div>
                    <div class="block_text" style="color:#64748B;">All amounts are excluding GST as per quotation line items.</div>
                </td>
                <td style="width:45%; vertical-align:top; padding:0;">
                    <table class="totals">
                        <tr><td class="t_label">Tickets Billed</td><td>' . (int)$ticketCount . '</td></tr>
                        <tr class="grand"><td>Grand Total (Excl. GST)</td><td>' . tb_pdf_amt($totalAmount) . '</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    ' . ($remarks !== '' ? apdf_box('Remarks', apdf_box_text(nl2br(tb_pdf_h($remarks)))) : '') . '
</body>
</html>';

try {
    $mpdf = apdf_create_mpdf('Ticket Billing Invoice - ' . $invoiceNo, 'Tax Invoice · ' . $invoiceNo, array('format' => 'A4'), $issuerName);
    $mpdf->WriteHTML($html);

    $safeInvoiceNo = preg_replace('/[^A-Za-z0-9\-_]/', '_', $invoiceNo);
    if ($safeInvoiceNo === '') {
        $safeInvoiceNo = 'billing-invoice';
    }
    $filename = 'ticket-billing-' . $safeInvoiceNo . '.pdf';

    if ($mode === 'download') {
        $mpdf->Output($filename, 'D');
    } else {
        $mpdf->Output($filename, 'I');
    }
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo 'Unable to generate PDF: ' . $e->getMessage();
}
?>
