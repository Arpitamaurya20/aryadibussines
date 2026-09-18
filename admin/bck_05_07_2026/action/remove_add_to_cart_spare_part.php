<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (isset($data['TicketID']) && isset($data['SparePart'])) {

    $dbh = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    $TicketID = intval($data['TicketID']);
    $SparePartID = intval($data['SparePart']);

    // Build delete query safely
    $query = "WHERE TicketID = $TicketID AND SparePart = $SparePartID";

    $result = $core->delete_identity_filter($conn, "post_spare_part", $query);

    if ($result) {
        $response['error'] = false;
        $response['message'] = "Spare part removed successfully.";
    } else {
        $response['error'] = true;
        $response['message'] = "Failed to remove spare part or not found.";
    }

} else {
    $response['error'] = true;
    $response['message'] = "Missing required fields: TicketID or SparePart.";
}

echo json_encode($response);
?>
