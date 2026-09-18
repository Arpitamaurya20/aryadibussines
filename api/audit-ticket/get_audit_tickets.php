<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/audit-ticket/controller/audit_ticket_controller.php');
require_once('audit_ticket_helpers.php');

setTimeZone();
$data = audit_ticket_api_parse_input();
$conn = _connectodb();

$filters = array();
if (isset($data['EmployeeID'])) {
    $filters['EmployeeID'] = (int) $data['EmployeeID'];
}
if (isset($data['CorporateID'])) {
    $filters['CorporateID'] = (int) $data['CorporateID'];
}
if (isset($data['BranchID'])) {
    $filters['BranchID'] = (int) $data['BranchID'];
}
if (isset($data['Status'])) {
    $filters['Status'] = $data['Status'];
}

if (isset($data['view_role'])) {
    $filters['view_role'] = $data['view_role'];
}
if (isset($data['ViewRole'])) {
    $filters['view_role'] = $data['ViewRole'];
}

$rows = getCorporateAuditTicketsForMobile($conn, $filters);
$baseUrl = defined('FRONT_SITE_PATH') ? rtrim(FRONT_SITE_PATH, '/') : '';
$list = array();
foreach ($rows as $row) {
    $list[] = formatAuditTicketForApi($conn, $row, false, $baseUrl);
}

echo json_encode(array(
    'error' => false,
    'message' => 'Audit tickets fetched.',
    'total_records' => count($list),
    'data' => $list,
));
