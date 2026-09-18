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
$filterMasterId = isset($_POST['filter_master_audit_id']) ? (int) $_POST['filter_master_audit_id'] : 0;
$filterStatus = isset($_POST['filter_status']) ? $_POST['filter_status'] : '-1';

$masterMap = corporateAuditMasterAuditNameMap($conn);

$baseWhere = ' WHERE 1=1';
if ($filterMasterId > 0) {
    $baseWhere .= ' AND MasterAuditID = ' . $filterMasterId;
}
if ($filterStatus !== '' && $filterStatus !== '-1') {
    $baseWhere .= ' AND IsActive = ' . (int) $filterStatus;
}
$totalRecords = _getTotalRows($conn, 'corporate_master_sub_audit', $baseWhere);

$filterWhere = $baseWhere;
if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $filterWhere .= " AND (SubAuditName LIKE '%$searchEsc%' OR SubAuditDescription LIKE '%$searchEsc%' OR IconClass LIKE '%$searchEsc%')";
}
$totalFiltered = _getTotalRows($conn, 'corporate_master_sub_audit', $filterWhere);

$sql = "SELECT * FROM corporate_master_sub_audit $filterWhere ORDER BY SortOrder ASC, SubAuditName ASC LIMIT $start, $length";
$result = mysqli_query($conn, $sql);
$data = array();

while ($row = mysqli_fetch_assoc($result)) {
    corporateAuditFormatRecordIcons($row);
    $status = ((int) $row['IsActive'] === 1)
        ? '<span class="badge badge-success">Active</span>'
        : '<span class="badge badge-danger">Inactive</span>';

    $masterName = isset($masterMap[(int) $row['MasterAuditID']]) ? $masterMap[(int) $row['MasterAuditID']] : '-';

    $action = '<button class="btn btn-xs btn-warning" onclick="editMasterSubAudit(' . (int) $row['ID'] . ')"><i class="fal fa-edit"></i> Edit</button> ';
    $action .= '<button class="btn btn-xs btn-danger" onclick="deleteMasterSubAudit(' . (int) $row['ID'] . ')"><i class="fal fa-trash"></i> Delete</button>';

    $data[] = array(
        'id' => $row['ID'],
        'Icon' => $row['icon_display'],
        'MasterAudit' => htmlspecialchars($masterName),
        'SubAuditName' => htmlspecialchars($row['SubAuditName']),
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
