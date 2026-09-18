<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();
require_once('../includes/autoloader.inc.php');
include("../controllers/common_controllers.php");
include('../corporate-tickets/controller/corporate_tickets_controller.php');
setTimeZone();

// PHPMailer files & mail templates / config
require '../mail/PHPMailer-master/src/Exception.php';
require '../mail/PHPMailer-master/src/PHPMailer.php';
require '../mail/PHPMailer-master/src/SMTP.php';
require '../mail/include/mail-config-techxpertgroup.php'; // defines $mail_header, $mail_footer
require '../mail/include/common-mail-design.php';

use PHPMailer\PHPMailer\PHPMailer;

$core = new Core();
$dbh  = new Dbh();
$conn = $dbh->_connectodb();

echo "== Process Ticket Status Queue (pending, created today) ==\n";

// ---------- Helper functions ----------
function csv_to_sql_in($csv, $conn) {
    $arr = array_filter(array_map('trim', explode(',', $csv)));
    if (count($arr) == 0) return "''";
    $escaped = array_map(function($v) use ($conn){
        return "'" . $conn->real_escape_string($v) . "'";
    }, $arr);
    return implode(',', $escaped);
}

function status_to_alias($s) {
    return preg_replace('/[^A-Za-z0-9]/', '_', $s);
}

// colors
$color_amc = '#007bff';   // sky blue
$color_rm  = '#ff8c00';   // red / orange

// Normalization CASE for services
$normal_service_case = '(
    CASE 
        WHEN Type = "R&M" THEN
            CASE 
                WHEN LOWER(TRIM(REPLACE(Service, "–","-"))) LIKE "%facilities - furniture%" THEN "Carpentry"
                WHEN LOWER(TRIM(REPLACE(Service, "–","-"))) LIKE "%facilities - lockers%" THEN "Carpentry"
                WHEN LOWER(TRIM(REPLACE(Service, "–","-"))) LIKE "%facilities - carpentry%" THEN "Carpentry"
                WHEN LOWER(TRIM(REPLACE(Service, "–","-"))) LIKE "%facilities - digital signage%" THEN "Electrical"
                WHEN LOWER(TRIM(REPLACE(Service, "–","-"))) LIKE "%facilities - gsb%" THEN "Electrical"
                WHEN LOWER(TRIM(Service)) LIKE "%carpentry%" THEN "Carpentry"
                WHEN LOWER(TRIM(Service)) LIKE "%electrical%" THEN "Electrical"
                WHEN LOWER(TRIM(Service)) LIKE "%plumb%" THEN "Plumbing"
                WHEN LOWER(TRIM(Service)) LIKE "%hvac%" THEN "HVAC"
                WHEN LOWER(TRIM(Service)) LIKE "%housekeep%" THEN "Housekeeping"
                WHEN LOWER(TRIM(Service)) LIKE "%network%" THEN "Networking"
                WHEN LOWER(TRIM(Service)) LIKE "%cctv%" THEN "CCTV"
                WHEN LOWER(TRIM(Service)) LIKE "%civil%" THEN "Civil"
                WHEN LOWER(TRIM(Service)) LIKE "%clean%" THEN "Cleaning"
                WHEN LOWER(TRIM(Service)) LIKE "%pest%" THEN "Pest Control"
                ELSE TRIM(Service)
            END
        ELSE TRIM(Service)
    END
)';

// fetch all pending queue items created today
$q = "SELECT id, setting_id FROM ticket_status_queue WHERE status = 'pending' AND DATE(created_at) = CURDATE() ORDER BY id ASC";
$qResult = $conn->query($q);

if (!$qResult) { echo "DB error while fetching queue: " . $conn->error . "\n"; exit; }
if ($qResult->num_rows == 0) { echo "No pending queue items for today.\n"; exit; }

