<?php
/**
 * Approval center: who may decide the current stage of a submitted request, the decisions themselves (approve, reject,
 * return), reopening an approved request for re-approval, escalation of overdue stages, and approval notifications.
 * Loaded by fp_requests.php; requires fp_access.php, fp_audit.php, fp_budgets.php and fp_policies.php.
 *
 * Only the stage named by branch_expense_requests.CurrentStageNo of the latest SubmissionRound can be decided, so stages
 * are always decided in order. A person may decide a stage when, for the stage's approver (or, once the stage is overdue,
 * its escalation target), they are the named employee or hold the named role with a scope covering the request, and they
 * hold the approve permission. A delegate decides with the delegator's rights and the decision records both people.
 * Nobody decides their own request, and with one_stage_per_approver nobody approves two stages of the same round.
 *
 * Decisions change only the request status (Status); PaymentStatus and SettlementStatus are never touched here.
 */

const FP_APPROVAL_COMMENT_MAX = 1000;
const FP_APPROVAL_REASON_MIN = 5;
const FP_APPROVAL_CANDIDATE_LIMIT = 60;
const FP_APPROVAL_NOTIFY_LIMIT = 25;

/**
 * Posted decision => stored decision / stage status.
 */
function fp_approval_decisions()
{
    return array('approve' => 'approved', 'reject' => 'rejected', 'return' => 'returned');
}

function fp_approval_decision_labels()
{
    return array('approved' => 'Approved', 'rejected' => 'Rejected', 'returned' => 'Returned for changes', 'reopened' => 'Reopened for changes');
}

function fp_approval_stage_status_labels()
{
    return array(
        'pending'   => 'Pending',
        'skipped'   => 'Skipped',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'returned'  => 'Returned',
        'cancelled' => 'Not needed',
    );
}

/* ------------------------------------------------------------------
   Loading
------------------------------------------------------------------ */

function fp_approval_stages($conn, $requestId, $round)
{
    return fp_db_rows(
        $conn,
        'SELECT * FROM branch_expense_request_stages WHERE RequestID = ? AND Round = ? ORDER BY StageNo',
        'ii',
        array((int) $requestId, (int) $round)
    );
}

function fp_approval_current_stage($request, $stages)
{
    foreach ($stages as $stage) {
        if ((int) $stage['StageNo'] === (int) $request['CurrentStageNo']) {
            return $stage;
        }
    }
    return null;
}

/**
 * Decisions of one round, or of every round when $round is 0, with the deciding and represented employees' names.
 */
function fp_approval_decisions_of($conn, $requestId, $round = 0)
{
    $sql = 'SELECT a.*, COALESCE(e.Name, a.ActorUserName) AS ActorName, d.Name AS OnBehalfOfName
            FROM branch_expense_approvals a
            LEFT JOIN employees e ON a.ActorEmployeeID > 0 AND e.ID = a.ActorEmployeeID
            LEFT JOIN employees d ON a.OnBehalfOfEmployeeID > 0 AND d.ID = a.OnBehalfOfEmployeeID
            WHERE a.RequestID = ?';
    $types = 'i';
    $params = array((int) $requestId);
    if ($round > 0) {
        $sql .= ' AND a.Round = ?';
        $types .= 'i';
        $params[] = (int) $round;
    }
    return fp_db_rows($conn, $sql . ' ORDER BY a.Round, a.DecidedAt, a.ID', $types, $params);
}

/**
 * Employees who already approved a stage of the round (as themselves or as the person a delegate acted for).
 */
function fp_approval_used_people($decisions)
{
    $used = array();
    foreach ($decisions as $decision) {
        if ($decision['Decision'] !== 'approved') {
            continue;
        }
        $used[(int) $decision['ActorEmployeeID']] = true;
        if ((int) $decision['OnBehalfOfEmployeeID'] > 0) {
            $used[(int) $decision['OnBehalfOfEmployeeID']] = true;
        }
    }
    return $used;
}

/* ------------------------------------------------------------------
   Who may decide
------------------------------------------------------------------ */

/**
 * Actor for an employee's own login (the first active login holding approve, else the first active login), or null.
 */
function fp_approval_actor_for_employee($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return null;
    }
    return fp_memo('approval_actor:' . $employeeId, function () use ($conn, $employeeId) {
        $best = null;
        foreach (fp_db_rows($conn, 'SELECT UserName FROM users WHERE EmployeeID = ? AND IsActive = 1 ORDER BY UserID', 'i', array($employeeId)) as $user) {
            $candidate = fp_build_actor($conn, $user['UserName'], '');
            if (!$candidate['ok'] || (int) $candidate['employee_id'] !== $employeeId) {
                continue;
            }
            if ($best === null) {
                $best = $candidate;
            }
            if (fp_can($candidate, 'approve')) {
                return $candidate;
            }
        }
        return $best;
    });
}

/**
 * The rights the actor can decide with: their own, then those of each employee who delegated approvals to them today.
 */
function fp_approval_authorities($conn, $actor)
{
    $list = array(array('actor' => $actor, 'employee_id' => (int) $actor['employee_id'], 'on_behalf' => 0, 'on_behalf_name' => ''));
    foreach (fp_delegators_of($conn, $actor['employee_id']) as $delegatorId) {
        $delegator = fp_approval_actor_for_employee($conn, $delegatorId);
        if ($delegator) {
            $list[] = array('actor' => $delegator, 'employee_id' => (int) $delegatorId, 'on_behalf' => (int) $delegatorId, 'on_behalf_name' => $delegator['employee_name']);
        }
    }
    return $list;
}

/**
 * Label of how $authority matches one approver target (named employee, or role + scope), or '' when it does not.
 */
