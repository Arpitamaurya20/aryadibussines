<?php
session_start();
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php';
include('../../controllers/common_controllers.php');
require_once('../controller/ticket_billing_controller.php');

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

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: sans-serif; font-size: 10.5pt; color: #222; }
        .header-table, .meta-table, .line-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .title { font-size: 20px; font-weight: bold; letter-spacing: 1px; color: #2c3e50; text-align: right; }
        .issuer-name { font-size: 14px; font-weight: 700; margin-top: 2px; }
        .muted { color: #666; }
        .small { font-size: 9.5pt; }
        .meta-table { margin-top: 8px; }
        .meta-table td { padding: 2px 0; }
        .billto-box {
            margin-top: 12px;
            border: 1px solid #d9d9d9;
            background: #fafafa;
            padding: 8px 10px;
        }
        .ticket-title {
            margin-top: 14px;
            background: #f5f7f9;
            border: 1px solid #d9d9d9;
            border-bottom: none;
            padding: 7px 9px;
            font-weight: 700;
        }
        .line-table th, .line-table td {
            border: 1px solid #d9d9d9;
            padding: 6px 7px;
            font-size: 9.8pt;
        }
        .line-table th {
            background: #f7f7f7;
            text-transform: uppercase;
            font-size: 9pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .grand-total {
            margin-top: 16px;
            border-top: 2px solid #2c3e50;
            padding-top: 8px;
            text-align: right;
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
        }
        .note { margin-top: 5px; text-align: right; font-size: 9pt; color: #777; }
        .remarks { margin-top: 14px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width:60%;">
                <?php if ($issuerLogoPath !== '') { ?>
                    <div><img src="<?php echo tb_pdf_h($issuerLogoPath); ?>" style="max-height:65px;"></div>
                <?php } ?>
                <div class="issuer-name"><?php echo tb_pdf_h(isset($issuer['CompanyName']) ? $issuer['CompanyName'] : 'TechXpert'); ?></div>
                <?php if (!empty($issuer['CompanyAddress'])) { ?>
                    <div class="muted small"><?php echo nl2br(tb_pdf_h($issuer['CompanyAddress'])); ?></div>
                <?php } ?>
                <div class="muted small" style="margin-top:2px;">
                    <?php if (!empty($issuer['GstNumber'])) { ?>GSTIN: <?php echo tb_pdf_h($issuer['GstNumber']); ?>&nbsp;&nbsp;<?php } ?>
                    <?php if (!empty($issuer['PanNumber'])) { ?>PAN: <?php echo tb_pdf_h($issuer['PanNumber']); ?>&nbsp;&nbsp;<?php } ?>
                    <?php if (!empty($issuer['Phone'])) { ?>Phone: <?php echo tb_pdf_h($issuer['Phone']); ?>&nbsp;&nbsp;<?php } ?>
                    <?php if (!empty($issuer['Email'])) { ?>Email: <?php echo tb_pdf_h($issuer['Email']); ?><?php } ?>
                </div>
            </td>
            <td style="width:40%;">
                <div class="title">TAX INVOICE</div>
                <table class="meta-table">
                    <tr><td><strong>Invoice No.</strong></td><td class="text-right"><?php echo tb_pdf_h($invoiceNo); ?></td></tr>
                    <tr><td><strong>Invoice Date</strong></td><td class="text-right"><?php echo tb_pdf_h($invoiceDate); ?></td></tr>
                    <tr><td><strong>Payment Status</strong></td><td class="text-right"><?php echo tb_pdf_h($paymentStatus); ?></td></tr>
                    <tr><td><strong>Tickets</strong></td><td class="text-right"><?php echo (int)$ticketCount; ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="billto-box">
        <strong>Bill To:</strong><br>
        <?php echo tb_pdf_h($billToDisplay); ?>
    </div>

    <?php foreach ($tickets as $ticket) { ?>
        <div class="ticket-title">
            Ticket: <?php echo tb_pdf_h(isset($ticket['TicketID']) ? $ticket['TicketID'] : ''); ?>
            | Type: <?php echo tb_pdf_h(isset($ticket['TicketType']) ? $ticket['TicketType'] : ''); ?>
            | <?php echo tb_pdf_h(isset($ticket['CompanyName']) ? $ticket['CompanyName'] : ''); ?>
            | <?php echo tb_pdf_h(isset($ticket['BranchSite']) ? $ticket['BranchSite'] : ''); ?>
        </div>
        <table class="line-table">
            <thead>
                <tr>
                    <th style="width:6%;" class="text-center">#</th>
                    <th style="width:54%;">Description</th>
                    <th style="width:12%;" class="text-right">Qty</th>
                    <th style="width:14%;" class="text-right">Rate</th>
                    <th style="width:14%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $lineIndex = 0;
                $lines = isset($ticket['line_items']) && is_array($ticket['line_items']) ? $ticket['line_items'] : array();
                foreach ($lines as $line) {
                    $lineIndex++;
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $lineIndex; ?></td>
                        <td><?php echo tb_pdf_h(isset($line['LineItemName']) ? $line['LineItemName'] : ''); ?></td>
                        <td class="text-right"><?php echo number_format((float)(isset($line['BilledQty']) ? $line['BilledQty'] : 0), 2); ?></td>
                        <td class="text-right"><?php echo tb_pdf_amt(isset($line['PerItemPrice']) ? $line['PerItemPrice'] : 0); ?></td>
                        <td class="text-right"><?php echo tb_pdf_amt(isset($line['BilledLineAmount']) ? $line['BilledLineAmount'] : 0); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>Ticket Subtotal</strong></td>
                    <td class="text-right"><strong><?php echo tb_pdf_amt(isset($ticket['TicketBilledAmount']) ? $ticket['TicketBilledAmount'] : 0); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    <?php } ?>

    <div class="grand-total">Grand Total (Excl. GST): <?php echo tb_pdf_amt($totalAmount); ?></div>
    <div class="note">All amounts are excluding GST as per quotation line items.</div>

    <?php if ($remarks !== '') { ?>
        <div class="remarks">
            <strong>Remarks:</strong><br>
            <?php echo nl2br(tb_pdf_h($remarks)); ?>
        </div>
    <?php } ?>
</body>
</html>
<?php
$html = ob_get_clean();

try {
    $mpdf = new \Mpdf\Mpdf(array(
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 12,
        'margin_bottom' => 12
    ));
    $mpdf->SetTitle('Ticket Billing Invoice - ' . $invoiceNo);
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
