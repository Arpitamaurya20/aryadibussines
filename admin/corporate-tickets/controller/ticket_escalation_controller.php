<?php

/**
 * Due-date ticket escalation — Branch Manager -> State Manager -> CEO.
 * Isolated from core corporate_tickets_controller assignment logic.
 */

define('TE_ESCALATION_RESPONSE_HOURS', 24);
define('TE_ESCALATION_LEVEL_BRANCH_MANAGER', 1);
define('TE_ESCALATION_LEVEL_STATE_MANAGER', 2);
define('TE_ESCALATION_LEVEL_CEO', 3);

function te_levelLabel($level)
{
    $map = array(
        TE_ESCALATION_LEVEL_BRANCH_MANAGER => 'Branch Manager',
        TE_ESCALATION_LEVEL_STATE_MANAGER => 'State Manager',
        TE_ESCALATION_LEVEL_CEO => 'CEO',
    );
    return isset($map[(int) $level]) ? $map[(int) $level] : 'Unknown';
}

function te_closedStatuses()
{
    return array('Closed', 'Cancel', 'Cancelled');
}

function te_employeeName($emp)
{
    return (is_array($emp) && isset($emp['Name']) && $emp['Name'] !== '') ? $emp['Name'] : '';
}

function te_resolveEmployeeIdFromSession($conn, $session)
{
    $roles = (isset($session['Roles']) && is_array($session['Roles'])) ? $session['Roles'] : array();
    if (isset($roles['EmployeeID']) && (int) $roles['EmployeeID'] > 0) {
        return (int) $roles['EmployeeID'];
    }
    if (empty($session['pb_username'])) {
        return 0;
    }
    $stateObj = new State($conn);
    return (int) $stateObj->resolveEmployeeIdFromSession($session);
}

function te_getCeoEmployeeId($conn)
{
    $sql = "SELECT ID FROM employees WHERE Designation = 'CEO' AND IsActive = 1 ORDER BY ID ASC LIMIT 1";
    $row = _getSQLDetails($conn, $sql);
    if (is_array($row) && isset($row['ID']) && (int) $row['ID'] > 0) {
        return (int) $row['ID'];
    }
    return 0;
}

function te_getBranchManagerId($conn, $branchId)
{
    $branchId = (int) $branchId;
    if ($branchId <= 0) {
        return 0;
    }
    $branch = _getTableDetails($conn, 'branch', " WHERE ID = $branchId");
    if (!is_array($branch)) {
        return 0;
    }
    $managerId = (int) $branch['AccountBranchManager'];
    return ($managerId > 0) ? $managerId : 0;
}

function te_getStateManagerId($conn, $branchState)
{
    $branchState = mysqli_real_escape_string($conn, trim((string) $branchState));
    if ($branchState === '') {
        return 0;
    }
    $state = _getTableDetails($conn, 'state', " WHERE StateName = '$branchState' AND IsActive = 1");
    if (!is_array($state)) {
        return 0;
    }
    $managerId = (int) $state['StateCorporateHead'];
    return ($managerId > 0) ? $managerId : 0;
}

function te_getEscalationTargetEmployeeId($conn, $level, $branchId, $branchState)
{
    $level = (int) $level;
    if ($level === TE_ESCALATION_LEVEL_BRANCH_MANAGER) {
        return te_getBranchManagerId($conn, $branchId);
    }
    if ($level === TE_ESCALATION_LEVEL_STATE_MANAGER) {
        return te_getStateManagerId($conn, $branchState);
    }
    if ($level === TE_ESCALATION_LEVEL_CEO) {
        return te_getCeoEmployeeId($conn);
    }
    return 0;
}

function te_getActiveEscalation($conn, $ticketPK)
{
    $ticketPK = (int) $ticketPK;
    if ($ticketPK <= 0) {
        return null;
    }
    $sql = "SELECT * FROM corporate_ticket_escalation
            WHERE TicketPK = $ticketPK AND IsActive = 1 AND Status = 'Pending'
            ORDER BY ID DESC LIMIT 1";
    $row = _getSQLDetails($conn, $sql);
    return (is_array($row) && isset($row['ID'])) ? $row : null;
}

