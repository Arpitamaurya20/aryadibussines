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

if (!hr_ticket_api_employee_has_hr_access($conn, $employeeId)) {
    hr_ticket_api_response(true, 'Access denied. HR role required.');
}

$hr = new Hrticket($conn);
$ticket = $hr->getTicketById($ticketId);
if (!$ticket) {
    hr_ticket_api_response(true, 'Ticket not found.');
}

$oldStatus = (string) ($ticket['status'] ?? '');
$oldAssigned = (int) ($ticket['assigned_to'] ?? 0);

$updateData = array(
    'assigned_to' => isset($data['AssignedTo']) || isset($data['assigned_to'])
        ? (int) ($data['AssignedTo'] ?? $data['assigned_to'])
        : (int) ($ticket['assigned_to'] ?? 0),
);
if (isset($data['Status']) || isset($data['status'])) {
    $updateData['status'] = strtolower(trim((string) ($data['Status'] ?? $data['status'])));
} else {
    $updateData['status'] = (string) ($ticket['status'] ?? 'open');
}
if (!isset($data['AssignedTo']) && !isset($data['assigned_to']) && !isset($data['Status']) && !isset($data['status'])) {
    hr_ticket_api_response(true, 'AssignedTo and/or Status is required.');
}

$updatedBy = hr_ticket_api_created_by_label($conn, $employeeId);
$result = $hr->updateAssignmentAndStatus($ticketId, $updateData, $updatedBy);

if (!empty($result['error'])) {
    hr_ticket_api_response(true, $result['message'] ?? 'Update failed.');
}

$newStatus = (string) ($result['new_status'] ?? $oldStatus);
$newAssigned = (int) ($result['new_assigned'] ?? $oldAssigned);
$updatedTicket = $result['ticket'] ?? $hr->getTicketById($ticketId);
hr_ticket_notify_on_update($conn, $updatedTicket, $oldStatus, $newStatus, $oldAssigned, $newAssigned, $updatedBy);

$baseUrl = hr_ticket_api_base_url();
echo json_encode(array(
    'error' => false,
    'message' => $result['message'] ?? 'Ticket updated.',
    'HrTicketID' => $ticketId,
    'data' => $updatedTicket ? hr_ticket_api_format_ticket($conn, $updatedTicket, true, $baseUrl, $employeeId) : null,
));
