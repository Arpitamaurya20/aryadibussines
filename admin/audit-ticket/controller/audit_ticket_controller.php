<?php

require_once dirname(__DIR__) . '/../corporate-audit/controller/corporate_audit_controller.php';
require_once dirname(__DIR__) . '/../../api/audit-ticket/audit_ticket_helpers.php';

define('AUDIT_TICKET_PREFIX', 'CS-AUD-');
define('AUDIT_TICKET_REPORTS_DIR', dirname(__DIR__) . '/reports/');

function auditTicketAuditsTableExists($conn)
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'corporate_audit_ticket_audits'");
    $exists = ($result && mysqli_num_rows($result) > 0);
    return $exists;
}

function parseAuditTicketSelections($conn, $data)
{
    $selections = array();
    $seenSubAudits = array();

    $addSelection = function ($masterAuditId, $subAuditId) use (&$selections, &$seenSubAudits) {
        $masterAuditId = (int) $masterAuditId;
        $subAuditId = (int) $subAuditId;
        if ($subAuditId <= 0 || isset($seenSubAudits[$subAuditId])) {
            return;
        }
        $seenSubAudits[$subAuditId] = true;
        $selections[] = array(
            'MasterAuditID' => $masterAuditId,
            'SubAuditID' => $subAuditId,
        );
    };

    if (isset($data['Audits'])) {
        $auditsRaw = $data['Audits'];
        if (is_string($auditsRaw)) {
            $decoded = json_decode($auditsRaw, true);
            if (is_array($decoded)) {
                $auditsRaw = $decoded;
            }
        }
        if (is_array($auditsRaw)) {
            foreach ($auditsRaw as $auditRow) {
                if (!is_array($auditRow)) {
                    continue;
                }
                $addSelection(
                    isset($auditRow['MasterAuditID']) ? $auditRow['MasterAuditID'] : (isset($auditRow['master_audit_id']) ? $auditRow['master_audit_id'] : 0),
                    isset($auditRow['SubAuditID']) ? $auditRow['SubAuditID'] : (isset($auditRow['sub_audit_id']) ? $auditRow['sub_audit_id'] : 0)
                );
            }
        }
    }

    $defaultMasterId = (int) (isset($data['MasterAuditID']) ? $data['MasterAuditID'] : 0);
    $subAuditIdLists = array();
    if (isset($data['SubAuditIDs'])) {
        $subAuditIdLists[] = $data['SubAuditIDs'];
    }
    if (isset($data['SubAuditID']) && is_array($data['SubAuditID'])) {
        $subAuditIdLists[] = $data['SubAuditID'];
    }

    foreach ($subAuditIdLists as $subAuditIdList) {
        if (is_string($subAuditIdList)) {
            $subAuditIdList = explode(',', $subAuditIdList);
        }
        if (!is_array($subAuditIdList)) {
            continue;
        }
        foreach ($subAuditIdList as $subAuditId) {
            $addSelection($defaultMasterId, $subAuditId);
        }
    }

    if (empty($selections) && isset($data['SubAuditID']) && !is_array($data['SubAuditID'])) {
        $addSelection($defaultMasterId, $data['SubAuditID']);
    }

    $validated = array();
    foreach ($selections as $selection) {
        $subAuditId = (int) $selection['SubAuditID'];
        $masterAuditId = (int) $selection['MasterAuditID'];

        $subAudit = _getTableDetails($conn, 'corporate_master_sub_audit', ' WHERE ID = ' . $subAuditId . ' AND IsActive = 1');
        if (!$subAudit) {
            continue;
        }

        if ($masterAuditId <= 0) {
            $masterAuditId = (int) $subAudit['MasterAuditID'];
        } elseif ((int) $subAudit['MasterAuditID'] !== $masterAuditId) {
            continue;
        }

        $masterAudit = _getTableDetails($conn, 'corporate_master_audit', ' WHERE ID = ' . $masterAuditId . ' AND IsActive = 1');
        if (!$masterAudit) {
            continue;
        }

        $validated[] = array(
            'MasterAuditID' => $masterAuditId,
            'SubAuditID' => $subAuditId,
            'MasterAuditName' => $masterAudit['AuditName'],
            'SubAuditName' => $subAudit['SubAuditName'],
        );
    }

    return $validated;
}

function saveCorporateAuditTicketAudits($conn, $ticketId, $audits)
{
    $ticketId = (int) $ticketId;
    if ($ticketId <= 0 || empty($audits) || !auditTicketAuditsTableExists($conn)) {
        return false;
    }

    $today = date('Y-m-d');
    $now = date('H:i:s');
    $sortOrder = 0;
    foreach ($audits as $audit) {
        $masterAuditId = (int) $audit['MasterAuditID'];
        $subAuditId = (int) $audit['SubAuditID'];
        $sql = "INSERT INTO corporate_audit_ticket_audits
            (AuditTicketID, MasterAuditID, SubAuditID, SortOrder, Status, IsActive, CreatedDate, CreatedTime)
            VALUES ($ticketId, $masterAuditId, $subAuditId, $sortOrder, 'Pending', 1, '$today', '$now')";
        _InsertTableRecords($conn, $sql);
        $sortOrder++;
    }

    return true;
}

function getCorporateAuditTicketAudits($conn, $ticketId)
{
    $ticketId = (int) $ticketId;
    $audits = array();

    if ($ticketId <= 0) {
        return $audits;
    }

    if (auditTicketAuditsTableExists($conn)) {
        $sql = "SELECT ta.*, ma.AuditName AS MasterAuditName, sa.SubAuditName, sa.SubAuditDescription, sa.IconImage AS SubAuditIconImage
            FROM corporate_audit_ticket_audits ta
            INNER JOIN corporate_master_audit ma ON ma.ID = ta.MasterAuditID
            INNER JOIN corporate_master_sub_audit sa ON sa.ID = ta.SubAuditID
            WHERE ta.AuditTicketID = $ticketId AND ta.IsActive = 1
            ORDER BY ta.SortOrder ASC, ta.ID ASC";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $audits[] = $row;
            }
        }
    }

    if (empty($audits)) {
        $ticket = _getTableDetails($conn, 'corporate_audit_tickets', ' WHERE ID = ' . $ticketId . ' AND IsActive = 1');
        if ($ticket) {
            $masterAudit = _getTableDetails($conn, 'corporate_master_audit', ' WHERE ID = ' . (int) $ticket['MasterAuditID']);
            $subAudit = _getTableDetails($conn, 'corporate_master_sub_audit', ' WHERE ID = ' . (int) $ticket['SubAuditID']);
            if ($masterAudit && $subAudit) {
                $audits[] = array(
                    'ID' => 0,
                    'AuditTicketID' => $ticketId,
                    'MasterAuditID' => (int) $ticket['MasterAuditID'],
                    'SubAuditID' => (int) $ticket['SubAuditID'],
                    'SortOrder' => 0,
                    'Status' => 'Pending',
                    'IsActive' => 1,
                    'MasterAuditName' => $masterAudit['AuditName'],
                    'SubAuditName' => $subAudit['SubAuditName'],
                    'SubAuditDescription' => isset($subAudit['SubAuditDescription']) ? $subAudit['SubAuditDescription'] : '',
                    'SubAuditIconImage' => isset($subAudit['IconImage']) ? $subAudit['IconImage'] : '',
                );
            }
        }
    }

    return $audits;
}

function getTicketSubAuditIdList($conn, $ticket)
{
    $ticketId = is_array($ticket) ? (int) $ticket['ID'] : (int) $ticket;
    $audits = getCorporateAuditTicketAudits($conn, $ticketId);
    $ids = array();
    foreach ($audits as $audit) {
        $ids[] = (int) $audit['SubAuditID'];
    }
    if (empty($ids) && is_array($ticket) && isset($ticket['SubAuditID'])) {
        $ids[] = (int) $ticket['SubAuditID'];
    }
    return array_values(array_unique($ids));
}

function auditTicketChecklistBelongsToTicket($conn, $checklistRow, $ticket)
{
    if (!$checklistRow) {
        return false;
    }
    $allowedSubAudits = getTicketSubAuditIdList($conn, $ticket);
    return in_array((int) $checklistRow['SubAuditID'], $allowedSubAudits, true);
}

function getAuditTicketAuditsDisplayLabel($conn, $ticketId, $primarySubAuditName = '')
{
    $audits = getCorporateAuditTicketAudits($conn, $ticketId);
    if (count($audits) <= 1) {
        if (!empty($audits[0]['SubAuditName'])) {
            return $audits[0]['SubAuditName'];
        }
        return $primarySubAuditName;
    }

    $names = array();
    foreach ($audits as $audit) {
        $names[] = $audit['SubAuditName'];
    }
    $first = $names[0];
    $extra = count($names) - 1;
    return $first . ' (+' . $extra . ' more)';
}

function getAuditTicketMasterAuditsDisplayLabel($conn, $ticketId, $primaryMasterAuditName = '')
{
    $audits = getCorporateAuditTicketAudits($conn, $ticketId);
    if (empty($audits)) {
        return $primaryMasterAuditName;
    }

    $names = array();
    foreach ($audits as $audit) {
        $names[] = $audit['MasterAuditName'];
    }
    $names = array_values(array_unique($names));
    if (count($names) <= 1) {
        return $names[0];
    }
    return $names[0] . ' (+' . (count($names) - 1) . ' more)';
}

function formatAuditTicketAuditsForApi($conn, $ticketId, $baseUrl = '')
{
    $audits = getCorporateAuditTicketAudits($conn, $ticketId);
    $formatted = array();
    foreach ($audits as $audit) {
        $iconUrl = '';
        if (!empty($audit['SubAuditIconImage']) && $baseUrl !== '') {
            $iconUrl = rtrim($baseUrl, '/') . '/admin/media/corporate-audit/' . ltrim($audit['SubAuditIconImage'], '/');
        }
        $formatted[] = array(
            'id' => isset($audit['ID']) ? (int) $audit['ID'] : 0,
            'master_audit_id' => (int) $audit['MasterAuditID'],
            'master_audit_name' => $audit['MasterAuditName'],
            'sub_audit_id' => (int) $audit['SubAuditID'],
            'sub_audit_name' => $audit['SubAuditName'],
            'sub_audit_description' => isset($audit['SubAuditDescription']) ? $audit['SubAuditDescription'] : '',
            'sub_audit_icon_url' => $iconUrl,
            'status' => isset($audit['Status']) ? $audit['Status'] : 'Pending',
            'sort_order' => isset($audit['SortOrder']) ? (int) $audit['SortOrder'] : 0,
        );
    }
    return $formatted;
}

function auditTicketStatuses()
{
    return array('Raised', 'Assigned', 'In Progress', 'Completed', 'Closed');
}

function auditTicketStatusBadge($status)
{
    $map = array(
        'Raised' => 'badge-info',
        'Assigned' => 'badge-primary',
        'In Progress' => 'badge-warning',
        'Completed' => 'badge-success',
        'Closed' => 'badge-secondary',
    );
    $class = isset($map[$status]) ? $map[$status] : 'badge-light';
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}

function auditTicketComputeCompliance($checklistRow, $responseValue)
{
    $value = trim((string) $responseValue);
    if ($value === '') {
        return 'Pending';
    }

    $fieldType = isset($checklistRow['FieldType']) ? $checklistRow['FieldType'] : 'text';
    $minVal = isset($checklistRow['MinValue']) ? trim((string) $checklistRow['MinValue']) : '';
    $maxVal = isset($checklistRow['MaxValue']) ? trim((string) $checklistRow['MaxValue']) : '';
    $idealVal = isset($checklistRow['IdealValue']) ? trim((string) $checklistRow['IdealValue']) : '';

    if ($fieldType === 'checkbox') {
        $normalized = strtolower($value);
        if (in_array($normalized, array('yes', '1', 'true'), true)) {
            return 'In Range';
        }
        if (in_array($normalized, array('no', '0', 'false'), true)) {
            return 'Out of Range';
        }
        return 'N/A';
    }

    if (in_array($fieldType, array('number', 'decimal'), true)) {
        if (!is_numeric($value)) {
            return 'N/A';
        }
        $num = (float) $value;
        if ($minVal !== '' && is_numeric($minVal) && $num < (float) $minVal) {
            return 'Out of Range';
        }
        if ($maxVal !== '' && is_numeric($maxVal) && $num > (float) $maxVal) {
            return 'Out of Range';
        }
        return 'In Range';
    }

    if ($idealVal !== '' && strcasecmp($value, $idealVal) === 0) {
        return 'In Range';
    }
    if ($idealVal !== '') {
        return 'Out of Range';
    }

    return 'N/A';
}

function auditTicketComplianceBadge($status)
{
    $map = array(
        'Pending' => 'badge-secondary',
        'In Range' => 'badge-success',
        'Out of Range' => 'badge-danger',
        'N/A' => 'badge-info',
    );
    $class = isset($map[$status]) ? $map[$status] : 'badge-light';
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}

function auditTicketOkStatusBadge($status)
{
    $status = audit_ticket_normalize_ok_status($status);
    $map = array(
        'Pending' => 'badge-secondary',
        'OK' => 'badge-success',
        'Not OK' => 'badge-danger',
    );
    $class = isset($map[$status]) ? $map[$status] : 'badge-light';
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}

function auditTicketGetBranchAccountManager($branchDetails)
{
    if (!is_array($branchDetails)) {
        return -1;
    }
    $bam = (int) (isset($branchDetails['AccountBranchManager']) ? $branchDetails['AccountBranchManager'] : -1);
    return $bam > 0 ? $bam : -1;
}

function auditTicketIsOpenStatus($status)
{
    return !in_array((string) $status, array('Completed', 'Closed'), true);
}

function auditTicketUserCanManageAssignment($session, $ticket)
{
    if (!is_array($ticket) || !auditTicketIsOpenStatus($ticket['Status'])) {
        return false;
    }
    if (isset($session['UserType']) && in_array($session['UserType'], array('Admin', 'Super Admin', 'Ticket Manager'), true)) {
        return true;
    }

    $employeeId = isset($session['Roles']['EmployeeID']) ? (int) $session['Roles']['EmployeeID'] : -1;
    $bamId = isset($ticket['BranchAccountManager']) ? (int) $ticket['BranchAccountManager'] : -1;
    if ($employeeId > 0 && $bamId > 0 && $employeeId === $bamId) {
        return true;
    }

    if (function_exists('CheckRole') && CheckRole($session, 'Branch Account Manager') === true && $employeeId > 0 && $bamId > 0 && $employeeId === $bamId) {
        return true;
    }
    return false;
}

function auditTicketEmployeeName($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return '';
    }
    $emp = getEmployeeDetailsfromID($conn, $employeeId);
    return is_array($emp) && !empty($emp['Name']) ? $emp['Name'] : '';
}

function auditTicketUserIsStateCorporateLead($session)
{
    return function_exists('CheckRole') && CheckRole($session, 'State Corporate Lead') === true;
}

function auditTicketUserIsTicketManager($session)
{
    $userType = isset($session['UserType']) ? $session['UserType'] : '';
    if (in_array($userType, array('Admin', 'Super Admin', 'Ticket Manager'), true)) {
        return true;
    }
    if (function_exists('CheckRole')) {
        if (CheckRole($session, 'HR') === true) {
            return true;
        }
    }
    if (isset($session['Roles']['EmployeeRoles']) && is_array($session['Roles']['EmployeeRoles'])) {
        foreach ($session['Roles']['EmployeeRoles'] as $role) {
            if ($role === 'Ticket Manager' || $role === 'HR') {
                return true;
            }
        }
    }
    return false;
}

function auditTicketRequireStateClass()
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    if (!class_exists('Core', false)) {
        require_once dirname(__DIR__) . '/../includes/autoloader.inc.php';
    }
    require_once dirname(__DIR__) . '/../classes/state.class.php';
    $loaded = true;
}

function auditTicketBuildStateScopeSqlForTicket($conn, $employeeId, $ticketAlias = 't')
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return '';
    }
    auditTicketRequireStateClass();
    $stateObject = new State($conn);
    if (!$stateObject->employeeHasStateScope($employeeId)) {
        return '';
    }
    $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $ticketAlias);
    if ($alias === '') {
        $alias = 't';
    }
    return " AND EXISTS (
        SELECT 1 FROM branch bx
        INNER JOIN state s ON s.IsActive = 1 AND s.StateCorporateHead = $employeeId
            AND TRIM(LOWER(bx.BranchState)) = TRIM(LOWER(s.StateName))
        WHERE bx.ID = $alias.BranchID
    )";
}

function auditTicketBranchInEmployeeStateScope($conn, $employeeId, $branchState)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return false;
    }
    auditTicketRequireStateClass();
    $stateObject = new State($conn);
    if (!$stateObject->employeeHasStateScope($employeeId)) {
        return false;
    }
    $allowed = $stateObject->getAllowedStateNamesForEmployee($employeeId);
    return $stateObject->branchStateMatchesAllowedStates($branchState, $allowed);
}

function auditTicketResolveEmployeeId($conn, $session)
{
    $employeeId = isset($session['Roles']['EmployeeID']) ? (int) $session['Roles']['EmployeeID'] : 0;
    if ($employeeId > 0) {
        return $employeeId;
    }
    auditTicketRequireStateClass();
    $stateObject = new State($conn);
    return (int) $stateObject->resolveEmployeeIdFromSession($session);
}

function auditTicketAppendPortalListScope($conn, $session, $baseWhere, $ticketAlias = 't')
{
    if (auditTicketUserIsTicketManager($session)) {
        return $baseWhere;
    }

    $employeeId = auditTicketResolveEmployeeId($conn, $session);
    if ($employeeId <= 0) {
        return $baseWhere;
    }

    auditTicketRequireStateClass();
    $stateObject = new State($conn);
    if ($stateObject->employeeHasStateScope($employeeId)) {
        $stateSql = auditTicketBuildStateScopeSqlForTicket($conn, $employeeId, $ticketAlias);
        if ($stateSql !== '') {
            return $baseWhere . $stateSql;
        }
    }

    $isBam = function_exists('CheckRole') && CheckRole($session, 'Branch Account Manager') === true;
    if ($isBam) {
        return $baseWhere . ' AND ' . $ticketAlias . '.BranchAccountManager = ' . $employeeId;
    }

    return $baseWhere;
}

function auditTicketUserCanViewTicket($conn, $session, $ticket)
{
    if (!is_array($ticket)) {
        return false;
    }
    if (auditTicketUserIsTicketManager($session)) {
        return true;
    }

    $userType = isset($session['UserType']) ? $session['UserType'] : '';
    if ($userType === 'Corporate Admin') {
        $corpId = isset($session['Roles']['CorporateID']) ? (int) $session['Roles']['CorporateID'] : -1;
        if ($corpId > 0 && (int) $ticket['CorporateID'] === $corpId) {
            return true;
        }
    }
    if ($userType === 'Corporate Branch User') {
        $branchId = isset($session['Roles']['BranchID']) ? (int) $session['Roles']['BranchID'] : -1;
        if ($branchId > 0 && (int) $ticket['BranchID'] === $branchId) {
            return true;
        }
    }

    $employeeId = auditTicketResolveEmployeeId($conn, $session);
    if ($employeeId <= 0) {
        return false;
    }

    if (function_exists('CheckRole') && CheckRole($session, 'Branch Account Manager') === true) {
        $bamId = isset($ticket['BranchAccountManager']) ? (int) $ticket['BranchAccountManager'] : -1;
        if ($bamId === $employeeId) {
            return true;
        }
    }

    if (isset($ticket['BranchState']) && $ticket['BranchState'] !== '') {
        $branchState = $ticket['BranchState'];
    } else {
        $branch = _getTableDetails($conn, 'branch', ' WHERE ID = ' . (int) $ticket['BranchID']);
        $branchState = is_array($branch) ? $branch['BranchState'] : '';
    }
    if (auditTicketBranchInEmployeeStateScope($conn, $employeeId, $branchState)) {
        return true;
    }

    $technicianId = isset($ticket['Technician']) ? (int) $ticket['Technician'] : -1;
    if ($technicianId === $employeeId) {
        return true;
    }

    return false;
}

