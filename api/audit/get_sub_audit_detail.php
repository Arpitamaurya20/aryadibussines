<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/corporate-audit/controller/corporate_audit_controller.php');
require_once('audit_helpers.php');

$data = audit_api_parse_input();
$response = array();

$conn = _connectodb();
$baseUrl = audit_api_base_url();
$subAuditId = isset($data['sub_audit_id']) ? (int) $data['sub_audit_id'] : (isset($data['id']) ? (int) $data['id'] : 0);
$activeOnly = audit_api_is_active_only($data);

if ($subAuditId <= 0) {
    $response['error'] = true;
    $response['message'] = 'sub_audit_id is required.';
    echo json_encode($response);
    exit;
}

$sub = getCorporateMasterSubAuditById($conn, $subAuditId);
if (empty($sub) || ($activeOnly && (int) $sub['IsActive'] !== 1)) {
    $response['error'] = true;
    $response['message'] = 'Sub audit not found.';
    echo json_encode($response);
    exit;
}

$masterAuditId = (int) $sub['MasterAuditID'];
$master = getCorporateMasterAuditById($conn, $masterAuditId);
$masterName = !empty($master) ? $master['AuditName'] : '';

$checklists = getCorporateAuditChecklists($conn, $subAuditId, $activeOnly);
$checklistItems = array();
foreach ($checklists as $cp) {
    $checklistItems[] = audit_api_format_checklist($cp, array(
        'master_audit_id' => $masterAuditId,
        'master_audit_name' => $masterName,
        'sub_audit_name' => $sub['SubAuditName'],
    ));
}

$response['error'] = false;
$response['message'] = 'Sub audit detail with dynamic checklist fetched.';
$response['sub_audit_id'] = $subAuditId;
$response['checklist_count'] = count($checklistItems);
$response['data'] = array(
    'master_audit' => !empty($master) ? audit_api_format_audit($master, $baseUrl) : null,
    'sub_audit' => audit_api_format_sub_audit($sub, $baseUrl, $masterName, array(
        'checklist_count' => count($checklistItems),
    )),
    'checklists' => $checklistItems,
    'dynamic_form' => array(
        'sub_audit_id' => $subAuditId,
        'fields' => $checklistItems,
    ),
);
echo json_encode($response);