function fp_approval_target_match($authority, $authorityEmployeeId, $type, $role, $employeeId, $record)
{
    if (!fp_can($authority, 'approve')) {
        return '';
    }
    if ($type === 'employee') {
        return (int) $employeeId > 0 && (int) $employeeId === (int) $authorityEmployeeId ? 'Named approver' : '';
    }
    if ($type === 'role' && isset($authority['roles'][$role]) && isset(fp_policy_approver_roles()[$role])
        && fp_scope_covers_record($authority, $authority['roles'][$role]['scope'], $record)) {
        return $authority['roles'][$role]['label'];
    }
    return '';
}

/**
 * Whether the stage has waited longer than its escalation time.
 */
function fp_approval_escalation_due($stage, $now)
{
    if ($stage['EscalateAfterHours'] === null || (string) $stage['EscalateToType'] === '' || empty($stage['ActivatedAt'])) {
        return false;
    }
    return strtotime($stage['ActivatedAt']) + 3600 * (int) $stage['EscalateAfterHours'] <= strtotime($now);
}

/**
 * array('basis' => role|employee|escalation, 'label' => ...) when the authority may decide the stage, else null.
 */
function fp_approval_stage_match($authority, $authorityEmployeeId, $stage, $record, $now)
{
    $label = fp_approval_target_match($authority, $authorityEmployeeId, $stage['ApproverType'], $stage['ApproverRole'], $stage['ApproverEmployeeID'], $record);
    if ($label !== '') {
        return array('basis' => $stage['ApproverType'], 'label' => $label);
    }
    if (fp_approval_escalation_due($stage, $now)) {
        $label = fp_approval_target_match($authority, $authorityEmployeeId, $stage['EscalateToType'], $stage['EscalateToRole'], $stage['EscalateToEmployeeID'], $record);
        if ($label !== '') {
            return array('basis' => 'escalation', 'label' => 'Escalation: ' . $label);
        }
    }
    return null;
}

/**
 * Whether the actor may decide the current stage now: array('ok' => true, 'basis', 'label', 'on_behalf') or array('ok' => false, 'reason').
 * $request needs Status, CurrentStageNo, EmployeeID, CreatedByEmployeeID, OfficeID and StateName.
 */
function fp_approval_eligibility($conn, $actor, $request, $stage, $decisions, $now = null, $authorities = null)
{
    $now = $now ?: date('Y-m-d H:i:s');
    if ($request['Status'] !== 'submitted' || !$stage || $stage['Status'] !== 'pending' || (int) $stage['StageNo'] !== (int) $request['CurrentStageNo']) {
        return array('ok' => false, 'reason' => 'This request is not waiting for a decision.');
    }
    $me = (int) $actor['employee_id'];
    if ($me <= 0) {
        return array('ok' => false, 'reason' => 'Approvals need a login linked to an active employee record.');
    }
    $requester = (int) $request['EmployeeID'];
    if ($me === $requester) {
        return array('ok' => false, 'reason' => 'You cannot decide on your own request.');
    }

    $record = fp_request_scope_record($request);
    $used = fp_access_matrix()['one_stage_per_approver'] ? fp_approval_used_people($decisions) : array();
    $blocked = '';
    foreach ($authorities ?? fp_approval_authorities($conn, $actor) as $authority) {
        if ($authority['on_behalf'] === $requester) {
            continue;
        }
        $match = fp_approval_stage_match($authority['actor'], $authority['employee_id'], $stage, $record, $now);
        if (!$match) {
            continue;
        }
        if (isset($used[$me]) || ($authority['on_behalf'] > 0 && isset($used[$authority['on_behalf']]))) {
            $blocked = 'You (or the person you act for) already approved an earlier stage of this request, so another approver must decide this stage.';
            continue;
        }
        return array(
            'ok'        => true,
            'basis'     => $match['basis'],
            'label'     => $match['label'] . ($authority['on_behalf'] > 0 ? ' (on behalf of ' . $authority['on_behalf_name'] . ')' : ''),
            'on_behalf' => $authority['on_behalf'],
        );
    }
    return array('ok' => false, 'reason' => $blocked !== '' ? $blocked : 'You are not an approver for the current stage of this request.');
}

/**
 * Employees who might match an approver target; every one is verified with a real eligibility check afterwards.
 */
function fp_approval_candidate_ids($conn, $request, $type, $role, $employeeId)
{
    if ($type === 'employee') {
        return fp_unique_ints(array((int) $employeeId));
    }
    $roles = fp_access_matrix()['roles'];
    if ($type !== 'role' || !isset($roles[$role])) {
        return array();
    }
    $def = $roles[$role];
    $ids = array();
    if ($def['erp_roles']) {
        $rows = fp_db_rows(
            $conn,
            'SELECT DISTINCT EmployeeID FROM user_roles WHERE IsActive = 1 AND Role IN (' . implode(',', array_fill(0, count($def['erp_roles']), '?')) . ')',
            str_repeat('s', count($def['erp_roles'])),
            $def['erp_roles']
        );
        $ids = array_merge($ids, array_column($rows, 'EmployeeID'));
    }
    if ($def['user_types']) {
        $rows = fp_db_rows(
            $conn,
            'SELECT DISTINCT EmployeeID FROM users WHERE IsActive = 1 AND EmployeeID > 0 AND UserType IN (' . implode(',', array_fill(0, count($def['user_types']), '?')) . ')',
            str_repeat('s', count($def['user_types'])),
            $def['user_types']
        );
        $ids = array_merge($ids, array_column($rows, 'EmployeeID'));
    }
    switch ($def['mapping']) {
        case 'office_manager':
            $rows = fp_db_rows($conn, 'SELECT EmployeeID FROM branch_expense_office_employees WHERE OfficeID = ? AND IsManager = 1 AND IsActive = 1', 'i', array((int) $request['OfficeID']));
            $ids = array_merge($ids, array_column($rows, 'EmployeeID'));
            break;
        case 'state_head':
            foreach (fp_db_rows($conn, 'SELECT StateHead, StateCorporateHead FROM state WHERE ID = ?', 'i', array((int) $request['StateID'])) as $row) {
                $ids[] = $row['StateHead'];
                $ids[] = $row['StateCorporateHead'];
            }
            break;
        case 'region_head':
            foreach (fp_db_rows($conn, 'SELECT r.RegionHead, r.RegionCorporateHead FROM state s INNER JOIN region r ON r.ID = s.RegionID WHERE s.ID = ?', 'i', array((int) $request['StateID'])) as $row) {
                $ids[] = $row['RegionHead'];
                $ids[] = $row['RegionCorporateHead'];
            }
            break;
        case 'supervisor':
            $ids = array_merge($ids, array_column(fp_db_rows($conn, 'SELECT Supervisor FROM employees WHERE ID = ?', 'i', array((int) $request['EmployeeID'])), 'Supervisor'));
            $ids = array_merge($ids, array_column(fp_db_rows(
                $conn,
                'SELECT DISTINCT c2.CorporateLead FROM employees e
                 INNER JOIN citydata c ON c.CityName = e.City
                 INNER JOIN citydata c2 ON c2.StateID = c.StateID
                 WHERE e.ID = ? AND c2.CorporateLead > 0',
                'i',
                array((int) $request['EmployeeID'])
            ), 'CorporateLead'));
            break;
    }
    return fp_unique_ints($ids);
}

