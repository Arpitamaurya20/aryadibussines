<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('hr_ticket_helpers.php');

header('Content-Type: application/json; charset=utf-8');
setTimeZone();

$data = hr_ticket_api_parse_input();
$conn = _connectodb();

$employeeId = isset($data['EmployeeID']) ? (int) $data['EmployeeID'] : 0;
$ticketId = hr_ticket_api_resolve_ticket_id($conn, $data);
if ($ticketId < 1) {
    hr_ticket_api_response(true, 'HrTicketID or TicketID is required.');
}

$hr = new Hrticket($conn);
$ticket = $hr->getTicketById($ticketId);
if ($employeeId > 0) {
    hr_ticket_api_assert_ticket_access($conn, $ticket, $employeeId);
}

$history = $hr->getTicketHistory($ticketId);
$list = array();
foreach ($history as $h) {
    $list[] = hr_ticket_api_format_history($h);
}

echo json_encode(array(
    'error' => false,
    'message' => 'HR ticket history fetched.',
    'total_records' => count($list),
    'HrTicketID' => $ticketId,
    'data' => $list,
));
