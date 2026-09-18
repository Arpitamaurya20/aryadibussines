<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/audit-ticket/controller/audit_ticket_controller.php');
require_once('audit_ticket_helpers.php');

setTimeZone();
$data = audit_ticket_api_parse_input();
$conn = _connectodb();

$ticketId = (int) (isset($data['AuditTicketID']) ? $data['AuditTicketID'] : (isset($data['id']) ? $data['id'] : 0));
if ($ticketId <= 0) {
    audit_ticket_api_response(true, 'AuditTicketID is required.');
}

if (!empty($data['save_report_meta']) || !empty($data['ReportFloor']) || !empty($data['report_floor'])) {
    saveAuditTicketReportMeta($conn, $data);
}

$response = generateCorporateAuditBranchReportPdf($conn, $ticketId);
echo json_encode($response);
