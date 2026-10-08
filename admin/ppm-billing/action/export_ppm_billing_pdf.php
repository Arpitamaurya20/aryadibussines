<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php';
include '../../controllers/common_controllers.php';
require_once __DIR__ . '/../../includes/aryadi_pdf_theme.php';

$conn = _connectodb();
setTimeZone();

$start_date    = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date      = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');
$CorporateID   = isset($_POST['CorporateID']) ? intval($_POST['CorporateID']) : -1;
$BranchID      = isset($_POST['BranchID']) ? intval($_POST['BranchID']) : -1;
$BillingStatus = isset($_POST['BillingStatus']) ? $_POST['BillingStatus'] : "";

$billing_obj = new Ppmbilling($conn);
$filters = array(
    'start_date'    => $start_date,
    'end_date'      => $end_date,
    'CorporateID'   => $CorporateID,
    'BranchID'      => $BranchID,
    'BillingStatus' => $BillingStatus
);
$billing_data = $billing_obj->getBillingData($filters);

// Build summary (billed vs pending and quarterly) for PDF header/footer
$total_billed  = 0;
$total_pending = 0;

foreach ($billing_data as $row) {
    $amount = (float)$row['DisplayBilledAmount'];
    if ($row['BillingStatus'] == 'Billed') {
        $total_billed += $amount;
    } else {
        $total_pending += $amount;
    }
}

$company_name = "All Accounts";
if ($CorporateID != -1) {
    $company_details = _getTableDetails($conn, 'company', ' where ID = '.$CorporateID);
    if ($company_details != null) {
        $company_name = $company_details['CompanyName'];
    }
}

$rows_html = '';
$i = 1;
foreach ($billing_data as $row) {
    $status_class = $row['BillingStatus'] == 'Billed' ? 'pill_yes' : 'pill_no';
    $rows_html .= '
    <tr'.($i % 2 == 0 ? ' class="alt"' : '').'>
        <td class="num">'.$i.'</td>
        <td><b>'.htmlspecialchars($row['TicketNumber']).'</b></td>
        <td>'.htmlspecialchars($row['CompanyName']).'<br><span style="color:#64748B; font-size:7.5pt;">'.htmlspecialchars($row['BranchSite'].' ('.$row['BranchCity'].')').'</span></td>
        <td>'.htmlspecialchars($row['EquipmentName']).'<br><span style="color:#64748B; font-size:7.5pt;">'.htmlspecialchars($row['EquipmentLocation']).'</span></td>
        <td>'.htmlspecialchars($row['PPMDate']).'</td>
        <td>'.htmlspecialchars($row['IntervalType']).'</td>
        <td style="text-align:right;">'.number_format($row['AssetContractAmount'],2).'</td>
        <td style="text-align:right;">'.number_format($row['DisplayBilledAmount'],2).'</td>
        <td class="'.$status_class.'">'.htmlspecialchars($row['BillingStatus']).'</td>
    </tr>';
    $i++;
}
if ($i == 1) {
    $rows_html .= '<tr><td colspan="9" style="text-align:center; color:#94A3B8; padding:4mm;">No PPM tickets found for the selected filters.</td></tr>';
}

$period_label = $start_date.' to '.$end_date;
$generated_on = date('Y-m-d H:i');

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PPM Billing</title>
    <style>'.apdf_css().'</style>
</head>
<body>
    '.apdf_header('PPM BILLING', 'DUE & CLAIMED SUMMARY').'

    '.apdf_stats(array(
        array('Account', htmlspecialchars($company_name)),
        array('Period', htmlspecialchars($period_label)),
        array('Claimed (Billed)', number_format($total_billed,2)),
        array('Due (Pending)', number_format($total_pending,2)),
        array('Total', number_format($total_billed + $total_pending,2)),
    )).'

    '.apdf_data_box('PPM Tickets', array(
        array('#', 5),
        array('Ticket', 12),
        array('Corporate / Branch', 20),
        array('Asset', 18),
        array('PPM Date', 10),
        array('Interval', 8),
        array('Contract Amt', 9),
        array('Per Visit / Billed Amt', 10),
        array('Status', 8),
    ), $rows_html, 'Generated On: '.$generated_on).'

    <div class="section keep">
        <table>
            <tr>
                <td style="width:55%; vertical-align:middle; padding:3mm 3.5mm; border-right:0.3mm solid #D6E4F2;">
                    <div class="block_label">Account</div>
                    <div class="block_text"><b>'.htmlspecialchars($company_name).'</b><br><span style="color:#64748B;">Period: '.htmlspecialchars($period_label).'</span></div>
                </td>
                <td style="width:45%; vertical-align:top; padding:0;">
                    <table class="totals">
                        <tr><td class="t_label">Claimed (Billed)</td><td>'.number_format($total_billed,2).'</td></tr>
                        <tr><td class="t_label">Due (Pending)</td><td>'.number_format($total_pending,2).'</td></tr>
                        <tr class="grand"><td>Total</td><td>'.number_format($total_billed + $total_pending,2).'</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>';

$mpdf = apdf_create_mpdf('PPM Billing '.$period_label, 'PPM Billing · '.$period_label, array('format' => 'A4', 'orientation' => 'L'));
$mpdf->WriteHTML($html);
$mpdf->Output('ppm-billing-'.date('YmdHis').'.pdf', 'I');
exit;