/**
 * People who can decide the current stage right now: list of array('employee_id', 'name', 'label').
 * Checks at most FP_APPROVAL_CANDIDATE_LIMIT candidates per target.
 */
function fp_approval_stage_approvers($conn, $request, $stage, $decisions, $now = null)
{
    $now = $now ?: date('Y-m-d H:i:s');
    if (!$stage || $stage['Status'] !== 'pending' || $request['Status'] !== 'submitted') {
        return array();
    }
    $record = fp_request_scope_record($request);
    $requester = (int) $request['EmployeeID'];
    $used = fp_access_matrix()['one_stage_per_approver'] ? fp_approval_used_people($decisions) : array();
    $targets = array(array($stage['ApproverType'], $stage['ApproverRole'], $stage['ApproverEmployeeID'], ''));
    if (fp_approval_escalation_due($stage, $now)) {
        $targets[] = array($stage['EscalateToType'], $stage['EscalateToRole'], $stage['EscalateToEmployeeID'], 'Escalation: ');
    }

    $result = array();
    foreach ($targets as $target) {
        list($type, $role, $employeeId, $prefix) = $target;
        foreach (array_slice(fp_approval_candidate_ids($conn, $request, $type, $role, $employeeId), 0, FP_APPROVAL_CANDIDATE_LIMIT) as $candidateId) {
            if ($candidateId === $requester || isset($result[$candidateId]) || isset($used[$candidateId])) {
                continue;
            }
            $candidate = fp_approval_actor_for_employee($conn, $candidateId);
            $label = $candidate ? fp_approval_target_match($candidate, $candidateId, $type, $role, $employeeId, $record) : '';
            if ($label !== '') {
                $result[$candidateId] = array('employee_id' => $candidateId, 'name' => $candidate['employee_name'], 'label' => $prefix . $label);
            }
        }
    }

    $today = substr($now, 0, 10);
    foreach ($result as $delegatorId => $row) {
        $delegateId = fp_delegation_delegate_for($conn, $delegatorId, $today);
        if ($delegateId <= 0 || $delegateId === $requester || isset($result[$delegateId]) || isset($used[$delegateId])) {
            continue;
        }
        $delegate = fp_approval_actor_for_employee($conn, $delegateId);
        if ($delegate) {
            $result[$delegateId] = array('employee_id' => $delegateId, 'name' => $delegate['employee_name'], 'label' => 'Delegate of ' . $row['name']);
        }
    }
    return array_values($result);
}

/**
 * Whether the actor may see a non-draft request because they can decide it now or decided one of its stages.
 */
function fp_approval_is_involved($conn, $actor, $request)
{
    $me = (int) $actor['employee_id'];
    if ($me <= 0 || $request['Status'] === 'draft') {
        return false;
    }
    if (fp_db_rows($conn, 'SELECT ID FROM branch_expense_approvals WHERE RequestID = ? AND ActorEmployeeID = ? LIMIT 1', 'ii', array((int) $request['ID'], $me))) {
        return true;
    }
    if ($request['Status'] !== 'submitted') {
        return false;
    }
    $round = (int) $request['SubmissionRound'];
    $stage = fp_approval_current_stage($request, fp_approval_stages($conn, $request['ID'], $round));
    return fp_approval_eligibility($conn, $actor, $request, $stage, fp_approval_decisions_of($conn, $request['ID'], $round))['ok'];
}

/**
 * Everything the request page needs about approvals for this actor.
 */
function fp_approval_panel($conn, $actor, $request)
{
    $round = (int) $request['SubmissionRound'];
    $stages = $round > 0 ? fp_approval_stages($conn, $request['ID'], $round) : array();
    $stage = fp_approval_current_stage($request, $stages);
    $roundDecisions = $round > 0 ? fp_approval_decisions_of($conn, $request['ID'], $round) : array();
    $now = date('Y-m-d H:i:s');
    $eligibility = fp_approval_eligibility($conn, $actor, $request, $stage, $roundDecisions, $now);
    $isOwn = (int) $request['EmployeeID'] === (int) $actor['employee_id'];

    return array(
        'can_decide'     => $eligibility['ok'],
        'decide_as'      => $eligibility['ok'] ? $eligibility['label'] : '',
        'decide_reason'  => $eligibility['ok'] ? '' : $eligibility['reason'],
        'stage'          => $stage,
        'overdue'        => $stage && $request['Status'] === 'submitted' && fp_approval_escalation_due($stage, $now),
        'waiting_for'    => $request['Status'] === 'submitted' ? fp_approval_stage_approvers($conn, $request, $stage, $roundDecisions, $now) : array(),
        'decisions'      => fp_approval_decisions_of($conn, $request['ID']),
        'can_reopen'     => fp_approval_reopen_error($actor, $request) === '' && !$isOwn,
    );
}

