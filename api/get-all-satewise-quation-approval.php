<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

function getStateWiseQuotationTickets($conn, $data)
{
    $response = array();
    $response['data'] = array();

    $StateName = trim($data['StateName']);
    $QuotationStatus = trim($data['QuotationStatus']);

    // 🔹 Pagination
    $filter_limit = "";
    if (isset($data['start_counter']) && isset($data['no_of_records'])) {
        $start_counter = (int)$data['start_counter'];
        $no_of_records = (int)$data['no_of_records'];
        $filter_limit = " LIMIT $start_counter, $no_of_records ";
    }

    // 🔹 Search Filter
    $filter_search = "";
    if (isset($data['search_term']) && $data['search_term'] != "") {
        $search_term = trim($data['search_term']);
        $filter_search = " 
            AND (
                ct.TicketID LIKE '%$search_term%' 
                OR bm.BranchCity LIKE '%$search_term%'
            )
        ";
    }

    // 🔹 Main Query
    $sql = "
        SELECT 
            ct.*, 
            qt.QuotationStatus,
            qt.ID AS QuotationID,
            bm.BranchCity
        FROM corporate_tickets ct
        INNER JOIN branch bm 
            ON ct.BranchID = bm.ID
        INNER JOIN corporate_ticket_quotation qt 
            ON ct.ID = qt.TicketID
        WHERE bm.BranchState = '$StateName'
        AND qt.QuotationStatus = '$QuotationStatus'
        AND ct.IsActive = 1
        $filter_search
        ORDER BY ct.ID DESC
        $filter_limit
    ";

    $response['data'] = _getSQLRecords($conn, $sql);

    // 🔹 Get Total Count (Without Limit)
    $count_sql = "
        SELECT COUNT(ct.ID) as total_records
        FROM corporate_tickets ct
        INNER JOIN branch bm 
            ON ct.BranchID = bm.ID
        INNER JOIN corporate_ticket_quotation qt 
            ON ct.ID = qt.TicketID
        WHERE bm.BranchState = '$StateName'
        AND qt.QuotationStatus = '$QuotationStatus'
        AND ct.IsActive = 1
        $filter_search
    ";

    $count_result = _getSQLRecords($conn, $count_sql);

    $response['total_records'] = $count_result[0]['total_records'] ?? 0;
    $response['error'] = false;
    $response['message'] = "Tickets fetched successfully";

    return $response;
}

if (
    isset($data['StateName']) &&
    isset($data['QuotationStatus'])
) {
    $conn = _connectodb();
    $response = getStateWiseQuotationTickets($conn, $data);
} else {
    $response['error'] = true;
    $response['message'] = "StateName and QuotationStatus required";
}

echo json_encode($response);

?>
