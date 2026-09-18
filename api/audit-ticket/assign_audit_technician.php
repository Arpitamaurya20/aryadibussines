<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/audit-ticket/controller/audit_ticket_controller.php');
require_once('audit_ticket_helpers.php');

setTimeZone();
$data = audit_ticket_api_parse_input();
$conn = _connectodb();

if (!isset($data['AuditTicketID']) || !isset($data['TechnicianID'])) {
    audit_ticket_api_response(true, 'AuditTicketID and TechnicianID are required.');
}

$session = array('pb_username' => isset($data['UpdatedBy']) ? $data['UpdatedBy'] : 'Mobile API');
if (isset($data['EmployeeID'])) {
    $session['Roles'] = array('EmployeeID' => (int) $data['EmployeeID']);
}
if (isset($data['IsReassign'])) {
    $data['is_reassign'] = (int) $data['IsReassign'];
}

$response = auditTicketAssignToTechnician($conn, $data, $session);
echo json_encode($response);
