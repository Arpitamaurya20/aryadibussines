<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();
$response['data'] = array();

// 🔹 Check TicketID
if (!isset($data['TicketID']) || $data['TicketID'] == "") {

    $response['error'] = true;
    $response['message'] = "TicketID is required";

    echo json_encode($response);
    exit;
}

// 🔹 DB Connection
$dbh  = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

// 🔹 Secure TicketID (integer)
$TicketID = (int)$data['TicketID'];

// 🔹 Query
$sql = "SELECT * 
        FROM corporate_tickets_payment_details  
        WHERE TicketID = $TicketID
        AND IsActive = 1";

$response['data'] = _getSQLRecords($conn, $sql);

$response['error'] = false;
$response['message'] = "Payments fetched successfully";

// 🔹 Output
echo json_encode($response);
?>