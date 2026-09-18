<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/branch/controller/branch_controller.php');
require_once('../admin/corporate-tickets/controller/corporate_tickets_controller.php');
require_once('../admin/includes/autoloader.inc.php');

setTimeZone();

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = [];

if (
    isset($data['CorporateID']) &&
    isset($data['BranchID']) &&
    isset($data['Type']) &&
    isset($data['CreatedBy'])
) {

    $conn = _connectodb();

    if ($data['Type'] == "R&M") {
        $data['BranchAssetID'] = -1;
    }

    if ($data['BranchID'] != -1) {
        $branch_details = GetBranchDetailsbyID($conn, $data['BranchID']);
    }

    $core = new Core();
    $duplicate = false;

    if (!empty($data['ClientTicketID'])) {
        $filter = " WHERE ClientTicketID = '{$data['ClientTicketID']}'
                    AND BranchID = {$data['BranchID']}";

        if (!$core->check_unique_identity_filter($conn, 'corporate_tickets', $filter)) {
            $duplicate = true;
            $response['error'] = true;
            $response['message'] = "Ticket with same Client Ticket ID already exists";
        }
    }

    if (!$duplicate) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($conn);

    try {

        // ✅ STEP-1: CREATE TICKET
        $response = CreateCorporateTicket($conn, $data, $branch_details);

        // ❌ Ticket creation failed
        if (!isset($response['error']) || $response['error'] === true) {
            throw new Exception($response['message'] ?? 'Ticket creation failed');
        }

        // ❌ TicketID missing (THIS IS YOUR BUG)
        if (empty($response['TicketIDNew']) || !is_numeric($response['TicketIDNew'])) {
            throw new Exception('Ticket created but TicketID not generated');
        }

        $TicketID = (int)$response['TicketIDNew'];

        // ✅ STEP-2: AUTO CREATE QUOTATION FROM CART
        AutoCreateQuotationFromCartItems(
            $conn,
            $data['CorporateID'],
            $TicketID,
            $data['CreatedBy']
        );

        // ✅ COMMIT ONLY AFTER EVERYTHING IS OK
        mysqli_commit($conn);

    } catch (Exception $e) {

        mysqli_rollback($conn);

        $response = [
            'error'   => true,
            'message' => $e->getMessage()
        ];
    }
}


} else {
    $response['error'] = true;
    $response['message'] = "Missing required fields";
}

echo json_encode($response);