/* ------------------------------------------------------------------
   Inbox
------------------------------------------------------------------ */

/**
 * Submitted requests whose current stage the actor may decide now, oldest waiting first.
 */
function fp_approval_inbox($conn, $actor)
{
    $me = (int) $actor['employee_id'];
    if ($me <= 0) {
        return array();
    }
    $authorities = fp_approval_authorities($conn, $actor);
    $employeeIds = array();
    $roles = array();
    foreach ($authorities as $authority) {
        if (!fp_can($authority['actor'], 'approve')) {
            continue;
        }
        $employeeIds[] = $authority['employee_id'];
        $roles = array_merge($roles, array_keys($authority['actor']['roles']));
    }
    $employeeIds = fp_unique_ints($employeeIds);
    $roles = array_values(array_intersect(array_unique($roles), array_keys(fp_policy_approver_roles())));
    if (!$employeeIds && !$roles) {
        return array();
    }

    $where = array();
    $types = '';
    $params = array();
    if ($employeeIds) {
        $in = implode(',', $employeeIds);
        $where[] = "(rs.ApproverType = 'employee' AND rs.ApproverEmployeeID IN ($in))";
        $where[] = "(rs.EscalateToType = 'employee' AND rs.EscalateToEmployeeID IN ($in))";
    }
    if ($roles) {
        $marks = implode(',', array_fill(0, count($roles), '?'));
        $where[] = "(rs.ApproverType = 'role' AND rs.ApproverRole IN ($marks))";
        $where[] = "(rs.EscalateToType = 'role' AND rs.EscalateToRole IN ($marks))";
        $types .= str_repeat('s', 2 * count($roles));
        $params = array_merge($roles, $roles);
    }
    $types .= 'i';
    $params[] = $me;

    $rows = fp_db_rows(
        $conn,
        'SELECT r.ID, r.ExpenseNumber, r.RequestType, r.Status, r.EmployeeID, r.CreatedByEmployeeID, r.OfficeID, r.StateID, r.TotalAmount, r.Purpose,
                r.SubmittedAt, r.SubmissionRound, r.CurrentStageNo, r.RowVersion, r.ApprovalPolicyCode, r.ApprovalPolicyVersion,
                s.StateName, o.OfficeCode, o.OfficeName, e.Name AS EmployeeName, e.EmployeeNumber,
                rs.StageNo, rs.StageName, rs.ApproverType, rs.ApproverRole, rs.ApproverEmployeeID, rs.EscalateAfterHours, rs.EscalateToType,
                rs.EscalateToRole, rs.EscalateToEmployeeID, rs.Status AS StageStatus, rs.ActivatedAt, rs.EscalatedAt,
                (SELECT COUNT(*) FROM branch_expense_request_stages x WHERE x.RequestID = r.ID AND x.Round = r.SubmissionRound AND x.Status <> \'skipped\') AS StageCount
         FROM branch_expense_requests r
         INNER JOIN branch_expense_request_stages rs ON rs.RequestID = r.ID AND rs.Round = r.SubmissionRound AND rs.StageNo = r.CurrentStageNo
         INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
         LEFT JOIN state s ON s.ID = r.StateID
         LEFT JOIN employees e ON e.ID = r.EmployeeID
         WHERE r.Status = \'submitted\' AND rs.Status = \'pending\' AND (' . implode(' OR ', $where) . ') AND r.EmployeeID <> ?
         ORDER BY rs.ActivatedAt, r.ID
         LIMIT 1000',
        $types,
        $params
    );
    if (!$rows) {
        return array();
    }

    $decisionsByRequest = array();
    foreach (fp_db_rows($conn, 'SELECT a.RequestID, a.Round, a.Decision, a.ActorEmployeeID, a.OnBehalfOfEmployeeID FROM branch_expense_approvals a WHERE a.RequestID IN (' . implode(',', fp_unique_ints(array_column($rows, 'ID'))) . ')') as $decision) {
        $decisionsByRequest[(int) $decision['RequestID']][(int) $decision['Round']][] = $decision;
    }

    $now = date('Y-m-d H:i:s');
    $types = fp_request_types();
    $items = array();
    foreach ($rows as $row) {
        $stage = array(
            'StageNo' => $row['StageNo'], 'StageName' => $row['StageName'], 'Status' => $row['StageStatus'],
            'ApproverType' => $row['ApproverType'], 'ApproverRole' => $row['ApproverRole'], 'ApproverEmployeeID' => $row['ApproverEmployeeID'],
            'EscalateAfterHours' => $row['EscalateAfterHours'], 'EscalateToType' => $row['EscalateToType'], 'EscalateToRole' => $row['EscalateToRole'],
            'EscalateToEmployeeID' => $row['EscalateToEmployeeID'], 'ActivatedAt' => $row['ActivatedAt'],
        );
        $decisions = $decisionsByRequest[(int) $row['ID']][(int) $row['SubmissionRound']] ?? array();
        $eligibility = fp_approval_eligibility($conn, $actor, $row, $stage, $decisions, $now, $authorities);
        if (!$eligibility['ok']) {
            continue;
        }
        $waitingSince = $row['ActivatedAt'] ?: $row['SubmittedAt'];
        $items[] = array(
            'ID'            => (int) $row['ID'],
            'ExpenseNumber' => $row['ExpenseNumber'],
            'RequestType'   => $types[$row['RequestType']] ?? $row['RequestType'],
            'Employee'      => $row['EmployeeName'] . ($row['EmployeeNumber'] ? ' (' . $row['EmployeeNumber'] . ')' : ''),
            'Office'        => $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')',
            'State'         => (string) $row['StateName'],
            'Purpose'       => mb_strimwidth((string) $row['Purpose'], 0, 120, '...'),
            'TotalAmount'   => $row['TotalAmount'],
            'Stage'         => 'Stage ' . (int) $row['StageNo'] . ' of ' . (int) $row['StageCount'] . ': ' . $row['StageName'],
            'StageNo'       => (int) $row['StageNo'],
            'DecideAs'      => $eligibility['label'],
            'WaitingSince'  => $waitingSince,
            'WaitingHours'  => max(0, (int) floor((strtotime($now) - strtotime($waitingSince)) / 3600)),
            'Overdue'       => fp_approval_escalation_due($stage, $now),
            'RowVersion'    => (int) $row['RowVersion'],
        );
    }
    return $items;
}

/**
 * Decisions the actor took (newest first).
 */
function fp_approval_my_decisions($conn, $actor, $limit = 300)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT a.ID, a.RequestID, a.Round, a.StageNo, a.StageName, a.Decision, a.Comments, a.BasisLabel, a.DecidedAt,
                r.ExpenseNumber, r.Status, r.PaymentStatus, r.TotalAmount, e.Name AS EmployeeName, o.OfficeName
         FROM branch_expense_approvals a
         INNER JOIN branch_expense_requests r ON r.ID = a.RequestID
         INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
         LEFT JOIN employees e ON e.ID = r.EmployeeID
         WHERE a.ActorEmployeeID = ?
         ORDER BY a.DecidedAt DESC, a.ID DESC
         LIMIT ' . (int) $limit,
        'i',
        array((int) $actor['employee_id'])
    );
    $decisionLabels = fp_approval_decision_labels();
    $statusLabels = fp_request_statuses();
    $items = array();
    foreach ($rows as $row) {
        $items[] = array(
            'RequestID'     => (int) $row['RequestID'],
            'ExpenseNumber' => $row['ExpenseNumber'],
            'Employee'      => (string) $row['EmployeeName'],
            'Office'        => $row['OfficeName'],
            'TotalAmount'   => $row['TotalAmount'],
            'Stage'         => (int) $row['StageNo'] > 0 ? 'Round ' . (int) $row['Round'] . ', stage ' . (int) $row['StageNo'] . ': ' . $row['StageName'] : 'Round ' . (int) $row['Round'] . ', after final approval',
            'Decision'      => $row['Decision'],
            'DecisionLabel' => $decisionLabels[$row['Decision']] ?? $row['Decision'],
            'Comments'      => $row['Comments'],
            'DecidedAs'     => $row['BasisLabel'],
            'DecidedAt'     => $row['DecidedAt'],
            'RequestStatus' => $statusLabels[$row['Status']] ?? $row['Status'],
        );
    }
    return $items;
}

