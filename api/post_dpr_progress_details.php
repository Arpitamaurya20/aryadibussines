<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
setTimeZone();
$data = json_decode($data_raw, true);
$response = array();

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

if (
    isset($data['TaskProgressID']) &&
    isset($data['TaskID']) &&
    isset($data['TaskDate']) &&
    isset($data['Status']) &&
    isset($data['Remarks'])
) {
    $conn = _connectodb();
    extract($data);
    $TicketID=intval($TicketID);
    $TaskID = intval($TaskID);
    $TaskDate = mysqli_real_escape_string($conn, $TaskDate);
    $Status = intval($Status); // integer
    $Remarks = mysqli_real_escape_string($conn, $Remarks);

    $UpdatedBy = 'System';
    $UpdatedDate = date('Y-m-d');
    $UpdatedTime = date('H:i:s');
    $IsActive = isset($data['IsActive']) ? intval($data['IsActive']) : 1;

    if (isset($data['TaskProgressID']) && $data['TaskProgressID'] != -1) {
        // ---------- UPDATE EXISTING RECORD ----------
        $TaskProgressID = intval($data['TaskProgressID']);
        $sql_update = "
            TaskID = '$TaskID',
            TaskDate = '$TaskDate',
            Status = '$Status',
            Remarks = '$Remarks',
            UpdatedBy = '$UpdatedBy',
            UpdatedDate = '$UpdatedDate',
            UpdatedTime = '$UpdatedTime',
            IsActive = '$IsActive'
            WHERE ID = $TaskProgressID
        ";
        $response = _UpdateTableRecords($conn, 'project_task_date_progress', $sql_update);
          if ($response['error'] == false) {
            $report_url = "https://techxpertindia.in/admin/corporate-tickets/action/generate-dpr-report?TicketID=" 
                          . urlencode($TicketID);
            trigger_async_mail($TicketID, $report_url);
        }
        $response['ID'] = $TaskProgressID;

    } else {
        // ---------- INSERT NEW RECORD ----------
        $sql_insert = "
            INSERT INTO project_task_date_progress 
            (TaskID, TaskDate, Status, Remarks, UpdatedBy, UpdatedDate, UpdatedTime, IsActive)
            VALUES
            ('$TaskID', '$TaskDate', '$Status', '$Remarks', '$UpdatedBy', '$UpdatedDate', '$UpdatedTime', '$IsActive')
        ";
        $response = _InsertTableRecords($conn, $sql_insert);

        if ($response['error'] == false) {
            $response['ID'] = $response['last_insert_id'];
        }
    }

    // ---------- CALCULATE CUMULATIVE STATUS ----------
    $result = mysqli_query($conn, "
        SELECT SUM(Status) AS total_progress 
        FROM project_task_date_progress 
        WHERE TaskID = $TaskID
    ");
    $row = mysqli_fetch_assoc($result);
    $totalProgress = intval($row['total_progress']);

    // If sum >= 100, set completed; 0 => ToStart; otherwise InProgress
    if ($totalProgress <= 0) {
        $taskStatus = 'To Start';
    } elseif ($totalProgress >= 100) {
        $taskStatus = 'Completed';
    } else {
        $taskStatus = 'In Progress';
    }

    // ---------- UPDATE TASK STATUS IN project_tasks ----------
    mysqli_query($conn, "
        UPDATE project_tasks
        SET TaskStatus = '$taskStatus'
        WHERE ID = $TaskID
    ");

} else {
    $response["error"] = true;
    $response["message"] = "Missing required fields.";
}

echo json_encode($response);
?>
