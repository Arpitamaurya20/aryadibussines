<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
require_once('../controller/ppm_billing_controller.php');

$conn = _connectodb();
header('Content-Type: application/json');

if (!isset($_SESSION['pb_username'])) {
    echo json_encode(['error' => true, 'message' => 'Login required']);
    exit;
}

$TicketIDs = $_POST['TicketIDs'] ?? [];
$BillingNumber = $_POST['BillingNumber'] ?? '';
$BilledDate = $_POST['BilledDate'] ?? date('Y-m-d');
$PaymentStatus = $_POST['PaymentStatus'] ?? 'Pending';
$Remarks = $_POST['Remarks'] ?? '';

if (empty($TicketIDs)) {
    echo json_encode(['error' => true, 'message' => 'No tickets selected']);
    exit;
}

$totalInvoiceAmount = 0;
$successCount = 0;

foreach ($TicketIDs as $TicketID) {

    $data = array(
        'TicketID' => intval($TicketID),
        'BillingStatus' => 'Billed',
        'PaymentStatus' => $PaymentStatus,
        'BillingNumber' => $BillingNumber,
        'BilledDate' => $BilledDate,
        'BillingStartDate' => date('Y-m-01'),
        'BillingEndDate' => date('Y-m-t'),
        'Remarks' => $Remarks,
        'CreatedBy' => $_SESSION['pb_username']
    );

    $result = CreateUpdateBillingTracking($conn, $data);

    if ($result['error'] == false) {

        // Add each ticket's billed amount to total
        $totalInvoiceAmount += floatval($result['BilledAmount']);
        $successCount++;
    }
}

if ($successCount > 0) {

    // 🔹 Check if Billing Number already exists
    $checkSql = "SELECT ID FROM ppm_billing_tracking_ammount 
                 WHERE BillingNumber = '$BillingNumber'";

    $checkResult = mysqli_query($conn, $checkSql);

    if (mysqli_num_rows($checkResult) > 0) {

        // 🔹 Update existing record
        $update_param = "
            TotalAmmount = $totalInvoiceAmount,
            UpdatedAt = NOW()
            WHERE BillingNumber = '$BillingNumber'
        ";

        _UpdateTableRecords($conn, 'ppm_billing_tracking_ammount', $update_param);

    } else {

        // 🔹 Insert new invoice total record
        $sql = "INSERT INTO ppm_billing_tracking_ammount
                (BillingNumber, TotalAmmount, UpdatedAt)
                VALUES
                ('$BillingNumber', $totalInvoiceAmount, NOW())";

        _InsertTableRecords($conn, $sql);
    }

    echo json_encode([
        'error' => false,
        'message' => "$successCount tickets billed successfully.",
        'TotalAmount' => $totalInvoiceAmount
    ]);
    exit;
}


echo json_encode(['error' => true, 'message' => 'No tickets processed']);