function te_getOpenEscalation($conn, $ticketPK)
{
    $ticketPK = (int) $ticketPK;
    if ($ticketPK <= 0) {
        return null;
    }
    $sql = "SELECT * FROM corporate_ticket_escalation
            WHERE TicketPK = $ticketPK AND IsActive = 1 AND Status IN ('Pending', 'Acknowledged')
            ORDER BY ID DESC LIMIT 1";
    $row = _getSQLDetails($conn, $sql);
    return (is_array($row) && isset($row['ID'])) ? $row : null;
}

function te_buildEscalatedStatusFilterSql($ticketAlias = 'a')
{
    $ticketAlias = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $ticketAlias);
    if ($ticketAlias === '') {
        $ticketAlias = 'a';
    }

    return " AND (
        {$ticketAlias}.Status = 'Escalated'
        OR EXISTS (
            SELECT 1 FROM corporate_ticket_escalation te_esc
            WHERE te_esc.TicketPK = {$ticketAlias}.ID
              AND te_esc.IsActive = 1
              AND te_esc.Status IN ('Pending', 'Acknowledged')
        )
    )";
}

function te_getEscalationHistory($conn, $ticketPK)
{
    $ticketPK = (int) $ticketPK;
    if ($ticketPK <= 0) {
        return array();
    }
    return _getTableRecords($conn, 'corporate_ticket_escalation_history', " WHERE TicketPK = $ticketPK ORDER BY ID DESC");
}

function te_logEscalationHistory($conn, $ticketPK, $level, $employeeId, $action, $remarks, $createdBy)
{
    $ticketPK = (int) $ticketPK;
    $level = (int) $level;
    $employeeId = (int) $employeeId;
    $action = mysqli_real_escape_string($conn, (string) $action);
    $remarks = mysqli_real_escape_string($conn, (string) $remarks);
    $createdBy = mysqli_real_escape_string($conn, (string) $createdBy);
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $sql = "INSERT INTO corporate_ticket_escalation_history
            (TicketPK, EscalationLevel, EscalatedToEmployeeID, Action, Remarks, CreatedBy, CreatedDate, CreatedTime)
            VALUES ($ticketPK, $level, $employeeId, '$action', '$remarks', '$createdBy', '$date', '$time')";
    _InsertTableRecords($conn, $sql);
}

function te_applyEscalatedTicketStatus($conn, $ticket, $level, $escalatedToEmployeeId, $reason, $createdBy, $isManual = false)
{
    $ticketPK = (int) $ticket['ID'];
    $currentStatus = trim((string) $ticket['Status']);
    if (in_array($currentStatus, te_closedStatuses(), true)) {
        return;
    }

    $historyRemarks = ($isManual ? 'Manual escalation' : 'Due date escalation') . ' to ' . te_levelLabel($level);
    if (trim((string) $reason) !== '') {
        $historyRemarks .= ' — ' . $reason;
    }
    if (strlen($historyRemarks) > 50) {
        $historyRemarks = substr($historyRemarks, 0, 47) . '...';
    }

    _UpdateTableRecords($conn, 'corporate_tickets', "Status = 'Escalated' WHERE ID = $ticketPK");

    require_once __DIR__ . '/corporate_tickets_controller.php';
    RecordTicketHistory($conn, array(
        'TicketID' => $ticketPK,
        'AssignedTo' => (int) $escalatedToEmployeeId,
        'Status' => 'Escalated',
        'Remarks' => $historyRemarks,
        'CreatedDate' => date('Y-m-d'),
        'CreatedTime' => date('H:i:s'),
        'CreatedBy' => $createdBy,
    ));
}

function te_isTicketEligibleForEscalation($ticket)
{
    if (!te_isTicketOpenForEscalation($ticket)) {
        return false;
    }
    $dueDate = trim((string) $ticket['DueDate']);
    if ($dueDate === '' || $dueDate === '0000-00-00') {
        return false;
    }
    return (strtotime($dueDate) < strtotime(date('Y-m-d')));
}

function te_isTicketOpenForEscalation($ticket)
{
    if (!is_array($ticket) || (int) ($ticket['IsActive'] ?? 0) !== 1) {
        return false;
    }
    $status = trim((string) ($ticket['Status'] ?? ''));
    if (in_array($status, te_closedStatuses(), true)) {
        return false;
    }
    return ((int) ($ticket['AssignedTo'] ?? 0) > 0);
}