while ($queueItem = $qResult->fetch_assoc()) {

    $queueID   = intval($queueItem['id']);
    $settingID = intval($queueItem['setting_id']);

    echo "Processing queue ID: $queueID (setting: $settingID)\n";

    // load setting
    $settingRes = $conn->query("SELECT * FROM ticket_status_setting WHERE ID = $settingID LIMIT 1");
    if (!$settingRes || $settingRes->num_rows == 0) {
        $err = "Setting not found";
        $conn->query("UPDATE ticket_status_queue SET status='failed', response='".$conn->real_escape_string($err)."' WHERE id = $queueID");
        echo " -> Failed: $err\n";
        continue;
    }
    $setting = $settingRes->fetch_assoc();

    $CorporateID  = intval($setting['CorporateID']);
    $State        = $conn->real_escape_string($setting['State']);
    $City         = $conn->real_escape_string($setting['City']);
    $BranchID     = intval($setting['BranchID']);
    $StatusFilter = $setting['StatusFilter'];
    $TypeFilter   = $setting['TypeFilter'];
    $DateFrom     = $setting['DateFrom'];
    $DateTo       = date("Y-m-d"); // today
    $EmailTo      = $setting['EmailTo'];
    $EmailCC      = $setting['EmailCC'];

    $StatusIN = csv_to_sql_in($StatusFilter, $conn);
    $TypeIN   = csv_to_sql_in($TypeFilter, $conn);
    $statusList = array_filter(array_map('trim', explode(',', $StatusFilter)));

    $CompanyName = "Unknown Company";
    $pst = $conn->prepare("SELECT CompanyName FROM company WHERE ID = ? LIMIT 1");
    if ($pst) {
        $pst->bind_param("i", $CorporateID);
        $pst->execute();
        $res = $pst->get_result();
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $CompanyName = $r['CompanyName'];
        }
        $pst->close();
    }

    // -------------------------------
    // Build STATUS-WISE report
    // -------------------------------
    $dynamicCols = "";
    $serviceDynamicCols = "";
    $totalDynamicRaw = "";
    foreach ($statusList as $st) {
        $safe = $conn->real_escape_string($st);
        $alias = status_to_alias($st);
        $dynamicCols .= "SUM(CASE WHEN Status='{$safe}' THEN 1 ELSE 0 END) AS {$alias},";
        $serviceDynamicCols .= "SUM(CASE WHEN Status='{$safe}' THEN 1 ELSE 0 END) AS {$alias},";
        $totalDynamicRaw .= "SUM(CASE WHEN Status='{$safe}' THEN 1 ELSE 0 END) + ";
    }
    $totalDynamic = empty($totalDynamicRaw) ? "0 AS TotalCount" : rtrim($totalDynamicRaw, "+ ") . " AS TotalCount";

    $typeFilterClause = ($TypeFilter == "") ? "" : "AND Type IN ($TypeIN)";
    $safeDateFrom = $conn->real_escape_string($DateFrom);
    $safeDateTo   = $conn->real_escape_string($DateTo);

    // SQL for status report with merged services
    $sql = "
    (
        SELECT 
            Type AS RowLabel,
            $dynamicCols
            $totalDynamic,
            0 AS SortLevel,
            Type AS TypeOrder,
            Type AS _Type,
            '' AS _Service
        FROM corporate_tickets
        WHERE CorporateID = $CorporateID
          AND DATE(STR_TO_DATE(LEFT(CreatedDate,10),'%Y-%m-%d')) BETWEEN '{$safeDateFrom}' AND '{$safeDateTo}'
          $typeFilterClause
        GROUP BY Type
    )
    UNION ALL
    (
        SELECT 
            CONCAT('   ', UPPER(TRIM(NormalService))) AS RowLabel,
            $serviceDynamicCols
            $totalDynamic,
            1 AS SortLevel,
            Type AS TypeOrder,
            Type AS _Type,
            NormalService AS _Service
        FROM (
            SELECT 
                t.Type, 
                t.Service,
                CASE 
                    WHEN t.Type='AMC' THEN (
                        SELECT IFNULL(ma.CategoriesName, t.Service)
                        FROM branch_assets ba
                        LEFT JOIN manage_categories ma ON ma.ID = ba.Category
                        WHERE ba.ID = t.BranchAssetID
                        LIMIT 1
                    )
                    ELSE {$normal_service_case}
                END AS NormalService,
                t.Status
            FROM corporate_tickets t
            WHERE t.CorporateID = $CorporateID
              AND DATE(STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) BETWEEN '{$safeDateFrom}' AND '{$safeDateTo}'
              $typeFilterClause
        ) AS tsub
        GROUP BY Type, NormalService
    )
    ORDER BY TypeOrder, SortLevel, RowLabel
    ";

    $result = $conn->query($sql);
    if (!$result) {
        $err = "Report SQL error: " . $conn->error;
        $conn->query("UPDATE ticket_status_queue SET status='failed', response='".$conn->real_escape_string($err)."' WHERE id = $queueID");
        echo " -> Failed: $err\n";
        continue;
    }

    // -------------------------------
    // Build HTML table (Status-Wise)
    // -------------------------------
    $table = "<table style='width:100%; border-collapse:collapse; font-size:12px; font-family:\"Poppins\", sans-serif; border:1px solid #ccc;'>
        <thead>
            <tr style='background:#1a73e8; color:white; text-align:center; font-weight:600;'>
                <th style='padding:8px; border:1px solid #ddd; text-align:left;'>Row Label</th>";
    foreach ($statusList as $st) {
        $table .= "<th style='padding:8px; border:1px solid #ddd; text-align:center;'>".htmlspecialchars($st)."</th>";
    }
    $table .= "<th style='padding:8px; border:1px solid #ddd; text-align:center;'>Total</th></tr></thead><tbody>";

    $z = 0;
    while ($row = $result->fetch_assoc()) {
        $bg = ($z % 2 == 0) ? "#f9f9f9" : "#ffffff";
        $z++;
        $typeVal = isset($row['_Type']) ? $row['_Type'] : '';
        $serviceVal = isset($row['_Service']) ? $row['_Service'] : '';
        if (trim($serviceVal) !== '') {
            $color = '#000';
            if (strtoupper(trim($typeVal)) === 'AMC') $color = $color_amc;
            elseif (strtoupper(trim($typeVal)) === 'R&M') $color = $color_rm;
            $displayLabel = "&nbsp;&nbsp;&nbsp;<span style='color:{$color}; font-weight:600;'>".htmlspecialchars($serviceVal)."</span>";
        } else {
            $color = '#000';
            if (strtoupper(trim($typeVal)) === 'AMC') $color = $color_amc;
            elseif (strtoupper(trim($typeVal)) === 'R&M') $color = $color_rm;
            $displayLabel = "<span style='color:{$color}; font-weight:700;'>".htmlspecialchars($row['RowLabel'])."</span>";
        }

        $table .= "<tr style='background:$bg;'><td style='padding:6px 10px; border:1px solid #ddd;'>$displayLabel</td>";
        foreach ($statusList as $st) {
            $alias = status_to_alias($st);
            $table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>".intval($row[$alias])."</td>";
        }
        $table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center; font-weight:700;'>".intval($row['TotalCount'])."</td></tr>";
    }

    // -------------------
