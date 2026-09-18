<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/corporate-audit/controller/corporate_audit_controller.php');
require_once('audit_helpers.php');

$data = audit_api_parse_input();
$response = array();

$conn = _connectodb();
$baseUrl = audit_api_base_url();
$masterAuditId = isset($data['master_audit_id']) ? (int) $data['master_audit_id'] : (isset($data['id']) ? (int) $data['id'] : 0);
$activeOnly = audit_api_is_active_only($data);

if ($masterAuditId <= 0) {
    $response['error'] = true;
    $response['message'] = 'master_audit_id is required.';
    echo json_encode($response);
    exit;
}

$audit = getCorporateMasterAuditById($conn, $masterAuditId);
if (empty($audit) || ($activeOnly && (int) $audit['IsActive'] !== 1)) {
    $response['error'] = true;
    $response['message'] = 'Master audit not found.';
    echo json_encode($response);
    exit;
}

$masterName = $audit['AuditName'];
$subAudits = getAllCorporateMasterSubAudits($conn, $masterAuditId, $activeOnly);
$subList = array();
$checklistTotal = 0;

foreach ($subAudits as $sub) {
    $checklists = getCorporateAuditChecklists($conn, (int) $sub['ID'], $activeOnly);
    $checklistItems = array();
    foreach ($checklists as $cp) {
        $checklistItems[] = audit_api_format_checklist($cp, array(
            'master_audit_id' => $masterAuditId,
            'master_audit_name' => $masterName,
            'sub_audit_name' => $sub['SubAuditName'],
        ));
    }
    $cc = count($checklistItems);
    $checklistTotal += $cc;
    $subList[] = audit_api_format_sub_audit($sub, $baseUrl, $masterName, array(
        'checklist_count' => $cc,
        'checklists' => $checklistItems,
    ));
}

$detail = audit_api_format_audit($audit, $baseUrl, array(
    'sub_audit_count' => count($subList),
    'checklist_count' => $checklistTotal,
    'sub_audits' => $subList,
));

$response['error'] = false;
$response['message'] = 'Master audit detail fetched.';
$response['master_audit_id'] = $masterAuditId;
$response['data'] = $detail;
echo json_encode($response);