function te_supersedeActiveEscalation($conn, $ticketPK, $remarks, $createdBy, $action = 'AutoEscalated')
{
    $active = te_getActiveEscalation($conn, $ticketPK);
    if (!$active) {
        return;
    }
    $id = (int) $active['ID'];
    _UpdateTableRecords($conn, 'corporate_ticket_escalation', "Status = 'Superseded' WHERE ID = $id");
    te_logEscalationHistory(
        $conn,
        $ticketPK,
        (int) $active['EscalationLevel'],
        (int) $active['EscalatedToEmployeeID'],
        $action,
        $remarks,
        $createdBy
    );
}

function te_sendEscalationNotification($conn, $ticket, $level, $employeeId, $isManual = false)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return;
    }
    $employee = getEmployeeDetailsfromID($conn, $employeeId);
    $employeeName = te_employeeName($employee);
    if ($employeeName === '' || empty($employee['ContactNumber'])) {
        return;
    }
    $phone = '+91' . $employee['ContactNumber'];
    $levelLabel = te_levelLabel($level);
    $ticketCode = $ticket['TicketID'];
    $dueDate = trim((string) $ticket['DueDate']);
    $dueLine = ($dueDate !== '' && $dueDate !== '0000-00-00') ? " (Due date: $dueDate)" : '';
    if ($isManual) {
        $message = "Hello $employeeName,\n\nTicket $ticketCode has been manually escalated to you as $levelLabel$dueLine.\n\nPlease review and acknowledge the escalation in the Assignment tab of the ticket.";
    } else {
        $message = "Hello $employeeName,\n\nTicket $ticketCode has passed its due date ($dueDate) and has been escalated to you as $levelLabel.\n\nPlease review and acknowledge the escalation in the Assignment tab of the ticket.";
    }
    sendWhatsAppMessage($phone, $message);
}

function te_createEscalation($conn, $ticket, $level, $employeeId, $reason, $createdBy, $historyAction = 'Escalated', $isManual = false)
{
    $ticketPK = (int) $ticket['ID'];
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return array('error' => true, 'message' => 'No employee mapped for ' . te_levelLabel($level));
    }

    if ($historyAction !== 'ManualEscalated') {
        te_supersedeActiveEscalation($conn, $ticketPK, 'Superseded for level ' . $level, $createdBy, 'AutoEscalated');
    }

    $ticketCode = mysqli_real_escape_string($conn, (string) $ticket['TicketID']);
    $reason = mysqli_real_escape_string($conn, (string) $reason);
    $dueDate = mysqli_real_escape_string($conn, (string) $ticket['DueDate']);
    $now = date('Y-m-d H:i:s');
    $deadline = date('Y-m-d H:i:s', strtotime('+' . TE_ESCALATION_RESPONSE_HOURS . ' hours'));

    $sql = "INSERT INTO corporate_ticket_escalation
            (TicketPK, TicketID, EscalationLevel, EscalatedToEmployeeID, Status, TriggerReason,
             DueDateAtTrigger, EscalatedAt, ResponseDeadline, IsActive)
            VALUES ($ticketPK, '$ticketCode', $level, $employeeId, 'Pending', '$reason',
                    '$dueDate', '$now', '$deadline', 1)";
    _InsertTableRecords($conn, $sql);

    te_logEscalationHistory($conn, $ticketPK, $level, $employeeId, $historyAction, $reason, $createdBy);
    te_applyEscalatedTicketStatus($conn, $ticket, $level, $employeeId, $reason, $createdBy, $isManual);
    te_sendEscalationNotification($conn, $ticket, $level, $employeeId, $isManual);

    return array('error' => false, 'message' => 'Ticket escalated to ' . te_levelLabel($level) . ' and status updated to Escalated');
}

function te_getNextEscalationLevel($conn, $currentLevel, $branchId, $branchState, $skipEmployeeId = 0)
{
    $start = ($currentLevel <= 0) ? TE_ESCALATION_LEVEL_BRANCH_MANAGER : ((int) $currentLevel + 1);
    $skipEmployeeId = (int) $skipEmployeeId;
    for ($level = $start; $level <= TE_ESCALATION_LEVEL_CEO; $level++) {
        $employeeId = te_getEscalationTargetEmployeeId($conn, $level, $branchId, $branchState);
        if ($employeeId > 0 && $employeeId !== $skipEmployeeId) {
            return $level;
        }
    }
    return 0;
}

