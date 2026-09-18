<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/audit-ticket/controller/audit_ticket_controller.php');
require_once('audit_ticket_helpers.php');

header('Content-Type: application/json; charset=utf-8');

setTimeZone();
$data = audit_ticket_api_parse_input();
$conn = _connectodb();

$ticketId = 0;
$ticketDisplayId = '';
if (isset($data['AuditTicketID'])) {
    $ticketId = (int) $data['AuditTicketID'];
}
if (isset($data['id'])) {
    $ticketId = (int) $data['id'];
}
if (isset($data['TicketID'])) {
    $ticketDisplayId = trim((string) $data['TicketID']);
}

$ticket = null;
if ($ticketId > 0) {
    $ticket = getCorporateAuditTicketById($conn, $ticketId);
} elseif ($ticketDisplayId !== '') {
    $ticket = getCorporateAuditTicketById($conn, $ticketDisplayId, true);
}

if (!$ticket) {
    audit_ticket_api_response(true, 'Audit ticket not found.');
}

try {
    $baseUrl = defined('FRONT_SITE_PATH') ? rtrim(FRONT_SITE_PATH, '/') : '';
    $formatted = formatAuditTicketForApi($conn, $ticket, true, $baseUrl);

    $jsonFlags = defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0;
    echo json_encode(array(
        'error' => false,
        'message' => 'Audit ticket detail fetched.',
        'total_records' => 1,
        'data' => $formatted,
    ), $jsonFlags);
} catch (Throwable $e) {
    audit_ticket_api_response(true, 'Unable to load audit ticket detail.', array(
        'detail' => $e->getMessage(),
    ));
}
