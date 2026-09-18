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

    $ID = $conn->real_escape_string($data['ID']);

    // Base URL for payment images
    $imageBaseUrl = "https://techxpertindia.in/admin/media/payment_media/";

    $where = "WHERE ID = '$ID' AND IsActive = 1";

    $row = $core->_getTableDetails(
        $conn,
        "corporate_tickets_payment_details",
        $where
    );

    if (!empty($row)) {

        // Append full image URLs
        $row['BillImage'] = !empty($row['BillImage'])
            ? $imageBaseUrl . $row['BillImage']
            : null;

        $row['QrImage'] = !empty($row['QrImage'])
            ? $imageBaseUrl . $row['QrImage']
            : null;

        $response = [
            "error" => false,
            "data"  => $row
        ];

    } else {
        $response = [
            "error" => true,
            "message" => "Record not found"
        ];
    }

} else {
    $response = [
        "error" => true,
        "message" => "ID is required"
    ];
}

echo json_encode($response);
