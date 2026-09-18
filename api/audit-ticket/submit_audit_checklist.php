<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/audit-ticket/controller/audit_ticket_controller.php');
require_once('audit_ticket_helpers.php');

setTimeZone();
$data = audit_ticket_api_parse_input();
$conn = _connectodb();

$response = submitCorporateAuditChecklistResponses($conn, $data);
echo json_encode($response);