/* ------------------------------------------------------------------
   Decisions
------------------------------------------------------------------ */

function fp_approval_clean_comment($text)
{
    return trim(preg_replace('/[ \t]+/u', ' ', str_replace("\r", '', (string) $text)));
}

function fp_approval_insert_decision($conn, $actor, $requestId, $round, $stageNo, $stageName, $decision, $comments, $basis, $basisLabel, $onBehalf, $now)
{
    $userId = (int) $actor['user_id'];
    $employeeId = (int) $actor['employee_id'];
    $userName = mb_substr((string) $actor['username'], 0, 100);
    $ip = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $basisLabel = mb_substr($basisLabel, 0, 200);
    fp_policy_exec(
        $conn,
        'INSERT INTO branch_expense_approvals
            (RequestID, Round, StageNo, StageName, Decision, Comments, Basis, BasisLabel, OnBehalfOfEmployeeID, ActorUserID, ActorEmployeeID, ActorUserName, IPAddress, DecidedAt)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'iiisssssiiisss',
        array((int) $requestId, (int) $round, (int) $stageNo, (string) $stageName, $decision, $comments, $basis, $basisLabel, (int) $onBehalf, $userId, $employeeId, $userName, $ip, $now)
    );
}

/**
 * Updates the request when it is still at $rowVersion and $status; returns affected rows.
 */
function fp_approval_update_request($conn, $actor, $requestId, $rowVersion, $status, $set, $types, $params, $now)
{
    return fp_policy_exec(
        $conn,
        'UPDATE branch_expense_requests SET ' . $set . ', RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
         WHERE ID = ? AND RowVersion = ? AND Status = ?',
        $types . 'iisiis',
        array_merge($params, array((int) $actor['user_id'], (int) $actor['employee_id'], $now, (int) $requestId, (int) $rowVersion, $status))
    );
}

function fp_approval_is_duplicate($e)
{
    return $e instanceof mysqli_sql_exception && (int) $e->getCode() === 1062;
}

/**
 * Approve, reject or return the current stage. Expects ID, RowVersion, StageNo, Decision and Comments.
 */
