<?php
require_once('../controller/corporate_tickets_controller.php');
require_once('../../includes/autoloader.inc.php');
$dbh = new dbh();
$conn = $dbh->_connectodb();

$TicketID = $_POST['TicketID'] ?? '';

if ($TicketID == "") {
    echo json_encode(["status" => "error", "message" => "TicketID is required"]);
    exit;
}

$otp_query = "SELECT TicketOTP, TicketCloseOTP FROM corporate_tickets WHERE ID = '$TicketID' LIMIT 1";
$otp_result = mysqli_query($conn, $otp_query);

if (!$otp_result || mysqli_num_rows($otp_result) == 0) {
    echo json_encode(["status" => "error", "message" => "Ticket not found"]);
    exit;
}

$otp_data = mysqli_fetch_assoc($otp_result);

echo json_encode([
    "status" => "success",
    "message" => "OTP fetched successfully",
    "TicketOTP" => $otp_data['TicketOTP'],
    "TicketCloseOTP" => $otp_data['TicketCloseOTP']
]);
exit;
?>
