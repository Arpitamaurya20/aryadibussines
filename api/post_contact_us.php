<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (
    isset($data['fname']) &&
    isset($data['lname']) &&
    isset($data['email']) &&
    isset($data['phone']) &&
    isset($data['subject']) &&
    isset($data['message'])
) {
    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    // Auto fields
    $data['added_on'] = date("Y-m-d");
    // Insert data
    $response = $core->_InsertTableRecords_prepare(
        $conn,
        "contact",
        $data
    );

    if (!empty($response['error']) && $response['error'] === true) {
        $response['message'] = "Technical Problem, please try again later";
    }
} else {
    $response['error']   = true;
    $response['message'] = "Missing Required Fields";
}

echo json_encode($response);
exit;
?>