// Compute GRAND TOTAL (AMC + R&M + Projects + Supply ONLY, SERVICE ROWS ONLY)
// -------------------
$allowedTypes = ['AMC','R&M','PROJECTS','SUPPLY'];

$grandTotals = [];
foreach ($statusList as $st) {
    $grandTotals[status_to_alias($st)] = 0;
}
$grandTotals['TotalCount'] = 0;

// Reset pointer
$result->data_seek(0);

while ($row = $result->fetch_assoc()) {

    $typeVal    = strtoupper(trim($row['_Type'] ?? ''));
    $serviceVal = trim((string)($row['_Service'] ?? ''));
    $rowLabel   = trim((string)($row['RowLabel'] ?? ''));

    // ✅ Only count Type-level row, ignore service-level rows, exclude blank rows
    if (in_array($typeVal, $allowedTypes) && $serviceVal === '' && $rowLabel !== '') {
        foreach ($statusList as $st) {
            $alias = status_to_alias($st);
            $grandTotals[$alias] += intval($row[$alias]);
        }
        $grandTotals['TotalCount'] += intval($row['TotalCount']);
    }
}



// Append GRAND TOTAL row
$table .= "<tr style='background:#e6f2ff; font-weight:700;'>
    <td style='padding:6px 10px; border:1px solid #ddd;'>GRAND TOTAL</td>";

foreach ($statusList as $st) {
    $alias = status_to_alias($st);
    $table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>{$grandTotals[$alias]}</td>";
}