function te_wasRecentlyReassignedToTechnician($conn, $ticketPK)
{
    $ticketPK = (int) $ticketPK;
    if ($ticketPK <= 0) {
        return false;
    }
    $row = _getSQLDetails(
        $conn,
        "SELECT ID FROM corporate_ticket_status_history
         WHERE TicketID = '$ticketPK'
           AND CreatedDate >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
           AND (
             Remarks LIKE '%reassign%'
             OR Remarks LIKE '%Reassign%'
           )
         ORDER BY ID DESC
         LIMIT 1"
    );
    return is_array($row) && isset($row['ID']) && (int) $row['ID'] > 0;
}

function te_resolveDueDateAfterReassign($ticket, $data)
{
    $today = strtotime(date('Y-m-d'));
    $posted = isset($data['DueDate']) ? trim((string) $data['DueDate']) : '';
    if ($posted !== '' && $posted !== '0000-00-00' && strtotime($posted) > $today) {
        return $posted;
    }
    return date('Y-m-d', strtotime('+1 day'));
}

function te_processTicketEscalation($conn, $ticketPK, $createdBy = 'system')
{
    $ticketPK = (int) $ticketPK;
    $ticket = _getTableDetails($conn, 'corporate_tickets', " WHERE ID = $ticketPK");
    if (!is_array($ticket)) {
        return array('error' => true, 'message' => 'Ticket not found');
    }

    if (!te_isTicketEligibleForEscalation($ticket)) {
        te_resolveEscalationsOnClose($conn, $ticketPK, $createdBy);
        return array('error' => false, 'message' => 'Ticket not eligible for escalation');
    }

    $active = te_getActiveEscalation($conn, $ticketPK);
    if (!$active && te_wasRecentlyReassignedToTechnician($conn, $ticketPK)) {
        return array('error' => false, 'message' => 'Recently reassigned to technician — auto-escalation deferred');
    }

    $branch = _getTableDetails($conn, 'branch', ' WHERE ID = ' . (int) $ticket['BranchID']);
    $branchState = is_array($branch) ? $branch['BranchState'] : '';

    if ($active) {
        if (strtotime($active['ResponseDeadline']) > time()) {
            return array('error' => false, 'message' => 'Awaiting response from ' . te_levelLabel($active['EscalationLevel']));
        }

        $nextLevel = te_getNextEscalationLevel(
            $conn,
            (int) $active['EscalationLevel'],
            (int) $ticket['BranchID'],
            $branchState,
            (int) $active['EscalatedToEmployeeID']
        );
        if ($nextLevel <= 0) {
            return array('error' => false, 'message' => 'Maximum escalation level reached');
        }

        $reason = 'No response within ' . TE_ESCALATION_RESPONSE_HOURS . ' hours from ' . te_levelLabel($active['EscalationLevel']);
        return te_createEscalation($conn, $ticket, $nextLevel, te_getEscalationTargetEmployeeId($conn, $nextLevel, (int) $ticket['BranchID'], $branchState), $reason, $createdBy);
    }

    $firstLevel = te_getNextEscalationLevel($conn, 0, (int) $ticket['BranchID'], $branchState);
    if ($firstLevel <= 0) {
        return array('error' => true, 'message' => 'No escalation target configured for this ticket');
    }

    $reason = 'Ticket due date (' . $ticket['DueDate'] . ') passed after assignment';
    return te_createEscalation(
        $conn,
        $ticket,
        $firstLevel,
        te_getEscalationTargetEmployeeId($conn, $firstLevel, (int) $ticket['BranchID'], $branchState),
        $reason,
        $createdBy
    );
}

function te_processAllOverdueEscalations($conn, $createdBy = 'cron')
{
    $closed = te_closedStatuses();
    $closedList = "'" . implode("','", array_map(function ($s) use ($conn) {
        return mysqli_real_escape_string($conn, $s);
    }, $closed)) . "'";

    $sql = "SELECT ID FROM corporate_tickets
            WHERE IsActive = 1
              AND AssignedTo > 0
              AND DueDate IS NOT NULL AND DueDate != '' AND DueDate != '0000-00-00'
              AND DueDate < CURDATE()
              AND Status NOT IN ($closedList)";
    $result = mysqli_query($conn, $sql);
    $processed = 0;
    $errors = array();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response = te_processTicketEscalation($conn, (int) $row['ID'], $createdBy);
            $processed++;
            if (!empty($response['error'])) {
                $errors[] = 'Ticket #' . $row['ID'] . ': ' . $response['message'];
            }
        }
    }

    return array(
        'error' => false,
        'processed' => $processed,
        'errors' => $errors,
        'message' => "Processed $processed overdue ticket(s)",
    );
}

