<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/corporate-audit/controller/corporate_audit_controller.php');
require_once('audit_helpers.php');

$data = audit_api_parse_input();
$response = array();

$conn = _connectodb();
$baseUrl = audit_api_base_url();
$activeOnly = audit_api_is_active_only($data);

$hierarchy = getCorporateAuditHierarchy($conn, $activeOnly, $baseUrl);
$audits = array();
$totalSub = 0;
$totalChecklist = 0;

foreach ($hierarchy as $audit) {
    $auditItem = audit_api_format_audit($audit, $baseUrl);
    $subList = array();
    $auditChecklistCount = 0;

    foreach ($audit['sub_audits'] as $sub) {
        $checklistItems = array();
        foreach ($sub['checklists'] as $cp) {
            $checklistItems[] = audit_api_format_checklist($cp, array(
                'master_audit_id' => (int) $audit['ID'],
                'master_audit_name' => $audit['AuditName'],
                'sub_audit_name' => $sub['SubAuditName'],
            ));
        }
        $cc = count($checklistItems);
        $auditChecklistCount += $cc;
        $totalChecklist += $cc;
        $subList[] = audit_api_format_sub_audit($sub, $baseUrl, $audit['AuditName'], array(
            'checklist_count' => $cc,
            'checklists' => $checklistItems,
        ));
    }

    $totalSub += count($subList);
    $auditItem['sub_audit_count'] = count($subList);
    $auditItem['checklist_count'] = $auditChecklistCount;
    $auditItem['sub_audits'] = $subList;
    $audits[] = $auditItem;
}

$response['error'] = false;
$response['message'] = 'Corporate audit mobile data fetched.';
$response['api_version'] = '1.0';
$response['data'] = array(
    'audits' => $audits,
    'summary' => array(
        'master_audit_count' => count($audits),
        'sub_audit_count' => $totalSub,
        'checklist_count' => $totalChecklist,
    ),
);
$response['total_records'] = count($audits);
echo json_encode($response);