$table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>{$grandTotals['TotalCount']}</td></tr>";


    $table .= "</tbody></table>";

    // -------------------------------
    // Build Open-Ticket TAT table (professional style)
    // -------------------------------
    $tat_type_filter_clause = ($TypeFilter == "") ? "" : "AND Type IN ($TypeIN)";
    $tat_normal_service_case = "(
        CASE
            WHEN Type = 'AMC' THEN (
                SELECT CONCAT(IFNULL(ma.CategoriesName, ''), ' - ', IFNULL(ba.EquipmentName, ''))
                FROM branch_assets ba
                LEFT JOIN manage_categories ma ON ma.ID = ba.Category
                WHERE ba.ID = t.BranchAssetID
                LIMIT 1
            )
            WHEN LOWER(TRIM(REPLACE(Service, '–','-'))) LIKE '%facilities - furniture%' THEN 'Carpentry'
            WHEN LOWER(TRIM(REPLACE(Service, '–','-'))) LIKE '%facilities - lockers%' THEN 'Carpentry'
            WHEN LOWER(TRIM(REPLACE(Service, '–','-'))) LIKE '%facilities - carpentry%' THEN 'Carpentry'
            WHEN LOWER(TRIM(REPLACE(Service, '–','-'))) LIKE '%facilities - digital signage%' THEN 'Electrical'
            WHEN LOWER(TRIM(REPLACE(Service, '–','-'))) LIKE '%facilities - gsb%' THEN 'Electrical'
            WHEN LOWER(TRIM(Service)) LIKE '%carpentry%' THEN 'Carpentry'
            WHEN LOWER(TRIM(Service)) LIKE '%electrical%' THEN 'Electrical'
            WHEN LOWER(TRIM(Service)) LIKE '%plumb%' THEN 'Plumbing'
            WHEN LOWER(TRIM(Service)) LIKE '%hvac%' THEN 'HVAC'
            WHEN LOWER(TRIM(Service)) LIKE '%housekeep%' THEN 'Housekeeping'
            WHEN LOWER(TRIM(Service)) LIKE '%network%' THEN 'Networking'
            WHEN LOWER(TRIM(Service)) LIKE '%cctv%' THEN 'CCTV'
            WHEN LOWER(TRIM(Service)) LIKE '%civil%' THEN 'Civil'
            WHEN LOWER(TRIM(Service)) LIKE '%clean%' THEN 'Cleaning'
            WHEN LOWER(TRIM(Service)) LIKE '%pest%' THEN 'Pest Control'
            ELSE TRIM(Service)
        END
    )";

    $tat_sql = "
    (
        SELECT 
            Type AS RowLabel,
            SUM(CASE WHEN TAT_Bucket = '0-3 Days' THEN 1 ELSE 0 END) AS 0_3,
            SUM(CASE WHEN TAT_Bucket = '3-7 Days' THEN 1 ELSE 0 END) AS 3_7,
            SUM(CASE WHEN TAT_Bucket = '7-15 Days' THEN 1 ELSE 0 END) AS 7_15,
            SUM(CASE WHEN TAT_Bucket = '15-30 Days' THEN 1 ELSE 0 END) AS 15_30,
            SUM(CASE WHEN TAT_Bucket = '>30' THEN 1 ELSE 0 END) AS gt_30,
            COUNT(*) AS Total,
            0 AS SortLevel,
            Type AS TypeOrder,
            Type AS _Type,
            '' AS _Service
        FROM (
            SELECT t.Type, t.Service, {$tat_normal_service_case} AS NormalService,
                DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) AS TAT,
                CASE
                    WHEN DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) <= 3 THEN '0-3 Days'
                    WHEN DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) BETWEEN 4 AND 7 THEN '3-7 Days'
                    WHEN DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) BETWEEN 8 AND 15 THEN '7-15 Days'
                    WHEN DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) BETWEEN 16 AND 30 THEN '15-30 Days'
                    ELSE '>30'
                END AS TAT_Bucket
            FROM corporate_tickets t
            WHERE t.CorporateID = $CorporateID
              AND t.Status IN ('Raised','Assigned')
              $tat_type_filter_clause
        ) AS sub
        GROUP BY Type
    )
    UNION ALL
    (
        SELECT 
            CONCAT('   ', UPPER(TRIM(NormalService))) AS RowLabel,
            SUM(CASE WHEN TAT_Bucket = '0-3 Days' THEN 1 ELSE 0 END) AS 0_3,
            SUM(CASE WHEN TAT_Bucket = '3-7 Days' THEN 1 ELSE 0 END) AS 3_7,
            SUM(CASE WHEN TAT_Bucket = '7-15 Days' THEN 1 ELSE 0 END) AS 7_15,
            SUM(CASE WHEN TAT_Bucket = '15-30 Days' THEN 1 ELSE 0 END) AS 15_30,
            SUM(CASE WHEN TAT_Bucket = '>30' THEN 1 ELSE 0 END) AS gt_30,
            COUNT(*) AS Total,
            1 AS SortLevel,
            Type AS TypeOrder,
            Type AS _Type,
            NormalService AS _Service
        FROM (
            SELECT t.Type, t.Service, {$tat_normal_service_case} AS NormalService,
                DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) AS TAT,
                CASE
                    WHEN DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) <= 3 THEN '0-3 Days'
                    WHEN DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) BETWEEN 4 AND 7 THEN '3-7 Days'
                    WHEN DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) BETWEEN 8 AND 15 THEN '7-15 Days'
                    WHEN DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(t.CreatedDate,10),'%Y-%m-%d')) BETWEEN 16 AND 30 THEN '15-30 Days'
                    ELSE '>30'
                END AS TAT_Bucket
            FROM corporate_tickets t
            WHERE t.CorporateID = $CorporateID
              AND t.Status IN ('Raised','Assigned')
              $tat_type_filter_clause
        ) AS sub
        GROUP BY Type, NormalService
    )
    ORDER BY TypeOrder, SortLevel, RowLabel
    ";

    $tat_result = $conn->query($tat_sql);
    if (!$tat_result) {
        echo "TAT SQL error: ".$conn->error."\n";
        continue;
    }

    // Build TAT HTML table
    $tat_table = "<table style='width:100%; border-collapse:collapse; font-size:12px; font-family:\"Poppins\", sans-serif; border:1px solid #ccc;'>
        <thead>
            <tr style='background:#28a745; color:white; text-align:center; font-weight:600;'>
                <th style='padding:8px; border:1px solid #ddd; text-align:left;'>Row Label</th>
                <th style='padding:8px; border:1px solid #ddd;'>0-3 Days</th>
                <th style='padding:8px; border:1px solid #ddd;'>3-7 Days</th>
                <th style='padding:8px; border:1px solid #ddd;'>7-15 Days</th>
                <th style='padding:8px; border:1px solid #ddd;'>15-30 Days</th>
                <th style='padding:8px; border:1px solid #ddd;'>>30 Days</th>
                <th style='padding:8px; border:1px solid #ddd;'>Total</th>
            </tr>
        </thead>
        <tbody>";

    $z = 0;
    while ($row = $tat_result->fetch_assoc()) {
        $bg = ($z % 2 == 0) ? "#f9f9f9" : "#ffffff";
        $z++;
        $typeVal = isset($row['_Type']) ? $row['_Type'] : '';
        $serviceVal = isset($row['_Service']) ? $row['_Service'] : '';
        if (trim($serviceVal) !== '') {
            $color = '#000';
            if (strtoupper(trim($typeVal)) === 'AMC') $color = $color_amc;
            elseif (strtoupper(trim($typeVal)) === 'R&M') $color = $color_rm;
            $displayLabel = "&nbsp;&nbsp;&nbsp;<span style='color:{$color}; font-weight:600;'>".htmlspecialchars($serviceVal)."</span>";
        } else {
            $color = '#000';
            if (strtoupper(trim($typeVal)) === 'AMC') $color = $color_amc;
            elseif (strtoupper(trim($typeVal)) === 'R&M') $color = $color_rm;
            $displayLabel = "<span style='color:{$color}; font-weight:700;'>".htmlspecialchars($row['RowLabel'])."</span>";
        }

        $tat_table .= "<tr style='background:$bg;'><td style='padding:6px 10px; border:1px solid #ddd;'>$displayLabel</td>";
        $tat_table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>".intval($row['0_3'])."</td>";
        $tat_table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>".intval($row['3_7'])."</td>";
        $tat_table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>".intval($row['7_15'])."</td>";
        $tat_table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>".intval($row['15_30'])."</td>";
        $tat_table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>".intval($row['gt_30'])."</td>";
        $tat_table .= "<td style='padding:6px; border:1px solid #ddd; text-align:center; font-weight:700;'>".intval($row['Total'])."</td></tr>";
    }

            // -------------------