function te_acknowledgeEscalation($conn, $data, $session)
{
    $ticketPK = (int) ($data['TicketPK'] ?? 0);
    $remarks = trim((string) ($data['Remarks'] ?? ''));
    $username = isset($session['pb_username']) ? $session['pb_username'] : '';
    $employeeId = te_resolveEmployeeIdFromSession($conn, $session);
    $isAdmin = isset($session['UserType']) && $session['UserType'] === 'Admin';

    if ($ticketPK <= 0) {
        return array('error' => true, 'message' => 'Invalid ticket');
    }

    $active = te_getActiveEscalation($conn, $ticketPK);
    if (!$active) {
        return array('error' => true, 'message' => 'No pending escalation found');
    }

    $escalatedTo = (int) $active['EscalatedToEmployeeID'];
    if (!$isAdmin && ($employeeId <= 0 || $employeeId !== $escalatedTo)) {
        return array('error' => true, 'message' => 'You are not authorized to acknowledge this escalation');
    }

    $escId = (int) $active['ID'];
    $now = date('Y-m-d H:i:s');
    $remarksSql = mysqli_real_escape_string($conn, $remarks);
    $usernameSql = mysqli_real_escape_string($conn, $username);

    _UpdateTableRecords(
        $conn,
        'corporate_ticket_escalation',
        "Status = 'Acknowledged', RespondedAt = '$now', RespondedBy = '$usernameSql', ResponseRemarks = '$remarksSql' WHERE ID = $escId"
    );

    te_logEscalationHistory(
        $conn,
        $ticketPK,
        (int) $active['EscalationLevel'],
        $escalatedTo,
        'Acknowledged',
        $remarks !== '' ? $remarks : 'Escalation acknowledged',
        $username
    );

    return array('error' => false, 'message' => 'Escalation acknowledged successfully');
}

function te_userCanReassignTechnician($conn, $session, $ticketPK)
{
    $ticketPK = (int) $ticketPK;
    if ($ticketPK <= 0) {
        return false;
    }

    $ticket = _getTableDetails($conn, 'corporate_tickets', " WHERE ID = $ticketPK");
    if (!is_array($ticket) || !te_isTicketOpenForEscalation($ticket)) {
        return false;
    }

    if ((int) ($ticket['AssignedTo'] ?? -1) <= 0) {
        return false;
    }

    if (isset($session['UserType']) && in_array($session['UserType'], array('Admin', 'Ticket Manager'), true)) {
        return true;
    }

    $allowedRoles = array('Branch Account Manager', 'State Corporate Lead');
    foreach ($allowedRoles as $role) {
        if (CheckRole($session, $role) === true) {
            return true;
        }
    }

    return false;
}

function te_userCanManualEscalate($session)
{
    if (isset($session['UserType']) && in_array($session['UserType'], array('Admin', 'Ticket Manager'), true)) {
        return true;
    }
    $roles = array(
        'City Lead',
        'City Corporate Lead',
        'Branch Account Manager',
        'State Corporate Lead',
        'Account Manager',
    );
    foreach ($roles as $role) {
        if (CheckRole($session, $role) === true) {
            return true;
        }
    }
    return false;
}

function te_getManualEscalationOptions($conn, $ticket)
{
    if (!is_array($ticket)) {
        return array();
    }
    $branchId = (int) $ticket['BranchID'];
    $branch = _getTableDetails($conn, 'branch', " WHERE ID = $branchId");
    $branchState = is_array($branch) ? $branch['BranchState'] : '';
    $options = array();
    for ($level = TE_ESCALATION_LEVEL_BRANCH_MANAGER; $level <= TE_ESCALATION_LEVEL_CEO; $level++) {
        $empId = te_getEscalationTargetEmployeeId($conn, $level, $branchId, $branchState);
        if ($empId > 0) {
            $emp = getEmployeeDetailsfromID($conn, $empId);
            $options[] = array(
                'level' => $level,
                'level_label' => te_levelLabel($level),
                'employee_id' => $empId,
                'employee_name' => te_employeeName($emp),
            );
        }
    }
    return $options;
}

