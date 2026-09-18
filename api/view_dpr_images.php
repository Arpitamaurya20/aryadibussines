<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
setTimeZone();
$data = json_decode($data_raw, true);
$response = array();

if (isset($data['TaskProgressID'])) {

    $conn = _connectodb();
    $TaskProgressID = intval($data['TaskProgressID']);
    
    // Fetch all active images for this TaskProgressID
    $sql = "SELECT 
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
            ORDER BY UploadedDate DESC";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $images = [];
        $baseUrl = "https://techxpertindia.in/"; // ✅ your actual domain base

        while ($row = mysqli_fetch_assoc($result)) {
            // Normalize the FilePath (remove leading ../ if exists)
            $filePath = str_replace("../", "", $row['FilePath']);

            // Build full image URL
            $row['FullImageURL'] = $baseUrl . $filePath;

            $images[] = $row;
        }

        $response['error'] = false;
        $response['data'] = $images;
        $response['count'] = count($images);
    } else {
        $response['error'] = false;
        $response['images'] = [];
        $response['message'] = "No images found for this TaskProgressID.";
    }

} else {
    $response['error'] = true;
    $response['message'] = "Missing required field: TaskProgressID.";
}

echo json_encode($response);
?>
