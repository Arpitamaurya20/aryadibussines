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

    // =========================================================
    // APPROVAL FLOW LOGIC
    // =========================================================
    //
    // FLOW:
    //
    // 1. State Approval Required First
    //
    // 2. After State Approved:
    //    - Finance can approve
    //    - CFO can approve
    //
    // 3. If CFO Approved:
    //    -> Consider Fully Approved
    //    -> Finance approval becomes optional
    //
    // =========================================================

    // ---------------------------------------------------------
    // FINAL APPROVED
    // Finance Approved OR CFO Approved
    // ---------------------------------------------------------
    if (!empty($data['FinalApproved'])) {

        $filter_conditions .= "
            AND pd.IsStateApprove = 'Approved'
            AND (
                pd.IsFinanceApprove = 'Approved'
                OR pd.IsCfoApprove = 'Approved'
            )
        ";
    }

    // ---------------------------------------------------------
    // CFO APPROVAL FILTER
    // CFO can approve after State Approved
    // ---------------------------------------------------------
    elseif (!empty($data['IsCfoApprove'])) {

        $filter_conditions .= "
            AND pd.IsStateApprove = 'Approved'
            AND pd.IsCfoApprove = '" . trim($data['IsCfoApprove']) . "'
        ";
    }

    // ---------------------------------------------------------
    // FINANCE APPROVAL FILTER
    // Finance can approve after State Approved
    // ---------------------------------------------------------
    elseif (!empty($data['IsFinanceApprove'])) {

        $filter_conditions .= "
            AND pd.IsStateApprove = 'Approved'
            AND pd.IsFinanceApprove = '" . trim($data['IsFinanceApprove']) . "'
        ";
    }

    // ---------------------------------------------------------
    // STATE APPROVAL FILTER
    // ---------------------------------------------------------
    elseif (!empty($data['IsStateApprove'])) {

        $filter_conditions .= "
            AND pd.IsStateApprove = '" . trim($data['IsStateApprove']) . "'
        ";
    }

    // =========================================================
    // OPTIONAL FILTERS
    // =========================================================

    // ---------------------------------------------------------
    // STATUS FILTER
    // ---------------------------------------------------------
    if (!empty($data['Status'])) {

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

    // ---------------------------------------------------------
    // PAYMENT TYPE FILTER
    // ---------------------------------------------------------
    if (!empty($data['PaymentType'])) {

        $filter_conditions .= "
            AND pd.PaymentType = '" . trim($data['PaymentType']) . "'
        ";
    }

    // =========================================================
    // SEARCH FILTER
    // =========================================================

    $filter_search = "";

    if (!empty($data['search_term'])) {

        $search_term = trim($data['search_term']);

        $filter_search = "
            AND (
                ct.TicketID LIKE '%$search_term%'
                OR bm.BranchCity LIKE '%$search_term%'
                OR bm.BranchState LIKE '%$search_term%'
                OR pd.Store LIKE '%$search_term%'
                OR pd.PaymentType LIKE '%$search_term%'
                OR pd.Status LIKE '%$search_term%'
            )
        ";
    }

    // =========================================================
    // PAGINATION
    // =========================================================

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

    // =========================================================
    // MAIN QUERY
    // =========================================================

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

    // =========================================================
    // COUNT QUERY
    // =========================================================

    $count_sql = "
        SELECT 
            COUNT(pd.ID) as total_records

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

    $response['error'] = false;

    $response['message'] =
        "Payments fetched successfully";

    return $response;
}

// =========================================================
// EXECUTE API
// =========================================================

$conn = _connectodb();

$response = getApprovalWisePayments($conn, $data);

echo json_encode($response);

?>