function te_manuallyEscalateTicket($conn, $data, $session)
{
    if (!te_userCanManualEscalate($session)) {
        return array('error' => true, 'message' => 'You are not authorized to manually escalate tickets');
    }

    $ticketPK = (int) ($data['TicketPK'] ?? 0);
    $level = isset($data['EscalationLevel']) ? (int) $data['EscalationLevel'] : 0;
    $remarks = trim((string) ($data['Remarks'] ?? ''));
    $username = isset($session['pb_username']) ? $session['pb_username'] : 'system';

    if ($ticketPK <= 0) {
        return array('error' => true, 'message' => 'Invalid ticket');
    }

    $ticket = _getTableDetails($conn, 'corporate_tickets', " WHERE ID = $ticketPK");
    if (!te_isTicketOpenForEscalation($ticket)) {
        return array('error' => true, 'message' => 'Ticket must be assigned and open to escalate');
    }

    $branch = _getTableDetails($conn, 'branch', ' WHERE ID = ' . (int) $ticket['BranchID']);
    $branchState = is_array($branch) ? $branch['BranchState'] : '';
    $active = te_getActiveEscalation($conn, $ticketPK);

    if ($level <= 0) {
        $skipEmployeeId = $active ? (int) $active['EscalatedToEmployeeID'] : 0;
        $currentLevel = $active ? (int) $active['EscalationLevel'] : 0;
        $level = te_getNextEscalationLevel(
            $conn,
            $currentLevel,
            (int) $ticket['BranchID'],
            $branchState,
            $skipEmployeeId
        );
    } else {
        $employeeId = te_getEscalationTargetEmployeeId($conn, $level, (int) $ticket['BranchID'], $branchState);
        if ($employeeId <= 0) {
            return array('error' => true, 'message' => 'No employee mapped for ' . te_levelLabel($level));
        }
    }

    if ($level <= 0 || $level > TE_ESCALATION_LEVEL_CEO) {
        return array('error' => true, 'message' => 'No further escalation level available');
    }

    $employeeId = te_getEscalationTargetEmployeeId($conn, $level, (int) $ticket['BranchID'], $branchState);
    if ($employeeId <= 0) {
        return array('error' => true, 'message' => 'No employee mapped for ' . te_levelLabel($level));
    }

    if ($active) {
        te_supersedeActiveEscalation($conn, $ticketPK, 'Replaced by manual escalation', $username, 'ManualEscalated');
    }

    $reason = 'Manual escalation by ' . $username;
    if ($remarks !== '') {
        $reason .= ' — ' . $remarks;
    }

    return te_createEscalation($conn, $ticket, $level, $employeeId, $reason, $username, 'ManualEscalated', true);
}

function te_resolveEscalationsOnReassign($conn, $ticketPK, $createdBy = 'system', $remarks = '')
{
    $ticketPK = (int) $ticketPK;
    $note = trim((string) $remarks);
    if ($note === '') {
        $note = 'Ticket reassigned to technician';
    }
    $active = te_getActiveEscalation($conn, $ticketPK);
    if ($active) {
        $escId = (int) $active['ID'];
        _UpdateTableRecords($conn, 'corporate_ticket_escalation', "Status = 'Resolved', IsActive = 0 WHERE ID = $escId");
        te_logEscalationHistory(
            $conn,
            $ticketPK,
            (int) $active['EscalationLevel'],
            (int) $active['EscalatedToEmployeeID'],
            'Resolved',
            $note,
            $createdBy
        );
        return;
    }

    _UpdateTableRecords($conn, 'corporate_ticket_escalation', "Status = 'Resolved', IsActive = 0 WHERE TicketPK = $ticketPK AND Status IN ('Pending','Acknowledged')");
}

