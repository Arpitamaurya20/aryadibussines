<?php

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

function getSingleTicketFinanceDetails($conn, $TicketID)
{
    $response = array();

    // 1️⃣ Finance Table
    $finance = _getTableRecordsassoc(
        $conn,
        'corporate_tickets_finance',
        " WHERE TicketID = '$TicketID'"
    );

    $CustomerPrice = 0;
    $InternalCost  = 0;

    if (!empty($finance)) {
        $CustomerPrice = floatval($finance['C_TotalPrice']);
        $InternalCost  = floatval($finance['T_TotalPrice']);
    }

    // 2️⃣ Quotation Table
    $quotation = _getTableRecordsassoc(
        $conn,
        'corporate_ticket_quotation',
        " WHERE TicketID = '$TicketID' AND IsActive = 1"
    );

    $ExpectedBudget = 0;

    if (!empty($quotation)) {
        $ExpectedBudget = floatval($quotation['expectedbudget']);
    }

    // 3️⃣ Payment Details
    $payments = _getTableRecords(
        $conn,
        'corporate_tickets_payment_details',
        " WHERE TicketID = '$TicketID' AND IsActive = 1"
    );

    $TotalPaymentDone = 0;

    if (!empty($payments)) {
        foreach ($payments as $pay) {
            $TotalPaymentDone += floatval($pay['Amount']);
        }
    }

    // 4️⃣ Profit / Loss
    $ProfitOrLoss = $CustomerPrice - $TotalPaymentDone - $InternalCost;

    $response['data'] = array(
        "TicketID"          => $TicketID,
        "CustomerPrice"     => $CustomerPrice,
        "InternalCost"      => $InternalCost,
        "ExpectedBudget"    => $ExpectedBudget,
        "TotalPaymentDone"  => $TotalPaymentDone,
        "ProfitOrLoss"      => $ProfitOrLoss
    );

    $response['error'] = false;
    $response['message'] = "Ticket finance details fetched successfully";

    return $response;
}


// Main Execution
if (isset($data['TicketID'])) {

    $conn = _connectodb();
    $TicketID = $data['TicketID'];

    $response = getSingleTicketFinanceDetails($conn, $TicketID);

} else {

    $response["error"] = true;
    $response["message"] = "Missing TicketID";

}

echo json_encode($response);

?>