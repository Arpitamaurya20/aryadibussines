<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();
require_once('../../includes/autoloader.inc.php');
include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
setTimeZone();

// PHPMailer
require '../../mail/PHPMailer-master/src/Exception.php';
require '../../mail/PHPMailer-master/src/PHPMailer.php';
require '../../mail/PHPMailer-master/src/SMTP.php';
require '../../mail/include/mail-config-techxpertgroup.php';
require '../../mail/include/common-mail-design.php';

use PHPMailer\PHPMailer\PHPMailer;

$core = new Core();
$dbh  = new Dbh();
$conn = $dbh->_connectodb();

/*****************************************************
  LOAD ACTIVE SETTINGS
*****************************************************/
$sqlSetting = "SELECT * FROM ticket_status_setting WHERE IsActive = 1 ORDER BY ID ASC";
$settings = $conn->query($sqlSetting);

if (!$settings || $settings->num_rows == 0) {
    die("No active settings found.");
}

/*****************************************************
  HELPER FUNCTIONS
*****************************************************/
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

/*****************************************************
  EMAIL TEMPLATE
*****************************************************/
$template_html = "
<h2 style='margin:0 0 10px 0; font-size:20px; font-weight:600;'>
    Daily Ticket Status Report – {{COMPANY}}
</h2>

<p style='margin:0 0 15px 0; font-size:14px; color:#444;'>
    Please find below the daily ticket status summary generated for your organization.
</p>

<p style='font-size:14px; margin:0 0 20px 0;'>
    <b>Date Range:</b> {{DATEFROM}} to {{DATETO}}
</p>

{{TABLE}}

<p style='margin-top:20px; font-size:14px; color:#555;'>
    This report is system-generated. For any clarification or support, feel free to contact our helpdesk team.
</p>
";

while ($setting = $settings->fetch_assoc()) {

    /*****************************************************
      EXTRACT SETTING VALUES
    *****************************************************/
    $CorporateID  = intval($setting['CorporateID']);
    $StatusFilter = trim($setting['StatusFilter']);
    $TypeFilter   = trim($setting['TypeFilter']);
    $DateFrom     = trim($setting['DateFrom']);
    $DateTo       = date("Y-m-d");

    $EmailTo      = trim($setting['EmailTo']);
    $EmailCC      = trim($setting['EmailCC']);

    $StatusIN = csv_to_sql_in($StatusFilter, $conn);
    $TypeIN   = csv_to_sql_in($TypeFilter, $conn);

    $statusList = array_filter(array_map('trim', explode(',', $StatusFilter)));


    /*****************************************************
      FETCH COMPANY DETAILS
    *****************************************************/
    $CompanyName = "Unknown Company";
    $CompanyEmail = "";

    $pst = $conn->prepare("SELECT CompanyName, CompanyEmail FROM company WHERE ID = ? LIMIT 1");
    if ($pst) {
        $pst->bind_param("i", $CorporateID);
        $pst->execute();
        $res = $pst->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $CompanyName  = $row['CompanyName'];
            $CompanyEmail = $row['CompanyEmail'];
        }
        $pst->close();
    }

    /*****************************************************
      BUILD DYNAMIC SQL COLUMNS
    *****************************************************/
    $dynamicCols = "";
    $serviceDynamicCols = "";
    $totalDynamicRaw = "";

    foreach ($statusList as $st) {
        $safe = $conn->real_escape_string($st);
        $alias = status_to_alias($st);

        $dynamicCols .= "SUM(CASE WHEN Status='$safe' THEN 1 ELSE 0 END) AS `$alias`,";
        $serviceDynamicCols .= "SUM(CASE WHEN Status='$safe' THEN 1 ELSE 0 END) AS `$alias`,";
        $totalDynamicRaw .= "SUM(CASE WHEN Status='$safe' THEN 1 ELSE 0 END) + ";
    }

    $totalDynamic = rtrim($totalDynamicRaw, "+ ") . " AS TotalCount";

    $typeFilterClause = ($TypeFilter == "") ? "" : "AND Type IN ($TypeIN)";

    /*****************************************************
      FINAL REPORT SQL
    *****************************************************/
    $sql = "
    (
        SELECT 
            Type AS RowLabel,
            $dynamicCols
            $totalDynamic,
            0 AS SortLevel,
            Type AS TypeOrder
        FROM corporate_tickets
        WHERE CorporateID = $CorporateID
          AND DATE(CreatedDate) BETWEEN '$DateFrom' AND '$DateTo'
          $typeFilterClause
        GROUP BY Type
    )
    UNION ALL
    (
        SELECT 
            CONCAT('   ', UPPER(TRIM(Service))) AS RowLabel,
            $serviceDynamicCols
            $totalDynamic,
            1 AS SortLevel,
            Type AS TypeOrder
        FROM corporate_tickets
        WHERE CorporateID = $CorporateID
          AND DATE(CreatedDate) BETWEEN '$DateFrom' AND '$DateTo'
          $typeFilterClause
        GROUP BY Type, CONCAT('   ', UPPER(TRIM(Service)))
    )
    ORDER BY TypeOrder, SortLevel, RowLabel";

    $result = $conn->query($sql);


    /*****************************************************
      MODERN TABLE (REPLACEMENT)
    *****************************************************/
    $table = "
