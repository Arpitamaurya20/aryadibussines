<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

function getApprovalWisePayments($conn, $data)
{
    $response = array();
    $response['data'] = array();

    $filter_conditions = "";

    // =====================================================
    // 🔹 APPROVAL LOGIC
    // =====================================================

    // CFO ROLE → SHOW ALL TICKETS
    // Optional CFO approval filter only
    if (isset($data['Role']) && $data['Role'] == 'CFO') {

        if (isset($data['IsCfoApprove']) && $data['IsCfoApprove'] !== '') {

            $filter_conditions .= "
                AND pd.IsCfoApprove = '" . trim($data['IsCfoApprove']) . "'
            ";
        }
    }

    // STATE + FINANCE BOTH
    elseif (
        isset($data['IsStateApprove']) && $data['IsStateApprove'] !== '' &&
        isset($data['IsFinanceApprove']) && $data['IsFinanceApprove'] !== ''
    ) {

        $filter_conditions .= "
            AND pd.IsStateApprove = '" . trim($data['IsStateApprove']) . "'
            AND pd.IsFinanceApprove = '" . trim($data['IsFinanceApprove']) . "'
        ";
    }

    // ONLY STATE
    elseif (
        isset($data['IsStateApprove']) && $data['IsStateApprove'] !== ''
    ) {

        $filter_conditions .= "
            AND pd.IsStateApprove = '" . trim($data['IsStateApprove']) . "'
        ";
    }

    // ONLY FINANCE
    elseif (
        isset($data['IsFinanceApprove']) && $data['IsFinanceApprove'] !== ''
    ) {

        $filter_conditions .= "
            AND pd.IsFinanceApprove = '" . trim($data['IsFinanceApprove']) . "'
        ";
    }

    // =====================================================
    // 🔹 STATUS FILTER
    // =====================================================

    if (isset($data['Status']) && $data['Status'] !== '') {

        // Example: Pending,Approved,Rejected
        $statusArray = explode(',', $data['Status']);

        $statusList = [];

        foreach ($statusArray as $status) {

            $statusList[] = "'" . trim($status) . "'";
        }

        $statusString = implode(",", $statusList);

        $filter_conditions .= " 
            AND pd.Status IN ($statusString) 
        ";
    }

    // =====================================================
    // 🔹 PAYMENT TYPE FILTER
    // =====================================================

    if (isset($data['PaymentType']) && $data['PaymentType'] !== '') {

        $filter_conditions .= "
            AND pd.PaymentType = '" . trim($data['PaymentType']) . "'
        ";
    }

    // =====================================================
    // 🔹 SEARCH FILTER
    // =====================================================

    $filter_search = "";

    if (isset($data['search_term']) && trim($data['search_term']) != '') {

        $search_term = trim($data['search_term']);

        $filter_search = "
            AND (
                ct.TicketID LIKE '%$search_term%'
                OR bm.BranchCity LIKE '%$search_term%'
                OR pd.Store LIKE '%$search_term%'
            )
        ";
    }

    // =====================================================
    // 🔹 PAGINATION
    // =====================================================

    $filter_limit = "";

    if (
        isset($data['start_counter']) &&
        isset($data['no_of_records'])
    ) {

        $start_counter = (int)$data['start_counter'];
        $no_of_records = (int)$data['no_of_records'];

        $filter_limit = "
            LIMIT $start_counter, $no_of_records
        ";
    }

    // =====================================================
    // 🔹 MAIN QUERY
    // =====================================================

    $sql = "
        SELECT 
            pd.*,
            ct.TicketID AS TicketNumber,
            bm.BranchCity,
            bm.BranchState

        FROM corporate_tickets_payment_details pd

        INNER JOIN corporate_tickets ct
            ON pd.TicketID = ct.ID

        INNER JOIN branch bm
            ON ct.BranchID = bm.ID

        WHERE pd.IsActive = 1

        $filter_conditions
        $filter_search

        ORDER BY pd.ID DESC

        $filter_limit
    ";

    $response['data'] = _getSQLRecords($conn, $sql);

    // =====================================================
    // 🔹 COUNT QUERY
    // =====================================================

    $count_sql = "
        SELECT COUNT(pd.ID) as total_records

        FROM corporate_tickets_payment_details pd

        INNER JOIN corporate_tickets ct
            ON pd.TicketID = ct.ID

        INNER JOIN branch bm
            ON ct.BranchID = bm.ID

        WHERE pd.IsActive = 1

        $filter_conditions
        $filter_search
    ";

    $count_result = _getSQLRecords($conn, $count_sql);

    $response['total_records'] =
        $count_result[0]['total_records'] ?? 0;

    // =====================================================
    // 🔹 RESPONSE
    // =====================================================

    $response['error'] = false;
    $response['message'] = "Payments fetched successfully";

    return $response;
}

// =====================================================
// 🔹 EXECUTE API
// =====================================================

$conn = _connectodb();

$response = getApprovalWisePayments($conn, $data);

echo json_encode($response);

?>