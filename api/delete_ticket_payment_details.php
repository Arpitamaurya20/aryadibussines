<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = [];

if (!empty($data['ID'])) {

    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();

    $updateData = [
        "IsActive" => 0
    ];

    $where = [
        "ID" => $data['ID']
    ];

    $response = $core->_UpdateTableRecords_prepare(
        $conn,
        "corporate_tickets_payment_details",
        $updateData,
        $where
    );

} else {
    $response = [
        "error" => true,
        "message" => "ID is required"
    ];
}

echo json_encode($response);