<table style='width:100%; border-collapse:collapse; font-size:14px; font-family:Arial, sans-serif; border:1px solid #ccc;'>

    <thead>
        <tr style='background:#1a73e8; color:white; text-align:center;'>
            <th style='padding:10px; border:1px solid #ddd; text-align:left;'>Row Label</th>";

    foreach ($statusList as $st) {
        $table .= "
            <th style='padding:10px; border:1px solid #ddd; text-align:center;'>$st</th>";
    }

    $table .= "
            <th style='padding:10px; border:1px solid #ddd; text-align:center;'>Total</th>
        </tr>
    </thead>

    <tbody>
";

    $z = 0;
    while ($row = $result->fetch_assoc()) {
        $bg = ($z % 2 == 0) ? "#f9f9f9" : "#ffffff";
        $z++;

        $table .= "
        <tr style='background:$bg;'>
            <td style='padding:8px 12px; border:1px solid #ddd; font-weight:600;'>{$row['RowLabel']}</td>";

        foreach ($statusList as $st) {
            $alias = status_to_alias($st);
            $val = $row[$alias] ?? 0;

            $table .= "
            <td style='padding:8px; border:1px solid #ddd; text-align:center;'>$val</td>";
        }

        $table .= "
            <td style='padding:8px; border:1px solid #ddd; text-align:center; font-weight:700;'>{$row['TotalCount']}</td>
        </tr>";
    }

    $table .= "
    </tbody>
</table>
";


    /*****************************************************
      FINAL MAIL BODY
    *****************************************************/
    $emailBody = str_replace(
        ["{{COMPANY}}","{{DATEFROM}}","{{DATETO}}","{{TABLE}}"],
        [$CompanyName, $DateFrom, $DateTo, $table],
        $template_html
    );

    /*****************************************************
      SEND EMAIL
    *****************************************************/
    $mail = new PHPMailer(true);

    include('../../mail/include/mail-config-techxpertgroup.php'); // SMTP Setup

    $mail->isHTML(true);
    $mail->Subject = "Daily Ticket Status Report – $CompanyName";
    $mail->Body    = $mail_header . $emailBody . $mail_footer;
     $mail->CharSet = 'UTF-8';
     $mail->Encoding = 'base64';

    foreach (array_filter(array_map('trim', explode(",", $EmailTo))) as $em) {
        if (filter_var($em, FILTER_VALIDATE_EMAIL)) $mail->addAddress($em);
    }

    foreach (array_filter(array_map('trim', explode(",", $EmailCC))) as $em) {
        if (filter_var($em, FILTER_VALIDATE_EMAIL)) $mail->addCC($em);
    }

    if (!empty($CompanyEmail)) {
        $mail->addCC($CompanyEmail);
    }

    try {
        $mail->send();
        echo "<p><b>Email sent:</b> $CompanyName → $EmailTo</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'><b>Email failed for $CompanyName:</b> {$mail->ErrorInfo}</p>";
    }
}

echo "<hr><p>Daily cron completed.</p>";

?>
