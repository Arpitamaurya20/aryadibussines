<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

require_once(__DIR__ . '/../../controllers/common_controllers.php');
setTimeZone();
$conn = _connectodb();

if (!$conn) die('DB Connection Failed');

// ================= CONFIG =================
$MAX_PER_MONTH = 100;

$INTERVAL_MAP = [
    'Q1' => 3,
    'Q2' => 3,
    'Q3' => 3,
    'Q4' => 3,
    'H1' => 6,
    'H2' => 6
];

$startMonth = date('m');
$startYear  = date('Y');

// ================= FETCH ASSETS =================
$sql = "
SELECT *
FROM temp_branch_assets_info
WHERE Interval IN ('Q1','Q2','Q3','Q4','H1','H2')
  AND IsActive = 1
";

$res = mysqli_query($conn, $sql);
$assetsByInterval = [];

while ($row = mysqli_fetch_assoc($res)) {
    $assetsByInterval[$row['Interval']][] = $row;
}

// ================= PROCESS EACH INTERVAL =================
foreach ($assetsByInterval as $interval => $assets) {
    distributeAssets($conn, $assets, $interval, $startMonth, $startYear, $MAX_PER_MONTH, $INTERVAL_MAP);
}

mysqli_close($conn);
echo "PPM Distribution Completed";

// ================= FUNCTIONS =================

function distributeAssets($conn, $assets, $interval, $startMonth, $startYear, $MAX_PER_MONTH, $INTERVAL_MAP) {

    $months = $INTERVAL_MAP[$interval];
    $chunks = array_chunk($assets, $MAX_PER_MONTH);

    for ($i = 0; $i < $months; $i++) {

        if (!isset($chunks[$i])) break;

        $month = date('m', strtotime("+$i month", strtotime("$startYear-$startMonth-01")));
        $year  = date('Y', strtotime("+$i month", strtotime("$startYear-$startMonth-01")));

        $weeklyDates = getWeeklyDates($year, $month);
        $weekIndex = 0;

        foreach ($chunks[$i] as $asset) {

            $ppmDate = $weeklyDates[$weekIndex];
            $weekIndex = ($weekIndex + 1) % count($weeklyDates);

            insertPPMDate($conn, $asset, $ppmDate, $interval);
        }
    }
}

function getWeeklyDates($year, $month) {
    $dates = [];
    $d = new DateTime("$year-$month-01");

    while ($d->format('m') == $month) {
        $dates[] = $d->format('Y-m-d');
        $d->modify('+7 days');
    }
    return $dates;
}

function insertPPMDate($conn, $asset, $ppmDate, $interval) {

    // Duplicate prevention
    $chk = mysqli_query($conn, "
        SELECT ID FROM temp_ppm_dates
        WHERE TempAssetInfoID = {$asset['ID']}
          AND IntervalIdentifier = '$interval'
    ");
    if (mysqli_num_rows($chk) > 0) return;

    // AMC range validation
    if ($ppmDate < $asset['AMCStartDate'] || $ppmDate > $asset['AMCEndDate']) return;

    mysqli_query($conn, "
        INSERT INTO temp_ppm_dates
        (TempAssetInfoID, PPMDate, IntervalIdentifier, IsTicketRaised, IsActive, CreatedDate, CreatedTime)
        VALUES
        ({$asset['ID']}, '$ppmDate', '$interval', 0, 1, CURDATE(), CURTIME())
    ");
}
?>