// Compute GRAND TOTAL for TAT (AMC + R&M services ONLY, ignore type-level row)
$tatAllowedTypes = ['AMC','R&M','PROJECTS','SUPPLY'];
$tatGrandTotals = ['0_3'=>0,'3_7'=>0,'7_15'=>0,'15_30'=>0,'gt_30'=>0,'Total'=>0];

// Reset pointer
$tat_result->data_seek(0);

while ($tr = $tat_result->fetch_assoc()) {
    $typeVal    = strtoupper(trim($tr['_Type'] ?? ''));
    $serviceVal = trim((string)($tr['_Service'] ?? ''));
    $rowLabel   = trim((string)($tr['RowLabel'] ?? ''));

    // ✅ Only count Type-level row, exclude blank rows
    if (in_array($typeVal, $tatAllowedTypes) && $serviceVal === '' && $rowLabel !== '') {
        foreach (['0_3','3_7','7_15','15_30','gt_30','Total'] as $col) {
            $tatGrandTotals[$col] += intval($tr[$col]);
        }
    }
}


// Append grand total row
$tat_table .= "<tr style='background:#e6f2ff; font-weight:700;'>
    <td style='padding:8px 12px; border:1px solid #ddd;'>GRAND TOTAL</td>";
foreach (['0_3','3_7','7_15','15_30','gt_30','Total'] as $col) {
    $tat_table .= "<td style='padding:8px; border:1px solid #ddd; text-align:center;'>{$tatGrandTotals[$col]}</td>";
}
$tat_table .= "</tr>";

    $tat_table .= "</tbody></table>";

    // -------------------------------
