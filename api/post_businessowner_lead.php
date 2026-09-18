<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$response = array();

if (
    isset($_POST['businessname']) &&
    isset($_POST['contactname']) &&
    isset($_POST['email']) &&
    isset($_POST['phone']) &&
    isset($_POST['pincode']) &&
    isset($_POST['gstin']) &&
    isset($_POST['description'])
) {

    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    // ===============================
    // FILE UPLOAD HANDLING
    // ===============================

    $fileName = null;
    $uploadDir = '../admin/media/pdf-assets/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {

        $allowedTypes = ['pdf','jpg','jpeg','png'];
        $fileExt = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedTypes)) {
            echo json_encode([
                "error" => true,
                "message" => "Invalid file type"
            ]);
            exit;
        }

        if ($_FILES['document']['size'] > 2 * 1024 * 1024) {
            echo json_encode([
                "error" => true,
                "message" => "File must be under 2MB"
            ]);
            exit;
        }

        $fileName = uniqid() . "." . $fileExt;

        move_uploaded_file(
            $_FILES['document']['tmp_name'],
            $uploadDir . $fileName
        );
    }

    // ===============================
    // INSERT DATA
    // ===============================

    $insertData = array(
        "BusinessName" => trim($_POST['businessname']),
        "ContactName"  => trim($_POST['contactname']),
        "Email"        => trim($_POST['email']),
        "Phone"        => trim($_POST['phone']),
        "Pincode"      => trim($_POST['pincode']),
        "GSTIN"        => strtoupper(trim($_POST['gstin'])),
        "Description"  => trim($_POST['description']),
        "DocumentPath" => $fileName,
        "IsActive"     => 1,
        "CreatedDate"  => date("Y-m-d H:i:s")
    );

    $response = $core->_InsertTableRecords_prepare(
        $conn,
        "businessowner_lead",
        $insertData
    );

    if (!empty($response['error']) && $response['error'] === true) {
        $response['message'] = "Technical Problem, please try again later";
    } else {
        $response['error'] = false;
        $response['message'] = "Business Owner Lead submitted successfully";
    }

} else {

    $response['error'] = true;
    $response['message'] = "Missing Required Fields";

}

echo json_encode($response);
exit;

?>
