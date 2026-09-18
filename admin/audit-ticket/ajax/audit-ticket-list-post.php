<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
include('../controller/audit_ticket_controller.php');

SessionCheck();
$conn = _connectodb();

header('Content-Type: application/json; charset=utf-8');

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
$filterStatus = isset($_POST['filter_status']) ? $_POST['filter_status'] : '-1';
$filterCorporate = isset($_POST['filter_corporate']) ? (int) $_POST['filter_corporate'] : -1;

$UserType = $_SESSION['UserType'];
$sessionCorporateId = -1;
if ($UserType === 'Corporate Admin' || $UserType === 'Corporate Branch User') {
    $sessionCorporateId = (int) $_SESSION['Roles']['CorporateID'];
}

$baseWhere = ' WHERE t.IsActive = 1';
if ($sessionCorporateId > 0) {
    $baseWhere .= ' AND t.CorporateID = ' . $sessionCorporateId;
}
if ($UserType === 'Corporate Branch User' && isset($_SESSION['Roles']['BranchID'])) {
    $baseWhere .= ' AND t.BranchID = ' . (int) $_SESSION['Roles']['BranchID'];
}
$baseWhere = auditTicketAppendPortalListScope($conn, $_SESSION, $baseWhere, 't');

$totalRecords = 0;
$countSql = "SELECT COUNT(*) AS cnt FROM corporate_audit_tickets t $baseWhere";
$countRes = mysqli_query($conn, $countSql);
if ($countRes) {
    $countRow = mysqli_fetch_assoc($countRes);
    $totalRecords = (int) $countRow['cnt'];
}

$filterWhere = $baseWhere;
if ($filterStatus !== '' && $filterStatus !== '-1') {
    $statusEsc = mysqli_real_escape_string($conn, $filterStatus);
    $filterWhere .= " AND t.Status = '$statusEsc'";
}
if ($filterCorporate > 0) {
    $filterWhere .= ' AND t.CorporateID = ' . $filterCorporate;
}
if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $filterWhere .= " AND (t.TicketID LIKE '%$searchEsc%' OR c.CompanyName LIKE '%$searchEsc%'
        OR b.BranchSite LIKE '%$searchEsc%' OR ma.AuditName LIKE '%$searchEsc%'
        OR sa.SubAuditName LIKE '%$searchEsc%' OR t.Status LIKE '%$searchEsc%')";
}

$totalFiltered = 0;
$filteredSql = "SELECT COUNT(*) AS cnt FROM corporate_audit_tickets t
    INNER JOIN company c ON c.ID = t.CorporateID
    INNER JOIN branch b ON b.ID = t.BranchID
    INNER JOIN corporate_master_audit ma ON ma.ID = t.MasterAuditID
    INNER JOIN corporate_master_sub_audit sa ON sa.ID = t.SubAuditID
    $filterWhere";
$filteredRes = mysqli_query($conn, $filteredSql);
if ($filteredRes) {
    $filteredRow = mysqli_fetch_assoc($filteredRes);
    $totalFiltered = (int) $filteredRow['cnt'];
}

$sql = "SELECT t.*, c.CompanyName, b.BranchSite, ma.AuditName, sa.SubAuditName
    FROM corporate_audit_tickets t
    INNER JOIN company c ON c.ID = t.CorporateID
    INNER JOIN branch b ON b.ID = t.BranchID
    INNER JOIN corporate_master_audit ma ON ma.ID = t.MasterAuditID
    INNER JOIN corporate_master_sub_audit sa ON sa.ID = t.SubAuditID
    $filterWhere
    ORDER BY t.ID DESC
    LIMIT $start, $length";

$result = mysqli_query($conn, $sql);
$data = array();

if ($result === false) {
    echo json_encode(array(
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array(),
        'error' => 'Database error: ' . mysqli_error($conn) . '. Please run admin/audit-ticket/sql/create_audit_ticket_tables.sql',
    ));
    exit;
}

while ($row = mysqli_fetch_assoc($result)) {
    $stats = getAuditTicketCompletionStats($conn, (int) $row['ID']);
    $progress = $stats['completion_percent'] . '%';
    $lastStatus = isset($row['LastStatus']) && $row['LastStatus'] !== '' ? htmlspecialchars($row['LastStatus']) : '-';

    $action = '<a class="btn btn-xs btn-info" href="view-audit-ticket-details?id=' . (int) $row['ID'] . '"><i class="fal fa-eye"></i> View</a> ';
    if ($row['Status'] === 'Completed' || $row['Status'] === 'Closed') {
        $action .= '<button class="btn btn-xs btn-success" onclick="generateAuditReport(' . (int) $row['ID'] . ')"><i class="fal fa-file-pdf"></i> PDF</button>';
    }

    $data[] = array(
        'TicketID' => htmlspecialchars($row['TicketID']),
        'CompanyName' => htmlspecialchars($row['CompanyName']),
        'BranchSite' => htmlspecialchars($row['BranchSite']),
        'AuditName' => htmlspecialchars(getAuditTicketMasterAuditsDisplayLabel($conn, (int) $row['ID'], $row['AuditName'])),
        'SubAuditName' => htmlspecialchars(getAuditTicketAuditsDisplayLabel($conn, (int) $row['ID'], $row['SubAuditName'])),
        'Status' => auditTicketStatusBadge($row['Status']),
        'LastStatus' => $lastStatus,
        'Progress' => $progress,
        'CreatedDate' => htmlspecialchars($row['CreatedDate'] . ' ' . $row['CreatedTime']),
        'Action' => $action,
    );
}

echo json_encode(array(
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalFiltered,
    'data' => $data,
));
