<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/branch/controller/branch_controller.php');
require_once('../../admin/audit-ticket/controller/audit_ticket_controller.php');
require_once('audit_ticket_helpers.php');

setTimeZone();
$data = audit_ticket_api_parse_input();
$conn = _connectodb();

$hasSingleAudit = isset($data['MasterAuditID']) && isset($data['SubAuditID']);
$hasMultiAudit = isset($data['SubAuditIDs']) || isset($data['Audits']);

if (!isset($data['CorporateID']) || !isset($data['BranchID']) || (!$hasSingleAudit && !$hasMultiAudit)) {
    audit_ticket_api_response(
        true,
        'CorporateID, BranchID and at least one audit are required. Use SubAuditID + MasterAuditID (single), SubAuditIDs[] (multiple), or Audits[] (multiple with master/sub pairs).'
    );
}

$branchDetails = null;
if ((int) $data['BranchID'] > 0) {
    $branchDetails = GetBranchDetailsbyID($conn, (int) $data['BranchID']);
}

if (!isset($data['CreatedBy'])) {
    $data['CreatedBy'] = 'Mobile API';
}

$response = createCorporateAuditTicket($conn, $data, $branchDetails);
echo json_encode($response);
