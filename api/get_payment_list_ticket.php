<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = [];

$TicketID=$data['TicketID'];
if (!empty($data['TicketID'])) {

    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();

    // Build full SQL (matches _getSQLRecords)
    $TicketID = $conn->real_escape_string($TicketID);
    $sql = "
        SELECT 
            ID,
            TicketID,
            Store,
            Amount,
            BillImage,
            QrImage,
            CreatedDate,
            CreatedTime
        FROM corporate_tickets_payment_details
        WHERE TicketID = '$TicketID'
          AND IsActive = 1
        ORDER BY ID DESC
    ";

    $response = $core->_getSQLRecords($conn, $sql);

} else {
    $response = [
        "error" => true,
        "message" => "TicketID is required"
    ];
}

echo json_encode($response);
