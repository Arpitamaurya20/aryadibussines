<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = [];

// 🔹 Base Upload Path
$upload_dir = "../admin/media/employee_concern/";
$base_url   = "https://techxpertindia.in/admin/media/employee_concern/";

try {

    if (empty($data['Issue']) || empty($data['Mobile'])) {
        echo json_encode([
            'error' => true,
            'message' => 'Mobile and Issue are required'
        ]);
        exit;
    }

    $dbh  = new Dbh();
    $conn = $dbh->_connectodb();

    // 🔹 Sanitize Inputs
    $name        = $conn->real_escape_string($data['Name'] ?? '');
    $mobile      = $conn->real_escape_string($data['Mobile']);
    $issue       = $conn->real_escape_string($data['Issue']);
    $isAnonymous = isset($data['IsAnonymous']) ? (int)$data['IsAnonymous'] : 0;
    $createdBy   = isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : 0;

    $attachmentFileName = "";

    /* =========================
       🔥 HANDLE BASE64 FILE
    ========================= */

    if (!empty($data['Attachment'])) {

        $fileData = $data['Attachment'];

        // Example: data:image/png;base64,xxxxx
        if (strpos($fileData, 'base64,') !== false) {

            $fileParts = explode(";base64,", $fileData);
            $fileTypeAux = explode("image/", $fileParts[0]);

            $fileExtension = isset($fileTypeAux[1]) ? $fileTypeAux[1] : "png";

            $fileBase64 = base64_decode($fileParts[1]);

            $attachmentFileName = "concern_" . time() . rand(1000,9999) . "." . $fileExtension;

            file_put_contents($upload_dir . $attachmentFileName, $fileBase64);
        }
    }

    /* =========================
       🔥 INSERT QUERY
    ========================= */

    $sql = "INSERT INTO employee_concerns 
            (Name, Mobile, Issue, Attachment, IsAnonymous, CreatedBy, Status, CreatedAt) 
            VALUES 
            ('$name', '$mobile', '$issue', '$attachmentFileName', $isAnonymous, $createdBy, 'New', NOW())";

    if ($conn->query($sql)) {

        echo json_encode([
            'error' => false,
            'message' => 'Concern submitted successfully',
            'insert_id' => $conn->insert_id,
            'attachment_url' => $attachmentFileName ? $base_url . $attachmentFileName : null
        ]);

    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>