// You can similarly update Open-Ticket TAT SQL to include AMC/branch_assets categories
// -------------------------------


// -------------------
// Build CSV Attachment (Same as Status-wise table)
// -------------------
// -------------------
// Build CSV Attachment (Status + Open TAT)
// -------------------
$csvRows = [];

/* =========================
   STATUS WISE REPORT
========================= */
$csvRows[] = ['STATUS WISE TICKET REPORT'];
$csvRows[] = [];

$header = ['Row Label'];
foreach ($statusList as $st) {
    $header[] = $st;
}
$header[] = 'Total';
$csvRows[] = $header;

// Reset pointer
$result->data_seek(0);

while ($row = $result->fetch_assoc()) {

    $label = trim($row['_Service']) !== ''
        ? $row['_Service']
        : $row['RowLabel'];

    $line = [$label];

    foreach ($statusList as $st) {
        $alias = status_to_alias($st);
        $line[] = intval($row[$alias]);
    }

    $line[] = intval($row['TotalCount']);
    $csvRows[] = $line;
}

// GRAND TOTAL
$gt = ['GRAND TOTAL'];
foreach ($statusList as $st) {
    $gt[] = $grandTotals[status_to_alias($st)];
}
$gt[] = $grandTotals['TotalCount'];
$csvRows[] = $gt;


/* =========================
   OPEN TICKET TAT REPORT
========================= */
$csvRows[] = [];
$csvRows[] = ['OPEN TICKET TAT REPORT'];
$csvRows[] = [];

$csvRows[] = ['Row Label','0-3 Days','3-7 Days','7-15 Days','15-30 Days','>30 Days','Total'];

// Reset TAT pointer
$tat_result->data_seek(0);

while ($row = $tat_result->fetch_assoc()) {

    $label = trim($row['_Service']) !== ''
        ? $row['_Service']
        : $row['RowLabel'];

    $csvRows[] = [
        $label,
        intval($row['0_3']),
        intval($row['3_7']),
        intval($row['7_15']),
        intval($row['15_30']),
        intval($row['gt_30']),
        intval($row['Total'])
    ];
}

