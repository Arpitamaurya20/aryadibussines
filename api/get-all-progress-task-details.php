<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Add this line to skip global user checks
define('SKIP_USER_VALIDATION', true);
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();
if (isset($data['TaskProgressID'])) {

    $conn = _connectodb();
    $TaskProgressID = intval($data['TaskProgressID']);
    $baseUrl = "https://techxpertindia.in/"; // ✅ Your base domain

    // 1️⃣ Get Task Progress Details
    $sql_progress = "
        SELECT 
            ID,
            TaskID,
            TaskDate,
            Status,
            Remarks,
            UpdatedBy,
            UpdatedDate,
            UpdatedTime,
            IsActive
        FROM project_task_date_progress
        WHERE ID = $TaskProgressID
        LIMIT 1
    ";
    $progressData = _getSQLRecords($conn, $sql_progress);

    if (!empty($progressData)) {
        $progress = $progressData[0]; // Get single record

        // 2️⃣ Get All Active Images for this TaskProgressID
        $sql_images = "
            SELECT 
                ID,
                TaskProgressID,
                TaskID,
                TaskDate,
                FileName,
                FilePath,
                UploadedBy,
                UploadedDate,
                IsActive
            FROM project_task_evidence
            WHERE TaskProgressID = $TaskProgressID 
              AND IsActive = 1
            ORDER BY UploadedDate DESC
        ";

        $images = _getSQLRecords($conn, $sql_images);

        // Add full URLs to images
        foreach ($images as &$img) {
            $filePath = str_replace("../", "", $img['FilePath']);
            $img['FullImageURL'] = $baseUrl . $filePath;
        }

        // ✅ Combine both progress + images
        $progress['Images'] = $images;
        $progress['ImageCount'] = count($images);

        $response = [
            "error" => false,
            "data" => $progress,
            "message" => "Task progress details fetched successfully."
        ];

    } else {
        $response = [
            "error" => true,
            "message" => "No task progress found for this ID."
        ];
    }

} else {
    $response = [
        "error" => true,
        "message" => "Missing required field: TaskProgressID."
    ];
}

echo json_encode($response);
?>