function auditTicketAssignToTechnician($conn, $data, $session = array())
{
    $ticketId = (int) (isset($data['AuditTicketID']) ? $data['AuditTicketID'] : 0);
    $technicianId = (int) (isset($data['TechnicianID']) ? $data['TechnicianID'] : (isset($data['AssignedTo']) ? $data['AssignedTo'] : 0));
    $remarks = trim((string) (isset($data['Remarks']) ? $data['Remarks'] : ''));
    $isReassign = !empty($data['is_reassign']) || !empty($data['IsReassign']);
    $updatedBy = cleantext(isset($data['UpdatedBy']) ? $data['UpdatedBy'] : (isset($session['pb_username']) ? $session['pb_username'] : 'Portal'));

    if ($ticketId <= 0 || $technicianId <= 0) {
        return array('error' => true, 'message' => 'Ticket and technician are required.');
    }

    $ticket = getCorporateAuditTicketById($conn, $ticketId);
    if (!$ticket) {
        return array('error' => true, 'message' => 'Audit ticket not found.');
    }

    if (!auditTicketUserCanManageAssignment($session, $ticket)) {
        return array('error' => true, 'message' => 'You are not authorized to assign this ticket.');
    }

    $oldTechnician = (int) (isset($ticket['Technician']) ? $ticket['Technician'] : -1);
    if ($oldTechnician === $technicianId) {
        $name = auditTicketEmployeeName($conn, $technicianId);
        return array('error' => true, 'message' => 'Ticket is already assigned to ' . ($name !== '' ? $name : 'this technician') . '.');
    }

    $today = date('Y-m-d');
    $now = date('H:i:s');
    $currentStatus = $ticket['Status'];
    $newStatus = $currentStatus;
    $previousStatus = $currentStatus;

    if (!$isReassign && $currentStatus === 'Raised') {
        $newStatus = 'Assigned';
        $previousStatus = 'Raised';
    }

    $oldName = $oldTechnician > 0 ? auditTicketEmployeeName($conn, $oldTechnician) : '';
    $newName = auditTicketEmployeeName($conn, $technicianId);
    if ($remarks === '') {
        if ($isReassign || $oldTechnician > 0) {
            $remarks = 'Reassigned from ' . ($oldName !== '' ? $oldName : 'previous technician') . ' to ' . $newName;
        } else {
            $remarks = 'Assigned to technician: ' . $newName;
        }
    }

    $remarksSql = cleantext($remarks);
    $previousStatusSql = cleantext($previousStatus);
    $lastStatusSql = cleantext($currentStatus);

    $extra = " Technician = $technicianId, AssignedTo = $technicianId,
        AssignedDate = '$today', AssignedTime = '$now', LastStatus = '$lastStatusSql'";
    if ($newStatus !== $currentStatus) {
        $extra .= ", Status = '$newStatus'";
    }

    $update = _UpdateTableRecords($conn, 'corporate_audit_tickets', $extra . " WHERE ID = $ticketId AND IsActive = 1");
    if ($update['error']) {
        return $update;
    }

    recordAuditTicketHistory($conn, array(
        'AuditTicketID' => $ticketId,
        'Status' => $newStatus,
        'PreviousStatus' => $previousStatus,
        'AssignedTo' => $technicianId,
        'Remarks' => $remarksSql,
        'CreatedBy' => $updatedBy,
        'CreatedDate' => $today,
        'CreatedTime' => $now,
    ));

    return array(
        'error' => false,
        'message' => ($isReassign || $oldTechnician > 0)
            ? 'Ticket reassigned to ' . $newName . ' (status kept as ' . $newStatus . ').'
            : 'Ticket assigned to ' . $newName . '.',
        'Status' => $newStatus,
        'Technician' => $technicianId,
    );
}

function auditTicketResolveAssignment($conn, $branchDetails, $requestedAssignee = -1)
{
    // On raise, tickets always go to branch account manager first.
    return auditTicketGetBranchAccountManager($branchDetails);
}

function recordAuditTicketHistory($conn, $data)
{
    $auditTicketId = (int) $data['AuditTicketID'];
    if ($auditTicketId <= 0) {
        return array('error' => true, 'message' => 'Invalid audit ticket ID for history.');
    }

    $status = mysqli_real_escape_string($conn, (string) $data['Status']);
    $previousStatus = mysqli_real_escape_string($conn, (string) (isset($data['PreviousStatus']) ? $data['PreviousStatus'] : ''));
    $assignedTo = (int) (isset($data['AssignedTo']) ? $data['AssignedTo'] : -1);
    $remarks = mysqli_real_escape_string($conn, (string) (isset($data['Remarks']) ? $data['Remarks'] : ''));
    $createdBy = mysqli_real_escape_string($conn, (string) (isset($data['CreatedBy']) ? $data['CreatedBy'] : 'System'));
    $createdDate = mysqli_real_escape_string($conn, (string) (isset($data['CreatedDate']) ? $data['CreatedDate'] : date('Y-m-d')));
    $createdTime = mysqli_real_escape_string($conn, (string) (isset($data['CreatedTime']) ? $data['CreatedTime'] : date('H:i:s')));

    $sql = "INSERT INTO corporate_audit_ticket_status_history
        (AuditTicketID, Status, PreviousStatus, AssignedTo, Remarks, CreatedBy, CreatedDate, CreatedTime)
        VALUES ($auditTicketId, '$status', '$previousStatus', $assignedTo, '$remarks', '$createdBy', '$createdDate', '$createdTime')";

    return _InsertTableRecords($conn, $sql);
}

function getAuditTicketStatusHistory($conn, $auditTicketId)
{
    $auditTicketId = (int) $auditTicketId;
    if ($auditTicketId <= 0) {
        return array();
    }
    $where = " WHERE AuditTicketID = $auditTicketId ORDER BY CreatedDate DESC, CreatedTime DESC, ID DESC";
    return _getTableRecords($conn, 'corporate_audit_ticket_status_history', $where);
}

function auditTicketApplyStatusChange($conn, $ticketId, $newStatus, $options = array())
{
    $ticketId = (int) $ticketId;
    $newStatus = cleantext($newStatus);

    if ($ticketId <= 0 || !in_array($newStatus, auditTicketStatuses(), true)) {
        return array('error' => true, 'message' => 'Invalid ticket or status.');
    }

    $ticket = getCorporateAuditTicketById($conn, $ticketId);
    if (!$ticket) {
        return array('error' => true, 'message' => 'Audit ticket not found.');
    }

    $currentStatus = $ticket['Status'];
    $force = !empty($options['force']);
    if ($currentStatus === $newStatus && !$force) {
        return array('error' => false, 'message' => 'Status unchanged.', 'Status' => $newStatus, 'LastStatus' => $ticket['LastStatus']);
    }

    $previousStatus = $currentStatus;
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $createdBy = cleantext(isset($options['CreatedBy']) ? $options['CreatedBy'] : 'System');
    $remarks = cleantext(isset($options['Remarks']) ? $options['Remarks'] : '');
    $assignedTo = isset($options['AssignedTo']) ? (int) $options['AssignedTo'] : (int) $ticket['AssignedTo'];
    $previousStatusSql = cleantext($previousStatus);

    $extra = ", LastStatus = '$previousStatusSql'";
    if ($newStatus === 'In Progress') {
        $extra .= ", StartedDate = COALESCE(StartedDate, '$today'), StartedTime = COALESCE(StartedTime, '$now')";
    }
    if ($newStatus === 'Completed') {
        $extra .= ", CompletedDate = '$today', CompletedTime = '$now'";
    }
    if ($newStatus === 'Closed') {
        $extra .= ", ClosedDate = '$today', ClosedTime = '$now'";
    }
    if ($newStatus === 'Assigned' && $assignedTo > 0) {
        $extra .= ", AssignedTo = $assignedTo, AssignedDate = '$today', AssignedTime = '$now'";
    }
    if (isset($options['TechnicianNotes']) && $options['TechnicianNotes'] !== '') {
        $extra .= ", TechnicianNotes = '" . cleantext($options['TechnicianNotes']) . "'";
    }

    $update = _UpdateTableRecords($conn, 'corporate_audit_tickets', " Status = '$newStatus' $extra WHERE ID = $ticketId AND IsActive = 1");
    if ($update['error']) {
        return $update;
    }

    recordAuditTicketHistory($conn, array(
        'AuditTicketID' => $ticketId,
        'Status' => $newStatus,
        'PreviousStatus' => $previousStatus,
        'AssignedTo' => $assignedTo,
        'Remarks' => $remarks,
        'CreatedBy' => $createdBy,
        'CreatedDate' => $today,
        'CreatedTime' => $now,
    ));

    return array(
        'error' => false,
        'message' => 'Status updated to ' . $newStatus . '.',
        'Status' => $newStatus,
        'LastStatus' => $previousStatus,
    );
}

function createCorporateAuditTicket($conn, $data, $branchDetails = null)
{
    $response = array('error' => true, 'message' => 'Unable to raise audit ticket.');

    $corporateId = (int) (isset($data['CorporateID']) ? $data['CorporateID'] : 0);
    $branchId = (int) (isset($data['BranchID']) ? $data['BranchID'] : 0);
    $auditSelections = parseAuditTicketSelections($conn, $data);
    $assignedTo = auditTicketResolveAssignment($conn, $branchDetails, -1);
    $branchAccountManager = $assignedTo;
    $technician = -1;
    $priority = cleantext(isset($data['Priority']) ? $data['Priority'] : 'Normal');
    $remarks = cleantext(isset($data['Remarks']) ? $data['Remarks'] : '');
    $createdBy = cleantext(isset($data['CreatedBy']) ? $data['CreatedBy'] : 'System');
    $ticketDate = date('Y-m-d');
    $ticketTime = date('H:i:s');

    if ($corporateId <= 0 || $branchId <= 0 || empty($auditSelections)) {
        $response['message'] = 'Corporate, Branch and at least one valid audit are required.';
        return $response;
    }

    $primaryAudit = $auditSelections[0];
    $masterAuditId = (int) $primaryAudit['MasterAuditID'];
    $subAuditId = (int) $primaryAudit['SubAuditID'];

    $status = 'Raised';
    $assignedDateSql = ($assignedTo > 0) ? "'$ticketDate'" : 'NULL';
    $assignedTimeSql = ($assignedTo > 0) ? "'$ticketTime'" : 'NULL';

    $sql = "INSERT INTO corporate_audit_tickets
        (TicketID, CorporateID, BranchID, MasterAuditID, SubAuditID, AssignedTo, BranchAccountManager, Technician, Status, Priority, Remarks,
         CreatedBy, CreatedDate, CreatedTime, AssignedDate, AssignedTime, IsActive)
        VALUES ('PENDING', $corporateId, $branchId, $masterAuditId, $subAuditId, $assignedTo, $branchAccountManager, $technician, '$status', '$priority', '$remarks',
         '$createdBy', '$ticketDate', '$ticketTime', $assignedDateSql, $assignedTimeSql, 1)";

    $insert = _InsertTableRecords($conn, $sql);
    if ($insert['error']) {
        $response['message'] = $insert['message'];
        return $response;
    }

    $id = (int) $insert['last_insert_id'];
    saveCorporateAuditTicketAudits($conn, $id, $auditSelections);

    $ticketId = AUDIT_TICKET_PREFIX . sprintf('%06d', $id);
    _UpdateTableRecords($conn, 'corporate_audit_tickets', " TicketID = '$ticketId' WHERE ID = $id");

    recordAuditTicketHistory($conn, array(
        'AuditTicketID' => $id,
        'Status' => $status,
        'PreviousStatus' => '',
        'AssignedTo' => $assignedTo,
        'Remarks' => $remarks !== '' ? $remarks : (count($auditSelections) > 1
            ? 'Multi-audit ticket raised to Branch Account Manager (' . count($auditSelections) . ' audits)'
            : 'Audit ticket raised to Branch Account Manager'),
        'CreatedBy' => $createdBy,
        'CreatedDate' => $ticketDate,
        'CreatedTime' => $ticketTime,
    ));

    $response['error'] = false;
    $response['message'] = count($auditSelections) > 1
        ? 'Multi-audit ticket raised successfully.'
        : 'Audit ticket raised successfully.';
    $response['ID'] = $id;
    $response['TicketID'] = $ticketId;
    $response['TicketIDNew'] = $id;
    $response['Status'] = $status;
    $response['LastStatus'] = '';
    $response['AssignedTo'] = $assignedTo;
    $response['BranchAccountManager'] = $branchAccountManager;
    $response['MasterAuditID'] = $masterAuditId;
    $response['SubAuditID'] = $subAuditId;
    $response['audit_count'] = count($auditSelections);
    $response['audits'] = array_map(function ($audit) {
        return array(
            'MasterAuditID' => (int) $audit['MasterAuditID'],
            'SubAuditID' => (int) $audit['SubAuditID'],
            'MasterAuditName' => $audit['MasterAuditName'],
            'SubAuditName' => $audit['SubAuditName'],
        );
    }, $auditSelections);

    return $response;
}

function getCorporateAuditTicketById($conn, $ticketId, $byDisplayId = false)
{
    $ticketId = $byDisplayId ? cleantext($ticketId) : (int) $ticketId;
    if ($byDisplayId) {
        $where = " WHERE t.TicketID = '$ticketId' AND t.IsActive = 1";
    } else {
        $where = ' WHERE t.ID = ' . (int) $ticketId . ' AND t.IsActive = 1';
    }

    $sql = "SELECT t.*,
            c.CompanyName,
            b.BranchSite, b.BranchAddress1, b.BranchAddress2, b.BranchCity, b.BranchState,
            b.BranchPostalCode, b.BranchMobile, b.BranchEmail, b.BranchCode, b.Latitude, b.Longitude,
            ma.AuditName AS MasterAuditName, ma.AuditDescription AS MasterAuditDescription,
            sa.SubAuditName, sa.SubAuditDescription AS SubAuditDescription, sa.IconImage AS SubAuditIconImage
        FROM corporate_audit_tickets t
        INNER JOIN company c ON c.ID = t.CorporateID
        INNER JOIN branch b ON b.ID = t.BranchID
        INNER JOIN corporate_master_audit ma ON ma.ID = t.MasterAuditID
        INNER JOIN corporate_master_sub_audit sa ON sa.ID = t.SubAuditID
        $where
        LIMIT 1";

    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }
    $ticket = mysqli_fetch_assoc($result);
    $ticket['audits'] = getCorporateAuditTicketAudits($conn, (int) $ticket['ID']);
    $ticket['audit_count'] = count($ticket['audits']);
    return $ticket;
}

function getCorporateAuditTicketChecklistWithResponses($conn, $auditTicketId, $subAuditIdFilter = 0)
{
    $auditTicketId = (int) $auditTicketId;
    $subAuditIdFilter = (int) $subAuditIdFilter;
    $ticket = getCorporateAuditTicketById($conn, $auditTicketId);
    if (!$ticket) {
        return array();
    }

    $ticketAudits = getCorporateAuditTicketAudits($conn, $auditTicketId);
    if ($subAuditIdFilter > 0) {
        $ticketAudits = array_values(array_filter($ticketAudits, function ($audit) use ($subAuditIdFilter) {
            return (int) $audit['SubAuditID'] === $subAuditIdFilter;
        }));
    }

    $responseMap = array();
    $sql = "SELECT * FROM corporate_audit_ticket_responses WHERE AuditTicketID = $auditTicketId";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $responseMap[(int) $row['ChecklistID']] = $row;
        }
    }

    $items = array();
    $idx = 1;
    foreach ($ticketAudits as $auditRow) {
        $subAuditId = (int) $auditRow['SubAuditID'];
        $checklists = getCorporateAuditChecklists($conn, $subAuditId, true);

        foreach ($checklists as $cp) {
            $cpId = (int) $cp['ID'];
            $resp = isset($responseMap[$cpId]) ? $responseMap[$cpId] : null;
            $items[] = array(
                'checklist' => $cp,
                'response' => $resp,
                'sequence_index' => $idx,
                'sub_audit_id' => $subAuditId,
                'sub_audit_name' => isset($auditRow['SubAuditName']) ? $auditRow['SubAuditName'] : '',
                'master_audit_id' => (int) $auditRow['MasterAuditID'],
                'master_audit_name' => isset($auditRow['MasterAuditName']) ? $auditRow['MasterAuditName'] : '',
                'response_value' => $resp ? $resp['ResponseValue'] : '',
                'compliance_status' => $resp ? $resp['ComplianceStatus'] : 'Pending',
                'ok_status' => $resp ? audit_ticket_normalize_ok_status(isset($resp['OkStatus']) ? $resp['OkStatus'] : 'Pending') : 'Pending',
                'filled_by' => $resp ? $resp['FilledBy'] : '',
                'filled_date' => $resp ? $resp['FilledDate'] : '',
                'filled_time' => $resp ? $resp['FilledTime'] : '',
                'remarks' => $resp ? $resp['Remarks'] : '',
                'response_image' => $resp && isset($resp['ResponseImage']) ? $resp['ResponseImage'] : '',
            );
            $idx++;
        }
    }

    return $items;
}

