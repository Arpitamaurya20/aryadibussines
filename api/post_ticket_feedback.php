<?php

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();

if(isset($data['TicketID']) && isset($data['Rating']) && isset($data['Message']))
{
    $dbh = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    // Add created date & time automatically
    $data['CreatedDate'] = date("Y-m-d");
    $data['CreatedTime'] = date("H:i:s");
    $data['IsActive'] = 1; // default active

    // Call your reusable function
    $response = $core->_InsertTableRecords_prepare($conn, "ticket_feedback", $data);

    if($response['error'] == true){
        $response["message"] = "Technical Problem, please try again later";
    }
}
else
{
    $response["error"] = true;
    $response["message"] = "Missing Required Fields";
}

echo json_encode($response);
?>
