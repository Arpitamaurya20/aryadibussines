<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = [];

/* ---------------- VALIDATION ---------------- */

if (
    !empty($data['name']) &&
    !empty($data['email']) &&
    !empty($data['phone']) &&
    !empty($data['resumeData']) &&
    !empty($data['resumeFileName'])
) {

    // Validate Email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "error" => true,
            "message" => "Invalid email format"
        ]);
        exit;
    }

    // Validate Phone (10–15 digits)
    if (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
        echo json_encode([
            "error" => true,
            "message" => "Invalid phone number"
        ]);
        exit;
    }

    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    /* ---------------- FILE UPLOAD ---------------- */

    $uploadDir = "../admin/media/resume/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $resumeFileName = null;

    // Get file extension
    $originalFileName = $data['resumeFileName'];
    $fileExt = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

    $allowedExtensions = ['pdf', 'doc', 'docx'];

    if (!in_array($fileExt, $allowedExtensions)) {
        echo json_encode([
            "error" => true,
            "message" => "Only PDF, DOC and DOCX files are allowed"
        ]);
        exit;
    }

    // Decode file
    $fileData = base64_decode($data['resumeData']);

    // Validate file size (max 2MB)
    if (strlen($fileData) > 2 * 1024 * 1024) {
        echo json_encode([
            "error" => true,
            "message" => "File size must be less than 2MB"
        ]);
        exit;
    }

    // Create unique filename
    $resumeFileName = "resume_" . time() . "_" . uniqid() . "." . $fileExt;

    file_put_contents($uploadDir . $resumeFileName, $fileData);

    /* ---------------- DB INSERT ---------------- */

    $insertData = [
        "name"      => trim($data['name']),
        "email"     => trim($data['email']),
        "phone"     => trim($data['phone']),
        "resume"    => $resumeFileName,
        "added_on"  => date("Y-m-d H:i:s")
    ];

    $response = $core->_InsertTableRecords_prepare(
        $conn,
        "resume",
        $insertData
    );

    if ($response['error'] === false) {
        $response['message'] = "Resume uploaded successfully";
    }

} else {
    $response = [
        "error" => true,
        "message" => "Name, Email, Phone and Resume file are required"
    ];
}

echo json_encode($response);
