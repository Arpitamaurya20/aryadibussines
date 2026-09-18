<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = [];

if (!empty($data['TicketID']) && !empty($data['PaymentID'])) {

    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    $ticketID  = $data['TicketID'];
    $paymentID = $data['PaymentID'];

    // Fetch payment record
    $sqlPayment = "SELECT PaymentType, Amount, Status FROM corporate_tickets_payment_details 
                   WHERE ID = ? AND TicketID = ?";
    $stmt = $conn->prepare($sqlPayment);
    $stmt->bind_param("ii", $paymentID, $ticketID);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if ($payment) {

        if ($payment['Status'] === 'Paid') {
            $response = [
                "error" => true,
                "message" => "Payment is already marked as Paid"
            ];
        } else {

            // Update payment status to 'Paid'
            $updatePayment = $conn->prepare("UPDATE corporate_tickets_payment_details SET Status = 'Paid' WHERE ID = ?");
            $updatePayment->bind_param("i", $paymentID);
            $updatePayment->execute();

            // Fetch latest finance record
            $sqlFinance = "SELECT * FROM corporate_tickets_finance 
                           WHERE TicketID = ? ORDER BY ID DESC LIMIT 1";
            $stmtFinance = $conn->prepare($sqlFinance);
            $stmtFinance->bind_param("i", $ticketID);
            $stmtFinance->execute();
            $finance = $stmtFinance->get_result()->fetch_assoc();

            if ($finance) {
                $tMaterial = $finance['T_MaterialCost'];
                $tLabour   = $finance['T_LabourCost'];
                $tVisit    = $finance['T_VisitCharge'];

                $type = strtolower($payment['PaymentType']);
                $amount = (float)$payment['Amount'];

                if ($type == 'material cost') $tMaterial += $amount;
                if ($type == 'labour cost')   $tLabour += $amount;
                if ($type == 'visit charge')  $tVisit += $amount;

                $tTotal = $tMaterial + $tLabour + $tVisit;

                // Update finance record
                $updateSql = "UPDATE corporate_tickets_finance SET 
                                T_MaterialCost = ?, 
                                T_LabourCost = ?, 
                                T_VisitCharge = ?, 
                                T_TotalPrice = ? 
                              WHERE ID = ?";
                $stmtUpdate = $conn->prepare($updateSql);
                $stmtUpdate->bind_param("ddddi", $tMaterial, $tLabour, $tVisit, $tTotal, $finance['ID']);
                $stmtUpdate->execute();

                $response = [
                    "error" => false,
                    "message" => "Payment marked as Paid and finance updated",
                    "T_MaterialCost" => $tMaterial,
                    "T_LabourCost" => $tLabour,
                    "T_VisitCharge" => $tVisit,
                    "T_TotalPrice" => $tTotal
                ];

            } else {
                $response = [
                    "error" => true,
                    "message" => "No finance record found for this TicketID"
                ];
            }
        }

    } else {
        $response = [
            "error" => true,
            "message" => "Payment record not found"
        ];
    }

} else {
    $response = [
        "error" => true,
        "message" => "TicketID and PaymentID are required"
    ];
}

echo json_encode($response);