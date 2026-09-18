<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

function getStateWisePayments($conn, $data)
{
    $response = array();
    $response['data'] = array();

    if (!isset($data['StateName']) || empty($data['StateName'])) {
        return [
            "error" => true,
            "message" => "StateName is required"
        ];
    }

    // 🔹 MULTIPLE STATE SUPPORT
    if (is_array($data['StateName'])) {

        $states = array_map('trim', $data['StateName']);
        $states = array_map(function($state){
            return "'" . $state . "'";
        }, $states);

        $state_condition = " AND bm.BranchState IN (" . implode(",", $states) . ") ";

    } else {

        $StateName = trim($data['StateName']);
        $state_condition = " AND bm.BranchState = '$StateName' ";
    }

    // 🔹 Dynamic Filters
    $filter_conditions = "";

    if (!empty($data['IsStateApprove'])) {
        $filter_conditions .= " AND pd.IsStateApprove = '" . trim($data['IsStateApprove']) . "' ";
    }

    if (!empty($data['IsFinanceApprove'])) {
        $filter_conditions .= " AND pd.IsFinanceApprove = '" . trim($data['IsFinanceApprove']) . "' ";
    }

    if (!empty($data['Status'])) {
        $filter_conditions .= " AND pd.Status = '" . trim($data['Status']) . "' ";
    }

    if (!empty($data['PaymentType'])) {
        $filter_conditions .= " AND pd.PaymentType = '" . trim($data['PaymentType']) . "' ";
    }

    // 🔹 Pagination
    $filter_limit = "";
    if (isset($data['start_counter']) && isset($data['no_of_records'])) {
        $start_counter = (int)$data['start_counter'];
        $no_of_records = (int)$data['no_of_records'];
        $filter_limit = " LIMIT $start_counter, $no_of_records ";
    }

    // 🔹 Search
    $filter_search = "";
    if (!empty($data['search_term'])) {
        $search_term = trim($data['search_term']);
        $filter_search = "
            AND (
                ct.TicketID LIKE '%$search_term%'
                OR bm.BranchCity LIKE '%$search_term%'
                OR pd.Store LIKE '%$search_term%'
            )
        ";
    }

    // 🔹 Main Query
    $sql = "
        SELECT 
            pd.*,
            ct.TicketID AS TicketNumber,
            bm.BranchCity,
            bm.BranchState
        FROM corporate_tickets_payment_details pd
        INNER JOIN corporate_tickets ct ON pd.TicketID = ct.ID
        INNER JOIN branch bm ON ct.BranchID = bm.ID
        WHERE pd.IsActive = 1
        $state_condition
        $filter_conditions
        $filter_search
        ORDER BY pd.ID DESC
        $filter_limit
    ";

    $response['data'] = _getSQLRecords($conn, $sql);

    // 🔹 Count Query
    $count_sql = "
        SELECT COUNT(pd.ID) as total_records
        FROM corporate_tickets_payment_details pd
        INNER JOIN corporate_tickets ct ON pd.TicketID = ct.ID
        INNER JOIN branch bm ON ct.BranchID = bm.ID
        WHERE pd.IsActive = 1
        $state_condition
        $filter_conditions
        $filter_search
    ";

    $count_result = _getSQLRecords($conn, $count_sql);
    $response['total_records'] = $count_result[0]['total_records'] ?? 0;

    $response['error'] = false;
    $response['message'] = "Payments fetched successfully";

    return $response;
}

if (isset($data['StateName'])) {

    $conn = _connectodb();
    $response = getStateWisePayments($conn, $data);

} else {

    $response['error'] = true;
    $response['message'] = "StateName is required";

}

echo json_encode($response);
?>