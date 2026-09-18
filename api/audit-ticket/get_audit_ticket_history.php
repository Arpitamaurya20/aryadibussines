<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/audit-ticket/controller/audit_ticket_controller.php');
require_once('audit_ticket_helpers.php');

setTimeZone();
$data = audit_ticket_api_parse_input();
$conn = _connectodb();

$ticketId = 0;
if (isset($data['AuditTicketID'])) {
    $ticketId = (int) $data['AuditTicketID'];
}
if (isset($data['id'])) {
    $ticketId = (int) $data['id'];
}

if ($ticketId <= 0) {
    audit_ticket_api_response(true, 'AuditTicketID is required.');
}

$historyRows = getAuditTicketStatusHistory($conn, $ticketId);
$list = formatAuditTicketHistoryForApi($conn, $historyRows);

echo json_encode(array(
    'error' => false,
    'message' => 'Audit ticket status history fetched.',
    'total_records' => count($list),
    'data' => $list,
));