function submitCorporateAuditChecklistResponses($conn, $data)
{
    $response = array('error' => true, 'message' => 'Unable to save checklist responses.');

    $data = audit_ticket_merge_payload_sources($data);
    $ticketId = audit_ticket_resolve_ticket_id($conn, $data);
    $responses = isset($data['responses']) ? $data['responses'] : (isset($data['checklist']) ? $data['checklist'] : array());
    $filledBy = cleantext(isset($data['FilledBy']) ? $data['FilledBy'] : (isset($data['CreatedBy']) ? $data['CreatedBy'] : 'Technician'));
    $filledByEmployeeId = (int) (isset($data['FilledByEmployeeID']) ? $data['FilledByEmployeeID'] : (isset($data['EmployeeID']) ? $data['EmployeeID'] : -1));
    $technicianNotes = cleantext(isset($data['TechnicianNotes']) ? $data['TechnicianNotes'] : '');
    $isDraft = audit_ticket_is_draft_save($data);
    $markCompleted = !$isDraft && (!isset($data['mark_completed']) || (int) $data['mark_completed'] === 1);

    if ($ticketId <= 0) {
        $response['message'] = 'Audit ticket ID is required.';
        return $response;
    }

    $ticket = getCorporateAuditTicketById($conn, $ticketId);
    if (!$ticket) {
        $response['message'] = 'Audit ticket not found.';
        return $response;
    }

    $filterSubAuditId = (int) (isset($data['SubAuditID']) ? $data['SubAuditID'] : (isset($data['sub_audit_id']) ? $data['sub_audit_id'] : 0));

    if (!is_array($responses) || empty($responses)) {
        $parsedLegacy = audit_ticket_parse_legacy_checklist_payload($conn, $ticket, $data, $filterSubAuditId);
        $responses = $parsedLegacy['responses'];
    }

    $reportMeta = audit_ticket_extract_report_meta_from_payload($data);
    $hasReportMeta = !empty($reportMeta);
    if ((!is_array($responses) || empty($responses)) && !$hasReportMeta) {
        $response['message'] = 'No checklist responses or report details provided.';
        return $response;
    }

    $today = date('Y-m-d');
    $now = date('H:i:s');
    $saved = 0;
    $validationErrors = array();

    mysqli_begin_transaction($conn);
    try {
        if (is_array($responses)) {
            foreach ($responses as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $checklistId = (int) (isset($item['ChecklistID']) ? $item['ChecklistID'] : (isset($item['checklist_id']) ? $item['checklist_id'] : 0));
                if ($checklistId <= 0) {
                    continue;
                }

                $checklistRow = getCorporateAuditChecklistById($conn, $checklistId);
                if (!auditTicketChecklistBelongsToTicket($conn, $checklistRow, $ticket)) {
                    continue;
                }
                if ($filterSubAuditId > 0 && (int) $checklistRow['SubAuditID'] !== $filterSubAuditId) {
                    continue;
                }

                $validated = audit_ticket_validate_checklist_response_item($item, $checklistRow, $isDraft);
                if (!empty($validated['skip'])) {
                    continue;
                }
                if (!empty($validated['errors'])) {
                    $validationErrors = array_merge($validationErrors, $validated['errors']);
                    if (!$isDraft) {
                        continue;
                    }
                }

                $responseValue = $validated['response_value'];
                $itemRemarks = $validated['remarks'];
                $okStatus = cleantext($validated['ok_status']);
                $compliance = auditTicketComputeCompliance($checklistRow, $responseValue);

                $existing = _getTableDetails($conn, 'corporate_audit_ticket_responses', " WHERE AuditTicketID = $ticketId AND ChecklistID = $checklistId");
                $existingImage = $existing && isset($existing['ResponseImage']) ? $existing['ResponseImage'] : '';
                $imageRaw = isset($item['ResponseImage']) ? $item['ResponseImage'] : (isset($item['image']) ? $item['image'] : '');
                $savedImage = audit_ticket_save_checklist_image($ticketId, $checklistId, $imageRaw, $existingImage);

                if ($existing) {
                    $updateSql = " ResponseValue = '$responseValue', ComplianceStatus = '$compliance', OkStatus = '$okStatus', Remarks = '$itemRemarks',
                        ResponseImage = '$savedImage',
                        FilledBy = '$filledBy', FilledByEmployeeID = $filledByEmployeeId,
                        UpdatedDate = '$today', UpdatedTime = '$now'
                        WHERE ID = " . (int) $existing['ID'];
                    _UpdateTableRecords($conn, 'corporate_audit_ticket_responses', $updateSql);
                } else {
                    $insertSql = "INSERT INTO corporate_audit_ticket_responses
                        (AuditTicketID, ChecklistID, ResponseValue, ComplianceStatus, OkStatus, Remarks, ResponseImage, FilledBy, FilledByEmployeeID,
                         FilledDate, FilledTime, UpdatedDate, UpdatedTime)
                        VALUES ($ticketId, $checklistId, '$responseValue', '$compliance', '$okStatus', '$itemRemarks', '$savedImage', '$filledBy', $filledByEmployeeId,
                         '$today', '$now', '$today', '$now')";
                    _InsertTableRecords($conn, $insertSql);
                }
                $saved++;
            }
        }

        if (!$isDraft && !empty($validationErrors)) {
            throw new Exception(implode(' ', $validationErrors));
        }
        if ($saved === 0 && !$hasReportMeta) {
            throw new Exception('No valid checklist responses to save.');
        }

        if ($hasReportMeta) {
            $reportResult = saveAuditTicketReportMeta($conn, array_merge($reportMeta, array(
                'AuditTicketID' => $ticketId,
                '_skip_response_wrap' => true,
            )));
            if ($reportResult['error']) {
                throw new Exception($reportResult['message']);
            }
        }

        $ticketStats = getAuditTicketCompletionStats($conn, $ticketId);
        if ($markCompleted) {
            if ($filterSubAuditId > 0) {
                $subAuditStats = null;
                if (!empty($ticketStats['by_sub_audit']) && is_array($ticketStats['by_sub_audit'])) {
                    foreach ($ticketStats['by_sub_audit'] as $group) {
                        if ((int) $group['sub_audit_id'] === $filterSubAuditId) {
                            $subAuditStats = isset($group['summary']) ? $group['summary'] : null;
                            break;
                        }
                    }
                }
                if (!$subAuditStats || (int) $subAuditStats['filled'] < (int) $subAuditStats['total']) {
                    throw new Exception('All checklist checkpoints for the selected audit must be completed before final submit.');
                }
            } elseif ($ticketStats['filled'] < $ticketStats['total']) {
                throw new Exception('All checklist checkpoints must be completed before final submit.');
            }
        }

        if ($isDraft) {
            _UpdateTableRecords($conn, 'corporate_audit_tickets', " DraftSavedDate = '$today', DraftSavedTime = '$now' WHERE ID = $ticketId");
        }

        syncAuditTicketAuditStatuses($conn, $ticketId);

        $newStatus = 'In Progress';
        if ($markCompleted && (int) $ticketStats['filled'] >= (int) $ticketStats['total']) {
            $newStatus = 'Completed';
        }
        $statusOptions = array(
            'CreatedBy' => $filledBy,
            'Remarks' => $technicianNotes !== '' ? $technicianNotes : ($isDraft ? 'Checklist draft saved' : 'Checklist submitted'),
            'TechnicianNotes' => $technicianNotes,
        );
        if ($filledByEmployeeId > 0) {
            $statusOptions['AssignedTo'] = $filledByEmployeeId;
            _UpdateTableRecords($conn, 'corporate_audit_tickets', " Technician = $filledByEmployeeId WHERE ID = $ticketId");
        }

        if ($ticket['Status'] !== $newStatus) {
            if ($saved > 0 || $hasReportMeta || $isDraft) {
                $statusResult = auditTicketApplyStatusChange($conn, $ticketId, $newStatus, $statusOptions);
                if ($statusResult['error']) {
                    throw new Exception($statusResult['message']);
                }
            }
        } elseif ($technicianNotes !== '') {
            _UpdateTableRecords($conn, 'corporate_audit_tickets', " TechnicianNotes = '$technicianNotes' WHERE ID = $ticketId");
        }

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = 'Failed to save responses: ' . $e->getMessage();
        return $response;
    }

    $response['error'] = false;
    $response['message'] = $isDraft ? 'Audit checklist draft saved.' : 'Checklist responses saved successfully.';
    $response['saved_count'] = $saved;
    $response['checklist_saved_count'] = $saved;
    $response['report_saved'] = $hasReportMeta ? 1 : 0;
    if (!empty($validationErrors)) {
        $response['draft_warnings'] = $validationErrors;
    }
    $response['is_draft'] = $isDraft ? 1 : 0;
    $response['AuditTicketID'] = $ticketId;
    $response['ServiceReportID'] = $ticketId;
    $response['TicketID'] = $ticket['TicketID'];
    $response['ServiceReportTicketID'] = $ticketId;

    $updatedTicket = getCorporateAuditTicketById($conn, $ticketId);
    if ($updatedTicket) {
        $response['Status'] = $updatedTicket['Status'];
        $response['LastStatus'] = isset($updatedTicket['LastStatus']) ? $updatedTicket['LastStatus'] : '';
        $baseUrl = defined('FRONT_SITE_PATH') ? rtrim(FRONT_SITE_PATH, '/') : '';
        $response['mobile_legacy_payload'] = audit_ticket_build_legacy_mobile_payload($conn, $updatedTicket, $baseUrl);
        $response['report_meta'] = auditTicketFormatReportMetaForApi($updatedTicket);
    }
    $response['checklist_summary'] = getAuditTicketCompletionStats($conn, $ticketId);

    return $response;
}