function fp_approval_decide($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $stageNo = fp_input_int($post, 'StageNo');
    $decision = fp_input_string($post, 'Decision');
    $comments = fp_approval_clean_comment($post['Comments'] ?? '');
    $map = fp_approval_decisions();
    if ($id <= 0 || $stageNo <= 0 || !isset($map[$decision])) {
        return fp_result(false, 422, 'Invalid decision.');
    }
    if (mb_strlen($comments) > FP_APPROVAL_COMMENT_MAX) {
        return fp_result(false, 422, 'Comments can be at most ' . FP_APPROVAL_COMMENT_MAX . ' characters.', array('errors' => array('Comments' => 'Too long.')));
    }
    if ($decision !== 'approve' && mb_strlen($comments) < FP_APPROVAL_REASON_MIN) {
        return fp_result(false, 422, 'Please write a reason (at least ' . FP_APPROVAL_REASON_MIN . ' characters) so the requester knows what to do.', array('errors' => array('Comments' => 'Required.')));
    }

    $result = fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $stageNo, $decision, $comments, $map) {
        if (!fp_request_get($conn, $id, 'update')) {
            return fp_result(false, 404, 'Request not found.');
        }
        $request = fp_request_get_detail($conn, $id);
        if ((int) $request['RowVersion'] !== $rowVersion || (int) $request['CurrentStageNo'] !== $stageNo) {
            return fp_result(false, 409, 'This request was changed or already decided by someone else. Reload the page to see its current state.');
        }
        $round = (int) $request['SubmissionRound'];
        $stages = fp_approval_stages($conn, $id, $round);
        $stage = fp_approval_current_stage($request, $stages);
        $now = date('Y-m-d H:i:s');
        $eligibility = fp_approval_eligibility($conn, $actor, $request, $stage, fp_approval_decisions_of($conn, $id, $round), $now);
        if (!$eligibility['ok']) {
            return fp_result(false, 403, $eligibility['reason']);
        }

        $status = $map[$decision];
        $next = null;
        if ($decision === 'approve') {
            foreach ($stages as $candidate) {
                if ((int) $candidate['StageNo'] > $stageNo && $candidate['Status'] === 'pending') {
                    $next = $candidate;
                    break;
                }
            }
        }
        $warnings = array();
        if ($decision === 'approve' && !$next) {
            $blocked = array();
            foreach (fp_budget_impact($conn, $request, fp_request_items($conn, $id), true, substr((string) $request['SubmittedAt'], 0, 10)) as $budget) {
                if ($budget['Exceeded']) {
                    $text = $budget['Label'] . ' would be exceeded by ' . fp_budget_money($budget['OverBy']) . '.';
                    if ($budget['Enforcement'] === 'block') {
                        $blocked[] = $text;
                    } else {
                        $warnings[] = $text;
                    }
                }
            }
            if ($blocked) {
                return fp_result(false, 422, 'Final approval is not possible because the budget is used up: ' . implode(' ', $blocked) . ' Ask Finance to increase the budget, or return or reject the request.', array('errors' => array('budget' => implode(' ', $blocked))));
            }
        }

        try {
            fp_approval_insert_decision($conn, $actor, $id, $round, $stageNo, $stage['StageName'], $status, $comments, $eligibility['basis'], $eligibility['label'], $eligibility['on_behalf'], $now);
        } catch (mysqli_sql_exception $e) {
            if (fp_approval_is_duplicate($e)) {
                return fp_result(false, 409, 'This stage was already decided by someone else. Reload the page to see its current state.');
            }
            throw $e;
        }
        $affected = fp_policy_exec(
            $conn,
            'UPDATE branch_expense_request_stages SET Status = ?, DecidedAt = ? WHERE RequestID = ? AND Round = ? AND StageNo = ? AND Status = \'pending\'',
            'ssiii',
            array($status, $now, $id, $round, $stageNo)
        );
        if ($affected !== 1) {
            return fp_result(false, 409, 'This stage was already decided by someone else. Reload the page to see its current state.');
        }

        if ($next) {
            fp_policy_exec($conn, 'UPDATE branch_expense_request_stages SET ActivatedAt = ? WHERE RequestID = ? AND Round = ? AND StageNo = ?', 'siii', array($now, $id, $round, (int) $next['StageNo']));
            $affected = fp_approval_update_request($conn, $actor, $id, $rowVersion, 'submitted', 'CurrentStageNo = ?', 'i', array((int) $next['StageNo']), $now);
            $newStatus = 'submitted';
        } else {
            if ($decision !== 'approve') {
                fp_policy_exec($conn, 'UPDATE branch_expense_request_stages SET Status = \'cancelled\' WHERE RequestID = ? AND Round = ? AND StageNo > ? AND Status = \'pending\'', 'iii', array($id, $round, $stageNo));
            }
            $timeColumn = array('approved' => 'ApprovedAt', 'rejected' => 'RejectedAt', 'returned' => 'ReturnedAt')[$status];
            $affected = fp_approval_update_request($conn, $actor, $id, $rowVersion, 'submitted', 'Status = ?, ' . $timeColumn . ' = ?, CurrentStageNo = 0', 'ss', array($status, $now), $now);
            $newStatus = $status;
        }
        if ($affected !== 1) {
            return fp_result(false, 409, 'This request was changed or already decided by someone else. Reload the page to see its current state.');
        }

        $newValues = array('Status' => $newStatus, 'Stage' => $stageNo . ' ' . $stage['StageName'], 'Round' => $round, 'DecidedAs' => $eligibility['label']);
        if ($next) {
            $newValues['NextStage'] = (int) $next['StageNo'] . ' ' . $next['StageName'];
        }
        if ($warnings) {
            $newValues['BudgetWarnings'] = $warnings;
        }
        $auditAction = $decision === 'approve' ? ($next ? 'approve_stage' : 'approve') : $decision;
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $id, $auditAction, array('Status' => 'submitted', 'Stage' => $stageNo), $newValues, $comments);

        if ($next) {
            fp_notice_add('stage', $id);
            $message = $request['ExpenseNumber'] . ': stage ' . $stageNo . ' approved. It now waits for stage ' . (int) $next['StageNo'] . ' (' . $next['StageName'] . ').';
        } else {
            fp_notice_add('outcome', $id, array('decision' => $status, 'comments' => $comments));
            $messages = array(
                'approved' => $request['ExpenseNumber'] . ' is fully approved.',
                'rejected' => $request['ExpenseNumber'] . ' was rejected.',
                'returned' => $request['ExpenseNumber'] . ' was returned to the requester for changes.',
            );
            $message = $messages[$status];
            if ($warnings) {
                $message .= ' Budget warning: ' . implode(' ', $warnings);
            }
        }
        return fp_result(true, 200, $message, array('data' => array('ID' => $id, 'Status' => $newStatus)));
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/**
 * '' when the actor may reopen the approved request for changes, otherwise the reason.
 */
