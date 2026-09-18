<?php
/**
 * AUTO PPM TICKET GENERATION (MONTHLY SAFE)
 * - Supports PPMDate change
 * - No duplicate tickets
 * - Asset-level quarterly distribution
 * - Idempotent & retry-safe
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

/* ---------------- BASE INCLUDES ---------------- */
$base = dirname(__FILE__) . '/../../';
require_once($base . 'controllers/common_controllers.php');
require_once($base . 'branch/controller/branch_controller.php');
require_once($base . 'ppm-ticket/controller/ppm_controller.php');
require_once(dirname(__FILE__) . '/../controller/auto_ppm_controller.php');

/* ---------------- LOG ---------------- */
$log_dir = dirname(__FILE__) . '/../../logs/';
@mkdir($log_dir, 0755, true);
$log_file = $log_dir . 'auto_ppm_monthly_' . date('Ymd') . '.log';

function writeLog($msg) {
    global $log_file;
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

/* ---------------- HELPERS ---------------- */
function quarterIndexFromIdentifier($identifier) {
    return (int) substr($identifier, 1, 1);
}

function getQuarterMonthsByAMC($amcStartDate, $quarterIndex) {
    $dt = new DateTime($amcStartDate);
    $dt->modify('+' . (($quarterIndex - 1) * 3) . ' months');

    $months = [];
    for ($i = 0; $i < 3; $i++) {
        $months[] = [
            'year'  => (int)$dt->format('Y'),
            'month' => (int)$dt->format('m')
        ];
        $dt->modify('+1 month');
    }
    return $months;
}

function randomDateInMonth($year, $month) {
    $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    return sprintf('%04d-%02d-%02d', $year, $month, rand(1, $days));
}

/* ---------------- INIT ---------------- */
writeLog("========== PPM CRON STARTED ==========");

$conn = _connectodb();
if (!$conn) die("DB connection failed");

$currentYear  = 2025;
$currentMonth = 10; // October
writeLog("Cron Month: $currentYear-$currentMonth");

/* ---------------- FETCH MONTH DATA ---------------- */
$sql = "
SELECT tpd.*, tba.AMCStartDate, tba.AMCEndDate
FROM temp_ppm_dates tpd
JOIN temp_branch_assets_info tba ON tba.ID = tpd.TempAssetInfoID
WHERE tpd.IsTicketRaised = 0
  AND tpd.IsProcessing = 0
  AND tpd.IsActive = 1
  AND tba.IsActive = 1
  AND MONTH(tpd.PPMDate) = '$currentMonth'
  AND YEAR(tpd.PPMDate)  = '$currentYear'
ORDER BY tpd.TempAssetInfoID, tpd.IntervalIdentifier, tpd.PPMDate
";

$res = mysqli_query($conn, $sql);
$total = mysqli_num_rows($res);
writeLog("Total records to process: $total");

/* ---------------- PROCESS ---------------- */
$quarterCache = [];
$ticketsCreated = 0;

while ($row = mysqli_fetch_assoc($res)) {

    $TempPPMID  = $row['ID'];
    $IntervalId = $row['IntervalIdentifier'];
    $AMCStart   = $row['AMCStartDate'];

    /* ---- LOCK ROW (VERY IMPORTANT) ---- */
    mysqli_query($conn, "
        UPDATE temp_ppm_dates
        SET IsProcessing = 1
        WHERE ID = '$TempPPMID'
    ");

    $PPMDate = $row['PPMDate'];

    /* ---- QUARTERLY DATE REDISTRIBUTION ---- */
    if (preg_match('/^Q[1-4]$/', $IntervalId)) {

        $cacheKey = $row['TempAssetInfoID'] . '_' . $IntervalId;

        if (!isset($quarterCache[$cacheKey])) {

            $qIndex = quarterIndexFromIdentifier($IntervalId);
            $months = getQuarterMonthsByAMC($AMCStart, $qIndex);

            /* count ONLY this asset + interval */
            $cntSql = "
                SELECT COUNT(*) AS total
                FROM temp_ppm_dates
                WHERE TempAssetInfoID = '{$row['TempAssetInfoID']}'
                  AND IntervalIdentifier = '$IntervalId'
                  AND IsActive = 1
            ";
            $cntRes = mysqli_query($conn, $cntSql);
            $totalQ = (int)mysqli_fetch_assoc($cntRes)['total'];

            $perMonth = ceil($totalQ / 3);
            $bucket = [];

            foreach ($months as $m) {
                $bucket = array_merge($bucket, array_fill(0, $perMonth, $m));
            }

            shuffle($bucket);

            $quarterCache[$cacheKey] = [
                'bucket' => $bucket,
                'index'  => 0
            ];

            writeLog("Quarter bucket created for Asset {$row['TempAssetInfoID']} $IntervalId");
        }

        $meta = &$quarterCache[$cacheKey];
        $ym   = $meta['bucket'][$meta['index']++];

        $PPMDate = randomDateInMonth($ym['year'], $ym['month']);
    }

    /* ---- SAVE UPDATED DATE IMMEDIATELY ---- */
    mysqli_query($conn, "
        UPDATE temp_ppm_dates
        SET PPMDate = '$PPMDate'
        WHERE ID = '$TempPPMID'
    ");

    /* ---- CREATE TICKET ---- */
    $row['PPMDate'] = $PPMDate;
    $resp = GeneratePPMTicket($conn, $row);

    if ($resp['error'] === false) {

        mysqli_query($conn, "
            UPDATE temp_ppm_dates
            SET IsTicketRaised = 1,
                TicketID = '{$resp['ID']}'
            WHERE ID = '$TempPPMID'
        ");

        $ticketsCreated++;
        writeLog("SUCCESS | Ticket {$resp['ID']} | $IntervalId | $PPMDate");

    } else {

        writeLog("FAILED | TempPPMID $TempPPMID | {$resp['message']}");
    }
}

writeLog("TOTAL TICKETS CREATED: $ticketsCreated");
writeLog("========== PPM CRON COMPLETED ==========");
mysqli_close($conn);

/* ---------------- TICKET CREATOR ---------------- */
function GeneratePPMTicket($conn, $ppmRow) {

    $asset = _getTableDetails(
        $conn,
        'temp_branch_assets_info',
        " WHERE ID = {$ppmRow['TempAssetInfoID']}"
    );

    if (!$asset) {
        return ['error' => true, 'message' => 'Asset not found'];
    }

    $branch = GetBranchDetailsbyID($conn, $asset['BranchID']);
    if (!$branch) {
        return ['error' => true, 'message' => 'Branch not found'];
    }

    $ticketData = [
        'CorporateID'   => $asset['CorporateID'],
        'BranchID'      => $asset['BranchID'],
        'BranchAssetID' => $asset['BranchAssetID'],
        'ppmdate'       => [$ppmRow['PPMDate']],
        'CreatedBy'     => 'System Auto PPM'
    ];

    return CreatePPMTicket($conn, $ticketData, $branch);
}