// TAT GRAND TOTAL
$csvRows[] = [
    'GRAND TOTAL',
    $tatGrandTotals['0_3'],
    $tatGrandTotals['3_7'],
    $tatGrandTotals['7_15'],
    $tatGrandTotals['15_30'],
    $tatGrandTotals['gt_30'],
    $tatGrandTotals['Total']
];

/* =========================
   Convert to CSV string
========================= */
$csvContent = '';
foreach ($csvRows as $row) {
    $cleanRow = array_map(function ($value) {
        if ($value === null) return '';
        $value = (string)$value;
        $value = str_replace(["\r", "\n"], ' ', $value);
        $value = str_replace('"', '""', $value);
        return $value;
    }, $row);

    $csvContent .= '"' . implode('","', $cleanRow) . '"' . "\n";
}

// Save CSV
$csvFilePath = sys_get_temp_dir() . "/Daily_Ticket_Report_{$CorporateID}_" . date('Ymd') . ".csv";
file_put_contents($csvFilePath, $csvContent);



// Email sending logic here remains same as your previous code
     $template_html = "
    <h2 style='margin:0 0 10px 0; font-size:20px; font-weight:600;'>Daily Ticket Status Report – {{COMPANY}} -{{STATE}} </h2>
    <p style='margin:0 0 15px 0; font-size:14px; color:#444;'>Please find below the daily ticket status summary generated for your organization.</p>
    <p style='font-size:14px; margin:0 0 20px 0;'><b>Date Range:</b> {{DATEFROM}} to {{DATETO}}</p>
    <h3 style='font-family:Arial, sans-serif; color:#1a73e8; margin-top:10px;'>Status-Wise Ticket Report</h3>
    {{TABLE}}
    <h3 style='font-family:Arial, sans-serif; color:#1a73e8; margin-top:10px;'>Open Ticket TAT Report</h3>
    {{TAT_TABLE}}
    <p style='margin-top:20px; font-size:14px; color:#555;'>This report is system-generated. For any clarification or support, feel free to contact our helpdesk team.</p>
    ";

    $emailBody = str_replace(
        ["{{COMPANY}}","{{STATE}}","{{DATEFROM}}","{{DATETO}}","{{TABLE}}","{{TAT_TABLE}}"],
        [htmlspecialchars($CompanyName),htmlspecialchars($State), htmlspecialchars($DateFrom), htmlspecialchars($DateTo), $table, $tat_table],
        $template_html
    );

    $mail = new PHPMailer(true);
    include('../mail/include/mail-config-techxpertgroup.php');

    $mail->isHTML(true);
    $mail->Subject = "Daily Ticket Status Report – " . $CompanyName. " " .$State;
    $mail->Body    = $mail_header . $emailBody . $mail_footer;
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    $addedAny = false;
    foreach (array_filter(array_map('trim', explode(',', $EmailTo))) as $toEmail) {
        if (filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addAddress($toEmail);
            $addedAny = true;
        }
    }

    if (!$addedAny) {
        $err = "No valid recipient (To) addresses: " . htmlspecialchars($EmailTo);
        $conn->query("UPDATE ticket_status_queue SET status='failed', response='".$conn->real_escape_string($err)."' WHERE id = $queueID");
        echo " -> Failed: $err\n";
        continue;
    }

    foreach (array_filter(array_map('trim', explode(',', $EmailCC))) as $ccEmail) {
        if (filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) $mail->addCC($ccEmail);
    }
   

    try {
        // Attach CSV
if (file_exists($csvFilePath)) {
    $mail->addAttachment($csvFilePath, basename($csvFilePath), 'base64', 'text/csv');
}

        $mail->send();
        $conn->query("UPDATE ticket_status_queue SET status='sent', sent_at = NOW(), response='Mail sent successfully' WHERE id = $queueID");
        echo " -> Sent (queue ID $queueID)\n";
    } catch (Exception $e) {
        $errMsg = $conn->real_escape_string($mail->ErrorInfo);
        $conn->query("UPDATE ticket_status_queue SET status='failed', response='$errMsg' WHERE id = $queueID");
        echo " -> Failed to send (queue ID $queueID): $errMsg\n";
    }



echo "Queue ID $queueID processed successfully.\n";

} // end while queue



echo "All queues processed.\n";
?>
