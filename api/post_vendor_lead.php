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
    isset($data['name']) &&
    isset($data['contactname']) &&
    isset($data['email']) &&
    isset($data['phone']) &&
    isset($data['pincode']) &&
    isset($data['pancard']) &&
    isset($data['description'])
) {

    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    // Prepare Insert Data as per vendorlead table
    $insertData = array(
        "Name"        => trim($data['name']),
        "ContactName" => trim($data['contactname']),
        "Email"       => trim($data['email']),
        "Phone"       => trim($data['phone']),
        "Pincode"     => trim($data['pincode']),
        "PanCard"     => strtoupper(trim($data['pancard'])),
        "Description" => trim($data['description']),
        "IsActive"    => 1,
        "CreatedDate" => date("Y-m-d H:i:s")
    );

    // Insert into vendorlead table
    $response = $core->_InsertTableRecords_prepare(
        $conn,
        "vendorlead",
        $insertData
    );

    if (!empty($response['error']) && $response['error'] === true) {
        $response['message'] = "Technical Problem, please try again later";
    } else {
        $response['error'] = false;
        $response['message'] = "Vendor Lead submitted successfully";
    }

} else {
    $response['error']   = true;
    $response['message'] = "Missing Required Fields";
}

echo json_encode($response);
exit;

?>
