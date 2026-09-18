<?php
// cron/create_queue.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once('../includes/autoloader.inc.php');
include("../controllers/common_controllers.php");
setTimeZone();

$db = new Dbh();
$conn = $db->_connectodb();
$today = date('Y-m-d');

function trigger_async_mail($TicketID, $ReportURL)
{
    $url = "https://techxpertindia.in/admin/mail/send-dpr-report-new.php";

    $postData = json_encode([
        "TicketID" => $TicketID,
        "ReportURL" => $ReportURL
    ]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);   
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);         // 0.3 second timeout
    curl_exec($ch);
    curl_close($ch);
}
function isProjectCompleted($conn, $TicketID)
{
    $sql = "
        SELECT TaskStatus
        FROM project_tasks
        WHERE TicketID = '$TicketID'
        AND IsActive = 1
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result || mysqli_num_rows($result) == 0) {
        return false;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $status = strtolower(trim($row['TaskStatus']));

        if ($status === 'to start' || $status === 'in progress') {
            return false;
        }
    }

    return true;
}


$sql = "
SELECT ID, TicketID, LastDprMailDate
FROM projects
WHERE IsActive = 1
AND IsDprMailActive = 1
AND (LastDprMailDate IS NULL OR LastDprMailDate < '$today')
";

// echo $sql;
// die();
$projects = mysqli_query($conn, $sql);

while ($project = mysqli_fetch_assoc($projects)) {

    $TicketID = $project['TicketID'];

    if (isProjectCompleted($conn, $TicketID)) {

        // STOP future mails
        mysqli_query($conn, "
            UPDATE projects
            SET IsDprMailActive = 0
            WHERE TicketID = '$TicketID'
        ");

        continue;
    }

    // Send daily DPR mail
    $report_url = "https://techxpertindia.in/admin/corporate-tickets/action/generate-dpr-report?TicketID="
                  . urlencode($TicketID);

    trigger_async_mail($TicketID, $report_url);

    // Update last sent date
    mysqli_query($conn, "
        UPDATE projects
        SET LastDprMailDate = CURDATE()
        WHERE TicketID = '$TicketID'
    ");
}


?>
