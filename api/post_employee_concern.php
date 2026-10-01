<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = [];

$upload_dir = "../admin/media/employee_concern/";
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = stripos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
$base_url = $isLocal
    ? 'http://' . $host . '/Projects/aryadibussines/admin/media/employee_concern/'
    : 'https://techxpertindia.in/admin/media/employee_concern/';

try {

    if (!is_array($data)) {
        echo json_encode([
            'error' => true,
            'message' => 'Invalid request.'
        ]);
        exit;
    }

    if (empty($data['Issue']) || empty($data['Mobile'])) {
        echo json_encode([
            'error' => true,
            'message' => 'Mobile and Issue are required'
        ]);
        exit;
    }

    $dbh  = new Dbh();
    $conn = $dbh->_connectodb();

    $name        = $conn->real_escape_string($data['Name'] ?? '');
    $mobile      = $conn->real_escape_string($data['Mobile']);
    $issue       = $conn->real_escape_string($data['Issue']);
    $isAnonymous = isset($data['IsAnonymous']) ? (int)$data['IsAnonymous'] : 0;
    $createdBy   = isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : 0;

    $attachmentFileName = "";

    if (!empty($data['Attachment'])) {

        $fileData = $data['Attachment'];

        if (strpos($fileData, 'base64,') !== false) {

            $fileParts = explode(";base64,", $fileData);
            $fileTypeAux = explode("image/", $fileParts[0]);
            $rawExtension = isset($fileTypeAux[1]) ? $fileTypeAux[1] : "jpg";
            $fileExtension = strtolower(preg_replace('/[^a-z0-9]/i', '', $rawExtension));
            if ($fileExtension === '' || $fileExtension === 'jpeg') {
                $fileExtension = 'jpg';
            }

            $fileBase64 = base64_decode(end($fileParts));
            if ($fileBase64 === false || $fileBase64 === '') {
                echo json_encode([
                    'error' => true,
                    'message' => 'Unable to read the selected image.'
                ]);
                exit;
            }

            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
                echo json_encode([
                    'error' => true,
                    'message' => 'Unable to save the image.'
                ]);
                exit;
            }

            $attachmentFileName = "concern_" . time() . rand(1000,9999) . "." . $fileExtension;

            if (file_put_contents($upload_dir . $attachmentFileName, $fileBase64) === false) {
                echo json_encode([
                    'error' => true,
                    'message' => 'Unable to save the image.'
                ]);
                exit;
            }
        }
    }

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
