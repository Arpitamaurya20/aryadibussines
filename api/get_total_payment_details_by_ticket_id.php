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

// 🔹 Base URL
$base_url = "https://techxpertindia.in/admin/media/payment_media/";

// 🔹 Validate
if (!isset($data['TicketID']) || $data['TicketID'] == "") {
    echo json_encode([
        "error" => true,
        "message" => "TicketID is required"
    ]);
    exit;
}

$dbh  = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

$TicketID = (int)$data['TicketID'];

/* =========================
   🔥 MAIN JOIN QUERY
========================= */

$sql = "
SELECT 
    p.ID AS PaymentID,
    p.PaymentType,
    p.Amount,
    p.Store,
    p.PhoneNumber,
    p.BillImage,
    p.QrImage,
    p.Status,
    p.CreatedDate,

    c.AmountDone,
    c.File AS CfoFile,
    c.Remark,
    c.Update_At

FROM corporate_tickets_payment_details p

LEFT JOIN payment_done_by_cfo c 
    ON p.ID = c.PaymentDetailID

WHERE p.TicketID = $TicketID
AND p.IsActive = 1

ORDER BY p.ID DESC
";

$records = _getSQLRecords($conn, $sql);

/* =========================
   🔹 FORMAT + IMAGE URL
========================= */

$total_amount = 0;
$total_cfo_paid = 0;
$paymentTypeTotals = [];

foreach ($records as $key => $row) {

    // 🔹 Image URLs
    if (!empty($row['BillImage'])) {
        $records[$key]['BillImage'] = $base_url . $row['BillImage'];
    }

    if (!empty($row['QrImage'])) {
        $records[$key]['QrImage'] = $base_url . $row['QrImage'];
    }

    if (!empty($row['CfoFile'])) {
        $records[$key]['CfoFile'] = $base_url . $row['CfoFile'];
    }

    // 🔹 Total Payment Amount
    $total_amount += (float)$row['Amount'];

    // 🔹 CFO Paid Total
    if (!empty($row['AmountDone'])) {
        $total_cfo_paid += (float)$row['AmountDone'];
    }

    // 🔹 PaymentType wise total
    $type = $row['PaymentType'];

    if (!isset($paymentTypeTotals[$type])) {
        $paymentTypeTotals[$type] = 0;
    }

    $paymentTypeTotals[$type] += (float)$row['Amount'];
}

/* =========================
   🔥 FINAL RESPONSE
========================= */

$response['error'] = false;
$response['message'] = "Payments & history fetched successfully";

$response['data'] = [
    "payments" => $records,
    "summary" => [
        "total_payment_amount" => $total_amount,
        "total_cfo_paid" => $total_cfo_paid,
        "payment_type_wise_total" => $paymentTypeTotals
    ]
];

echo json_encode($response);
?>