function getCorporateAuditTicketsForMobile($conn, $filters = array())
{
    $where = ' WHERE t.IsActive = 1';

    if (isset($filters['EmployeeID']) && (int) $filters['EmployeeID'] > 0) {
        $empId = (int) $filters['EmployeeID'];
        $viewRole = isset($filters['view_role']) ? (string) $filters['view_role'] : 'technician';
        if ($viewRole === 'manager') {
            $where .= " AND t.BranchAccountManager = $empId";
        } elseif ($viewRole === 'state_manager' || $viewRole === 'state_lead') {
            $stateSql = auditTicketBuildStateScopeSqlForTicket($conn, $empId, 't');
            if ($stateSql !== '') {
                $where .= $stateSql;
            }
        } else {
            $where .= " AND t.Technician = $empId";
        }
    }
    if (isset($filters['CorporateID']) && (int) $filters['CorporateID'] > 0) {
        $where .= ' AND t.CorporateID = ' . (int) $filters['CorporateID'];
    }
    if (isset($filters['BranchID']) && (int) $filters['BranchID'] > 0) {
        $where .= ' AND t.BranchID = ' . (int) $filters['BranchID'];
    }
    if (isset($filters['Status']) && $filters['Status'] !== '' && $filters['Status'] !== '-1') {
        $status = cleantext($filters['Status']);
        $where .= " AND t.Status = '$status'";
    }

    $sql = "SELECT t.ID, t.TicketID, t.CorporateID, t.BranchID, t.MasterAuditID, t.SubAuditID,
            t.AssignedTo, t.Technician, t.BranchAccountManager, t.Status, t.LastStatus, t.Priority,
            t.Remarks, t.CreatedBy, t.CreatedDate, t.CreatedTime, t.CompletedDate, t.CompletedTime,
            t.ReportGenerated,
            c.CompanyName, b.BranchSite, b.BranchCity, b.BranchState, b.BranchAddress1, b.BranchMobile,
            ma.AuditName AS MasterAuditName, sa.SubAuditName
        FROM corporate_audit_tickets t
        INNER JOIN company c ON c.ID = t.CorporateID
        INNER JOIN branch b ON b.ID = t.BranchID
        INNER JOIN corporate_master_audit ma ON ma.ID = t.MasterAuditID
        INNER JOIN corporate_master_sub_audit sa ON sa.ID = t.SubAuditID
        $where
        ORDER BY t.ID DESC";

    $result = mysqli_query($conn, $sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function auditTicketFormatBranchDetailsForApi($ticket)
{
    if (!is_array($ticket)) {
        return array();
    }
    $addressParts = array();
    foreach (array('BranchAddress1', 'BranchAddress2') as $key) {
        if (!empty($ticket[$key])) {
            $addressParts[] = trim((string) $ticket[$key]);
        }
    }
    $cityLine = array();
    foreach (array('BranchCity', 'BranchState', 'BranchPostalCode') as $key) {
        if (!empty($ticket[$key])) {
            $cityLine[] = trim((string) $ticket[$key]);
        }
    }
    if (!empty($cityLine)) {
        $addressParts[] = implode(', ', $cityLine);
    }
    $fullAddress = !empty($ticket['ReportSiteAddress'])
        ? trim((string) $ticket['ReportSiteAddress'])
        : implode(', ', array_filter($addressParts));

    return array(
        'branch_site' => isset($ticket['BranchSite']) ? $ticket['BranchSite'] : '',
        'branch_code' => isset($ticket['BranchCode']) ? $ticket['BranchCode'] : '',
        'address_line_1' => isset($ticket['BranchAddress1']) ? $ticket['BranchAddress1'] : '',
        'address_line_2' => isset($ticket['BranchAddress2']) ? $ticket['BranchAddress2'] : '',
        'city' => isset($ticket['BranchCity']) ? $ticket['BranchCity'] : '',
        'state' => isset($ticket['BranchState']) ? $ticket['BranchState'] : '',
        'postal_code' => isset($ticket['BranchPostalCode']) ? $ticket['BranchPostalCode'] : '',
        'floor' => isset($ticket['ReportFloor']) ? $ticket['ReportFloor'] : '',
        'full_address' => $fullAddress,
        'mobile' => isset($ticket['BranchMobile']) ? $ticket['BranchMobile'] : '',
        'email' => isset($ticket['BranchEmail']) ? $ticket['BranchEmail'] : '',
        'latitude' => isset($ticket['Latitude']) ? $ticket['Latitude'] : '',
        'longitude' => isset($ticket['Longitude']) ? $ticket['Longitude'] : '',
    );
}

function auditTicketFormatSubAuditDetailsForApi($ticket, $baseUrl = '')
{
    if (!is_array($ticket)) {
        return array();
    }
    $iconUrl = '';
    $iconFile = '';
    if (!empty($ticket['SubAuditIconImage'])) {
        $iconFile = $ticket['SubAuditIconImage'];
    } elseif (!empty($ticket['IconImage'])) {
        $iconFile = $ticket['IconImage'];
    }
    if ($iconFile !== '' && $baseUrl !== '') {
        $iconUrl = $baseUrl . '/admin/media/corporate-audit/' . ltrim($iconFile, '/');
    }
    return array(
        'master_audit_id' => isset($ticket['MasterAuditID']) ? (int) $ticket['MasterAuditID'] : 0,
        'master_audit_name' => isset($ticket['MasterAuditName']) ? $ticket['MasterAuditName'] : '',
        'master_audit_description' => isset($ticket['MasterAuditDescription']) ? $ticket['MasterAuditDescription'] : '',
        'sub_audit_id' => isset($ticket['SubAuditID']) ? (int) $ticket['SubAuditID'] : 0,
        'sub_audit_name' => isset($ticket['SubAuditName']) ? $ticket['SubAuditName'] : '',
        'sub_audit_description' => isset($ticket['SubAuditDescription']) ? $ticket['SubAuditDescription'] : '',
        'sub_audit_icon_url' => $iconUrl,
    );
}

function auditTicketFormatReportMetaForApi($ticket)
{
    if (!is_array($ticket)) {
        return array();
    }
    return array(
        'report_floor' => isset($ticket['ReportFloor']) ? $ticket['ReportFloor'] : '',
        'report_site_address' => isset($ticket['ReportSiteAddress']) ? $ticket['ReportSiteAddress'] : '',
        'client_representative' => isset($ticket['ClientRepresentative']) ? $ticket['ClientRepresentative'] : '',
        'client_representative_contact' => isset($ticket['ClientRepresentativeContact']) ? $ticket['ClientRepresentativeContact'] : '',
        'client_representative_designation' => isset($ticket['ClientRepresentativeDesignation']) ? $ticket['ClientRepresentativeDesignation'] : '',
        'client_representative_email' => isset($ticket['ClientRepresentativeEmail']) ? $ticket['ClientRepresentativeEmail'] : '',
        'audit_observation' => isset($ticket['AuditObservation']) ? $ticket['AuditObservation'] : '',
        'audit_conclusion' => isset($ticket['AuditConclusion']) ? $ticket['AuditConclusion'] : '',
        'technician_notes' => isset($ticket['TechnicianNotes']) ? $ticket['TechnicianNotes'] : '',
        'problem_reported_by_client' => isset($ticket['ProblemReportedByClient']) ? $ticket['ProblemReportedByClient'] : '',
        'action_taken' => isset($ticket['ActionTaken']) ? $ticket['ActionTaken'] : '',
        'general_remarks' => isset($ticket['GeneralRemarks']) ? $ticket['GeneralRemarks'] : '',
        'client_signature' => isset($ticket['ClientSignature']) ? $ticket['ClientSignature'] : '',
        'report_latitude' => isset($ticket['ReportLatitude']) ? $ticket['ReportLatitude'] : '',
        'report_longitude' => isset($ticket['ReportLongitude']) ? $ticket['ReportLongitude'] : '',
        'draft_saved_date' => isset($ticket['DraftSavedDate']) ? $ticket['DraftSavedDate'] : '',
        'draft_saved_time' => isset($ticket['DraftSavedTime']) ? $ticket['DraftSavedTime'] : '',
        'report_generated' => !empty($ticket['ReportGenerated']) ? 1 : 0,
    );
}

function saveAuditTicketReportMeta($conn, $data)
{
    $response = array('error' => true, 'message' => 'Unable to save report details.');

    $data = audit_ticket_merge_payload_sources($data);
    $ticketId = audit_ticket_resolve_ticket_id($conn, $data);
    if ($ticketId <= 0) {
        $ticketId = (int) (isset($data['AuditTicketID']) ? $data['AuditTicketID'] : 0);
    }
    if ($ticketId <= 0) {
        $response['message'] = 'AuditTicketID is required.';
        return $response;
    }

    $ticket = getCorporateAuditTicketById($conn, $ticketId);
    if (!$ticket) {
        $response['message'] = 'Audit ticket not found.';
        return $response;
    }

    $extracted = audit_ticket_extract_report_meta_from_payload($data);
    $fields = array(
        'ReportFloor' => cleantext(isset($extracted['ReportFloor']) ? $extracted['ReportFloor'] : (isset($data['ReportFloor']) ? $data['ReportFloor'] : (isset($data['report_floor']) ? $data['report_floor'] : ''))),
        'ReportSiteAddress' => cleantext(isset($extracted['ReportSiteAddress']) ? $extracted['ReportSiteAddress'] : (isset($data['ReportSiteAddress']) ? $data['ReportSiteAddress'] : (isset($data['report_site_address']) ? $data['report_site_address'] : ''))),
        'ClientRepresentative' => cleantext(isset($extracted['ClientRepresentative']) ? $extracted['ClientRepresentative'] : (isset($data['ClientRepresentative']) ? $data['ClientRepresentative'] : (isset($data['client_representative']) ? $data['client_representative'] : ''))),
        'ClientRepresentativeContact' => cleantext(isset($extracted['ClientRepresentativeContact']) ? $extracted['ClientRepresentativeContact'] : (isset($data['ClientRepresentativeContact']) ? $data['ClientRepresentativeContact'] : (isset($data['client_representative_contact']) ? $data['client_representative_contact'] : ''))),
        'ClientRepresentativeDesignation' => cleantext(isset($extracted['ClientRepresentativeDesignation']) ? $extracted['ClientRepresentativeDesignation'] : (isset($data['ClientRepresentativeDesignation']) ? $data['ClientRepresentativeDesignation'] : (isset($data['client_representative_designation']) ? $data['client_representative_designation'] : ''))),
        'ClientRepresentativeEmail' => cleantext(isset($extracted['ClientRepresentativeEmail']) ? $extracted['ClientRepresentativeEmail'] : (isset($data['ClientRepresentativeEmail']) ? $data['ClientRepresentativeEmail'] : (isset($data['client_representative_email']) ? $data['client_representative_email'] : ''))),
        'AuditObservation' => cleantext(isset($extracted['AuditObservation']) ? $extracted['AuditObservation'] : (isset($data['AuditObservation']) ? $data['AuditObservation'] : (isset($data['audit_observation']) ? $data['audit_observation'] : ''))),
        'AuditConclusion' => cleantext(isset($extracted['AuditConclusion']) ? $extracted['AuditConclusion'] : (isset($data['AuditConclusion']) ? $data['AuditConclusion'] : (isset($data['audit_conclusion']) ? $data['audit_conclusion'] : ''))),
        'ProblemReportedByClient' => cleantext(isset($extracted['ProblemReportedByClient']) ? $extracted['ProblemReportedByClient'] : ''),
        'ActionTaken' => cleantext(isset($extracted['ActionTaken']) ? $extracted['ActionTaken'] : ''),
        'GeneralRemarks' => cleantext(isset($extracted['GeneralRemarks']) ? $extracted['GeneralRemarks'] : ''),
        'ReportLatitude' => cleantext(isset($extracted['ReportLatitude']) ? $extracted['ReportLatitude'] : ''),
        'ReportLongitude' => cleantext(isset($extracted['ReportLongitude']) ? $extracted['ReportLongitude'] : ''),
    );

    if (isset($data['TechnicianNotes']) || isset($data['technician_notes'])) {
        $fields['TechnicianNotes'] = cleantext(isset($data['TechnicianNotes']) ? $data['TechnicianNotes'] : $data['technician_notes']);
    }

    if (isset($extracted['ClientSignature']) || isset($data['ClientSignature'])) {
        $signatureRaw = isset($extracted['ClientSignature']) ? $extracted['ClientSignature'] : $data['ClientSignature'];
        $existingSignature = isset($ticket['ClientSignature']) ? $ticket['ClientSignature'] : '';
        $fields['ClientSignature'] = audit_ticket_save_client_signature_image($ticketId, $signatureRaw, $existingSignature);
    }

    $setParts = array();
    foreach ($fields as $column => $value) {
        if ($value === '' && !in_array($column, array('ReportLatitude', 'ReportLongitude'), true)) {
            continue;
        }
        $setParts[] = "$column = '" . mysqli_real_escape_string($conn, $value) . "'";
    }

    if (empty($setParts)) {
        $response['message'] = 'No report fields provided.';
        return $response;
    }

    $sql = 'UPDATE corporate_audit_tickets SET ' . implode(', ', $setParts) . ' WHERE ID = ' . $ticketId;
    if (!mysqli_query($conn, $sql)) {
        $response['message'] = 'Database error while saving report details.';
        return $response;
    }

    $updated = getCorporateAuditTicketById($conn, $ticketId);
    $response['error'] = false;
    $response['message'] = 'Audit report details saved.';
    $response['AuditTicketID'] = $ticketId;
    $response['ServiceReportID'] = $ticketId;
    $response['TicketID'] = $updated['TicketID'];
    $response['ServiceReportTicketID'] = $ticketId;
    $response['report_meta'] = auditTicketFormatReportMetaForApi($updated);
    $response['branch_details'] = auditTicketFormatBranchDetailsForApi($updated);
    $baseUrl = defined('FRONT_SITE_PATH') ? rtrim(FRONT_SITE_PATH, '/') : '';
    $response['mobile_legacy_payload'] = audit_ticket_build_legacy_mobile_payload($conn, $updated, $baseUrl);

    if (empty($data['_skip_response_wrap'])) {
        return $response;
    }
    return $response;
}

function auditTicketPdfExtractMediaFilename($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (strpos($value, 'data:') === 0 || strpos($value, 'base64,') !== false) {
        return '';
    }
    if (($queryPos = strpos($value, '?')) !== false) {
        $value = substr($value, 0, $queryPos);
    }
    $value = str_replace('\\', '/', $value);
    if (preg_match('#/(checklist|signatures)/([^/]+)$#i', $value, $matches)) {
        return $matches[2];
    }
    return basename($value);
}

function auditTicketPdfResolveImageForEmbed($storedValue, $type = 'checklist')
{
    $filename = auditTicketPdfExtractMediaFilename($storedValue);
    if ($filename === '') {
        return '';
    }

    $baseDir = $type === 'signature' ? AUDIT_TICKET_SIGNATURE_MEDIA_DIR : AUDIT_TICKET_CHECKLIST_MEDIA_DIR;
    $localPath = $baseDir . $filename;

    if (is_file($localPath)) {
        $resolved = realpath($localPath);
        return str_replace('\\', '/', $resolved !== false ? $resolved : $localPath);
    }

    $baseUrl = defined('FRONT_SITE_PATH') ? rtrim(FRONT_SITE_PATH, '/') : 'https://techxpertindia.in';
    return $type === 'signature'
        ? audit_ticket_signature_image_url($filename, $baseUrl)
        : audit_ticket_checklist_image_url($filename, $baseUrl);
}

function auditTicketPdfResolveLocalImagePath($filename, $type = 'checklist')
{
    return auditTicketPdfResolveImageForEmbed($filename, $type);
}

function auditTicketPdfGeneratePieChart($segments, $title, $filepath)
{
    if (!function_exists('imagecreatetruecolor')) {
        return '';
    }

    $width = 440;
    $height = 230;
    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255, 255, 255);
    $border = imagecolorallocate($img, 220, 220, 220);
    $textColor = imagecolorallocate($img, 33, 37, 41);
    imagefill($img, 0, 0, $white);
    imagerectangle($img, 0, 0, $width - 1, $height - 1, $border);

    $total = 0;
    foreach ($segments as $segment) {
        $total += max(0, (int) $segment['value']);
    }

    imagestring($img, 4, 12, 10, $title, $textColor);

    if ($total <= 0) {
        imagestring($img, 3, 150, 110, 'No data available', $textColor);
        imagepng($img, $filepath);
        imagedestroy($img);
        return $filepath;
    }

    $cx = 115;
    $cy = 125;
    $radius = 78;
    $startAngle = 0.0;

    foreach ($segments as $segment) {
        $value = max(0, (int) $segment['value']);
        if ($value <= 0) {
            continue;
        }
        $rgb = isset($segment['color']) ? $segment['color'] : array(2, 125, 193);
        $sliceColor = imagecolorallocate($img, (int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        $sliceAngle = ($value / $total) * 360;
        imagefilledarc(
            $img,
            $cx,
            $cy,
            $radius * 2,
            $radius * 2,
            (int) $startAngle,
            (int) ($startAngle + $sliceAngle),
            $sliceColor,
            IMG_ARC_PIE
        );
        $startAngle += $sliceAngle;
    }

    $legendX = 230;
    $legendY = 52;
    foreach ($segments as $segment) {
        $value = max(0, (int) $segment['value']);
        if ($value <= 0) {
            continue;
        }
        $rgb = isset($segment['color']) ? $segment['color'] : array(2, 125, 193);
        $legendColor = imagecolorallocate($img, (int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        imagefilledrectangle($img, $legendX, $legendY, $legendX + 16, $legendY + 16, $legendColor);
        imagerectangle($img, $legendX, $legendY, $legendX + 16, $legendY + 16, $border);
        $pct = round(($value / $total) * 100, 1);
        $legendText = $segment['label'] . ': ' . $value . ' (' . $pct . '%)';
        imagestring($img, 3, $legendX + 22, $legendY + 2, $legendText, $textColor);
        $legendY += 24;
    }

    imagepng($img, $filepath);
    imagedestroy($img);
    return $filepath;
}

function auditTicketPdfGenerateDonutChart($percent, $filepath, $label = 'Completion')
{
    if (!function_exists('imagecreatetruecolor')) {
        return '';
    }

    $percent = max(0, min(100, (int) $percent));
    $width = 220;
    $height = 220;
    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255, 255, 255);
    $blue = imagecolorallocate($img, 2, 125, 193);
    $red = imagecolorallocate($img, 220, 53, 69);
    $gray = imagecolorallocate($img, 233, 236, 239);
    $black = imagecolorallocate($img, 33, 37, 41);
    imagefill($img, 0, 0, $white);

    $cx = (int) ($width / 2);
    $cy = (int) ($height / 2);
    $radius = 88;
    $thickness = 34;

    imagefilledarc($img, $cx, $cy, $radius * 2, $radius * 2, 0, 360, $gray, IMG_ARC_PIE);
    if ($percent > 0) {
        $startAngle = 270;
        $endAngle = $startAngle + (($percent / 100) * 360);
        imagefilledarc($img, $cx, $cy, $radius * 2, $radius * 2, $startAngle, $endAngle, $blue, IMG_ARC_PIE);
    }

    $holeRadius = $radius - $thickness;
    imagefilledellipse($img, $cx, $cy, $holeRadius * 2, $holeRadius * 2, $white);

    $text = $percent . '%';
    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    imagestring($img, $font, (int) ($cx - ($textWidth / 2)), (int) ($cy - ($textHeight / 2) - 6), $text, $black);
    imagestring($img, 2, (int) ($cx - (imagefontwidth(2) * strlen($label) / 2)), $cy + 14, $label, $red);

    imagepng($img, $filepath);
    imagedestroy($img);
    return $filepath;
}

function auditTicketPdfBuildComplianceBreakdown($checklistItems)
{
    $counts = array(
        'In Range' => 0,
        'Out of Range' => 0,
        'Pending' => 0,
        'N/A' => 0,
    );
    foreach ($checklistItems as $item) {
        $status = isset($item['compliance_status']) ? (string) $item['compliance_status'] : 'Pending';
        if (!isset($counts[$status])) {
            $counts['N/A']++;
        } else {
            $counts[$status]++;
        }
    }
    return $counts;
}

function getAuditTicketBranchTrendData($conn, $ticket, $currentTicketId = 0)
{
    $currentTicketId = (int) $currentTicketId;
    $branchId = (int) $ticket['BranchID'];
    $corporateId = (int) $ticket['CorporateID'];
    $subAuditIds = getTicketSubAuditIdList($conn, $ticket);
    if (empty($subAuditIds) && isset($ticket['SubAuditID'])) {
        $subAuditIds[] = (int) $ticket['SubAuditID'];
    }
    $subAuditIds = array_values(array_unique(array_filter(array_map('intval', $subAuditIds), function ($id) {
        return $id > 0;
    })));
    $subAuditFilterSql = '';
    if (!empty($subAuditIds)) {
        $subAuditFilterSql = ' AND t.SubAuditID IN (' . implode(',', $subAuditIds) . ')';
    }

    $data = array(
        'daily' => array(),
        'monthly' => array(),
        'monthly_pass_rate' => array(),
        'recent_audits' => array(),
        'previous_audit' => null,
        'branch_totals' => array(
            'total_audits' => 0,
            'completed_audits' => 0,
            'avg_pass_rate' => 0,
        ),
    );

    $sqlDaily = "SELECT DATE(COALESCE(t.CompletedDate, t.CreatedDate)) AS audit_day,
            COUNT(*) AS raised,
            SUM(CASE WHEN t.Status IN ('Completed','Closed') THEN 1 ELSE 0 END) AS completed
        FROM corporate_audit_tickets t
        WHERE t.BranchID = $branchId AND t.CorporateID = $corporateId AND t.IsActive = 1
            $subAuditFilterSql
            AND COALESCE(t.CompletedDate, t.CreatedDate) >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY DATE(COALESCE(t.CompletedDate, t.CreatedDate))
        ORDER BY audit_day ASC";
    $dailyMap = array();
    $result = mysqli_query($conn, $sqlDaily);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $dailyMap[$row['audit_day']] = array(
                'raised' => (int) $row['raised'],
                'completed' => (int) $row['completed'],
            );
        }
    }
    for ($d = 13; $d >= 0; $d--) {
        $day = date('Y-m-d', strtotime("-$d days"));
        $raised = isset($dailyMap[$day]) ? $dailyMap[$day]['raised'] : 0;
        $completed = isset($dailyMap[$day]) ? $dailyMap[$day]['completed'] : 0;
        $conversion = $raised > 0 ? round(($completed / $raised) * 100, 1) : 0;
        $data['daily'][] = array(
            'label' => date('d/m', strtotime($day)),
            'day' => $day,
            'raised' => $raised,
            'completed' => $completed,
            'conversion' => $conversion,
        );
    }

    $monthlyMap = array();
    $sqlMonthly = "SELECT DATE_FORMAT(COALESCE(t.CompletedDate, t.CreatedDate), '%Y-%m') AS ym,
            COUNT(*) AS raised,
            SUM(CASE WHEN t.Status IN ('Completed','Closed') THEN 1 ELSE 0 END) AS completed
        FROM corporate_audit_tickets t
        WHERE t.BranchID = $branchId AND t.CorporateID = $corporateId AND t.IsActive = 1
            $subAuditFilterSql
            AND COALESCE(t.CompletedDate, t.CreatedDate) >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(COALESCE(t.CompletedDate, t.CreatedDate), '%Y-%m')
        ORDER BY ym ASC";
    $result = mysqli_query($conn, $sqlMonthly);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ym = $row['ym'];
            $monthlyMap[$ym] = array(
                'raised' => (int) $row['raised'],
                'completed' => (int) $row['completed'],
            );
        }
    }
    $passMonthlyMap = array();
    $sqlPassMonthly = "SELECT DATE_FORMAT(COALESCE(t.CompletedDate, t.CreatedDate), '%Y-%m') AS ym,
            SUM(CASE WHEN r.OkStatus = 'OK' THEN 1 ELSE 0 END) AS ok_count,
            SUM(CASE WHEN r.OkStatus IN ('OK','Not OK') THEN 1 ELSE 0 END) AS evaluated
        FROM corporate_audit_tickets t
        LEFT JOIN corporate_audit_ticket_responses r ON r.AuditTicketID = t.ID
        WHERE t.BranchID = $branchId AND t.CorporateID = $corporateId AND t.IsActive = 1
            $subAuditFilterSql
            AND t.Status IN ('Completed','Closed')
            AND COALESCE(t.CompletedDate, t.CreatedDate) >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(COALESCE(t.CompletedDate, t.CreatedDate), '%Y-%m')
        ORDER BY ym ASC";
    $result = mysqli_query($conn, $sqlPassMonthly);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ym = $row['ym'];
            $evaluated = (int) $row['evaluated'];
            $okCount = (int) $row['ok_count'];
            $passMonthlyMap[$ym] = array(
                'pass_rate' => $evaluated > 0 ? round(($okCount / $evaluated) * 100, 1) : 0,
                'ok' => $okCount,
                'evaluated' => $evaluated,
            );
        }
    }

    $data['monthly'] = array();
    $data['monthly_pass_rate'] = array();
    for ($m = 5; $m >= 0; $m--) {
        $ym = date('Y-m', strtotime('first day of -' . $m . ' months'));
        $monthLabel = date('M y', strtotime($ym . '-01'));
        $raised = isset($monthlyMap[$ym]) ? $monthlyMap[$ym]['raised'] : 0;
        $completed = isset($monthlyMap[$ym]) ? $monthlyMap[$ym]['completed'] : 0;
        $data['monthly'][] = array(
            'ym' => $ym,
            'label' => $monthLabel,
            'raised' => $raised,
            'completed' => $completed,
            'conversion' => $raised > 0 ? round(($completed / $raised) * 100, 1) : 0,
        );
        $passRow = isset($passMonthlyMap[$ym]) ? $passMonthlyMap[$ym] : array('pass_rate' => 0, 'ok' => 0, 'evaluated' => 0);
        $data['monthly_pass_rate'][] = array(
            'ym' => $ym,
            'label' => $monthLabel,
            'pass_rate' => $passRow['pass_rate'],
            'ok' => $passRow['ok'],
            'evaluated' => $passRow['evaluated'],
        );
    }

    $sqlRecent = "SELECT t.ID, t.TicketID, t.Status,
            COALESCE(t.CompletedDate, t.CreatedDate) AS audit_date,
            (SELECT COUNT(*) FROM corporate_audit_ticket_responses r WHERE r.AuditTicketID = t.ID) AS resp_count,
            (SELECT COUNT(*) FROM corporate_audit_ticket_responses r WHERE r.AuditTicketID = t.ID AND r.OkStatus = 'OK') AS ok_count,
            (SELECT COUNT(*) FROM corporate_audit_ticket_responses r WHERE r.AuditTicketID = t.ID AND r.OkStatus = 'Not OK') AS not_ok_count
        FROM corporate_audit_tickets t
        WHERE t.BranchID = $branchId AND t.CorporateID = $corporateId AND t.IsActive = 1
            $subAuditFilterSql
            AND t.Status IN ('Completed','Closed')
        ORDER BY COALESCE(t.CompletedDate, t.CreatedDate) DESC, t.ID DESC
        LIMIT 12";
    $result = mysqli_query($conn, $sqlRecent);
    $passRates = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $evaluated = (int) $row['ok_count'] + (int) $row['not_ok_count'];
            $passRate = $evaluated > 0 ? round(((int) $row['ok_count'] / $evaluated) * 100, 1) : 0;
            $entry = array(
                'id' => (int) $row['ID'],
                'ticket_id' => $row['TicketID'],
                'audit_date' => $row['audit_date'],
                'pass_rate' => $passRate,
                'ok' => (int) $row['ok_count'],
                'not_ok' => (int) $row['not_ok_count'],
                'is_current' => ((int) $row['ID'] === $currentTicketId),
            );
            $data['recent_audits'][] = $entry;
            $passRates[] = $passRate;
            if ($data['previous_audit'] === null && (int) $row['ID'] !== $currentTicketId) {
                $data['previous_audit'] = $entry;
            }
        }
    }

    $sqlTotals = "SELECT COUNT(*) AS total_audits,
            SUM(CASE WHEN t.Status IN ('Completed','Closed') THEN 1 ELSE 0 END) AS completed_audits
        FROM corporate_audit_tickets t
        WHERE t.BranchID = $branchId AND t.CorporateID = $corporateId AND t.IsActive = 1
            $subAuditFilterSql";
    $result = mysqli_query($conn, $sqlTotals);
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $data['branch_totals']['total_audits'] = (int) $row['total_audits'];
        $data['branch_totals']['completed_audits'] = (int) $row['completed_audits'];
    }
    if (!empty($passRates)) {
        $data['branch_totals']['avg_pass_rate'] = round(array_sum($passRates) / count($passRates), 1);
    }

    return $data;
}

function auditTicketPdfCalculateAuditGrade($passRate, $notOkCount)
{
    if ($notOkCount === 0 && $passRate >= 95) {
        return array('grade' => 'A', 'label' => 'Excellent', 'color' => '#198754');
    }
    if ($passRate >= 85 && $notOkCount <= 2) {
        return array('grade' => 'B', 'label' => 'Good', 'color' => '#027dc1');
    }
    if ($passRate >= 70) {
        return array('grade' => 'C', 'label' => 'Satisfactory', 'color' => '#fd7e14');
    }
    if ($passRate >= 50) {
        return array('grade' => 'D', 'label' => 'Needs Improvement', 'color' => '#dc3545');
    }
    return array('grade' => 'F', 'label' => 'Critical', 'color' => '#842029');
}

function auditTicketPdfCalculateRiskLevel($notOkCount, $outOfRangeCount)
{
    $score = $notOkCount + $outOfRangeCount;
    if ($score === 0) {
        return array('level' => 'Low', 'color' => '#198754');
    }
    if ($score <= 3) {
        return array('level' => 'Medium', 'color' => '#fd7e14');
    }
    if ($score <= 7) {
        return array('level' => 'High', 'color' => '#dc3545');
    }
    return array('level' => 'Critical', 'color' => '#842029');
}

function auditTicketPdfCalculateTurnaroundHours($ticket)
{
    $start = trim((string) (isset($ticket['CreatedDate']) ? $ticket['CreatedDate'] : '') . ' ' . (isset($ticket['CreatedTime']) ? $ticket['CreatedTime'] : ''));
    $endDate = !empty($ticket['CompletedDate']) ? $ticket['CompletedDate'] : (isset($ticket['ClosedDate']) ? $ticket['ClosedDate'] : '');
    $endTime = !empty($ticket['CompletedTime']) ? $ticket['CompletedTime'] : (isset($ticket['ClosedTime']) ? $ticket['ClosedTime'] : '');
    $end = trim((string) $endDate . ' ' . $endTime);
    if ($start === '' || $end === '' || $end === '-') {
        return null;
    }
    $startTs = strtotime($start);
    $endTs = strtotime($end);
    if ($startTs === false || $endTs === false || $endTs < $startTs) {
        return null;
    }
    return round(($endTs - $startTs) / 3600, 1);
}

function auditTicketPdfChartTextWidth($text, $font = 2)
{
    return imagefontwidth($font) * strlen((string) $text);
}

