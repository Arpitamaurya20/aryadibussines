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

    $ticketID  = (int)$data['TicketID'];
    $paymentID = (int)$data['PaymentID'];

    // Check if record exists
    $checkSql = "SELECT Status, IsStateApprove, IsFinanceApprove 
                 FROM corporate_tickets_payment_details 
                 WHERE ID = ? AND TicketID = ?";
                 
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ii", $paymentID, $ticketID);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        echo json_encode([
            "error" => true,
            "message" => "Payment record not found"
        ]);
        exit;
    }

    if ($payment['Status'] === 'Paid') {
        echo json_encode([
            "error" => true,
            "message" => "Cannot change approval. Payment already marked as Paid."
        ]);
        exit;
    }

    $updateFields = [];
    $params = [];
    $types = "";

    // 🔹 Update State Approval
    if (isset($data['IsStateApprove']) && $data['IsStateApprove'] == 'Yes') {
        $updateFields[] = "IsStateApprove = ?";
        $params[] = "Yes";
        $types .= "s";
    }

    // 🔹 Update Finance Approval
    if (isset($data['IsFinanceApprove']) && $data['IsFinanceApprove'] == 'Yes') {
        $updateFields[] = "IsFinanceApprove = ?";
        $params[] = "Yes";
        $types .= "s";
    }

    if (empty($updateFields)) {
        echo json_encode([
            "error" => true,
            "message" => "No approval field provided to update"
        ]);
        exit;
    }

    $types .= "ii";
    $params[] = $paymentID;
    $params[] = $ticketID;

    $sql = "UPDATE corporate_tickets_payment_details 
            SET " . implode(", ", $updateFields) . " 
            WHERE ID = ? AND TicketID = ?";

    $stmtUpdate = $conn->prepare($sql);
    $stmtUpdate->bind_param($types, ...$params);
    $stmtUpdate->execute();

    echo json_encode([
        "error" => false,
        "message" => "Approval updated successfully"
    ]);

} else {

    echo json_encode([
        "error" => true,
        "message" => "TicketID and PaymentID are required"
    ]);
}

?>