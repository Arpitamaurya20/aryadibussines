<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php';
include '../../controllers/common_controllers.php';

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

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family: sans-serif; font-size:11px;}
        table{width:100%; border-collapse:collapse;}
        th,td{border:1px solid #ccc; padding:4px 6px;}
        th{background:#f4f4f4;}
        .title{font-size:16px; font-weight:bold; margin-bottom:5px;}
        .subtitle{font-size:12px; margin-bottom:10px;}
        .text-right{text-align:right;}
    </style>
    <title>PPM Billing</title>
</head>
<body>
    <div class="title">PPM Billing – Due & Claimed Summary</div>
    <div class="subtitle">
        Account: <?php echo htmlspecialchars($company_name); ?><br>
        Period: <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?><br>
        Generated On: <?php echo date('Y-m-d H:i'); ?><br>
        Claimed (Billed): <?php echo number_format($total_billed,2); ?> |
        Due (Pending): <?php echo number_format($total_pending,2); ?> |
        Total: <?php echo number_format($total_billed + $total_pending,2); ?>
    </div>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Ticket</th>
            <th>Corporate / Branch</th>
            <th>Asset</th>
            <th>PPM Date</th>
            <th>Interval</th>
            <th class="text-right">Contract Amt</th>
            <th class="text-right">Per Visit / Billed Amt</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        foreach ($billing_data as $row) {
            ?>
            <tr>
                <td><?php echo $i; ?></td>
                <td><?php echo htmlspecialchars($row['TicketNumber']); ?></td>
                <td>
                    <?php echo htmlspecialchars($row['CompanyName']); ?><br>
                    <small><?php echo htmlspecialchars($row['BranchSite'].' ('.$row['BranchCity'].')'); ?></small>
                </td>
                <td>
                    <?php echo htmlspecialchars($row['EquipmentName']); ?><br>
                    <small><?php echo htmlspecialchars($row['EquipmentLocation']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($row['PPMDate']); ?></td>
                <td><?php echo htmlspecialchars($row['IntervalType']); ?></td>
                <td class="text-right"><?php echo number_format($row['AssetContractAmount'],2); ?></td>
                <td class="text-right"><?php echo number_format($row['DisplayBilledAmount'],2); ?></td>
                <td><?php echo htmlspecialchars($row['BillingStatus']); ?></td>
            </tr>
            <?php
            $i++;
        }
        if ($i == 1) {
            ?>
            <tr>
                <td colspan="9">No PPM tickets found for the selected filters.</td>
            </tr>
            <?php
        }
        ?>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="7" class="text-right">Claimed (Billed)</th>
            <th class="text-right"><?php echo number_format($total_billed,2); ?></th>
            <th></th>
        </tr>
        <tr>
            <th colspan="7" class="text-right">Due (Pending)</th>
            <th class="text-right"><?php echo number_format($total_pending,2); ?></th>
            <th></th>
        </tr>
        <tr>
            <th colspan="7" class="text-right">Total</th>
            <th class="text-right"><?php echo number_format($total_billed + $total_pending,2); ?></th>
            <th></th>
        </tr>
        </tfoot>
    </table>
</body>
</html>
<?php
$html = ob_get_clean();

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output('ppm-billing-'.date('YmdHis').'.pdf', 'I');
exit;


