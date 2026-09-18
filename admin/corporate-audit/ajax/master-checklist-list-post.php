<?php
session_start();
include('../../controllers/common_controllers.php');
include('../controller/corporate_audit_controller.php');

SessionCheck();
$conn = _connectodb();

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
$filterSubAuditId = isset($_POST['filter_sub_audit_id']) ? (int) $_POST['filter_sub_audit_id'] : 0;
$filterMasterId = isset($_POST['filter_master_audit_id']) ? (int) $_POST['filter_master_audit_id'] : 0;
$filterFieldType = isset($_POST['filter_field_type']) ? trim($_POST['filter_field_type']) : '';
$filterMandatory = isset($_POST['filter_is_mandatory']) ? $_POST['filter_is_mandatory'] : '-1';
$filterStatus = isset($_POST['filter_status']) ? $_POST['filter_status'] : '-1';

$subMap = corporateAuditSubAuditNameMap($conn);
$fieldTypes = corporateAuditFieldTypes();

$baseWhere = ' WHERE 1=1';
if ($filterSubAuditId > 0) {
    $baseWhere .= ' AND SubAuditID = ' . $filterSubAuditId;
} elseif ($filterMasterId > 0) {
    $subIds = array();
    $subs = getAllCorporateMasterSubAudits($conn, $filterMasterId, false);
    foreach ($subs as $sub) {
        $subIds[] = (int) $sub['ID'];
    }
    if (!empty($subIds)) {
        $baseWhere .= ' AND SubAuditID IN (' . implode(',', $subIds) . ')';
    } else {
        $baseWhere .= ' AND 1=0';
    }
}
if ($filterFieldType !== '' && isset($fieldTypes[$filterFieldType])) {
    $fieldTypeEsc = mysqli_real_escape_string($conn, $filterFieldType);
    $baseWhere .= " AND FieldType = '$fieldTypeEsc'";
}
if ($filterMandatory !== '' && $filterMandatory !== '-1') {
    $baseWhere .= ' AND IsMandatory = ' . (int) $filterMandatory;
}
if ($filterStatus !== '' && $filterStatus !== '-1') {
    $baseWhere .= ' AND IsActive = ' . (int) $filterStatus;
}

$totalRecords = _getTotalRows($conn, 'corporate_audit_checklist', $baseWhere);

$filterWhere = $baseWhere;
if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $filterWhere .= " AND (CheckpointName LIKE '%$searchEsc%' OR CheckpointDescription LIKE '%$searchEsc%' OR IdealValue LIKE '%$searchEsc%' OR Unit LIKE '%$searchEsc%')";
}
$totalFiltered = _getTotalRows($conn, 'corporate_audit_checklist', $filterWhere);

$sql = "SELECT * FROM corporate_audit_checklist $filterWhere ORDER BY SortOrder ASC, CheckpointName ASC LIMIT $start, $length";
$result = mysqli_query($conn, $sql);
$data = array();

while ($row = mysqli_fetch_assoc($result)) {
    $status = ((int) $row['IsActive'] === 1)
        ? '<span class="badge badge-success">Active</span>'
        : '<span class="badge badge-danger">Inactive</span>';
    $mandatory = ((int) $row['IsMandatory'] === 1)
        ? '<span class="badge badge-primary">Yes</span>'
        : '<span class="badge badge-secondary">No</span>';

    $subName = isset($subMap[(int) $row['SubAuditID']]) ? $subMap[(int) $row['SubAuditID']] : '-';
    $fieldLabel = isset($fieldTypes[$row['FieldType']]) ? $fieldTypes[$row['FieldType']] : $row['FieldType'];

    $action = '<button class="btn btn-xs btn-warning" onclick="editChecklist(' . (int) $row['ID'] . ')"><i class="fal fa-edit"></i> Edit</button> ';
    $action .= '<button class="btn btn-xs btn-danger" onclick="deleteChecklist(' . (int) $row['ID'] . ')"><i class="fal fa-trash"></i> Delete</button>';

    $idealDisplay = htmlspecialchars($row['IdealValue']);
    if ($row['Unit'] !== '') {
        $idealDisplay .= ' <small class="text-muted">' . htmlspecialchars($row['Unit']) . '</small>';
    }

    $data[] = array(
        'id' => $row['ID'],
        'SubAudit' => htmlspecialchars($subName),
        'CheckpointName' => htmlspecialchars($row['CheckpointName']),
        'FieldType' => htmlspecialchars($fieldLabel),
        'IdealValue' => $idealDisplay,
        'IsMandatory' => $mandatory,
        'SortOrder' => (int) $row['SortOrder'],
        'IsActive' => $status,
        'Action' => $action,
    );
}

echo json_encode(array(
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalFiltered,
    'data' => $data,
));
