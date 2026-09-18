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
$activeOnly = audit_api_is_active_only($data);

$rows = getAllCorporateMasterAudits($conn, $activeOnly);
foreach ($rows as $row) {
    $auditId = (int) $row['ID'];
    $subs = getAllCorporateMasterSubAudits($conn, $auditId, $activeOnly);
    $checklistCount = 0;
    foreach ($subs as $sub) {
        $where = ' WHERE SubAuditID = ' . (int) $sub['ID'];
        if ($activeOnly) {
            $where .= ' AND IsActive = 1';
        }
        $checklistCount += (int) _getTotalRows($conn, 'corporate_audit_checklist', $where);
    }
    $response['data'][] = audit_api_format_audit($row, $baseUrl, array(
        'sub_audit_count' => count($subs),
        'checklist_count' => $checklistCount,
    ));
}

$response['error'] = false;
$response['message'] = 'Master audit list fetched.';
$response['total_records'] = count($response['data']);
echo json_encode($response);
