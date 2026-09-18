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

$ticket = $ticketId > 0 ? getCorporateAuditTicketById($conn, $ticketId) : null;
if (!$ticket) {
    audit_ticket_api_response(true, 'Audit ticket not found.');
}

$subAuditFilter = (int) (isset($data['SubAuditID']) ? $data['SubAuditID'] : (isset($data['sub_audit_id']) ? $data['sub_audit_id'] : 0));
$checklistItems = getCorporateAuditTicketChecklistWithResponses($conn, $ticketId, $subAuditFilter);
$groupedChecklists = getAuditTicketChecklistGroupedBySubAudit($conn, $ticketId);
$audits = formatAuditTicketAuditsForApi($conn, $ticketId);

$mobileFields = array();
foreach ($checklistItems as $ci) {
    $mobileFields[] = audit_ticket_format_checklist_field_for_mobile($ci['checklist'], $ci)['mobile_form'];
}

$subAuditForms = array();
foreach ($groupedChecklists as $group) {
    $groupMobileFields = array();
    foreach ($group['items'] as $ci) {
        $groupMobileFields[] = audit_ticket_format_checklist_field_for_mobile($ci['checklist'], $ci)['mobile_form'];
    }
    $subAuditForms[] = array(
        'sub_audit_id' => (int) $group['sub_audit_id'],
        'sub_audit_name' => $group['sub_audit_name'],
        'master_audit_id' => (int) $group['master_audit_id'],
        'master_audit_name' => $group['master_audit_name'],
        'status' => $group['status'],
        'checklist_summary' => $group['summary'],
        'mobile_checklist_form' => array(
            'audit_ticket_id' => $ticketId,
            'sub_audit_id' => (int) $group['sub_audit_id'],
            'fields' => $groupMobileFields,
        ),
    );
}

echo json_encode(array(
    'error' => false,
    'message' => 'Mobile checklist form loaded.',
    'audit_ticket_id' => $ticketId,
    'ticket_id' => $ticket['TicketID'],
    'sub_audit_id' => (int) $ticket['SubAuditID'],
    'audit_count' => count($audits),
    'is_multi_audit' => count($audits) > 1 ? 1 : 0,
    'audits' => $audits,
    'instructions' => count($audits) > 1
        ? 'This ticket includes multiple audits. Use sub_audit_id when submitting each audit checklist separately, or submit all checkpoints together.'
        : 'For each checkpoint: fill Value, select OK or Not OK, and add Remarks (required when Not OK).',
    'total_fields' => count($mobileFields),
    'data' => array(
        'mobile_checklist_form' => array(
            'audit_ticket_id' => $ticketId,
            'audit_count' => count($audits),
            'fields' => $mobileFields,
            'sub_audits' => $subAuditForms,
        ),
        'checklists_by_sub_audit' => $subAuditForms,
    ),
));