function auditTicketPdfChartDrawCenteredLabel($img, $text, $centerX, $y, $textColor, $font = 2)
{
    $text = (string) $text;
    $x = (int) ($centerX - (auditTicketPdfChartTextWidth($text, $font) / 2));
    if ($x < 0) {
        $x = 0;
    }
    imagestring($img, $font, $x, $y, $text, $textColor);
}

function auditTicketPdfGenerateBarChart($labels, $values, $title, $filepath, $barColor = array(2, 125, 193), $maxValue = 0)
{
    if (!function_exists('imagecreatetruecolor') || empty($labels)) {
        return '';
    }

    $count = count($labels);
    $slotWidth = $count > 10 ? 34 : 42;
    $barWidth = $count > 10 ? 16 : 22;
    $marginLeft = 48;
    $marginRight = 20;
    $marginTop = 34;
    $marginBottom = 58;
    $width = $marginLeft + $marginRight + ($slotWidth * $count);
    if ($width < 520) {
        $width = 520;
        $slotWidth = (int) floor(($width - $marginLeft - $marginRight) / max(1, $count));
        $barWidth = max(12, (int) floor($slotWidth * 0.55));
    }
    $height = 270;
    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255, 255, 255);
    $border = imagecolorallocate($img, 220, 220, 220);
    $textColor = imagecolorallocate($img, 33, 37, 41);
    $gridColor = imagecolorallocate($img, 235, 235, 235);
    $axisColor = imagecolorallocate($img, 180, 180, 180);
    imagefill($img, 0, 0, $white);
    imagerectangle($img, 0, 0, $width - 1, $height - 1, $border);
    imagestring($img, 4, 12, 8, $title, $textColor);

    $maxVal = $maxValue > 0 ? $maxValue : max(1, max(array_map('floatval', $values)));
    $chartWidth = $width - $marginLeft - $marginRight;
    $chartHeight = $height - $marginTop - $marginBottom;
    $barColorRes = imagecolorallocate($img, (int) $barColor[0], (int) $barColor[1], (int) $barColor[2]);
    $labelStep = $count > 12 ? 2 : 1;

    for ($g = 0; $g <= 4; $g++) {
        $y = $marginTop + (int) (($chartHeight / 4) * $g);
        imageline($img, $marginLeft, $y, $width - $marginRight, $y, $gridColor);
        $gridVal = round($maxVal - (($maxVal / 4) * $g), 1);
        imagestring($img, 1, 4, $y - 4, (string) $gridVal, $textColor);
    }
    imageline($img, $marginLeft, $marginTop, $marginLeft, $marginTop + $chartHeight, $axisColor);
    imageline($img, $marginLeft, $marginTop + $chartHeight, $width - $marginRight, $marginTop + $chartHeight, $axisColor);

    $plotWidth = $slotWidth * $count;
    $startX = $marginLeft + (int) max(0, ($chartWidth - $plotWidth) / 2);

    foreach ($labels as $idx => $label) {
        $val = isset($values[$idx]) ? (float) $values[$idx] : 0;
        $slotCenter = $startX + ($idx * $slotWidth) + (int) ($slotWidth / 2);
        $x1 = $slotCenter - (int) ($barWidth / 2);
        $x2 = $x1 + $barWidth;
        $barH = $val > 0 ? max(2, (int) round(($val / $maxVal) * $chartHeight)) : 0;
        $y1 = $marginTop + $chartHeight - $barH;
        $y2 = $marginTop + $chartHeight;
        if ($barH > 0) {
            imagefilledrectangle($img, $x1, $y1, $x2, $y2, $barColorRes);
            imagerectangle($img, $x1, $y1, $x2, $y2, $border);
            auditTicketPdfChartDrawCenteredLabel($img, (string) $val, $slotCenter, max($marginTop + 2, $y1 - 12), $textColor, 2);
        }
        if ($idx % $labelStep === 0) {
            auditTicketPdfChartDrawCenteredLabel($img, $label, $slotCenter, $y2 + 8, $textColor, 2);
        }
    }

    imagepng($img, $filepath);
    imagedestroy($img);
    return $filepath;
}

