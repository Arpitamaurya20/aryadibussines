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
$filterStatus = isset($_POST['filter_status']) ? $_POST['filter_status'] : '-1';

$totalRecords = _getTotalRows($conn, 'corporate_master_audit', ' WHERE 1=1');

$filterWhere = ' WHERE 1=1';
if ($filterStatus !== '' && $filterStatus !== '-1') {
    $filterWhere .= ' AND IsActive = ' . (int) $filterStatus;
}
if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $filterWhere .= " AND (AuditName LIKE '%$searchEsc%' OR AuditDescription LIKE '%$searchEsc%' OR IconClass LIKE '%$searchEsc%')";
}
$totalFiltered = _getTotalRows($conn, 'corporate_master_audit', $filterWhere);

$sql = "SELECT * FROM corporate_master_audit $filterWhere ORDER BY SortOrder ASC, AuditName ASC LIMIT $start, $length";
$result = mysqli_query($conn, $sql);
$data = array();

while ($row = mysqli_fetch_assoc($result)) {
    corporateAuditFormatRecordIcons($row);
    $status = ((int) $row['IsActive'] === 1)
        ? '<span class="badge badge-success">Active</span>'
        : '<span class="badge badge-danger">Inactive</span>';

    $action = '<button class="btn btn-xs btn-warning" onclick="editMasterAudit(' . (int) $row['ID'] . ')"><i class="fal fa-edit"></i> Edit</button> ';
    $action .= '<button class="btn btn-xs btn-danger" onclick="deleteMasterAudit(' . (int) $row['ID'] . ')"><i class="fal fa-trash"></i> Delete</button>';

    $data[] = array(
        'id' => $row['ID'],
        'Icon' => $row['icon_display'],
        'AuditName' => htmlspecialchars($row['AuditName']),
        'IconClass' => htmlspecialchars($row['IconClass']),
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
