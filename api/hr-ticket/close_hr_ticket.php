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
if ($employeeId < 1 || $ticketId < 1) {
    hr_ticket_api_response(true, 'EmployeeID and HrTicketID are required.');
}

$hr = new Hrticket($conn);
$ticket = $hr->getTicketById($ticketId);
if (!$ticket) {
    hr_ticket_api_response(true, 'Ticket not found.');
}
if ((int) ($ticket['employee_id'] ?? 0) !== $employeeId) {
    hr_ticket_api_response(true, 'Only the ticket owner can close a resolved ticket.');
}

$updatedBy = hr_ticket_api_created_by_label($conn, $employeeId);
$result = $hr->closeByEmployee($ticketId, $employeeId, $updatedBy);

if (!empty($result['error'])) {
    hr_ticket_api_response(true, $result['message'] ?? 'Could not close ticket.');
}

$updatedTicket = $hr->getTicketById($ticketId);
if ($updatedTicket) {
    hr_ticket_notify_on_update($conn, $updatedTicket, 'resolved', 'closed', (int) ($ticket['assigned_to'] ?? 0), (int) ($ticket['assigned_to'] ?? 0), $updatedBy);
}

$baseUrl = hr_ticket_api_base_url();
echo json_encode(array(
    'error' => false,
    'message' => $result['message'] ?? 'Ticket closed.',
    'HrTicketID' => $ticketId,
    'data' => $updatedTicket ? hr_ticket_api_format_ticket($conn, $updatedTicket, true, $baseUrl, $employeeId) : null,
));
