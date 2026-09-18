<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/corporate-audit/controller/corporate_audit_controller.php');
require_once('audit_helpers.php');

$data = audit_api_parse_input();
$response = array();
$response['data'] = array();

$conn = _connectodb();
$baseUrl = audit_api_base_url();
$masterAuditId = isset($data['master_audit_id']) ? (int) $data['master_audit_id'] : 0;
$activeOnly = audit_api_is_active_only($data);
$masterMap = corporateAuditMasterAuditNameMap($conn);

$rows = getAllCorporateMasterSubAudits($conn, $masterAuditId, $activeOnly);
foreach ($rows as $row) {
    $subId = (int) $row['ID'];
    $masterId = (int) $row['MasterAuditID'];
    $where = ' WHERE SubAuditID = ' . $subId;
    if ($activeOnly) {
        $where .= ' AND IsActive = 1';
    }
    $checklistCount = (int) _getTotalRows($conn, 'corporate_audit_checklist', $where);
    $response['data'][] = audit_api_format_sub_audit($row, $baseUrl, isset($masterMap[$masterId]) ? $masterMap[$masterId] : '', array(
        'checklist_count' => $checklistCount,
    ));
}

$response['error'] = false;
$response['message'] = 'Sub audit list fetched.';
$response['master_audit_id'] = $masterAuditId;
$response['total_records'] = count($response['data']);
echo json_encode($response);
