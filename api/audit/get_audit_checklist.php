<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/corporate-audit/controller/corporate_audit_controller.php');
require_once('audit_helpers.php');

$data = audit_api_parse_input();
$response = array();
$response['data'] = array();

$conn = _connectodb();
$subAuditId = isset($data['sub_audit_id']) ? (int) $data['sub_audit_id'] : 0;
$masterAuditId = isset($data['master_audit_id']) ? (int) $data['master_audit_id'] : 0;
$fetchAll = isset($data['all']) && (int) $data['all'] === 1;
$activeOnly = audit_api_is_active_only($data);

if ($subAuditId <= 0 && $masterAuditId <= 0 && !$fetchAll) {
    $response['error'] = true;
    $response['message'] = 'sub_audit_id, master_audit_id, or all=1 is required.';
    echo json_encode($response);
    exit;
}

$masterMap = corporateAuditMasterAuditNameMap($conn);
$subMap = corporateAuditSubAuditNameMap($conn);
$rows = array();

if ($subAuditId > 0) {
    $rows = getCorporateAuditChecklists($conn, $subAuditId, $activeOnly);
} elseif ($masterAuditId > 0) {
    $subs = getAllCorporateMasterSubAudits($conn, $masterAuditId, $activeOnly);
    foreach ($subs as $sub) {
        $checklists = getCorporateAuditChecklists($conn, (int) $sub['ID'], $activeOnly);
        foreach ($checklists as $cp) {
            $rows[] = $cp;
        }
    }
} else {
    $rows = getCorporateAuditChecklists($conn, 0, $activeOnly);
}

foreach ($rows as $row) {
    $sid = (int) $row['SubAuditID'];
    $sub = getCorporateMasterSubAuditById($conn, $sid);
    $mid = !empty($sub) ? (int) $sub['MasterAuditID'] : 0;
    $response['data'][] = audit_api_format_checklist($row, array(
        'master_audit_id' => $mid,
        'master_audit_name' => isset($masterMap[$mid]) ? $masterMap[$mid] : '',
        'sub_audit_name' => isset($subMap[$sid]) ? $subMap[$sid] : '',
    ));
}

$response['error'] = false;
$response['message'] = 'Audit checklist fetched.';
$response['sub_audit_id'] = $subAuditId;
$response['master_audit_id'] = $masterAuditId;
$response['total_records'] = count($response['data']);
echo json_encode($response);