function fp_approval_reopen_error($actor, $request)
{
    if (!fp_can($actor, 'approve') || !$actor['grants']['approve']['all'] || (int) $actor['employee_id'] <= 0) {
        return 'Only Head Office Finance, CFO or Admin can reopen an approved request.';
    }
    if ($request['Status'] !== 'approved') {
        return 'Only approved requests can be reopened.';
    }
    if ($request['PaymentStatus'] !== 'unpaid' || !in_array($request['SettlementStatus'], array('not_started', 'not_applicable'), true)) {
        return 'This request is already (partly) paid or settled, so it cannot be reopened.';
    }
    return '';
}

/**
 * Sends an approved, unpaid request back to the requester for changes; resubmitting starts a new round
 * in which every stage must approve again. Expects ID, RowVersion and Reason.
 */
function fp_approval_reopen($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $reason = fp_approval_clean_comment($post['Reason'] ?? '');
    if ($id <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (mb_strlen($reason) < FP_APPROVAL_REASON_MIN || mb_strlen($reason) > FP_APPROVAL_COMMENT_MAX) {
        return fp_result(false, 422, 'Please write what must be changed (' . FP_APPROVAL_REASON_MIN . '-' . FP_APPROVAL_COMMENT_MAX . ' characters).', array('errors' => array('Reason' => 'Required.')));
    }

    $result = fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $reason) {
        if (!fp_request_get($conn, $id, 'update')) {
            return fp_result(false, 404, 'Request not found.');
        }
        $request = fp_request_get_detail($conn, $id);
        if ((int) $request['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This request was changed by someone else. Reload the page to see its current state.');
        }
        $error = fp_approval_reopen_error($actor, $request);
        if ($error !== '') {
            return fp_result(false, 403, $error);
        }
        if ((int) $request['EmployeeID'] === (int) $actor['employee_id']) {
            return fp_result(false, 403, 'You cannot reopen your own request.');
        }
        if (fp_db_rows($conn, "SELECT ID FROM branch_expense_settlements WHERE RequestID = ? AND Status <> 'cancelled' LIMIT 1", 'i', array($id))) {
            return fp_result(false, 403, 'The holder has started settling this advance, so it cannot be reopened.');
        }
        $now = date('Y-m-d H:i:s');
        $round = (int) $request['SubmissionRound'];
        try {
            fp_approval_insert_decision($conn, $actor, $id, $round, 0, '', 'reopened', $reason, 'finance', 'Reopened after final approval', 0, $now);
        } catch (mysqli_sql_exception $e) {
            if (fp_approval_is_duplicate($e)) {
                return fp_result(false, 409, 'This request was already reopened. Reload the page.');
            }
            throw $e;
        }
        $affected = fp_approval_update_request($conn, $actor, $id, $rowVersion, 'approved', 'Status = \'returned\', ReturnedAt = ?, CurrentStageNo = 0', 's', array($now), $now);
        if ($affected !== 1) {
            return fp_result(false, 409, 'This request was changed by someone else. Reload the page to see its current state.');
        }
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $id, 'reopen', array('Status' => 'approved'), array('Status' => 'returned', 'Round' => $round), $reason);
        fp_notice_add('outcome', $id, array('decision' => 'reopened', 'comments' => $reason));
        return fp_result(true, 200, $request['ExpenseNumber'] . ' was sent back to the requester. After the changes it must be approved again.', array('data' => array('ID' => $id)));
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/* ------------------------------------------------------------------
   Escalation
------------------------------------------------------------------ */

/**
 * Marks overdue current stages as escalated (once each), audits it and notifies everyone who can now decide.
 * The escalation target can decide as soon as the time has passed, even before this runs.
 */
function fp_approval_process_escalations($conn, $limit = 200)
{
    $now = date('Y-m-d H:i:s');
    $rows = fp_db_rows(
        $conn,
        'SELECT rs.ID, rs.RequestID, rs.StageNo, rs.StageName, rs.EscalateAfterHours
         FROM branch_expense_request_stages rs
         INNER JOIN branch_expense_requests r ON r.ID = rs.RequestID AND r.SubmissionRound = rs.Round AND r.CurrentStageNo = rs.StageNo
         WHERE r.Status = \'submitted\' AND rs.Status = \'pending\' AND rs.EscalatedAt IS NULL AND rs.EscalateAfterHours IS NOT NULL
           AND rs.EscalateToType <> \'\' AND rs.ActivatedAt IS NOT NULL AND rs.ActivatedAt <= DATE_SUB(?, INTERVAL rs.EscalateAfterHours HOUR)
         ORDER BY rs.ActivatedAt
         LIMIT ' . (int) $limit,
        's',
        array($now)
    );
    $system = array('user_id' => 0, 'employee_id' => 0, 'username' => 'System (escalation)');
    $count = 0;
    foreach ($rows as $row) {
        if (fp_policy_exec($conn, 'UPDATE branch_expense_request_stages SET EscalatedAt = ? WHERE ID = ? AND EscalatedAt IS NULL', 'si', array($now, (int) $row['ID'])) !== 1) {
            continue;
        }
        fp_audit_log($conn, $system, FP_REQUEST_ENTITY, $row['RequestID'], 'escalate', null, array('Stage' => (int) $row['StageNo'] . ' ' . $row['StageName']), 'No decision within ' . (int) $row['EscalateAfterHours'] . ' hours');
        fp_notice_add('escalated', (int) $row['RequestID']);
        $count++;
    }
    fp_notice_send($conn);
    return $count;
}

/* ------------------------------------------------------------------
   Notifications (ERP notification bell + mobile push), sent after commit
------------------------------------------------------------------ */

function fp_notice_buffer($notice = null)
{
    static $buffer = array();
    if ($notice !== null) {
        $buffer[] = $notice;
        return array();
    }
    $out = $buffer;
    $buffer = array();
    return $out;
}

function fp_notice_add($type, $requestId, $extra = array())
{
    fp_notice_buffer(array('type' => $type, 'request_id' => (int) $requestId) + $extra);
}

/**
 * Sends queued notices when $result succeeded (the transaction committed), otherwise drops them.
 */
function fp_notice_finish($conn, $result)
{
    if (!empty($result['ok'])) {
        fp_notice_send($conn);
    } else {
        fp_notice_buffer();
    }
}

/**
 * Notification messages for one notice: list of array('employee_id', 'title', 'body').
 */
function fp_notice_messages($conn, $notice)
{
    $request = fp_request_get_detail($conn, $notice['request_id']);
    if (!$request) {
        return array();
    }
    $summary = $request['ExpenseNumber'] . ' (' . fp_budget_money(fp_decimal_to_paise($request['TotalAmount'])) . ', ' . $request['OfficeName'] . ')';
    $messages = array();

    if ($notice['type'] === 'stage' || $notice['type'] === 'escalated') {
        $round = (int) $request['SubmissionRound'];
        $stage = fp_approval_current_stage($request, fp_approval_stages($conn, $request['ID'], $round));
        if (!$stage || $request['Status'] !== 'submitted') {
            return array();
        }
        $approvers = fp_approval_stage_approvers($conn, $request, $stage, fp_approval_decisions_of($conn, $request['ID'], $round));
        $title = $notice['type'] === 'escalated' ? 'Overdue expense approval' : 'Expense approval needed';
        $body = $notice['type'] === 'escalated'
            ? $summary . ' from ' . $request['EmployeeName'] . ' is waiting longer than ' . (int) $stage['EscalateAfterHours'] . ' hours at stage ' . (int) $stage['StageNo'] . ' (' . $stage['StageName'] . ').'
            : $summary . ' from ' . $request['EmployeeName'] . ' is waiting for your decision at stage ' . (int) $stage['StageNo'] . ' (' . $stage['StageName'] . ').';
        foreach (array_slice($approvers, 0, FP_APPROVAL_NOTIFY_LIMIT) as $approver) {
            $messages[] = array('employee_id' => $approver['employee_id'], 'title' => $title, 'body' => $body);
        }
        return $messages;
    }

    if ($notice['type'] === 'outcome') {
        $titles = array(
            'approved' => 'Expense request approved',
            'rejected' => 'Expense request rejected',
            'returned' => 'Expense request returned for changes',
            'reopened' => 'Approved expense request reopened for changes',
        );
        $decision = $notice['decision'] ?? '';
        if (!isset($titles[$decision])) {
            return array();
        }
        $body = $summary . ': ' . strtolower(fp_approval_decision_labels()[$decision]) . '.';
        if (($notice['comments'] ?? '') !== '') {
            $body .= ' Comment: ' . mb_strimwidth($notice['comments'], 0, 300, '...');
        }
        return array(array('employee_id' => (int) $request['EmployeeID'], 'title' => $titles[$decision], 'body' => $body));
    }

    if ($notice['type'] === 'payment') {
        $statusLabel = strtolower(fp_payment_request_statuses()[$notice['status']] ?? $notice['status']);
        $amount = fp_payment_money((int) $notice['amount']);
        if ($notice['event'] === 'recorded') {
            $title = 'Expense payment made';
            $body = $summary . ': ' . $amount . ' paid by ' . $notice['mode'] . ($notice['reference'] !== '' ? ' (ref. ' . $notice['reference'] . ')' : '') . ' as ' . $notice['number'] . '.';
        } else {
            $title = $notice['event'] === 'failed' ? 'Expense payment failed' : 'Expense payment reversed';
            $body = $summary . ': payment ' . $notice['number'] . ' of ' . $amount . ' was marked ' . $notice['event'] . '. Reason: ' . mb_strimwidth($notice['reason'], 0, 300, '...');
        }
        return array(array('employee_id' => (int) $request['EmployeeID'], 'title' => $title, 'body' => $body . ' Payment status: ' . $statusLabel . '.'));
    }

    if ($notice['type'] === 'settlement') {
        return fp_settlement_notice_messages($conn, $notice, $request, $summary);
    }

    if ($notice['type'] === 'recurring' || $notice['type'] === 'reminder') {
        return fp_direct_notice_messages($conn, $notice, $request, $summary);
    }
    return array();
}

/**
 * Publishes queued notices through the ERP notification controller. Failures are logged and never undo the decision.
 */
function fp_notice_send($conn)
{
    $notices = fp_notice_buffer();
    $settings = fp_access_matrix()['notifications'] ?? array();
    if (!$notices || empty($settings['portal'])) {
        return 0;
    }
    $sent = 0;
    try {
        if (!function_exists('pnc_publishNotification')) {
            $file = dirname(__DIR__, 2) . '/controllers/portal_notification_controller.php';
            if (!is_file($file) || !function_exists('_InsertTableRecords_prepare')) {
                return 0;
            }
            require_once $file;
        }
        foreach ($notices as $notice) {
            foreach (fp_notice_messages($conn, $notice) as $message) {
                if ((int) $message['employee_id'] <= 0) {
                    continue;
                }
                try {
                    $published = pnc_publishNotification($conn, array(
                        'target'      => 'employee',
                        'employee_id' => (int) $message['employee_id'],
                        'title'       => $message['title'],
                        'body'        => $message['body'],
                        'payload'     => array('type' => 'fp_expense_request', 'request_id' => $notice['request_id'], 'url' => $message['url'] ?? 'finance-performance/view-expense-request.php?id=' . $notice['request_id']),
                        'send_push'   => !empty($settings['mobile_push']),
                        'save_portal' => true,
                        'created_by'  => 'Finance & Performance',
                    ));
                    if (empty($published['error'])) {
                        $sent++;
                    }
                } catch (Throwable $e) {
                    error_log('Finance & Performance notification failed: ' . $e->getMessage());
                }
            }
        }
    } catch (Throwable $e) {
        error_log('Finance & Performance notifications failed: ' . $e->getMessage());
    }
    return $sent;
}
