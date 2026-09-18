<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = [];

if (
    !empty($data['TicketID']) &&
    !empty($data['Store']) &&
    !empty($data['Amount'])
) {
    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    /* ---------------- IMAGE HANDLING ---------------- */

    $uploadDir = "../admin/media/payment_media/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $billImageName = null;
    $qrImageName   = null;

    // Bill Image
    if (!empty($data['BillImageData'])) {
        $billImageData = base64_decode($data['BillImageData']);
        $billImageName = "bill_" . $data['TicketID'] . "_" . uniqid() . ".jpg";
        file_put_contents($uploadDir . $billImageName, $billImageData);
    }

    // QR Image
    if (!empty($data['QrImageData'])) {
        $qrImageData = base64_decode($data['QrImageData']);
        $qrImageName = "qr_" . $data['TicketID'] . "_" . uniqid() . ".jpg";
        file_put_contents($uploadDir . $qrImageName, $qrImageData);
    }

    /* ---------------- DB INSERT ---------------- */

    $insertData = [
        "TicketID"    => $data['TicketID'],
        "Store"       => $data['Store'],
        "Amount"      => $data['Amount'],
        "BillImage"   => $billImageName,
        "QrImage"     => $qrImageName,
        "CreatedDate" => date("Y-m-d"),
        "CreatedTime" => date("H:i:s"),
        "IsActive"    => 1
    ];

    $response = $core->_InsertTableRecords_prepare(
        $conn,
        "corporate_tickets_payment_details",
        $insertData
    );

    if ($response['error'] === false) {
        $response['message'] = "Payment details added successfully";
    }

} else {
    $response = [
        "error" => true,
        "message" => "TicketID, Store and Amount are required"
    ];
}

echo json_encode($response);