function te_resolveEscalationsOnClose($conn, $ticketPK, $createdBy = 'system')
{
    $ticketPK = (int) $ticketPK;
    $active = te_getActiveEscalation($conn, $ticketPK);
    if ($active) {
        $escId = (int) $active['ID'];
        _UpdateTableRecords($conn, 'corporate_ticket_escalation', "Status = 'Resolved', IsActive = 0 WHERE ID = $escId");
        te_logEscalationHistory(
            $conn,
            $ticketPK,
            (int) $active['EscalationLevel'],
            (int) $active['EscalatedToEmployeeID'],
            'Resolved',
            'Ticket closed or no longer eligible',
            $createdBy
        );
        return;
    }

    _UpdateTableRecords($conn, 'corporate_ticket_escalation', "Status = 'Resolved', IsActive = 0 WHERE TicketPK = $ticketPK AND Status IN ('Pending','Acknowledged')");
}

function te_getEscalationSummary($conn, $ticketPK, $session = array())
{
    $ticketPK = (int) $ticketPK;
    $ticket = ($ticketPK > 0) ? _getTableDetails($conn, 'corporate_tickets', " WHERE ID = $ticketPK") : null;
    if (!is_array($ticket) || !isset($ticket['ID'])) {
        return array(
            'active' => null,
            'history' => array(),
            'response_hours' => TE_ESCALATION_RESPONSE_HOURS,
            'can_manual_escalate' => false,
            'can_reassign_technician' => false,
            'manual_options' => array(),
        );
    }
    $active = te_getActiveEscalation($conn, $ticketPK);
    $history = te_getEscalationHistory($conn, $ticketPK);
    $employeeId = te_resolveEmployeeIdFromSession($conn, $session);
    $isAdmin = isset($session['UserType']) && $session['UserType'] === 'Admin';
    $canManual = te_userCanManualEscalate($session) && te_isTicketOpenForEscalation($ticket);
    $canReassignTechnician = te_userCanReassignTechnician($conn, $session, $ticketPK);

    $activeSummary = null;
    if ($active) {
        $emp = getEmployeeDetailsfromID($conn, (int) $active['EscalatedToEmployeeID']);
        $activeSummary = array(
            'id' => (int) $active['ID'],
            'level' => (int) $active['EscalationLevel'],
            'level_label' => te_levelLabel($active['EscalationLevel']),
            'escalated_to_id' => (int) $active['EscalatedToEmployeeID'],
            'escalated_to_name' => te_employeeName($emp),
            'status' => $active['Status'],
            'trigger_reason' => $active['TriggerReason'],
            'due_date_at_trigger' => $active['DueDateAtTrigger'],
            'escalated_at' => $active['EscalatedAt'],
            'response_deadline' => $active['ResponseDeadline'],
            'can_acknowledge' => $isAdmin || ($employeeId > 0 && $employeeId === (int) $active['EscalatedToEmployeeID']),
        );
    }

    $historyRows = array();
    foreach ($history as $row) {
        $empName = '';
        if ((int) $row['EscalatedToEmployeeID'] > 0) {
            $emp = getEmployeeDetailsfromID($conn, (int) $row['EscalatedToEmployeeID']);
            $empName = te_employeeName($emp);
        }
        $historyRows[] = array(
            'level' => (int) $row['EscalationLevel'],
            'level_label' => te_levelLabel($row['EscalationLevel']),
            'escalated_to_name' => $empName,
            'action' => $row['Action'],
            'remarks' => $row['Remarks'],
            'created_by' => $row['CreatedBy'],
            'created_at' => $row['CreatedDate'] . ' ' . $row['CreatedTime'],
        );
    }

    return array(
        'active' => $activeSummary,
        'history' => $historyRows,
        'response_hours' => TE_ESCALATION_RESPONSE_HOURS,
        'can_manual_escalate' => $canManual,
        'can_reassign_technician' => $canReassignTechnician,
        'manual_options' => $canManual ? te_getManualEscalationOptions($conn, $ticket) : array(),
    );
}

function te_userCanViewEscalation($session)
{
    if (isset($session['UserType']) && in_array($session['UserType'], array('Admin', 'Ticket Manager'), true)) {
        return true;
    }
    $roles = array('Branch Account Manager', 'State Corporate Lead', 'Account Manager', 'City Lead', 'City Corporate Lead');
    foreach ($roles as $role) {
        if (CheckRole($session, $role) === true) {
            return true;
        }
    }
    $conn = _connectodb();
    $employeeId = te_resolveEmployeeIdFromSession($conn, $session);
    if ($employeeId > 0 && te_getCeoEmployeeId($conn) === $employeeId) {
        return true;
    }
    return false;
}