function auditTicketPdfGenerateGroupedBarChart($labels, $seriesList, $title, $filepath, $maxValue = 0)
{
    if (!function_exists('imagecreatetruecolor') || empty($labels) || empty($seriesList)) {
        return '';
    }

    $count = count($labels);
    $seriesCount = count($seriesList);
    $slotWidth = $count > 8 ? 48 : 62;
    $barWidth = max(8, (int) floor(($slotWidth - 8) / $seriesCount));
    $marginLeft = 48;
    $marginRight = 20;
    $marginTop = 38;
    $marginBottom = 62;
    $width = $marginLeft + $marginRight + ($slotWidth * $count);
    if ($width < 520) {
        $width = 520;
        $slotWidth = (int) floor(($width - $marginLeft - $marginRight) / max(1, $count));
        $barWidth = max(8, (int) floor(($slotWidth - 8) / $seriesCount));
    }
    $height = 280;
    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255, 255, 255);
    $border = imagecolorallocate($img, 220, 220, 220);
    $textColor = imagecolorallocate($img, 33, 37, 41);
    $gridColor = imagecolorallocate($img, 235, 235, 235);
    $axisColor = imagecolorallocate($img, 180, 180, 180);
    imagefill($img, 0, 0, $white);
    imagerectangle($img, 0, 0, $width - 1, $height - 1, $border);
    imagestring($img, 4, 12, 8, $title, $textColor);

    $allValues = array();
    foreach ($seriesList as $series) {
        foreach ($series['values'] as $v) {
            $allValues[] = (float) $v;
        }
    }
    $maxVal = $maxValue > 0 ? $maxValue : max(1, max($allValues));
    $chartWidth = $width - $marginLeft - $marginRight;
    $chartHeight = $height - $marginTop - $marginBottom;
    $labelStep = $count > 8 ? 2 : 1;

    for ($g = 0; $g <= 4; $g++) {
        $y = $marginTop + (int) (($chartHeight / 4) * $g);
        imageline($img, $marginLeft, $y, $width - $marginRight, $y, $gridColor);
        $gridVal = round($maxVal - (($maxVal / 4) * $g), 1);
        imagestring($img, 1, 4, $y - 4, (string) $gridVal, $textColor);
    }
    imageline($img, $marginLeft, $marginTop, $marginLeft, $marginTop + $chartHeight, $axisColor);
    imageline($img, $marginLeft, $marginTop + $chartHeight, $width - $marginRight, $marginTop + $chartHeight, $axisColor);

    $legendX = $marginLeft;
    foreach ($seriesList as $series) {
        $rgb = isset($series['color']) ? $series['color'] : array(2, 125, 193);
        $c = imagecolorallocate($img, (int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        imagefilledrectangle($img, $legendX, 22, $legendX + 10, 32, $c);
        imagestring($img, 2, $legendX + 14, 21, $series['label'], $textColor);
        $legendX += 95;
    }

    $plotWidth = $slotWidth * $count;
    $startX = $marginLeft + (int) max(0, ($chartWidth - $plotWidth) / 2);
    $groupInnerWidth = ($barWidth * $seriesCount) + (max(0, $seriesCount - 1) * 2);

    foreach ($labels as $idx => $label) {
        $slotCenter = $startX + ($idx * $slotWidth) + (int) ($slotWidth / 2);
        $innerX = $slotCenter - (int) ($groupInnerWidth / 2);
        foreach ($seriesList as $series) {
            $val = isset($series['values'][$idx]) ? (float) $series['values'][$idx] : 0;
            $rgb = isset($series['color']) ? $series['color'] : array(2, 125, 193);
            $c = imagecolorallocate($img, (int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
            $barH = $val > 0 ? max(2, (int) round(($val / $maxVal) * $chartHeight)) : 0;
            $y1 = $marginTop + $chartHeight - $barH;
            $y2 = $marginTop + $chartHeight;
            if ($barH > 0) {
                imagefilledrectangle($img, $innerX, $y1, $innerX + $barWidth, $y2, $c);
                imagerectangle($img, $innerX, $y1, $innerX + $barWidth, $y2, $border);
            }
            $innerX += $barWidth + 2;
        }
        if ($idx % $labelStep === 0) {
            auditTicketPdfChartDrawCenteredLabel($img, $label, $slotCenter, $marginTop + $chartHeight + 10, $textColor, 2);
        }
    }

    imagepng($img, $filepath);
    imagedestroy($img);
    return $filepath;
}

function auditTicketPdfBuildNonConformanceRows($checklistItems)
{
    $rows = '';
    $n = 1;
    foreach ($checklistItems as $item) {
        if ($item['ok_status'] !== 'Not OK' && $item['compliance_status'] !== 'Out of Range') {
            continue;
        }
        $cp = $item['checklist'];
        $rows .= '<tr>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center;">' . $n++ . '</td>
            <td style="padding:6px; border:1px solid #d0d7de;">' . htmlspecialchars($cp['CheckpointName']) . '</td>
            <td style="padding:6px; border:1px solid #d0d7de;">' . htmlspecialchars($item['response_value'] ?: '-') . '</td>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center;">' . auditTicketPdfStatusBadgeHtml($item['ok_status']) . '</td>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center;">' . auditTicketPdfStatusBadgeHtml($item['compliance_status'], 'compliance') . '</td>
            <td style="padding:6px; border:1px solid #d0d7de;">' . ($item['remarks'] !== '' ? htmlspecialchars($item['remarks']) : 'Corrective action required') . '</td>
        </tr>';
    }
    if ($rows === '') {
        $rows = '<tr><td colspan="6" style="padding:10px; border:1px solid #d0d7de; text-align:center; color:#198754; font-weight:bold;">No non-conformances recorded — all checkpoints passed.</td></tr>';
    }
    return $rows;
}

function auditTicketPdfBuildHistoryRows($recentAudits)
{
    $rows = '';
    foreach ($recentAudits as $audit) {
        $highlight = !empty($audit['is_current']) ? 'background:#eef6ff; font-weight:bold;' : '';
        $rows .= '<tr style="' . $highlight . '">
            <td style="padding:6px; border:1px solid #d0d7de;">' . htmlspecialchars($audit['ticket_id']) . '</td>
            <td style="padding:6px; border:1px solid #d0d7de;">' . htmlspecialchars($audit['audit_date'] ?: '-') . '</td>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center; color:#198754;">' . (int) $audit['ok'] . '</td>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center; color:#dc3545;">' . (int) $audit['not_ok'] . '</td>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center;">' . $audit['pass_rate'] . '%</td>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center;">' . (!empty($audit['is_current']) ? 'Current' : '-') . '</td>
        </tr>';
    }
    if ($rows === '') {
        $rows = '<tr><td colspan="6" style="padding:10px; border:1px solid #d0d7de; text-align:center; color:#6c757d;">No prior audit history available for this branch.</td></tr>';
    }
    return $rows;
}

function auditTicketPdfFormatDateTimeCell($date, $time)
{
    $value = trim((string) $date . ' ' . (string) $time);
    return $value !== '' ? htmlspecialchars($value) : '-';
}

function auditTicketPdfStatusBadgeHtml($status, $type = 'ok')
{
    $colors = array(
        'OK' => '#198754',
        'Not OK' => '#dc3545',
        'Pending' => '#6c757d',
        'In Range' => '#198754',
        'Out of Range' => '#dc3545',
        'N/A' => '#0d6efd',
    );
    $bg = isset($colors[$status]) ? $colors[$status] : '#6c757d';
    return '<span style="display:inline-block; padding:2px 8px; border-radius:10px; font-size:9px; font-weight:bold; color:#fff; background:' . $bg . ';">'
        . htmlspecialchars($status) . '</span>';
}

function auditTicketPdfGetBrandAssets($ticket)
{
    $adminImgDir = dirname(__DIR__) . '/../img/';
    $techxpertLogo = 'https://techxpertindia.in/techxpertservice/assets/techx-14.png';
    $localLogoCandidates = array(
        $adminImgDir . 'tech-logo.jpg',
        $adminImgDir . 'logo-flat.svg',
        $adminImgDir . 'logo.svg',
    );
    foreach ($localLogoCandidates as $candidate) {
        if (is_file($candidate)) {
            $resolved = realpath($candidate);
            $techxpertLogo = str_replace('\\', '/', $resolved !== false ? $resolved : $candidate);
            break;
        }
    }

    $clientLogo = '';
    $corporateId = is_array($ticket) ? (int) $ticket['CorporateID'] : 0;
    if ($corporateId === 183) {
        $clientLogo = 'https://techxpertindia.in/admin/img/innov_logo.jpg';
    }
    $clientLogoCandidates = array(
        dirname(__DIR__) . '/../media/company/' . $corporateId . '/logo.png',
        dirname(__DIR__) . '/../media/company/' . $corporateId . '/logo.jpg',
        dirname(__DIR__) . '/../img/innov_logo.jpg',
    );
    foreach ($clientLogoCandidates as $candidate) {
        if (is_file($candidate)) {
            $resolved = realpath($candidate);
            $clientLogo = str_replace('\\', '/', $resolved !== false ? $resolved : $candidate);
            break;
        }
    }

    return array(
        'techxpert_logo' => $techxpertLogo,
        'client_logo' => $clientLogo,
        'company_name' => isset($ticket['CompanyName']) ? $ticket['CompanyName'] : '',
        'iso_line' => '(An ISO 9001:2015, ISO 14001:2015 &amp; ISO 45001:2018 Certified Company)',
        'issuer_name' => 'Techxpert Facilities India Private Limited',
        'issuer_address' => '451 - 452, First Floor, Leela Ram Market, Masjid Moth, South Extension Part - 2, New Delhi, Delhi - 110049, India',
    );
}

function auditTicketPdfBuildFrontPageHeaderHtml($assets)
{
    $techLogo = htmlspecialchars($assets['techxpert_logo']);
    $clientLogoHtml = '';
    if ($assets['client_logo'] !== '') {
        $clientLogoHtml = '<img src="' . htmlspecialchars($assets['client_logo']) . '" style="max-width:95px; max-height:55px;">';
    } elseif ($assets['company_name'] !== '') {
        $clientLogoHtml = '<div style="font-size:18px; font-weight:bold; color:#c00;">' . htmlspecialchars($assets['company_name']) . '</div>';
    }

    return '<table style="width:100%; border-collapse:collapse; margin-bottom:18px;">
        <tr>
            <td style="width:55%; vertical-align:top;">
                <img src="' . $techLogo . '" style="max-width:180px; max-height:52px;">
                <div style="font-size:8px; color:#333; margin-top:4px;">' . $assets['iso_line'] . '</div>
            </td>
            <td style="width:45%; text-align:right; vertical-align:top;">' . $clientLogoHtml . '</td>
        </tr>
    </table>';
}

function auditTicketPdfGeneratePageBorderImage($filepath)
{
    if (!function_exists('imagecreatetruecolor')) {
        return '';
    }

    $w = 595;
    $h = 842;
    $img = imagecreatetruecolor($w, $h);
    imagesavealpha($img, true);
    imagealphablending($img, false);
    $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
    imagefilledrectangle($img, 0, 0, $w, $h, $transparent);
    imagealphablending($img, true);

    $outerColor = imagecolorallocate($img, 26, 77, 124);
    $innerColor = imagecolorallocate($img, 127, 168, 201);

    imagesetthickness($img, 3);
    imagerectangle($img, 10, 10, $w - 11, $h - 11, $outerColor);
    imagesetthickness($img, 1);
    imagerectangle($img, 16, 16, $w - 17, $h - 17, $innerColor);

    imagepng($img, $filepath);
    imagedestroy($img);

    $resolved = realpath($filepath);
    return $resolved !== false ? str_replace('\\', '/', $resolved) : str_replace('\\', '/', $filepath);
}

function auditTicketPdfGetGlobalStyleBlock($borderImagePath = '')
{
    $pageBorderCss = '';
    if ($borderImagePath !== '' && is_file($borderImagePath)) {
        $borderUrl = str_replace('\\', '/', $borderImagePath);
        $pageBorderCss = '@page {
            background-image: url("' . $borderUrl . '");
            background-image-resize: 6;
            background-image-opacity: 1;
            background-repeat: no-repeat;
            background-position: top left;
        }';
    }

    return '<style>
        ' . $pageBorderCss . '
        body { background: #ffffff; color: #111; }
        .at-pdf-page-border-outer {
            position: fixed;
            left: 6mm;
            top: 6mm;
            width: 198mm;
            height: 285mm;
            border: 1.6pt solid #1a4d7c;
            z-index: 0;
            pointer-events: none;
        }
        .at-pdf-page-border-inner {
            position: fixed;
            left: 7.5mm;
            top: 7.5mm;
            width: 195mm;
            height: 282mm;
            border: 0.4pt solid #7fa8c9;
            z-index: 0;
            pointer-events: none;
        }
        .at-contents-wrap {
            background: #ffffff;
            padding: 0 8mm;
        }
        .at-contents-title {
            text-align: center;
            margin: 28px 0 24px 0;
        }
        .at-contents-title span {
            display: inline-block;
            font-family: dejavuserif, times, serif;
            font-size: 26px;
            font-weight: bold;
            text-decoration: underline;
            color: #000;
            letter-spacing: 0.3px;
        }
        div.mpdf_toc {
            counter-reset: at-toc-num;
            font-family: dejavuserif, times, serif;
            font-size: 12pt;
            line-height: 1.2;
            margin: 8px 12mm 0 12mm;
            color: #000;
        }
        div.mpdf_toc_level_0 {
            counter-increment: at-toc-num;
            line-height: 2.1;
            margin: 0;
            padding: 1px 0;
        }
        span.mpdf_toc_t_level_0::before {
            content: counter(at-toc-num) ". ";
            font-weight: bold;
        }
        span.mpdf_toc_t_level_0 {
            font-weight: bold;
            font-family: dejavuserif, times, serif;
        }
        span.mpdf_toc_p_level_0 {
            font-weight: normal;
            font-family: dejavuserif, times, serif;
        }
        a.mpdf_toc_a {
            color: #000;
            text-decoration: none;
        }
    </style>';
}

function auditTicketPdfGetPageBorderHtml()
{
    return '<div class="at-pdf-page-border-outer"></div><div class="at-pdf-page-border-inner"></div>';
}

function auditTicketPdfPrependPageBorder($html)
{
    return auditTicketPdfGetPageBorderHtml() . $html;
}

function auditTicketPdfFormatReportDate($ticket)
{
    $date = '';
    if (!empty($ticket['CompletedDate'])) {
        $date = $ticket['CompletedDate'];
    } elseif (!empty($ticket['CreatedDate'])) {
        $date = $ticket['CreatedDate'];
    }
    if ($date === '') {
        return date('F j, Y');
    }
    $ts = strtotime($date);
    return $ts !== false ? date('F j, Y', $ts) : $date;
}

function auditTicketPdfGetAuditScopeTitle($ticketAudits)
{
    if (empty($ticketAudits)) {
        return 'Comprehensive Audit Report';
    }
    if (count($ticketAudits) === 1) {
        $audit = $ticketAudits[0];
        $master = isset($audit['MasterAuditName']) ? $audit['MasterAuditName'] : '';
        $sub = isset($audit['SubAuditName']) ? $audit['SubAuditName'] : '';
        if ($master !== '' && $sub !== '') {
            return 'Comprehensive ' . $master . ' Audit Report';
        }
        return 'Comprehensive Audit Report';
    }
    return 'Comprehensive Multi-Audit Report';
}

function auditTicketPdfGetAuditTypeLine($ticketAudits)
{
    if (empty($ticketAudits)) {
        return 'Audit Assessment';
    }
    if (count($ticketAudits) === 1) {
        $audit = $ticketAudits[0];
        $master = isset($audit['MasterAuditName']) ? $audit['MasterAuditName'] : '';
        $sub = isset($audit['SubAuditName']) ? $audit['SubAuditName'] : '';
        if ($master !== '' && $sub !== '') {
            return $master . ' and ' . $sub;
        }
        return $sub !== '' ? $sub : $master;
    }

    $names = array();
    foreach ($ticketAudits as $audit) {
        if (!empty($audit['SubAuditName'])) {
            $names[] = $audit['SubAuditName'];
        }
    }
    return implode(' | ', $names);
}

function auditTicketPdfBuildCoverPageHtml($ticket, $branchDetails, $ticketAudits, $assets)
{
    $header = auditTicketPdfBuildFrontPageHeaderHtml($assets);
    $reportTitle = auditTicketPdfGetAuditScopeTitle($ticketAudits);
    $auditTypeLine = auditTicketPdfGetAuditTypeLine($ticketAudits);
    $auditDate = auditTicketPdfFormatReportDate($ticket);
    $branchSite = isset($ticket['BranchSite']) ? $ticket['BranchSite'] : '';
    $solId = isset($branchDetails['branch_code']) && $branchDetails['branch_code'] !== '' ? $branchDetails['branch_code'] : '-';
    $state = isset($branchDetails['state']) && $branchDetails['state'] !== '' ? $branchDetails['state'] : (isset($ticket['BranchState']) ? $ticket['BranchState'] : '-');
    $companyName = $assets['company_name'];

    $sectionLine = 'Section - I';
    if (count($ticketAudits) > 1) {
        $sectionParts = array();
        $sectionNo = 1;
        $roman = array('I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X');
        foreach ($ticketAudits as $audit) {
            $label = isset($roman[$sectionNo - 1]) ? $roman[$sectionNo - 1] : (string) $sectionNo;
            $sectionParts[] = 'Section - ' . $label . ': ' . htmlspecialchars(isset($audit['SubAuditName']) ? $audit['SubAuditName'] : 'Audit');
            $sectionNo++;
        }
        $sectionLine = implode('<br>', $sectionParts);
    } else {
        $sectionLine = htmlspecialchars($sectionLine);
    }

    $conductedFor = '';
    if ($assets['client_logo'] !== '') {
        $conductedFor = '<img src="' . htmlspecialchars($assets['client_logo']) . '" style="max-width:120px; max-height:70px; margin-top:8px;">';
    } else {
        $conductedFor = '<div style="font-size:28px; font-weight:bold; color:#c00; margin-top:10px;">' . htmlspecialchars($companyName) . '</div>';
    }

    return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family: dejavuserif, times, serif; font-size:12px; color:#111; background:#fff;">
        ' . $header . '
        <div style="text-align:center; margin-top:30px;">
            <div style="font-size:24px; font-weight:bold; margin-bottom:8px;">' . htmlspecialchars($reportTitle) . '</div>
            <div style="font-size:13px; margin-bottom:18px;">' . $sectionLine . '</div>
            <div style="font-size:16px; font-weight:bold; margin-bottom:28px;">' . htmlspecialchars($auditTypeLine) . '</div>
            <div style="font-size:13px; margin-bottom:6px;">Conducted for</div>
            ' . $conductedFor . '
            <div style="margin-top:28px; font-size:13px; line-height:1.9;">
                <div><strong>At:</strong> ' . htmlspecialchars($branchSite) . '</div>
                <div><strong>Sol ID:</strong> ' . htmlspecialchars($solId) . '</div>
                <div><strong>State/UT:</strong> ' . htmlspecialchars($state) . '</div>
            </div>
            <div style="margin-top:24px; font-size:13px;">Audit Work Conducted on <strong>' . htmlspecialchars($auditDate) . '</strong></div>
            <div style="margin-top:34px; font-size:13px;">
                <div><strong>By</strong></div>
                <div style="margin-top:8px; font-weight:bold;">' . htmlspecialchars($assets['issuer_name']) . '</div>
                <div style="margin-top:6px; max-width:420px; margin-left:auto; margin-right:auto; line-height:1.6;">' . htmlspecialchars($assets['issuer_address']) . '</div>
            </div>
        </div>
        <pagebreak />
    </body></html>';
}

function auditTicketPdfBuildContentsPreHtml($assets)
{
    $header = auditTicketPdfBuildFrontPageHeaderHtml($assets);
    return '<div class="at-contents-wrap">' . $header . '
        <div class="at-contents-title"><span>Contents</span></div>
    </div>';
}

function auditTicketPdfBuildDisclaimerPageHtml($assets)
{
    $header = auditTicketPdfBuildFrontPageHeaderHtml($assets);
    return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family: dejavuserif, times, serif; font-size:12px; color:#111; background:#fff;">
        ' . $header . '
        <div style="margin-top:30px; padding-right:20px;">
            <div style="font-size:14px; font-weight:bold; margin-bottom:10px;">Disclaimer:</div>
            <div style="font-size:12px; line-height:1.8; text-align:justify;">
                The findings mentioned in report are based on the data &amp; information collected during Assessment period &amp;
                recommendations are based on International Standards &amp; Guidelines. Any modification done in the facility after
                the Assessment period is not covered in the report.
            </div>
        </div>
        <pagebreak />
    </body></html>';
}

function auditTicketPdfBuildChecklistSectionRows($items, &$attachmentBlocks, &$photoCount, $startIndex = 1)
{
    $rows = '';
    $i = $startIndex;
    foreach ($items as $item) {
        $cp = $item['checklist'];
        $bg = '#ffffff';
        if ($item['compliance_status'] === 'Out of Range' || $item['ok_status'] === 'Not OK') {
            $bg = '#fff1f1';
        } elseif ($item['compliance_status'] === 'In Range' || $item['ok_status'] === 'OK') {
            $bg = '#f0fff4';
        }
        $value = $item['response_value'] !== '' ? htmlspecialchars($item['response_value']) : 'Pending';
        $photoCell = '-';
        $imagePath = auditTicketPdfResolveImageForEmbed($item['response_image'], 'checklist');
        if ($imagePath !== '') {
            $photoCount++;
            $photoCell = '<img src="' . $imagePath . '" style="width:52px; height:52px; object-fit:cover; border:1px solid #ccc; border-radius:4px;">';
            $attachmentBlocks .= '
            <div style="width:48%; display:inline-block; vertical-align:top; margin-bottom:14px; page-break-inside:avoid;">
                <table style="width:100%; border-collapse:collapse; border:1px solid #d0d7de;">
                    <tr style="background:#f6f8fa;">
                        <td style="padding:6px 8px; font-size:10px; font-weight:bold; color:#027dc1;">#' . $i . ' - ' . htmlspecialchars($cp['CheckpointName']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px; text-align:center;">
                            <img src="' . $imagePath . '" style="width:100%; max-height:220px; object-fit:contain; border:1px solid #e5e7eb; border-radius:4px;">
                            <div style="font-size:9px; color:#666; margin-top:6px;">Status: ' . auditTicketPdfStatusBadgeHtml($item['ok_status']) . ' | Value: ' . $value . '</div>
                        </td>
                    </tr>
                </table>
            </div>';
        }

        $rows .= '<tr style="background:' . $bg . ';">
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center;">' . $i . '</td>
            <td style="padding:6px; border:1px solid #d0d7de;">' . htmlspecialchars($cp['CheckpointName']) . '</td>
            <td style="padding:6px; border:1px solid #d0d7de;">' . htmlspecialchars($cp['IdealValue'] ?: '-') . '</td>
            <td style="padding:6px; border:1px solid #d0d7de;">' . htmlspecialchars($cp['MinValue'] ?: '-') . '</td>
            <td style="padding:6px; border:1px solid #d0d7de;">' . htmlspecialchars($cp['MaxValue'] ?: '-') . '</td>
            <td style="padding:6px; border:1px solid #d0d7de; font-weight:bold;">' . $value . '</td>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center;">' . auditTicketPdfStatusBadgeHtml($item['ok_status']) . '</td>
            <td style="padding:6px; border:1px solid #d0d7de;">' . ($item['remarks'] !== '' ? htmlspecialchars($item['remarks']) : '-') . '</td>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center;">' . auditTicketPdfStatusBadgeHtml($item['compliance_status'], 'compliance') . '</td>
            <td style="padding:6px; border:1px solid #d0d7de; text-align:center;">' . $photoCell . '</td>
        </tr>';
        $i++;
    }

    return $rows;
}

function generateCorporateAuditBranchReportPdf($conn, $auditTicketId, $options = array())
{
    $auditTicketId = (int) $auditTicketId;
    $response = array('error' => true, 'message' => 'Unable to generate report.');

    if ($auditTicketId <= 0) {
        $response['message'] = 'AuditTicketID is required.';
        return $response;
    }

    if (!class_exists('Mpdf\Mpdf')) {
        require_once dirname(__DIR__) . '/../vendor/autoload.php';
    }
    require_once dirname(__DIR__) . '/../employees/controller/employee_controller.php';

    $ticket = getCorporateAuditTicketById($conn, $auditTicketId);
    if (!$ticket) {
        $response['message'] = 'Audit ticket not found.';
        return $response;
    }

    $ticketAudits = getCorporateAuditTicketAudits($conn, $auditTicketId);
    $groupedChecklists = getAuditTicketChecklistGroupedBySubAudit($conn, $auditTicketId);
    $brandAssets = auditTicketPdfGetBrandAssets($ticket);
    $isMultiAudit = count($ticketAudits) > 1;

    $checklistItems = getCorporateAuditTicketChecklistWithResponses($conn, $auditTicketId);
    $stats = getAuditTicketCompletionStats($conn, $auditTicketId);
    $complianceBreakdown = auditTicketPdfBuildComplianceBreakdown($checklistItems);
    $trendData = getAuditTicketBranchTrendData($conn, $ticket, $auditTicketId);

    $employeeObj = new Employee($conn);
    $employeeArray = $employeeObj->setEmployeeArray('All');

    $technicianId = (int) (isset($ticket['Technician']) && (int) $ticket['Technician'] > 0 ? $ticket['Technician'] : $ticket['AssignedTo']);
    $technicianName = 'N.A.';
    $technicianPhone = '';
    if ($technicianId > 0 && isset($employeeArray[$technicianId])) {
        $technicianName = $employeeArray[$technicianId]['Name'];
        $technicianPhone = $employeeArray[$technicianId]['ContactNumber'];
    }

    $bamName = 'N.A.';
    $bamPhone = '';
    $bamId = isset($ticket['BranchAccountManager']) ? (int) $ticket['BranchAccountManager'] : -1;
    if ($bamId > 0 && isset($employeeArray[$bamId])) {
        $bamName = $employeeArray[$bamId]['Name'];
        $bamPhone = $employeeArray[$bamId]['ContactNumber'];
    }

    $branchDetails = auditTicketFormatBranchDetailsForApi($ticket);
    $reportMeta = auditTicketFormatReportMetaForApi($ticket);
    $floor = $branchDetails['floor'] !== '' ? $branchDetails['floor'] : '-';
    $siteAddress = $branchDetails['full_address'] !== '' ? $branchDetails['full_address'] : '-';
    $clientRep = $reportMeta['client_representative'] !== '' ? $reportMeta['client_representative'] : '_______________________';
    $clientRepContact = $reportMeta['client_representative_contact'] !== '' ? $reportMeta['client_representative_contact'] : '';
    $clientRepDesignation = $reportMeta['client_representative_designation'] !== '' ? $reportMeta['client_representative_designation'] : '';
    $clientRepEmail = $reportMeta['client_representative_email'] !== '' ? $reportMeta['client_representative_email'] : '';
    $observation = $reportMeta['audit_observation'] !== '' ? nl2br(htmlspecialchars($reportMeta['audit_observation'])) : '-';
    $conclusion = $reportMeta['audit_conclusion'] !== '' ? nl2br(htmlspecialchars($reportMeta['audit_conclusion'])) : '-';
    $technicianNotes = $reportMeta['technician_notes'] !== '' ? nl2br(htmlspecialchars($reportMeta['technician_notes'])) : '-';
    $problemReported = $reportMeta['problem_reported_by_client'] !== '' ? nl2br(htmlspecialchars($reportMeta['problem_reported_by_client'])) : '-';
    $actionTaken = $reportMeta['action_taken'] !== '' ? nl2br(htmlspecialchars($reportMeta['action_taken'])) : '-';
    $generalRemarks = $reportMeta['general_remarks'] !== '' ? nl2br(htmlspecialchars($reportMeta['general_remarks'])) : '-';
    $geoLocation = trim($reportMeta['report_latitude'] . ', ' . $reportMeta['report_longitude'], ', ');
    if ($geoLocation === '') {
        $geoLocation = '-';
    }

    $reportDir = AUDIT_TICKET_REPORTS_DIR;
    $chartDir = $reportDir . 'charts/';
    if (!is_dir($reportDir)) {
        mkdir($reportDir, 0777, true);
    }
    if (!is_dir($chartDir)) {
        mkdir($chartDir, 0777, true);
    }

    $chartToken = 'audit_' . $auditTicketId . '_' . time();
    $okStatusChart = auditTicketPdfGeneratePieChart(array(
        array('label' => 'OK', 'value' => (int) $stats['ok'], 'color' => array(25, 135, 84)),
        array('label' => 'Not OK', 'value' => (int) $stats['not_ok'], 'color' => array(220, 53, 69)),
        array('label' => 'Pending', 'value' => max(0, (int) $stats['pending']), 'color' => array(108, 117, 125)),
    ), 'Checkpoint Status Distribution', $chartDir . $chartToken . '_ok_status.png');

    $complianceChart = auditTicketPdfGeneratePieChart(array(
        array('label' => 'In Range', 'value' => (int) $complianceBreakdown['In Range'], 'color' => array(25, 135, 84)),
        array('label' => 'Out of Range', 'value' => (int) $complianceBreakdown['Out of Range'], 'color' => array(220, 53, 69)),
        array('label' => 'Pending', 'value' => (int) $complianceBreakdown['Pending'], 'color' => array(108, 117, 125)),
        array('label' => 'N/A', 'value' => (int) $complianceBreakdown['N/A'], 'color' => array(2, 125, 193)),
    ), 'Compliance Distribution', $chartDir . $chartToken . '_compliance.png');

    $completionChart = auditTicketPdfGenerateDonutChart(
        (int) round($stats['completion_percent']),
        $chartDir . $chartToken . '_completion.png',
        'Filled'
    );

    $dailyLabels = array();
    $dailyConversion = array();
    $dailyRaised = array();
    $dailyCompleted = array();
    foreach ($trendData['daily'] as $dayRow) {
        $dailyLabels[] = $dayRow['label'];
        $dailyConversion[] = $dayRow['conversion'];
        $dailyRaised[] = $dayRow['raised'];
        $dailyCompleted[] = $dayRow['completed'];
    }
    $dailyConversionChart = auditTicketPdfGenerateBarChart(
        $dailyLabels,
        $dailyConversion,
        'Daily Conversion Trend (14 Days %)',
        $chartDir . $chartToken . '_daily_conversion.png',
        array(2, 125, 193),
        100
    );
    $dailyVolumeChart = auditTicketPdfGenerateGroupedBarChart(
        $dailyLabels,
        array(
            array('label' => 'Raised', 'values' => $dailyRaised, 'color' => array(108, 117, 125)),
            array('label' => 'Completed', 'values' => $dailyCompleted, 'color' => array(25, 135, 84)),
        ),
        'Daily Audit Volume (14 Days)',
        $chartDir . $chartToken . '_daily_volume.png'
    );

    $monthlyLabels = array();
    $monthlyRaised = array();
    $monthlyCompleted = array();
    $monthlyPassRates = array();
    foreach ($trendData['monthly'] as $monthRow) {
        $monthlyLabels[] = $monthRow['label'];
        $monthlyRaised[] = $monthRow['raised'];
        $monthlyCompleted[] = $monthRow['completed'];
    }
    foreach ($trendData['monthly_pass_rate'] as $monthPassRow) {
        $monthlyPassRates[] = $monthPassRow['pass_rate'];
    }
    $monthlyTrendChart = auditTicketPdfGenerateGroupedBarChart(
        $monthlyLabels,
        array(
            array('label' => 'Raised', 'values' => $monthlyRaised, 'color' => array(108, 117, 125)),
            array('label' => 'Completed', 'values' => $monthlyCompleted, 'color' => array(2, 125, 193)),
        ),
        'Monthly Audit Trend (Last 6 Months)',
        $chartDir . $chartToken . '_monthly_trend.png'
    );
    $monthlyPassChart = auditTicketPdfGenerateBarChart(
        $monthlyLabels,
        $monthlyPassRates,
        'Monthly Pass Rate Trend (%)',
        $chartDir . $chartToken . '_monthly_pass.png',
        array(25, 135, 84),
        100
    );

    $recentPassLabels = array();
    $recentPassValues = array();
    $recentSlice = array_slice(array_reverse($trendData['recent_audits']), 0, 8);
    foreach ($recentSlice as $recentRow) {
        $recentPassLabels[] = '#' . substr($recentRow['ticket_id'], -4);
        $recentPassValues[] = $recentRow['pass_rate'];
    }
    $auditPassTrendChart = auditTicketPdfGenerateBarChart(
        $recentPassLabels,
        $recentPassValues,
        'Pass Rate — Last Audits at Branch',
        $chartDir . $chartToken . '_audit_pass_trend.png',
        array(2, 125, 193),
        100
    );

    $passRate = $stats['total'] > 0 ? round(($stats['ok'] / $stats['total']) * 100, 1) : 0;
    $complianceRate = $stats['total'] > 0 ? round((($complianceBreakdown['In Range']) / $stats['total']) * 100, 1) : 0;
    $auditGrade = auditTicketPdfCalculateAuditGrade($passRate, (int) $stats['not_ok']);
    $riskLevel = auditTicketPdfCalculateRiskLevel((int) $stats['not_ok'], (int) $complianceBreakdown['Out of Range']);
    $turnaroundHours = auditTicketPdfCalculateTurnaroundHours($ticket);
    $turnaroundText = $turnaroundHours !== null ? $turnaroundHours . ' hrs' : '-';

    $trendDirection = 'Stable';
    $trendColor = '#6c757d';
    if (!empty($trendData['previous_audit'])) {
        $prevPass = (float) $trendData['previous_audit']['pass_rate'];
        if ($passRate > $prevPass) {
            $trendDirection = 'Improving (+' . round($passRate - $prevPass, 1) . '% vs last audit)';
            $trendColor = '#198754';
        } elseif ($passRate < $prevPass) {
            $trendDirection = 'Declining (' . round($passRate - $prevPass, 1) . '% vs last audit)';
            $trendColor = '#dc3545';
        } else {
            $trendDirection = 'Stable (same as last audit)';
        }
    }

    $nonConformanceRows = auditTicketPdfBuildNonConformanceRows($checklistItems);
    $historyRows = auditTicketPdfBuildHistoryRows($trendData['recent_audits']);
    $previousAuditSummary = '-';
    if (!empty($trendData['previous_audit'])) {
        $prev = $trendData['previous_audit'];
        $previousAuditSummary = htmlspecialchars($prev['ticket_id']) . ' on ' . htmlspecialchars($prev['audit_date'])
            . ' — Pass Rate: ' . $prev['pass_rate'] . '% | Not OK: ' . (int) $prev['not_ok'];
    }

    $attachmentBlocks = '';
    $photoCount = 0;
    $checklistSectionsHtml = '';
    $globalIndex = 1;

    if ($isMultiAudit) {
        foreach ($groupedChecklists as $group) {
            $sectionRows = auditTicketPdfBuildChecklistSectionRows($group['items'], $attachmentBlocks, $photoCount, $globalIndex);
            $globalIndex += count($group['items']);
            $tocLabel = htmlspecialchars($group['sub_audit_name'] . ' - Checklist');
            $checklistSectionsHtml .= '<tocentry content="' . $tocLabel . '" level="0" />
            <div class="section-title" style="margin-bottom:0;">' . htmlspecialchars($group['sub_audit_name']) . ' — Detailed Checklist</div>
            <div style="font-size:9px; color:#6c757d; margin:4px 0 8px;">' . htmlspecialchars($group['master_audit_name']) . '</div>
            <table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
                <thead>
                    <tr style="background:#eef4f8;">
                        <th style="padding:6px; border:1px solid #d0d7de; width:4%;">#</th>
                        <th style="padding:6px; border:1px solid #d0d7de;">Checkpoint</th>
                        <th style="padding:6px; border:1px solid #d0d7de;">Ideal</th>
                        <th style="padding:6px; border:1px solid #d0d7de;">Min</th>
                        <th style="padding:6px; border:1px solid #d0d7de;">Max</th>
                        <th style="padding:6px; border:1px solid #d0d7de;">Value</th>
                        <th style="padding:6px; border:1px solid #d0d7de;">OK / Not OK</th>
                        <th style="padding:6px; border:1px solid #d0d7de;">Remarks</th>
                        <th style="padding:6px; border:1px solid #d0d7de;">Compliance</th>
                        <th style="padding:6px; border:1px solid #d0d7de;">Photo</th>
                    </tr>
                </thead>
                <tbody>' . $sectionRows . '</tbody>
            </table>';
        }
    } else {
        $checklistRows = auditTicketPdfBuildChecklistSectionRows($checklistItems, $attachmentBlocks, $photoCount, 1);
        $checklistSectionsHtml = '<tocentry content="Detailed Checkpoint Checklist" level="0" />
        <div class="section-title" style="margin-bottom:0;">Detailed Checkpoint Checklist</div>
        <table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
            <thead>
                <tr style="background:#eef4f8;">
                    <th style="padding:6px; border:1px solid #d0d7de; width:4%;">#</th>
                    <th style="padding:6px; border:1px solid #d0d7de;">Checkpoint</th>
                    <th style="padding:6px; border:1px solid #d0d7de;">Ideal</th>
                    <th style="padding:6px; border:1px solid #d0d7de;">Min</th>
                    <th style="padding:6px; border:1px solid #d0d7de;">Max</th>
                    <th style="padding:6px; border:1px solid #d0d7de;">Value</th>
                    <th style="padding:6px; border:1px solid #d0d7de;">OK / Not OK</th>
                    <th style="padding:6px; border:1px solid #d0d7de;">Remarks</th>
                    <th style="padding:6px; border:1px solid #d0d7de;">Compliance</th>
                    <th style="padding:6px; border:1px solid #d0d7de;">Photo</th>
                </tr>
            </thead>
            <tbody>' . $checklistRows . '</tbody>
        </table>';
    }

    if ($attachmentBlocks === '') {
        $attachmentBlocks = '<p style="color:#6c757d; font-style:italic; padding:8px 0;">No checkpoint photos were attached for this audit.</p>';
    }

    $signaturePath = auditTicketPdfResolveImageForEmbed($reportMeta['client_signature'], 'signature');
    if ($signaturePath === '' && !empty($ticket['ClientSignature'])) {
        $signaturePath = auditTicketPdfResolveImageForEmbed($ticket['ClientSignature'], 'signature');
    }
    if ($signaturePath !== '') {
        $clientSignatureHtml = '<img src="' . $signaturePath . '" style="max-width:200px; max-height:90px; display:block; margin-top:8px; object-fit:contain;">';
    } else {
        $clientSignatureHtml = '<div style="margin-top:30px; border-bottom:1px solid #333; width:180px;">&nbsp;</div>';
    }

    $defaultLogo = $brandAssets['techxpert_logo'];
    if ((int) $ticket['CorporateID'] === 183 && $brandAssets['client_logo'] !== '') {
        $defaultLogo = $brandAssets['client_logo'];
    }

    $auditScopeText = auditTicketPdfGetAuditTypeLine($ticketAudits);
    if ($isMultiAudit) {
        $auditScopeText .= ' | ' . count($ticketAudits) . ' audits on ticket #' . $ticket['TicketID'];
    } else {
        $auditScopeText = htmlspecialchars($ticket['MasterAuditName']) . ' &rarr; ' . htmlspecialchars($ticket['SubAuditName']) . ' | ' . (int) $stats['total'] . ' checkpoints evaluated';
    }

    $coverHtml = auditTicketPdfBuildCoverPageHtml($ticket, $branchDetails, $ticketAudits, $brandAssets);
    $contentsPreHtml = auditTicketPdfBuildContentsPreHtml($brandAssets);
    $disclaimerHtml = auditTicketPdfBuildDisclaimerPageHtml($brandAssets);

    $html = '<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        body { font-family: poppins, sans-serif; font-size: 10.5px; color: #212529; }
        .section-title { background: #027dc1; color: #fff; padding: 8px 10px; font-weight: bold; font-size: 11px; letter-spacing: 0.3px; }
        .kpi-box { border: 1px solid #d0d7de; padding: 10px; text-align: center; background: #f8fafc; }
        .kpi-value { font-size: 18px; font-weight: bold; color: #027dc1; }
        .kpi-label { font-size: 9px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.4px; }
        .detail-label { font-weight: bold; width: 38%; color: #374151; }
        .detail-value { color: #111827; }
    </style>
    </head><body>

<tocentry content="Executive Summary" level="0" />
<table style="width:100%; border-collapse:collapse; margin-bottom:14px; border-bottom:3px solid #027dc1;">
    <tr>
        <td style="width:28%; vertical-align:middle;">
            <img src="' . $defaultLogo . '" style="max-width:170px; max-height:55px;">
        </td>
        <td style="width:44%; text-align:center; vertical-align:middle;">
            <div style="font-size:16px; font-weight:bold; color:#027dc1; letter-spacing:0.5px;">CORPORATE AUDIT BRANCH REPORT</div>
            <div style="font-size:10px; color:#6c757d; margin-top:4px;">Professional Site Audit &amp; Compliance Assessment</div>
            <div style="font-size:9px; color:#9ca3af; margin-top:2px;">Confidential — For Client Use Only</div>
        </td>
        <td style="width:28%; text-align:right; vertical-align:middle; font-size:9px; color:#4b5563;">
            <strong>Report Ref:</strong> ' . htmlspecialchars($ticket['TicketID']) . '<br>
            <strong>Generated:</strong> ' . date('d-M-Y H:i') . '<br>
            <strong>Audit Type:</strong> ' . htmlspecialchars($ticket['SubAuditName']) . '
        </td>
    </tr>
</table>

<table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
    <tr>
        <td class="section-title" style="width:50%;">Customer / Site Details</td>
        <td class="section-title" style="width:50%;">Audit Ticket Details</td>
    </tr>
    <tr>
        <td style="vertical-align:top; padding:10px; border:1px solid #d0d7de; border-top:none;">
            <table style="width:100%;">
                <tr><td class="detail-label">Company</td><td class="detail-value">: ' . htmlspecialchars($ticket['CompanyName']) . '</td></tr>
                <tr><td class="detail-label">Branch</td><td class="detail-value">: ' . htmlspecialchars($ticket['BranchSite']) . '</td></tr>
                <tr><td class="detail-label">Branch Code</td><td class="detail-value">: ' . htmlspecialchars($branchDetails['branch_code'] ?: '-') . '</td></tr>
                <tr><td class="detail-label">Floor / Level</td><td class="detail-value">: ' . htmlspecialchars($floor) . '</td></tr>
                <tr><td class="detail-label">Address</td><td class="detail-value">: ' . htmlspecialchars($siteAddress) . '</td></tr>
                <tr><td class="detail-label">City / State</td><td class="detail-value">: ' . htmlspecialchars(trim($branchDetails['city'] . ', ' . $branchDetails['state'], ', ')) . '</td></tr>
                <tr><td class="detail-label">PIN Code</td><td class="detail-value">: ' . htmlspecialchars($branchDetails['postal_code'] ?: '-') . '</td></tr>
                <tr><td class="detail-label">Contact</td><td class="detail-value">: ' . htmlspecialchars($branchDetails['mobile']) . '</td></tr>
                <tr><td class="detail-label">Email</td><td class="detail-value">: ' . htmlspecialchars($branchDetails['email'] ?: '-') . '</td></tr>
            </table>
        </td>
        <td style="vertical-align:top; padding:10px; border:1px solid #d0d7de; border-top:none; border-left:none;">
            <table style="width:100%;">
                <tr><td class="detail-label">Ticket ID</td><td class="detail-value">: ' . htmlspecialchars($ticket['TicketID']) . '</td></tr>
                <tr><td class="detail-label">Master Audit</td><td class="detail-value">: ' . htmlspecialchars($ticket['MasterAuditName']) . '</td></tr>
                <tr><td class="detail-label">Sub Audit</td><td class="detail-value">: ' . htmlspecialchars($ticket['SubAuditName']) . '</td></tr>
                <tr><td class="detail-label">Status</td><td class="detail-value">: ' . htmlspecialchars($ticket['Status']) . '</td></tr>
                <tr><td class="detail-label">Raised On</td><td class="detail-value">: ' . htmlspecialchars(trim($ticket['CreatedDate'] . ' ' . $ticket['CreatedTime'])) . '</td></tr>
                <tr><td class="detail-label">Completed On</td><td class="detail-value">: ' . htmlspecialchars(trim(($ticket['CompletedDate'] ?: '-') . ' ' . ($ticket['CompletedTime'] ?: ''))) . '</td></tr>
                <tr><td class="detail-label">Auditor</td><td class="detail-value">: ' . htmlspecialchars($technicianName) . ' (' . htmlspecialchars($technicianPhone) . ')</td></tr>
                <tr><td class="detail-label">Branch Account Manager</td><td class="detail-value">: ' . htmlspecialchars($bamName) . ' (' . htmlspecialchars($bamPhone) . ')</td></tr>
                <tr><td class="detail-label">Geo Location</td><td class="detail-value">: ' . htmlspecialchars($geoLocation) . '</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="section-title" style="margin-bottom:0;">Executive Audit Summary</div>
<table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <tr>
        <td class="kpi-box" style="width:16%;"><div class="kpi-value" style="color:' . $auditGrade['color'] . ';">' . $auditGrade['grade'] . '</div><div class="kpi-label">Audit Grade</div><div style="font-size:8px; color:#6c757d;">' . $auditGrade['label'] . '</div></td>
        <td class="kpi-box" style="width:16%;"><div class="kpi-value" style="color:' . $riskLevel['color'] . ';">' . $riskLevel['level'] . '</div><div class="kpi-label">Risk Level</div></td>
        <td class="kpi-box" style="width:17%;"><div class="kpi-value" style="color:' . $trendColor . '; font-size:11px;">' . htmlspecialchars($trendDirection) . '</div><div class="kpi-label">Trend vs Last Audit</div></td>
        <td class="kpi-box" style="width:17%;"><div class="kpi-value">' . $turnaroundText . '</div><div class="kpi-label">Turnaround Time</div></td>
        <td class="kpi-box" style="width:17%;"><div class="kpi-value">' . (int) $trendData['branch_totals']['total_audits'] . '</div><div class="kpi-label">Branch Audits (All)</div></td>
        <td class="kpi-box" style="width:17%;"><div class="kpi-value">' . $trendData['branch_totals']['avg_pass_rate'] . '%</div><div class="kpi-label">Branch Avg Pass Rate</div></td>
    </tr>
</table>
<table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <tr><td style="padding:8px; border:1px solid #d0d7de; width:22%; font-weight:bold; background:#f9fafb;">Previous Audit at Branch</td><td style="padding:8px; border:1px solid #d0d7de;">' . $previousAuditSummary . '</td></tr>
    <tr><td style="padding:8px; border:1px solid #d0d7de; font-weight:bold; background:#f9fafb;">Audit Scope</td><td style="padding:8px; border:1px solid #d0d7de;">' . $auditScopeText . '</td></tr>
</table>

<tocentry content="Audit Lifecycle Timeline" level="0" />
<div class="section-title" style="margin-bottom:0;">Audit Lifecycle Timeline</div>
<table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <tr style="background:#eef4f8;">
        <th style="padding:6px; border:1px solid #d0d7de;">Stage</th>
        <th style="padding:6px; border:1px solid #d0d7de;">Date &amp; Time</th>
    </tr>
    <tr><td style="padding:6px; border:1px solid #d0d7de;">Raised</td><td style="padding:6px; border:1px solid #d0d7de;">' . auditTicketPdfFormatDateTimeCell($ticket['CreatedDate'], $ticket['CreatedTime']) . '</td></tr>
    <tr><td style="padding:6px; border:1px solid #d0d7de;">Assigned</td><td style="padding:6px; border:1px solid #d0d7de;">' . auditTicketPdfFormatDateTimeCell($ticket['AssignedDate'], $ticket['AssignedTime']) . '</td></tr>
    <tr><td style="padding:6px; border:1px solid #d0d7de;">Started</td><td style="padding:6px; border:1px solid #d0d7de;">' . auditTicketPdfFormatDateTimeCell($ticket['StartedDate'], $ticket['StartedTime']) . '</td></tr>
    <tr><td style="padding:6px; border:1px solid #d0d7de;">Completed</td><td style="padding:6px; border:1px solid #d0d7de;">' . auditTicketPdfFormatDateTimeCell($ticket['CompletedDate'], $ticket['CompletedTime']) . '</td></tr>
    <tr><td style="padding:6px; border:1px solid #d0d7de;">Closed</td><td style="padding:6px; border:1px solid #d0d7de;">' . auditTicketPdfFormatDateTimeCell(isset($ticket['ClosedDate']) ? $ticket['ClosedDate'] : '', isset($ticket['ClosedTime']) ? $ticket['ClosedTime'] : '') . '</td></tr>
</table>

<table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <tr>
        <td class="kpi-box" style="width:20%;"><div class="kpi-value">' . (int) $stats['total'] . '</div><div class="kpi-label">Total Checkpoints</div></td>
        <td class="kpi-box" style="width:20%;"><div class="kpi-value" style="color:#198754;">' . (int) $stats['ok'] . '</div><div class="kpi-label">OK</div></td>
        <td class="kpi-box" style="width:20%;"><div class="kpi-value" style="color:#dc3545;">' . (int) $stats['not_ok'] . '</div><div class="kpi-label">Not OK</div></td>
        <td class="kpi-box" style="width:20%;"><div class="kpi-value">' . $passRate . '%</div><div class="kpi-label">Pass Rate</div></td>
        <td class="kpi-box" style="width:20%;"><div class="kpi-value">' . $complianceRate . '%</div><div class="kpi-label">In Range</div></td>
    </tr>
</table>

<tocentry content="Audit Analytics and Visual Summary" level="0" />
<div class="section-title" style="margin-bottom:8px;">Audit Analytics &amp; Visual Summary</div>
<table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
    <tr>
        <td style="width:33%; text-align:center; vertical-align:top; padding:6px; border:1px solid #d0d7de;">
            ' . ($okStatusChart !== '' ? '<img src="' . $okStatusChart . '" style="width:100%; max-height:200px;">' : '') . '
        </td>
        <td style="width:33%; text-align:center; vertical-align:top; padding:6px; border:1px solid #d0d7de;">
            ' . ($complianceChart !== '' ? '<img src="' . $complianceChart . '" style="width:100%; max-height:200px;">' : '') . '
        </td>
        <td style="width:34%; text-align:center; vertical-align:top; padding:6px; border:1px solid #d0d7de;">
            ' . ($completionChart !== '' ? '<img src="' . $completionChart . '" style="width:130px; max-height:130px;">' : '') . '
            <div style="font-size:9px; color:#6c757d; margin-top:4px;">' . (int) $stats['filled'] . ' of ' . (int) $stats['total'] . ' checkpoints filled</div>
        </td>
    </tr>
</table>

<div class="section-title" style="margin-bottom:8px;">Trend Analysis — Daily &amp; Monthly</div>
<table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <tr>
        <td style="width:50%; text-align:center; vertical-align:top; padding:6px; border:1px solid #d0d7de;">
            ' . ($dailyConversionChart !== '' ? '<img src="' . $dailyConversionChart . '" style="width:100%; max-height:240px; object-fit:contain;">' : '') . '
        </td>
        <td style="width:50%; text-align:center; vertical-align:top; padding:6px; border:1px solid #d0d7de;">
            ' . ($dailyVolumeChart !== '' ? '<img src="' . $dailyVolumeChart . '" style="width:100%; max-height:240px; object-fit:contain;">' : '') . '
        </td>
    </tr>
    <tr>
        <td style="width:50%; text-align:center; vertical-align:top; padding:6px; border:1px solid #d0d7de;">
            ' . ($monthlyTrendChart !== '' ? '<img src="' . $monthlyTrendChart . '" style="width:100%; max-height:240px; object-fit:contain;">' : '') . '
        </td>
        <td style="width:50%; text-align:center; vertical-align:top; padding:6px; border:1px solid #d0d7de;">
            ' . ($monthlyPassChart !== '' ? '<img src="' . $monthlyPassChart . '" style="width:100%; max-height:240px; object-fit:contain;">' : '') . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="text-align:center; vertical-align:top; padding:6px; border:1px solid #d0d7de;">
            ' . ($auditPassTrendChart !== '' ? '<img src="' . $auditPassTrendChart . '" style="width:100%; max-height:240px; object-fit:contain;">' : '') . '
        </td>
    </tr>
</table>

<tocentry content="Branch Audit History" level="0" />
<div class="section-title" style="margin-bottom:0;">Branch Audit History (Last 12 Audits)</div>
<table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <thead>
        <tr style="background:#eef4f8;">
            <th style="padding:6px; border:1px solid #d0d7de;">Ticket ID</th>
            <th style="padding:6px; border:1px solid #d0d7de;">Audit Date</th>
            <th style="padding:6px; border:1px solid #d0d7de;">OK</th>
            <th style="padding:6px; border:1px solid #d0d7de;">Not OK</th>
            <th style="padding:6px; border:1px solid #d0d7de;">Pass Rate</th>
            <th style="padding:6px; border:1px solid #d0d7de;">Note</th>
        </tr>
    </thead>
    <tbody>' . $historyRows . '</tbody>
</table>

<tocentry content="Non-Conformance Register" level="0" />
<div class="section-title" style="margin-bottom:0;">Non-Conformance Register</div>
<table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <thead>
        <tr style="background:#fff1f1;">
            <th style="padding:6px; border:1px solid #d0d7de; width:4%;">#</th>
            <th style="padding:6px; border:1px solid #d0d7de;">Checkpoint</th>
            <th style="padding:6px; border:1px solid #d0d7de;">Actual Value</th>
            <th style="padding:6px; border:1px solid #d0d7de;">Status</th>
            <th style="padding:6px; border:1px solid #d0d7de;">Compliance</th>
            <th style="padding:6px; border:1px solid #d0d7de;">Remarks / Action</th>
        </tr>
    </thead>
    <tbody>' . $nonConformanceRows . '</tbody>
</table>

' . $checklistSectionsHtml . '

<tocentry content="Audit Notes and Findings" level="0" />
<div class="section-title" style="margin-bottom:0;">Audit Notes &amp; Findings</div>
<table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <tr><td style="padding:8px; border:1px solid #d0d7de; width:22%; font-weight:bold; background:#f9fafb;">Problem Reported by Client</td><td style="padding:8px; border:1px solid #d0d7de;">' . $problemReported . '</td></tr>
    <tr><td style="padding:8px; border:1px solid #d0d7de; font-weight:bold; background:#f9fafb;">Observation</td><td style="padding:8px; border:1px solid #d0d7de;">' . $observation . '</td></tr>
    <tr><td style="padding:8px; border:1px solid #d0d7de; font-weight:bold; background:#f9fafb;">Action Taken</td><td style="padding:8px; border:1px solid #d0d7de;">' . $actionTaken . '</td></tr>
    <tr><td style="padding:8px; border:1px solid #d0d7de; font-weight:bold; background:#f9fafb;">Conclusion</td><td style="padding:8px; border:1px solid #d0d7de;">' . $conclusion . '</td></tr>
    <tr><td style="padding:8px; border:1px solid #d0d7de; font-weight:bold; background:#f9fafb;">Auditor Notes</td><td style="padding:8px; border:1px solid #d0d7de;">' . $technicianNotes . '</td></tr>
    <tr><td style="padding:8px; border:1px solid #d0d7de; font-weight:bold; background:#f9fafb;">General Remarks</td><td style="padding:8px; border:1px solid #d0d7de;">' . $generalRemarks . '</td></tr>
</table>

<tocentry content="Site Photographs" level="0" />
<div class="section-title" style="margin-bottom:8px;">Site Photo Evidence (' . $photoCount . ' attachment' . ($photoCount === 1 ? '' : 's') . ')</div>
<div style="margin-bottom:16px;">' . $attachmentBlocks . '</div>

<tocentry content="Acknowledgement" level="0" />
<div class="section-title" style="margin-bottom:0;">Authorization &amp; Sign-off</div>
<table style="width:100%; border-collapse:collapse; margin-top:0; page-break-inside:avoid;">
    <tr>
        <td style="padding:16px; border:1px solid #d0d7de; width:50%; vertical-align:top;">
            <strong>Auditor</strong><br>
            Name: ' . htmlspecialchars($technicianName) . '<br>
            Contact: ' . htmlspecialchars($technicianPhone) . '<br>
            <div style="margin-top:30px; border-bottom:1px solid #333; width:180px;">&nbsp;</div>
            <div style="font-size:9px; color:#6c757d; margin-top:4px;">Signature</div>
        </td>
        <td style="padding:16px; border:1px solid #d0d7de; width:50%; vertical-align:top; border-left:none;">
            <strong>Client Representative</strong><br>
            Name: ' . htmlspecialchars($clientRep) . '<br>
            Designation: ' . htmlspecialchars($clientRepDesignation ?: '-') . '<br>
            Contact: ' . htmlspecialchars($clientRepContact ?: '-') . '<br>
            Email: ' . htmlspecialchars($clientRepEmail ?: '-') . '<br>
            ' . $clientSignatureHtml . '
            <div style="font-size:9px; color:#6c757d; margin-top:4px;">Client Signature</div>
        </td>
    </tr>
</table>

<div style="margin-top:14px; padding:8px 10px; background:#f8fafc; border:1px solid #e5e7eb; font-size:8.5px; color:#6b7280; text-align:center;">
    This is a system-generated audit report prepared by TechXpert Service. The information contained herein is based on on-site inspection data
    captured via the mobile audit application. Photos are stored securely at admin/media/audit-ticket/.
</div>
</body></html>';

    $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];
    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf(array(
        'fontDir' => array_merge($fontDirs, array(dirname(__DIR__) . '/../fonts')),
        'fontdata' => $fontData + array(
            'poppins' => array(
                'R' => 'Poppins-Regular.ttf',
                'B' => 'Poppins-Bold.ttf',
                'I' => 'Poppins-Italic.ttf',
            ),
        ),
        'default_font' => 'poppins',
        'margin_top' => 16,
        'margin_bottom' => 18,
        'margin_left' => 14,
        'margin_right' => 14,
    ));

    $pdfGlobalStyles = auditTicketPdfGetGlobalStyleBlock();
    $pdfPageBorder = auditTicketPdfGetPageBorderHtml();
    $borderImagePath = auditTicketPdfGeneratePageBorderImage($chartDir . $chartToken . '_page_border.png');
    if ($borderImagePath !== '') {
        $pdfGlobalStyles = auditTicketPdfGetGlobalStyleBlock($borderImagePath);
    }

    $mpdf->SetWatermarkImage($defaultLogo, 0.06, array(50, 20), 'F', true, 45);
    $mpdf->showWatermarkImage = true;
    $mpdf->SetFooter('
<div style="font-size:8px; color:#6b7280; border-top:1px solid #d0d7de; padding:4px 8px;">
    <span style="float:left;">TechXpert Service | System Generated Audit Report | Private &amp; Confidential | ' . date('d-M-Y H:i') . '</span>
    <span style="float:right; font-weight:bold;">Page {PAGENO} of {nbpg}</span>
</div>');

    $mpdf->WriteHTML($pdfGlobalStyles);
    $mpdf->WriteHTML(auditTicketPdfPrependPageBorder($coverHtml));
    $mpdf->TOCpagebreak(array(
        'toc-preHTML' => auditTicketPdfPrependPageBorder($contentsPreHtml),
        'toc-bookmarkText' => 'Contents',
        'toc-RESET' => true,
        'TOCusePaging' => true,
        'TOCuseLinking' => false,
        'tocoutdent' => '2em',
    ));
    $mpdf->WriteHTML(auditTicketPdfPrependPageBorder($disclaimerHtml));
    $mpdf->WriteHTML(auditTicketPdfPrependPageBorder($html));

    $pdfName = 'audit-branch-report-' . preg_replace('/[^A-Za-z0-9\-]/', '', $ticket['TicketID']) . '.pdf';
    $pdfPath = $reportDir . $pdfName;
    $mpdf->Output($pdfPath, 'F');

    _UpdateTableRecords($conn, 'corporate_audit_tickets', " ReportGenerated = 1 WHERE ID = $auditTicketId");

    $baseUrl = defined('FRONT_SITE_PATH') ? rtrim(FRONT_SITE_PATH, '/') : '';
    $pdfUrl = $baseUrl !== '' ? $baseUrl . '/admin/audit-ticket/reports/' . $pdfName : '';

    $response['error'] = false;
    $response['message'] = 'Audit branch report generated.';
    $response['pdfname'] = $pdfName;
    $response['pdf_url'] = $pdfUrl;
    $response['AuditTicketID'] = $auditTicketId;
    $response['TicketID'] = $ticket['TicketID'];
    $response['photo_count'] = $photoCount;
    $response['checklist_summary'] = $stats;
    $response['audit_grade'] = $auditGrade['grade'];
    $response['risk_level'] = $riskLevel['level'];
    $response['trend_direction'] = $trendDirection;

    if (!empty($options['inline'])) {
        $response['inline'] = true;
    }

    return $response;
}

function getAuditTicketChecklistGroupedBySubAudit($conn, $auditTicketId)
{
    $items = getCorporateAuditTicketChecklistWithResponses($conn, $auditTicketId);
    $grouped = array();

    foreach ($items as $item) {
        $subAuditId = (int) $item['sub_audit_id'];
        if (!isset($grouped[$subAuditId])) {
            $grouped[$subAuditId] = array(
                'sub_audit_id' => $subAuditId,
                'sub_audit_name' => $item['sub_audit_name'],
                'master_audit_id' => (int) $item['master_audit_id'],
                'master_audit_name' => $item['master_audit_name'],
                'items' => array(),
            );
        }
        $grouped[$subAuditId]['items'][] = $item;
    }

    $ticketAudits = getCorporateAuditTicketAudits($conn, $auditTicketId);
    foreach ($ticketAudits as $auditRow) {
        $subAuditId = (int) $auditRow['SubAuditID'];
        if (!isset($grouped[$subAuditId])) {
            $grouped[$subAuditId] = array(
                'sub_audit_id' => $subAuditId,
                'sub_audit_name' => $auditRow['SubAuditName'],
                'master_audit_id' => (int) $auditRow['MasterAuditID'],
                'master_audit_name' => $auditRow['MasterAuditName'],
                'items' => array(),
            );
        }
    }

    foreach ($grouped as $subAuditId => &$group) {
        $total = count($group['items']);
        $filled = 0;
        $okCount = 0;
        $notOkCount = 0;
        foreach ($group['items'] as $item) {
            if (trim((string) $item['response_value']) !== '') {
                $filled++;
            }
            if ($item['ok_status'] === 'OK') {
                $okCount++;
            }
            if ($item['ok_status'] === 'Not OK') {
                $notOkCount++;
            }
        }

        $status = 'Pending';
        if ($filled > 0 && $filled < $total) {
            $status = 'In Progress';
        } elseif ($total > 0 && $filled >= $total) {
            $status = 'Completed';
        }

        $group['status'] = $status;
        $group['summary'] = array(
            'total' => $total,
            'filled' => $filled,
            'pending' => max(0, $total - $filled),
            'ok' => $okCount,
            'not_ok' => $notOkCount,
            'completion_percent' => $total > 0 ? round(($filled / $total) * 100, 1) : 0,
        );
    }
    unset($group);

    return array_values($grouped);
}

function syncAuditTicketAuditStatuses($conn, $ticketId)
{
    if (!auditTicketAuditsTableExists($conn)) {
        return;
    }

    $groups = getAuditTicketChecklistGroupedBySubAudit($conn, $ticketId);
    foreach ($groups as $group) {
        $status = cleantext($group['status']);
        $subAuditId = (int) $group['sub_audit_id'];
        _UpdateTableRecords(
            $conn,
            'corporate_audit_ticket_audits',
            " Status = '$status' WHERE AuditTicketID = " . (int) $ticketId . " AND SubAuditID = $subAuditId"
        );
    }
}

function formatAuditTicketForApi($conn, $ticket, $includeChecklist = false, $baseUrl = '')
{
    if (!$ticket) {
        return null;
    }

    $item = array(
        'id' => (int) $ticket['ID'],
        'ticket_id' => $ticket['TicketID'],
        'corporate_id' => (int) $ticket['CorporateID'],
        'branch_id' => (int) $ticket['BranchID'],
        'master_audit_id' => (int) $ticket['MasterAuditID'],
        'sub_audit_id' => (int) $ticket['SubAuditID'],
        'assigned_to' => (int) $ticket['AssignedTo'],
        'branch_account_manager' => isset($ticket['BranchAccountManager']) ? (int) $ticket['BranchAccountManager'] : -1,
        'technician' => isset($ticket['Technician']) ? (int) $ticket['Technician'] : -1,
        'status' => $ticket['Status'],
        'last_status' => isset($ticket['LastStatus']) ? $ticket['LastStatus'] : '',
        'priority' => $ticket['Priority'],
        'remarks' => $ticket['Remarks'],
        'technician_notes' => isset($ticket['TechnicianNotes']) ? $ticket['TechnicianNotes'] : '',
        'created_by' => $ticket['CreatedBy'],
        'created_date' => $ticket['CreatedDate'],
        'created_time' => $ticket['CreatedTime'],
        'completed_date' => isset($ticket['CompletedDate']) ? $ticket['CompletedDate'] : '',
        'completed_time' => isset($ticket['CompletedTime']) ? $ticket['CompletedTime'] : '',
        'company_name' => isset($ticket['CompanyName']) ? $ticket['CompanyName'] : '',
        'branch_site' => isset($ticket['BranchSite']) ? $ticket['BranchSite'] : '',
        'branch_city' => isset($ticket['BranchCity']) ? $ticket['BranchCity'] : '',
        'master_audit_name' => isset($ticket['MasterAuditName']) ? $ticket['MasterAuditName'] : '',
        'sub_audit_name' => isset($ticket['SubAuditName']) ? $ticket['SubAuditName'] : '',
        'report_generated' => !empty($ticket['ReportGenerated']) ? 1 : 0,
    );

    $item['branch_details'] = auditTicketFormatBranchDetailsForApi($ticket);
    $item['sub_audit'] = auditTicketFormatSubAuditDetailsForApi($ticket, $baseUrl);
    $item['report_meta'] = auditTicketFormatReportMetaForApi($ticket);

    $audits = formatAuditTicketAuditsForApi($conn, (int) $ticket['ID'], $baseUrl);
    $item['audits'] = $audits;
    $item['sub_audits'] = $audits;
    $item['audit_count'] = count($audits);
    $item['is_multi_audit'] = count($audits) > 1 ? 1 : 0;
    if (count($audits) > 1) {
        $item['sub_audit_name'] = getAuditTicketAuditsDisplayLabel($conn, (int) $ticket['ID'], $item['sub_audit_name']);
        $item['master_audit_name'] = getAuditTicketMasterAuditsDisplayLabel($conn, (int) $ticket['ID'], $item['master_audit_name']);
    }

    if ($includeChecklist) {
        require_once dirname(__DIR__) . '/../../api/audit-ticket/audit_ticket_helpers.php';

        $checklistItems = getCorporateAuditTicketChecklistWithResponses($conn, (int) $ticket['ID']);
        $groupedChecklists = getAuditTicketChecklistGroupedBySubAudit($conn, (int) $ticket['ID']);
        $fields = array();
        $checklists = array();
        $mobileFields = array();
        foreach ($checklistItems as $ci) {
            $formatted = audit_ticket_format_checklist_field_for_mobile($ci['checklist'], $ci, $baseUrl);
            $formatted['sub_audit_id'] = (int) $ci['sub_audit_id'];
            $formatted['sub_audit_name'] = $ci['sub_audit_name'];
            $formatted['master_audit_id'] = (int) $ci['master_audit_id'];
            $formatted['master_audit_name'] = $ci['master_audit_name'];
            $checklists[] = $formatted;
            $fields[] = $formatted;
            $mobileFields[] = $formatted['mobile_form'];
        }

        $item['checklists'] = $checklists;
        $item['checklists_by_sub_audit'] = array();
        foreach ($groupedChecklists as $group) {
            $groupFields = array();
            $groupMobileFields = array();
            foreach ($group['items'] as $ci) {
                $formatted = audit_ticket_format_checklist_field_for_mobile($ci['checklist'], $ci, $baseUrl);
                $formatted['sub_audit_id'] = (int) $ci['sub_audit_id'];
                $formatted['sub_audit_name'] = $ci['sub_audit_name'];
                $groupFields[] = $formatted;
                $groupMobileFields[] = $formatted['mobile_form'];
            }
            $item['checklists_by_sub_audit'][] = array(
                'sub_audit_id' => (int) $group['sub_audit_id'],
                'sub_audit_name' => $group['sub_audit_name'],
                'master_audit_id' => (int) $group['master_audit_id'],
                'master_audit_name' => $group['master_audit_name'],
                'status' => $group['status'],
                'checklists' => $groupFields,
                'mobile_checklist_form' => array(
                    'audit_ticket_id' => (int) $ticket['ID'],
                    'sub_audit_id' => (int) $group['sub_audit_id'],
                    'fields' => $groupMobileFields,
                ),
                'checklist_summary' => $group['summary'],
            );
        }
        $item['dynamic_form'] = array(
            'audit_ticket_id' => (int) $ticket['ID'],
            'sub_audit_id' => (int) $ticket['SubAuditID'],
            'audit_count' => count($audits),
            'fields' => $fields,
        );
        $item['mobile_checklist_form'] = array(
            'audit_ticket_id' => (int) $ticket['ID'],
            'audit_count' => count($audits),
            'instructions' => count($audits) > 1
                ? 'This ticket includes multiple audits. Fill each audit checklist. For each checkpoint: Value, Status (OK / Not OK), Remarks (required if Not OK), and optional photo.'
                : 'For each checkpoint fill: Value, Status (OK / Not OK), Remarks (required if Not OK), and optional photo.',
            'fields' => $mobileFields,
            'sub_audits' => $item['checklists_by_sub_audit'],
        );
        $item['mobile_legacy_payload'] = audit_ticket_build_legacy_mobile_payload($conn, $ticket, $baseUrl);
        $item['draft_restored'] = !empty($ticket['DraftSavedDate']) ? 1 : 0;
        $item['checklist_summary'] = array(
            'total' => count($checklists),
            'filled' => count(array_filter($checklistItems, function ($x) {
                return trim((string) $x['response_value']) !== '';
            })),
            'ok' => count(array_filter($checklistItems, function ($x) {
                return $x['ok_status'] === 'OK';
            })),
            'not_ok' => count(array_filter($checklistItems, function ($x) {
                return $x['ok_status'] === 'Not OK';
            })),
            'in_range' => count(array_filter($checklistItems, function ($x) {
                return $x['compliance_status'] === 'In Range';
            })),
            'out_of_range' => count(array_filter($checklistItems, function ($x) {
                return $x['compliance_status'] === 'Out of Range';
            })),
        );

        $historyRows = getAuditTicketStatusHistory($conn, (int) $ticket['ID']);
        $item['status_history'] = formatAuditTicketHistoryForApi($conn, $historyRows);
    }

    return $item;
}

function updateCorporateAuditTicketStatus($conn, $ticketId, $status, $updatedBy = '', $remarks = '')
{
    return auditTicketApplyStatusChange($conn, $ticketId, $status, array(
        'CreatedBy' => $updatedBy !== '' ? $updatedBy : 'System',
        'Remarks' => $remarks !== '' ? $remarks : 'Status updated from portal',
    ));
}

function formatAuditTicketHistoryForApi($conn, $historyRows)
{
    $list = array();
    foreach ($historyRows as $row) {
        $assignedName = '';
        if ((int) $row['AssignedTo'] > 0 && function_exists('getEmployeeDetailsfromID')) {
            $emp = getEmployeeDetailsfromID($conn, (int) $row['AssignedTo']);
            if (is_array($emp) && !empty($emp['Name'])) {
                $assignedName = $emp['Name'];
            }
        }
        $list[] = array(
            'id' => (int) $row['ID'],
            'status' => $row['Status'],
            'previous_status' => isset($row['PreviousStatus']) ? $row['PreviousStatus'] : '',
            'assigned_to' => (int) $row['AssignedTo'],
            'assigned_to_name' => $assignedName,
            'remarks' => $row['Remarks'],
            'created_by' => $row['CreatedBy'],
            'created_date' => $row['CreatedDate'],
            'created_time' => $row['CreatedTime'],
        );
    }
    return $list;
}

function getAuditTicketCompletionStats($conn, $auditTicketId)
{
    $items = getCorporateAuditTicketChecklistWithResponses($conn, $auditTicketId);
    $total = count($items);
    $filled = 0;
    $inRange = 0;
    $outOfRange = 0;
    $okCount = 0;
    $notOkCount = 0;
    foreach ($items as $item) {
        if (trim((string) $item['response_value']) !== '') {
            $filled++;
        }
        if ($item['compliance_status'] === 'In Range') {
            $inRange++;
        }
        if ($item['compliance_status'] === 'Out of Range') {
            $outOfRange++;
        }
        if ($item['ok_status'] === 'OK') {
            $okCount++;
        }
        if ($item['ok_status'] === 'Not OK') {
            $notOkCount++;
        }
    }
    return array(
        'total' => $total,
        'filled' => $filled,
        'pending' => $total - $filled,
        'ok' => $okCount,
        'not_ok' => $notOkCount,
        'in_range' => $inRange,
        'out_of_range' => $outOfRange,
        'completion_percent' => $total > 0 ? round(($filled / $total) * 100, 1) : 0,
        'audit_count' => count(getCorporateAuditTicketAudits($conn, $auditTicketId)),
        'by_sub_audit' => getAuditTicketChecklistGroupedBySubAudit($conn, $auditTicketId),
    );
}
