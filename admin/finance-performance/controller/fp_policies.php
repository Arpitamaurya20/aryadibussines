<?php
/**
 * Approval policies (branch_expense_approval_policies, branch_expense_policy_stages), approval delegations,
 * and the approval route chosen for a request when it is submitted.
 * Loaded by fp_requests.php; requires fp_access.php and fp_audit.php.
 *
 * A policy covers a request when every criterion it sets matches: legal entity, state, branch office, expense type,
 * category, request total between MinAmount and MaxAmount (inclusive), and the submission date within its effective
 * dates. A category policy covers a request only when every line is in that category or one of its sub-categories.
 * The lowest Priority number wins. Publishing refuses a policy that overlaps another active policy with the same
 * priority, so policies cannot tie; if no policy or a tie is found anyway, submission is refused.
 *
 * Approvers always come from the stored policy (a logical role from the access matrix, or a named employee whose
 * login holds the approve permission), never from values sent by the browser at submission or approval time.
 */

const FP_POLICY_ENTITY = 'approval_policy';
const FP_DELEGATION_ENTITY = 'approval_delegation';
const FP_POLICY_MAX_STAGES = 10;
const FP_DELEGATION_MAX_DAYS = 90;

function fp_policy_statuses()
{
    return array('draft' => 'Draft', 'active' => 'Active', 'retired' => 'Retired');
}

/**
 * Logical roles that hold the approve permission: [key => label].
 */
function fp_policy_approver_roles()
{
    $roles = array();
    foreach (fp_access_matrix()['roles'] as $key => $role) {
        if (in_array('approve', $role['permissions'], true)) {
            $roles[$key] = $role['label'];
        }
    }
    return $roles;
}

function fp_policy_exec($conn, $sql, $types, $params)
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

/**
 * '' when the employee may approve (active, non-vendor, active login holding the approve permission), otherwise the reason.
 */
function fp_policy_employee_approver_error($conn, $employeeId)
{
    static $cache = array();
    $employeeId = (int) $employeeId;
    if (isset($cache[$employeeId])) {
        return $cache[$employeeId];
    }
    $vendorFilter = fp_access_matrix()['exclude_vendor_employees'] ? " AND e.Vendor <> '1'" : '';
    $users = $employeeId > 0 ? fp_db_rows(
        $conn,
        'SELECT u.UserName FROM users u INNER JOIN employees e ON e.ID = u.EmployeeID
         WHERE u.EmployeeID = ? AND u.IsActive = 1 AND e.IsActive = 1' . $vendorFilter,
        'i',
        array($employeeId)
    ) : array();
    if (!$users) {
        return $cache[$employeeId] = 'is not an active employee with an active login';
    }
    foreach ($users as $user) {
        if (fp_can(fp_build_actor($conn, $user['UserName'], ''), 'approve')) {
            return $cache[$employeeId] = '';
        }
    }
    return $cache[$employeeId] = 'does not have approval rights';
}

function fp_policy_employee_names($conn, $ids)
{
    $ids = fp_unique_ints($ids);
    if (!$ids) {
        return array();
    }
    $names = array();
    foreach (fp_db_rows($conn, 'SELECT ID, Name, EmployeeNumber FROM employees WHERE ID IN (' . implode(',', $ids) . ')') as $row) {
        $names[(int) $row['ID']] = $row['Name'] . ($row['EmployeeNumber'] !== null && $row['EmployeeNumber'] !== '' ? ' (' . $row['EmployeeNumber'] . ')' : '');
    }
    return $names;
}

function fp_policy_approver_label($type, $role, $employeeId, $names)
{
    if ($type === 'role') {
        $roles = fp_policy_approver_roles();
        return 'Any ' . ($roles[$role] ?? $role) . ' covering the request';
    }
    if ($type === 'employee') {
        return $names[(int) $employeeId] ?? 'Employee #' . (int) $employeeId;
    }
    return '';
}

/* ------------------------------------------------------------------
   Loading
------------------------------------------------------------------ */

function fp_policy_normalize($row)
{
    foreach (array('ID', 'Version', 'CompanyID', 'StateID', 'OfficeID', 'Priority', 'RowVersion') as $field) {
        $row[$field] = (int) $row[$field];
    }
    $row['CategoryID'] = $row['CategoryID'] === null ? null : (int) $row['CategoryID'];
    return $row;
}

function fp_policy_stage_normalize($row)
{
    foreach (array('StageNo', 'ApproverEmployeeID', 'IsMandatory', 'EscalateToEmployeeID') as $field) {
        $row[$field] = (int) $row[$field];
    }
    $row['EscalateAfterHours'] = $row['EscalateAfterHours'] === null ? null : (int) $row['EscalateAfterHours'];
    return $row;
}

function fp_policy_get($conn, $id, $lock = '')
{
    $rows = fp_db_rows($conn, 'SELECT * FROM branch_expense_approval_policies WHERE ID = ?' . ($lock === 'update' ? ' FOR UPDATE' : ''), 'i', array((int) $id));
    return $rows ? fp_policy_normalize($rows[0]) : null;
}

function fp_policy_stages($conn, $policyId)
{
    return array_map('fp_policy_stage_normalize', fp_db_rows(
        $conn,
        'SELECT StageNo, StageName, ApproverType, ApproverRole, ApproverEmployeeID, IsMandatory, ApplyFromAmount,
                EscalateAfterHours, EscalateToType, EscalateToRole, EscalateToEmployeeID
         FROM branch_expense_policy_stages WHERE PolicyID = ? ORDER BY StageNo',
        'i',
        array((int) $policyId)
    ));
}

function fp_policy_category_parents($conn)
{
    $parents = array();
    foreach (fp_db_rows($conn, 'SELECT ID, ParentCategoryID FROM branch_expense_categories') as $row) {
        $parents[(int) $row['ID']] = $row['ParentCategoryID'] === null ? 0 : (int) $row['ParentCategoryID'];
    }
    return $parents;
}

function fp_policy_amount_label($row)
{
    $min = fp_decimal_to_paise($row['MinAmount']);
    if ($row['MaxAmount'] === null) {
        return $min > 0 ? 'INR ' . number_format($min / 100, 2) . ' and above' : 'Any amount';
    }
    return 'INR ' . number_format($min / 100, 2) . ' to ' . number_format(fp_decimal_to_paise($row['MaxAmount']) / 100, 2);
}

function fp_policy_snapshot($policy, $stages)
{
    $snapshot = array();
    foreach (array('PolicyCode', 'PolicyName', 'Description', 'CompanyID', 'StateID', 'OfficeID', 'RequestType', 'CategoryID',
                   'MinAmount', 'MaxAmount', 'Priority', 'EffectiveFrom', 'EffectiveTo', 'Status') as $field) {
        $snapshot[$field] = $policy[$field];
    }
    $snapshot['Stages'] = array();
    foreach ($stages as $stage) {
        $snapshot['Stages'][] = array(
            'StageNo' => $stage['StageNo'], 'StageName' => $stage['StageName'], 'ApproverType' => $stage['ApproverType'],
            'ApproverRole' => $stage['ApproverRole'], 'ApproverEmployeeID' => $stage['ApproverEmployeeID'], 'IsMandatory' => $stage['IsMandatory'],
            'ApplyFromAmount' => $stage['ApplyFromAmount'], 'EscalateAfterHours' => $stage['EscalateAfterHours'],
            'EscalateToType' => $stage['EscalateToType'], 'EscalateToRole' => $stage['EscalateToRole'], 'EscalateToEmployeeID' => $stage['EscalateToEmployeeID'],
        );
    }
    return $snapshot;
}

function fp_policy_list($conn)
{
    $rows = array_map('fp_policy_normalize', fp_db_rows(
        $conn,
        'SELECT p.*, q.CompanyName, s.StateName, o.OfficeName, o.OfficeCode, c.CategoryName,
                (SELECT COUNT(DISTINCT rs.RequestID) FROM branch_expense_request_stages rs WHERE rs.PolicyID = p.ID) AS UsedCount,
                COALESCE(uu.UserName, cu.UserName) AS LastChangedBy
         FROM branch_expense_approval_policies p
         LEFT JOIN quote_company_details q ON q.ID = p.CompanyID
         LEFT JOIN state s ON s.ID = p.StateID
         LEFT JOIN branch_expense_offices o ON o.ID = p.OfficeID
         LEFT JOIN branch_expense_categories c ON c.ID = p.CategoryID
         LEFT JOIN users uu ON p.UpdatedByUserID > 0 AND uu.UserID = p.UpdatedByUserID
         LEFT JOIN users cu ON p.CreatedByUserID > 0 AND cu.UserID = p.CreatedByUserID
         ORDER BY FIELD(p.Status, \'active\', \'draft\', \'retired\'), p.Priority, p.PolicyCode, p.Version DESC'
    ));
    $stagesByPolicy = array();
    $employeeIds = array();
    foreach (fp_db_rows($conn, 'SELECT PolicyID, StageNo, StageName, ApproverType, ApproverRole, ApproverEmployeeID, IsMandatory, ApplyFromAmount FROM branch_expense_policy_stages ORDER BY PolicyID, StageNo') as $stage) {
        $stagesByPolicy[(int) $stage['PolicyID']][] = $stage;
        $employeeIds[] = (int) $stage['ApproverEmployeeID'];
    }
    $names = fp_policy_employee_names($conn, $employeeIds);
    $latest = array();
    $hasDraft = array();
    foreach ($rows as $row) {
        $latest[$row['PolicyCode']] = max($latest[$row['PolicyCode']] ?? 0, $row['Version']);
        if ($row['Status'] === 'draft') {
            $hasDraft[$row['PolicyCode']] = true;
        }
    }

    $types = fp_request_types();
    $statuses = fp_policy_statuses();
    $today = date('Y-m-d');
    $list = array();
    foreach ($rows as $row) {
        $scope = array();
        $scope[] = $row['CompanyID'] > 0 ? (string) $row['CompanyName'] : 'Any legal entity';
        if ($row['OfficeID'] > 0) {
            $scope[] = 'Branch ' . $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')';
        } elseif ($row['StateID'] > 0) {
            $scope[] = 'State ' . $row['StateName'];
        }
        $stageText = array();
        foreach ($stagesByPolicy[$row['ID']] ?? array() as $stage) {
            $stageText[] = $stage['StageNo'] . '. ' . $stage['StageName'] . ': '
                . fp_policy_approver_label($stage['ApproverType'], $stage['ApproverRole'], $stage['ApproverEmployeeID'], $names)
                . ((int) $stage['IsMandatory'] === 1 ? '' : ' (optional)')
                . ($stage['ApplyFromAmount'] !== null ? ' (from INR ' . number_format((float) $stage['ApplyFromAmount'], 2) . ')' : '');
        }
        $effective = $row['Status'] === 'active' && $row['EffectiveFrom'] <= $today && ($row['EffectiveTo'] === null || $row['EffectiveTo'] >= $today);
        $list[] = array(
            'ID'            => $row['ID'],
            'RowVersion'    => $row['RowVersion'],
            'PolicyCode'    => $row['PolicyCode'],
            'Version'       => $row['Version'],
            'PolicyName'    => $row['PolicyName'],
            'Description'   => $row['Description'],
            'ScopeLabel'    => implode(', ', $scope),
            'TypeLabel'     => $row['RequestType'] === '' ? 'Any type' : ($types[$row['RequestType']] ?? $row['RequestType']),
            'CategoryLabel' => $row['CategoryID'] === null ? 'Any category' : (string) $row['CategoryName'],
            'AmountLabel'   => fp_policy_amount_label($row),
            'Priority'      => $row['Priority'],
            'EffectiveFrom' => $row['EffectiveFrom'],
            'EffectiveTo'   => $row['EffectiveTo'],
            'Status'        => $row['Status'],
            'StatusLabel'   => $statuses[$row['Status']] ?? $row['Status'],
            'InEffect'      => $effective,
            'Stages'        => $stageText,
            'UsedCount'     => (int) $row['UsedCount'],
            'IsLatest'      => $row['Version'] === $latest[$row['PolicyCode']],
            'CodeHasDraft'  => !empty($hasDraft[$row['PolicyCode']]),
            'LastChangedAt' => (string) ($row['UpdatedAt'] ?: $row['CreatedAt']),
            'LastChangedBy' => (string) ($row['LastChangedBy'] ?: 'System'),
        );
    }
    return $list;
}

function fp_policy_lookups($conn)
{
    $roles = array();
    foreach (fp_policy_approver_roles() as $key => $label) {
        $roles[] = array('key' => $key, 'label' => $label);
    }
    $types = array();
    foreach (fp_request_types() as $key => $label) {
        $types[] = array('key' => $key, 'label' => $label);
    }
    return array(
        'companies'  => fp_db_rows($conn, 'SELECT ID, CompanyName FROM quote_company_details WHERE IsActive = 1 ORDER BY CompanyName'),
        'states'     => fp_db_rows($conn, 'SELECT ID, StateName FROM state WHERE IsActive = 1 ORDER BY StateName'),
        'offices'    => fp_db_rows($conn, 'SELECT ID, OfficeCode, OfficeName, CompanyID, StateID FROM branch_expense_offices WHERE IsActive = 1 ORDER BY OfficeName'),
        'categories' => fp_db_rows($conn, 'SELECT ID, CategoryCode, CategoryName, CompanyID, ParentCategoryID FROM branch_expense_categories WHERE IsActive = 1 ORDER BY SortOrder, CategoryName'),
        'roles'      => $roles,
        'types'      => $types,
    );
}

/**
 * Policy and stages for the editor, with employee names for named approvers.
 */
function fp_policy_detail($conn, $id)
{
    $policy = fp_policy_get($conn, $id);
    if (!$policy) {
        return fp_result(false, 404, 'This policy no longer exists. Reload the page.');
    }
    $stages = fp_policy_stages($conn, $id);
    $names = fp_policy_employee_names($conn, array_merge(array_column($stages, 'ApproverEmployeeID'), array_column($stages, 'EscalateToEmployeeID')));
    foreach ($stages as &$stage) {
        $stage['ApproverEmployeeName'] = $names[$stage['ApproverEmployeeID']] ?? '';
        $stage['EscalateToEmployeeName'] = $names[$stage['EscalateToEmployeeID']] ?? '';
    }
    unset($stage);
    return fp_result(true, 200, 'OK', array('data' => array('policy' => $policy, 'stages' => $stages)));
}

/* ------------------------------------------------------------------
   Validation
------------------------------------------------------------------ */

function fp_policy_input($post)
{
    $stages = array();
    foreach (array_values(isset($post['stages']) && is_array($post['stages']) ? $post['stages'] : array()) as $stage) {
        if (!is_array($stage)) {
            continue;
        }
        $stages[] = array(
            'StageName'            => preg_replace('/\s+/u', ' ', fp_input_string($stage, 'StageName')),
            'ApproverType'         => fp_input_string($stage, 'ApproverType'),
            'ApproverRole'         => fp_input_string($stage, 'ApproverRole'),
            'ApproverEmployeeID'   => fp_input_int($stage, 'ApproverEmployeeID'),
            'IsMandatory'          => fp_input_string($stage, 'IsMandatory') === '1' ? 1 : 0,
            'ApplyFromAmount'      => fp_input_string($stage, 'ApplyFromAmount'),
            'EscalateAfterHours'   => fp_input_string($stage, 'EscalateAfterHours'),
            'EscalateToType'       => fp_input_string($stage, 'EscalateToType'),
            'EscalateToRole'       => fp_input_string($stage, 'EscalateToRole'),
            'EscalateToEmployeeID' => fp_input_int($stage, 'EscalateToEmployeeID'),
        );
    }
    return array(
        'ID'            => fp_input_int($post, 'ID'),
        'RowVersion'    => fp_input_int($post, 'RowVersion'),
        'PolicyCode'    => strtoupper(fp_input_string($post, 'PolicyCode')),
        'PolicyName'    => preg_replace('/\s+/u', ' ', fp_input_string($post, 'PolicyName')),
        'Description'   => fp_input_string($post, 'Description'),
        'CompanyID'     => fp_input_int($post, 'CompanyID'),
        'StateID'       => fp_input_int($post, 'StateID'),
        'OfficeID'      => fp_input_int($post, 'OfficeID'),
        'RequestType'   => fp_input_string($post, 'RequestType'),
        'CategoryID'    => fp_input_int($post, 'CategoryID'),
        'MinAmount'     => fp_input_string($post, 'MinAmount'),
        'MaxAmount'     => fp_input_string($post, 'MaxAmount'),
        'Priority'      => fp_input_string($post, 'Priority'),
        'EffectiveFrom' => fp_input_string($post, 'EffectiveFrom'),
        'EffectiveTo'   => fp_input_string($post, 'EffectiveTo'),
        'stages'        => $stages,
    );
}

/**
 * Turns a stored policy and stages back into input form, so publishing re-runs the same validation.
 */
function fp_policy_as_input($policy, $stages)
{
    $in = $policy;
    $in['CategoryID'] = (int) $policy['CategoryID'];
    $in['MaxAmount'] = $policy['MaxAmount'] === null ? '' : (string) $policy['MaxAmount'];
    $in['MinAmount'] = (string) $policy['MinAmount'];
    $in['Priority'] = (string) $policy['Priority'];
    $in['EffectiveTo'] = (string) $policy['EffectiveTo'];
    $in['stages'] = array();
    foreach ($stages as $stage) {
        $stage['ApplyFromAmount'] = $stage['ApplyFromAmount'] === null ? '' : (string) $stage['ApplyFromAmount'];
        $stage['EscalateAfterHours'] = $stage['EscalateAfterHours'] === null ? '' : (string) $stage['EscalateAfterHours'];
        $in['stages'][] = $stage;
    }
    return $in;
}

function fp_policy_validate_approver($conn, $type, $role, $employeeId, $prefix, $label, &$errors)
{
    $roles = fp_policy_approver_roles();
    if ($type === 'role') {
        if (!isset($roles[$role])) {
            $errors[$prefix . 'Role'] = 'Choose a role that can approve.';
            return null;
        }
        return 'role:' . $role;
    }
    if ($type === 'employee') {
        if ($employeeId <= 0) {
            $errors[$prefix . 'EmployeeID'] = 'Choose the employee.';
            return null;
        }
        $error = fp_policy_employee_approver_error($conn, $employeeId);
        if ($error !== '') {
            $errors[$prefix . 'EmployeeID'] = 'This employee ' . $error . '.';
            return null;
        }
        return 'employee:' . $employeeId;
    }
    $errors[$prefix . 'Type'] = 'Choose who ' . $label . '.';
    return null;
}

/**
 * Returns array(errors, clean). Field errors use the form names; stage errors are keyed stages.N.Field.
 */
function fp_policy_validate($conn, $in)
{
    $errors = array();
    $clean = array();

    if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,29}$/', $in['PolicyCode'])) {
        $errors['PolicyCode'] = 'Use 2-30 characters: letters, numbers, hyphen or underscore.';
    }
    $nameLength = mb_strlen($in['PolicyName']);
    if ($nameLength < 2 || $nameLength > 150) {
        $errors['PolicyName'] = 'Name must be 2-150 characters.';
    }
    if (mb_strlen($in['Description']) > 500) {
        $errors['Description'] = 'At most 500 characters.';
    }

    $companyId = $in['CompanyID'];
    $stateId = $in['StateID'];
    $officeId = $in['OfficeID'];
    if ($officeId > 0) {
        $office = fp_db_rows($conn, 'SELECT CompanyID, StateID, IsActive FROM branch_expense_offices WHERE ID = ?', 'i', array($officeId));
        if (!$office || (int) $office[0]['IsActive'] !== 1) {
            $errors['OfficeID'] = 'Choose an active branch office.';
        } elseif ($companyId > 0 && (int) $office[0]['CompanyID'] !== $companyId) {
            $errors['OfficeID'] = 'This office belongs to a different legal entity.';
        } elseif ($stateId > 0 && (int) $office[0]['StateID'] !== $stateId) {
            $errors['OfficeID'] = 'This office is in a different state.';
        } else {
            $companyId = (int) $office[0]['CompanyID'];
            $stateId = (int) $office[0]['StateID'];
        }
    }
    if ($companyId > 0 && !isset($errors['OfficeID']) && !fp_db_rows($conn, 'SELECT ID FROM quote_company_details WHERE ID = ? AND IsActive = 1', 'i', array($companyId))) {
        $errors['CompanyID'] = 'Choose an active legal entity.';
    }
    if ($stateId > 0 && !isset($errors['OfficeID']) && !fp_db_rows($conn, 'SELECT ID FROM state WHERE ID = ? AND IsActive = 1', 'i', array($stateId))) {
        $errors['StateID'] = 'Choose an active state.';
    }
    if ($in['RequestType'] !== '' && !isset(fp_request_types()[$in['RequestType']])) {
        $errors['RequestType'] = 'Choose an expense type.';
    }
    $categoryId = $in['CategoryID'] > 0 ? $in['CategoryID'] : null;
    if ($categoryId !== null) {
        $category = fp_db_rows($conn, 'SELECT CompanyID, IsActive FROM branch_expense_categories WHERE ID = ?', 'i', array($categoryId));
        if (!$category || (int) $category[0]['IsActive'] !== 1) {
            $errors['CategoryID'] = 'Choose an active category.';
        } elseif ($companyId > 0 && (int) $category[0]['CompanyID'] !== 0 && (int) $category[0]['CompanyID'] !== $companyId) {
            $errors['CategoryID'] = 'This category is not available for the selected legal entity.';
        }
    }

    $min = $in['MinAmount'] === '' ? 0 : fp_parse_paise($in['MinAmount']);
    if ($min === false) {
        $errors['MinAmount'] = 'Enter an amount with up to 2 decimals.';
        $min = 0;
    }
    $max = $in['MaxAmount'] === '' ? null : fp_parse_paise($in['MaxAmount']);
    if ($max === false || ($max !== null && $max < $min)) {
        $errors['MaxAmount'] = 'Enter an amount not below the minimum, or leave blank for no upper limit.';
        $max = null;
    }
    if (!preg_match('/^\d{1,3}$/', $in['Priority']) || (int) $in['Priority'] < 1) {
        $errors['Priority'] = 'Enter a whole number from 1 to 999 (lower wins).';
    }
    $from = fp_parse_date($in['EffectiveFrom']);
    $to = fp_parse_date($in['EffectiveTo']);
    if (!$from) {
        $errors['EffectiveFrom'] = 'Enter the date the policy takes effect.';
    }
    if ($to === false || ($to && $from && $to < $from)) {
        $errors['EffectiveTo'] = 'Enter a date on or after the start, or leave blank for no end date.';
    }

    $stages = array();
    $count = count($in['stages']);
    if ($count === 0) {
        $errors['stages'] = 'Add at least one approval stage.';
    } elseif ($count > FP_POLICY_MAX_STAGES) {
        $errors['stages'] = 'A policy can have at most ' . FP_POLICY_MAX_STAGES . ' stages.';
    }
    $approverKeys = array();
    $hasBaseStage = false;
    foreach ($in['stages'] as $index => $stage) {
        $prefix = 'stages.' . $index . '.';
        $length = mb_strlen($stage['StageName']);
        if ($length < 2 || $length > 100) {
            $errors[$prefix . 'StageName'] = 'Enter a stage name of 2-100 characters.';
        }
        $key = fp_policy_validate_approver($conn, $stage['ApproverType'], $stage['ApproverRole'], $stage['ApproverEmployeeID'], $prefix . 'Approver', 'approves this stage', $errors);
        if ($key !== null) {
            if (isset($approverKeys[$key])) {
                $errors[$prefix . 'ApproverType'] = 'The same approver is already used in stage ' . $approverKeys[$key] . '.';
            }
            $approverKeys[$key] = $index + 1;
        }
        $apply = $stage['ApplyFromAmount'] === '' ? null : fp_parse_paise($stage['ApplyFromAmount']);
        if ($apply === false) {
            $errors[$prefix . 'ApplyFromAmount'] = 'Enter an amount with up to 2 decimals, or leave blank.';
            $apply = null;
        }
        if ($stage['IsMandatory'] && $apply === null) {
            $hasBaseStage = true;
        }

        $hours = null;
        $escType = '';
        $escRole = '';
        $escEmployee = 0;
        if ($stage['EscalateAfterHours'] !== '') {
            if (!preg_match('/^\d{1,3}$/', $stage['EscalateAfterHours']) || (int) $stage['EscalateAfterHours'] < 1 || (int) $stage['EscalateAfterHours'] > 720) {
                $errors[$prefix . 'EscalateAfterHours'] = 'Enter 1-720 hours, or leave blank for no escalation.';
            } else {
                $hours = (int) $stage['EscalateAfterHours'];
                $escKey = fp_policy_validate_approver($conn, $stage['EscalateToType'], $stage['EscalateToRole'], $stage['EscalateToEmployeeID'], $prefix . 'EscalateTo', 'receives the escalation', $errors);
                if ($escKey !== null && $escKey === $key) {
                    $errors[$prefix . 'EscalateToType'] = 'Escalate to someone other than the stage approver.';
                }
                $escType = $stage['EscalateToType'];
                $escRole = $escType === 'role' ? $stage['EscalateToRole'] : '';
                $escEmployee = $escType === 'employee' ? $stage['EscalateToEmployeeID'] : 0;
            }
        } elseif ($stage['EscalateToType'] !== '') {
            $errors[$prefix . 'EscalateAfterHours'] = 'Enter after how many hours to escalate.';
        }

        $stages[] = array(
            'StageNo'              => $index + 1,
            'StageName'            => $stage['StageName'],
            'ApproverType'         => $stage['ApproverType'],
            'ApproverRole'         => $stage['ApproverType'] === 'role' ? $stage['ApproverRole'] : '',
            'ApproverEmployeeID'   => $stage['ApproverType'] === 'employee' ? $stage['ApproverEmployeeID'] : 0,
            'IsMandatory'          => $stage['IsMandatory'],
            'ApplyFromAmount'      => $apply === null ? null : fp_paise_to_decimal($apply),
            'EscalateAfterHours'   => $hours,
            'EscalateToType'       => $escType,
            'EscalateToRole'       => $escRole,
            'EscalateToEmployeeID' => $escEmployee,
        );
    }
    if ($count > 0 && !$hasBaseStage && !isset($errors['stages'])) {
        $errors['stages'] = 'At least one mandatory stage must apply to every amount (leave its "applies from" amount blank).';
    }

    $clean = array(
        'PolicyCode' => $in['PolicyCode'], 'PolicyName' => $in['PolicyName'], 'Description' => $in['Description'],
        'CompanyID' => $companyId, 'StateID' => $stateId, 'OfficeID' => $officeId, 'RequestType' => $in['RequestType'],
        'CategoryID' => $categoryId, 'MinAmount' => fp_paise_to_decimal($min), 'MaxAmount' => $max === null ? null : fp_paise_to_decimal($max),
        'Priority' => (int) $in['Priority'], 'EffectiveFrom' => $from ?: null, 'EffectiveTo' => $to ?: null, 'stages' => $stages,
    );
    return array($errors, $clean);
}

/* ------------------------------------------------------------------
   Overlap (ambiguity) checks
------------------------------------------------------------------ */

function fp_policy_overlaps($a, $b, $parents)
{
    foreach (array('CompanyID', 'StateID', 'OfficeID') as $field) {
        if ($a[$field] !== 0 && $b[$field] !== 0 && $a[$field] !== $b[$field]) {
            return false;
        }
    }
    if ($a['RequestType'] !== '' && $b['RequestType'] !== '' && $a['RequestType'] !== $b['RequestType']) {
        return false;
    }
    if ($a['CategoryID'] !== null && $b['CategoryID'] !== null && $a['CategoryID'] !== $b['CategoryID']
        && ($parents[$a['CategoryID']] ?? 0) !== $b['CategoryID'] && ($parents[$b['CategoryID']] ?? 0) !== $a['CategoryID']) {
        return false;
    }
    $aMax = $a['MaxAmount'] === null ? PHP_INT_MAX : fp_decimal_to_paise($a['MaxAmount']);
    $bMax = $b['MaxAmount'] === null ? PHP_INT_MAX : fp_decimal_to_paise($b['MaxAmount']);
    if (fp_decimal_to_paise($a['MinAmount']) > $bMax || fp_decimal_to_paise($b['MinAmount']) > $aMax) {
        return false;
    }
    $aTo = $a['EffectiveTo'] ?: '9999-12-31';
    $bTo = $b['EffectiveTo'] ?: '9999-12-31';
    return $a['EffectiveFrom'] <= $bTo && $b['EffectiveFrom'] <= $aTo;
}

/**
 * Active policies of other codes that could match the same request with the same priority.
 */
function fp_policy_conflicts($conn, $policy)
{
    $parents = fp_policy_category_parents($conn);
    $conflicts = array();
    $others = array_map('fp_policy_normalize', fp_db_rows(
        $conn,
        'SELECT * FROM branch_expense_approval_policies WHERE Status = \'active\' AND PolicyCode <> ? AND Priority = ?',
        'si',
        array($policy['PolicyCode'], $policy['Priority'])
    ));
    foreach ($others as $other) {
        if (fp_policy_overlaps($policy, $other, $parents)) {
            $conflicts[] = $other['PolicyCode'] . ' v' . $other['Version'] . ' (' . $other['PolicyName'] . ')';
        }
    }
    return $conflicts;
}

/* ------------------------------------------------------------------
   Writes
------------------------------------------------------------------ */

function fp_policy_locked_transaction($conn, $work)
{
    $got = fp_db_rows($conn, "SELECT GET_LOCK(CONCAT(DATABASE(), '.fp_policy_write'), 10) AS L");
    if ((int) ($got[0]['L'] ?? 0) !== 1) {
        return fp_result(false, 409, 'Another policy change is in progress. Please try again.');
    }
    try {
        return fp_transaction($conn, $work);
    } finally {
        fp_db_rows($conn, "SELECT RELEASE_LOCK(CONCAT(DATABASE(), '.fp_policy_write')) AS L");
    }
}

function fp_policy_write_stages($conn, $policyId, $stages)
{
    fp_policy_exec($conn, 'DELETE FROM branch_expense_policy_stages WHERE PolicyID = ?', 'i', array($policyId));
    foreach ($stages as $stage) {
        fp_policy_exec(
            $conn,
            'INSERT INTO branch_expense_policy_stages
                (PolicyID, StageNo, StageName, ApproverType, ApproverRole, ApproverEmployeeID, IsMandatory, ApplyFromAmount,
                 EscalateAfterHours, EscalateToType, EscalateToRole, EscalateToEmployeeID)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'iisssiisissi',
            array($policyId, $stage['StageNo'], $stage['StageName'], $stage['ApproverType'], $stage['ApproverRole'], $stage['ApproverEmployeeID'],
                  $stage['IsMandatory'], $stage['ApplyFromAmount'], $stage['EscalateAfterHours'], $stage['EscalateToType'], $stage['EscalateToRole'], $stage['EscalateToEmployeeID'])
        );
    }
}

/**
 * Creates a new policy (draft version 1) or updates a draft. Active and retired versions cannot be edited.
 */
function fp_policy_save($conn, $actor, $post)
{
    $in = fp_policy_input($post);
    return fp_policy_locked_transaction($conn, function () use ($conn, $actor, $in) {
        $existing = null;
        if ($in['ID'] > 0) {
            $existing = fp_policy_get($conn, $in['ID'], 'update');
            if (!$existing) {
                return fp_result(false, 404, 'This policy no longer exists. Reload the page.');
            }
            if ($existing['RowVersion'] !== $in['RowVersion']) {
                return fp_result(false, 409, 'Someone else changed this policy. Reload the page and try again.');
            }
            if ($existing['Status'] !== 'draft') {
                return fp_result(false, 422, 'Only draft versions can be edited. Create a new version instead.');
            }
            $in['PolicyCode'] = $existing['PolicyCode'];
        }
        list($errors, $clean) = fp_policy_validate($conn, $in);
        if (!$existing && !isset($errors['PolicyCode'])
            && fp_db_rows($conn, 'SELECT ID FROM branch_expense_approval_policies WHERE PolicyCode = ?', 's', array($clean['PolicyCode']))) {
            $errors['PolicyCode'] = 'This code is already used. Open that policy and create a new version instead.';
        }
        if ($errors) {
            return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
        }

        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $now = date('Y-m-d H:i:s');
        if (!$existing) {
            $version = 1;
            fp_policy_exec(
                $conn,
                'INSERT INTO branch_expense_approval_policies
                    (PolicyCode, Version, PolicyName, Description, CompanyID, StateID, OfficeID, RequestType, CategoryID, MinAmount, MaxAmount,
                     Priority, EffectiveFrom, EffectiveTo, Status, RowVersion, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'draft\', 1, ?, ?, ?)',
                'sissiiisississiis',
                array($clean['PolicyCode'], $version, $clean['PolicyName'], $clean['Description'], $clean['CompanyID'], $clean['StateID'], $clean['OfficeID'],
                      $clean['RequestType'], $clean['CategoryID'], $clean['MinAmount'], $clean['MaxAmount'], $clean['Priority'], $clean['EffectiveFrom'],
                      $clean['EffectiveTo'], $userId, $employeeId, $now)
            );
            $id = (int) $conn->insert_id;
            fp_policy_write_stages($conn, $id, $clean['stages']);
            $saved = fp_policy_get($conn, $id);
            fp_audit_log($conn, $actor, FP_POLICY_ENTITY, $id, 'create', null, fp_policy_snapshot($saved, fp_policy_stages($conn, $id)));
            return fp_result(true, 200, 'Draft policy ' . $clean['PolicyCode'] . ' saved. Publish it to start using it.', array('data' => array('ID' => $id)));
        }

        $id = $existing['ID'];
        $old = fp_policy_snapshot($existing, fp_policy_stages($conn, $id));
        fp_policy_exec(
            $conn,
            'UPDATE branch_expense_approval_policies
             SET PolicyName = ?, Description = ?, CompanyID = ?, StateID = ?, OfficeID = ?, RequestType = ?, CategoryID = ?, MinAmount = ?, MaxAmount = ?,
                 Priority = ?, EffectiveFrom = ?, EffectiveTo = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND Status = \'draft\'',
            'ssiiisississiisi',
            array($clean['PolicyName'], $clean['Description'], $clean['CompanyID'], $clean['StateID'], $clean['OfficeID'], $clean['RequestType'],
                  $clean['CategoryID'], $clean['MinAmount'], $clean['MaxAmount'], $clean['Priority'], $clean['EffectiveFrom'], $clean['EffectiveTo'],
                  $userId, $employeeId, $now, $id)
        );
        fp_policy_write_stages($conn, $id, $clean['stages']);
        $new = fp_policy_snapshot(fp_policy_get($conn, $id), fp_policy_stages($conn, $id));
        if (fp_audit_changed_fields($old, $new)) {
            fp_audit_log($conn, $actor, FP_POLICY_ENTITY, $id, 'update', $old, $new);
        }
        return fp_result(true, 200, 'Draft saved.', array('data' => array('ID' => $id)));
    });
}

function fp_policy_publish($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    return fp_policy_locked_transaction($conn, function () use ($conn, $actor, $id, $rowVersion) {
        $policy = fp_policy_get($conn, $id, 'update');
        if (!$policy) {
            return fp_result(false, 404, 'This policy no longer exists. Reload the page.');
        }
        if ($policy['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'Someone else changed this policy. Reload the page and try again.');
        }
        if ($policy['Status'] !== 'draft') {
            return fp_result(false, 422, 'Only a draft can be published.');
        }
        $stages = fp_policy_stages($conn, $id);
        list($errors) = fp_policy_validate($conn, fp_policy_as_input($policy, $stages));
        if ($errors) {
            return fp_result(false, 422, 'The draft has problems; open it, correct them and save before publishing: ' . implode(' ', array_slice(array_values($errors), 0, 3)));
        }
        $today = date('Y-m-d');
        if ($policy['EffectiveFrom'] < $today) {
            return fp_result(false, 422, 'The effective-from date is in the past. Set it to today or later, save, then publish.');
        }

        $previous = array_map('fp_policy_normalize', fp_db_rows(
            $conn,
            'SELECT * FROM branch_expense_approval_policies WHERE PolicyCode = ? AND Status = \'active\' AND ID <> ? FOR UPDATE',
            'si',
            array($policy['PolicyCode'], $id)
        ));
        foreach ($previous as $old) {
            if ($old['EffectiveFrom'] >= $policy['EffectiveFrom']) {
                return fp_result(false, 422, 'Version ' . $old['Version'] . ' starts on ' . $old['EffectiveFrom'] . '. The new version must start after it.');
            }
        }
        $conflicts = fp_policy_conflicts($conn, $policy);
        if ($conflicts) {
            return fp_result(false, 422, 'This policy overlaps ' . implode(', ', $conflicts) . ' with the same priority, so a request could match both. '
                . 'Narrow the criteria or give one of them a different priority.');
        }

        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $now = date('Y-m-d H:i:s');
        $cutTo = date('Y-m-d', strtotime($policy['EffectiveFrom'] . ' -1 day'));
        foreach ($previous as $old) {
            if ($old['EffectiveTo'] === null || $old['EffectiveTo'] > $cutTo) {
                fp_policy_exec(
                    $conn,
                    'UPDATE branch_expense_approval_policies SET EffectiveTo = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?',
                    'siisi',
                    array($cutTo, $userId, $employeeId, $now, $old['ID'])
                );
                fp_audit_log($conn, $actor, FP_POLICY_ENTITY, $old['ID'], 'superseded', array('EffectiveTo' => $old['EffectiveTo']), array('EffectiveTo' => $cutTo),
                    'Replaced by version ' . $policy['Version'] . ' from ' . $policy['EffectiveFrom']);
            }
        }
        fp_policy_exec(
            $conn,
            'UPDATE branch_expense_approval_policies
             SET Status = \'active\', PublishedAt = ?, PublishedByUserID = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND Status = \'draft\'',
            'siiisi',
            array($now, $userId, $userId, $employeeId, $now, $id)
        );
        fp_audit_log($conn, $actor, FP_POLICY_ENTITY, $id, 'publish', array('Status' => 'draft'), array('Status' => 'active'));
        return fp_result(true, 200, $policy['PolicyCode'] . ' version ' . $policy['Version'] . ' published. It applies to requests submitted from ' . date('d M Y', strtotime($policy['EffectiveFrom'])) . '.');
    });
}

function fp_policy_new_version($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    return fp_policy_locked_transaction($conn, function () use ($conn, $actor, $id) {
        $source = fp_policy_get($conn, $id, 'update');
        if (!$source) {
            return fp_result(false, 404, 'This policy no longer exists. Reload the page.');
        }
        if ($source['Status'] === 'draft') {
            return fp_result(false, 422, 'This version is still a draft; edit it directly.');
        }
        if (fp_db_rows($conn, 'SELECT ID FROM branch_expense_approval_policies WHERE PolicyCode = ? AND Status = \'draft\'', 's', array($source['PolicyCode']))) {
            return fp_result(false, 422, 'A draft version of ' . $source['PolicyCode'] . ' already exists. Edit or delete it first.');
        }
        $max = fp_db_rows($conn, 'SELECT MAX(Version) AS V FROM branch_expense_approval_policies WHERE PolicyCode = ?', 's', array($source['PolicyCode']));
        $version = (int) $max[0]['V'] + 1;
        $today = date('Y-m-d');
        $from = max($today, $source['EffectiveFrom']);
        $to = $source['EffectiveTo'] !== null && $source['EffectiveTo'] >= $from ? $source['EffectiveTo'] : null;
        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            'INSERT INTO branch_expense_approval_policies
                (PolicyCode, Version, PolicyName, Description, CompanyID, StateID, OfficeID, RequestType, CategoryID, MinAmount, MaxAmount,
                 Priority, EffectiveFrom, EffectiveTo, Status, RowVersion, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'draft\', 1, ?, ?, ?)',
            'sissiiisississiis',
            array($source['PolicyCode'], $version, $source['PolicyName'], $source['Description'], $source['CompanyID'], $source['StateID'], $source['OfficeID'],
                  $source['RequestType'], $source['CategoryID'], $source['MinAmount'], $source['MaxAmount'], $source['Priority'], $from, $to,
                  $userId, $employeeId, $now)
        );
        $newId = (int) $conn->insert_id;
        fp_policy_write_stages($conn, $newId, fp_policy_stages($conn, $source['ID']));
        fp_audit_log($conn, $actor, FP_POLICY_ENTITY, $newId, 'create', null, fp_policy_snapshot(fp_policy_get($conn, $newId), fp_policy_stages($conn, $newId)),
            'Copied from version ' . $source['Version']);
        return fp_result(true, 200, 'Draft version ' . $version . ' of ' . $source['PolicyCode'] . ' created. Edit it, then publish.', array('data' => array('ID' => $newId)));
    });
}

function fp_policy_retire($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $reason = preg_replace('/\s+/u', ' ', fp_input_string($post, 'Reason'));
    if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'Please give a reason (5-500 characters).', array('errors' => array('Reason' => 'Required.')));
    }
    return fp_policy_locked_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $reason) {
        $policy = fp_policy_get($conn, $id, 'update');
        if (!$policy) {
            return fp_result(false, 404, 'This policy no longer exists. Reload the page.');
        }
        if ($policy['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'Someone else changed this policy. Reload the page and try again.');
        }
        if ($policy['Status'] !== 'active') {
            return fp_result(false, 422, 'Only an active policy can be retired.');
        }
        $userId = (int) $actor['user_id'];
        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            'UPDATE branch_expense_approval_policies
             SET Status = \'retired\', RetiredAt = ?, RetiredByUserID = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND Status = \'active\'',
            'siiisi',
            array($now, $userId, $userId, (int) $actor['employee_id'], $now, $id)
        );
        fp_audit_log($conn, $actor, FP_POLICY_ENTITY, $id, 'retire', array('Status' => 'active'), array('Status' => 'retired'), $reason);
        return fp_result(true, 200, $policy['PolicyCode'] . ' version ' . $policy['Version'] . ' retired. Requests already submitted keep their approval route.');
    });
}

function fp_policy_delete_draft($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    return fp_policy_locked_transaction($conn, function () use ($conn, $actor, $id, $rowVersion) {
        $policy = fp_policy_get($conn, $id, 'update');
        if (!$policy) {
            return fp_result(false, 404, 'This policy no longer exists. Reload the page.');
        }
        if ($policy['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'Someone else changed this policy. Reload the page and try again.');
        }
        if ($policy['Status'] !== 'draft') {
            return fp_result(false, 422, 'Only drafts can be deleted. Retire an active policy instead.');
        }
        $snapshot = fp_policy_snapshot($policy, fp_policy_stages($conn, $id));
        fp_policy_exec($conn, 'DELETE FROM branch_expense_policy_stages WHERE PolicyID = ?', 'i', array($id));
        fp_policy_exec($conn, 'DELETE FROM branch_expense_approval_policies WHERE ID = ? AND Status = \'draft\'', 'i', array($id));
        fp_audit_log($conn, $actor, FP_POLICY_ENTITY, $id, 'delete', $snapshot, null);
        return fp_result(true, 200, 'Draft deleted.');
    });
}

function fp_policy_history($conn, $id)
{
    $policy = fp_policy_get($conn, $id);
    if (!$policy) {
        return fp_result(false, 404, 'This policy no longer exists.');
    }
    return fp_result(true, 200, 'OK', array('data' => array(
        'policy'  => array('ID' => $policy['ID'], 'Label' => $policy['PolicyCode'] . ' v' . $policy['Version'] . ': ' . $policy['PolicyName']),
        'history' => fp_audit_history($conn, FP_POLICY_ENTITY, $id),
    )));
}

/* ------------------------------------------------------------------
   Matching and the approval route
------------------------------------------------------------------ */

/**
 * $ctx: CompanyID, StateID, OfficeID, RequestType, TotalPaise, CategoryIDs (one per line), Date.
 * Returns array('policy' => row|null, 'candidates' => matching rows by priority, 'error' => '').
 */
function fp_policy_select($conn, $ctx)
{
    $rows = array_map('fp_policy_normalize', fp_db_rows(
        $conn,
        'SELECT * FROM branch_expense_approval_policies
         WHERE Status = \'active\' AND EffectiveFrom <= ? AND (EffectiveTo IS NULL OR EffectiveTo >= ?)
           AND CompanyID IN (0, ?) AND StateID IN (0, ?) AND OfficeID IN (0, ?) AND RequestType IN (\'\', ?)
         ORDER BY Priority, PolicyCode',
        'ssiiis',
        array($ctx['Date'], $ctx['Date'], $ctx['CompanyID'], $ctx['StateID'], $ctx['OfficeID'], $ctx['RequestType'])
    ));
    $parents = fp_policy_category_parents($conn);
    $candidates = array();
    foreach ($rows as $row) {
        if ($ctx['TotalPaise'] < fp_decimal_to_paise($row['MinAmount'])) {
            continue;
        }
        if ($row['MaxAmount'] !== null && $ctx['TotalPaise'] > fp_decimal_to_paise($row['MaxAmount'])) {
            continue;
        }
        if ($row['CategoryID'] !== null) {
            if (!$ctx['CategoryIDs']) {
                continue;
            }
            foreach ($ctx['CategoryIDs'] as $categoryId) {
                if ($categoryId !== $row['CategoryID'] && ($parents[$categoryId] ?? 0) !== $row['CategoryID']) {
                    continue 2;
                }
            }
        }
        $candidates[] = $row;
    }
    if (!$candidates) {
        return array('policy' => null, 'candidates' => array(), 'error' => 'No approval policy covers this request, so it cannot be submitted yet. Please contact Finance.');
    }
    $tied = array_values(array_filter($candidates, function ($row) use ($candidates) {
        return $row['Priority'] === $candidates[0]['Priority'];
    }));
    if (count($tied) > 1) {
        return array('policy' => null, 'candidates' => $candidates, 'error' => 'Approval policies ' . implode(' and ', array_map(function ($row) {
            return $row['PolicyCode'] . ' v' . $row['Version'];
        }, $tied)) . ' match this request equally, so it cannot be submitted. Please contact Finance.');
    }
    return array('policy' => $candidates[0], 'candidates' => $candidates, 'error' => '');
}

/**
 * Stages for one request: each 'pending' or 'skipped' with a note. Returns array(stages, error).
 * A stage named for the requester goes to its escalation approver, is skipped when optional, or blocks submission.
 */
function fp_policy_stage_plan($conn, $stages, $totalPaise, $requesterEmployeeId)
{
    $plan = array();
    foreach ($stages as $stage) {
        $stage['Status'] = 'pending';
        $stage['Note'] = '';
        $label = 'Stage ' . $stage['StageNo'] . ' (' . $stage['StageName'] . ')';
        if ($stage['ApplyFromAmount'] !== null && $totalPaise < fp_decimal_to_paise($stage['ApplyFromAmount'])) {
            $stage['Status'] = 'skipped';
            $stage['Note'] = 'Applies only from INR ' . number_format((float) $stage['ApplyFromAmount'], 2) . '.';
        } elseif ($stage['ApproverType'] === 'employee' && $requesterEmployeeId > 0 && $stage['ApproverEmployeeID'] === $requesterEmployeeId) {
            $canEscalate = $stage['EscalateToType'] === 'role'
                || ($stage['EscalateToType'] === 'employee' && $stage['EscalateToEmployeeID'] !== $requesterEmployeeId);
            if ($canEscalate) {
                $stage['ApproverType'] = $stage['EscalateToType'];
                $stage['ApproverRole'] = $stage['EscalateToRole'];
                $stage['ApproverEmployeeID'] = $stage['EscalateToEmployeeID'];
                $stage['Note'] = 'Sent to the escalation approver because the requester is the named approver.';
            } elseif (!$stage['IsMandatory']) {
                $stage['Status'] = 'skipped';
                $stage['Note'] = 'Skipped because the requester is the named approver.';
            } else {
                return array(array(), $label . ': the requester is the named approver and no escalation approver is set. Please contact Finance.');
            }
        } elseif ($stage['ApproverType'] === 'employee' && ($error = fp_policy_employee_approver_error($conn, $stage['ApproverEmployeeID'])) !== '') {
            if ($stage['IsMandatory']) {
                return array(array(), $label . ': the named approver ' . $error . '. Please contact Finance.');
            }
            $stage['Status'] = 'skipped';
            $stage['Note'] = 'Skipped because the named approver ' . $error . '.';
        }
        $plan[] = $stage;
    }
    if (!array_filter($plan, function ($stage) { return $stage['Status'] === 'pending'; })) {
        return array(array(), 'No approval stage applies to this request. Please contact Finance.');
    }
    return array($plan, '');
}

/**
 * Policy and stage plan for a request being submitted on $date. Everything comes from the database.
 */
function fp_policy_route($conn, $request, $items, $date)
{
    $categoryIds = array();
    foreach ($items as $item) {
        $categoryIds[] = (int) $item['CategoryID'];
    }
    $selection = fp_policy_select($conn, array(
        'CompanyID'   => (int) $request['CompanyID'],
        'StateID'     => (int) $request['StateID'],
        'OfficeID'    => (int) $request['OfficeID'],
        'RequestType' => (string) $request['RequestType'],
        'TotalPaise'  => fp_decimal_to_paise($request['TotalAmount']),
        'CategoryIDs' => array_values(array_unique($categoryIds)),
        'Date'        => $date,
    ));
    if ($selection['error'] !== '') {
        return array('policy' => null, 'stages' => array(), 'error' => $selection['error']);
    }
    list($plan, $error) = fp_policy_stage_plan($conn, fp_policy_stages($conn, $selection['policy']['ID']), fp_decimal_to_paise($request['TotalAmount']), (int) $request['EmployeeID']);
    return array('policy' => $selection['policy'], 'stages' => $plan, 'error' => $error);
}

function fp_policy_snapshot_route($conn, $requestId, $round, $route, $now)
{
    $activated = false;
    foreach ($route['stages'] as $stage) {
        $activatedAt = null;
        if (!$activated && $stage['Status'] === 'pending') {
            $activatedAt = $now;
            $activated = true;
        }
        fp_policy_exec(
            $conn,
            'INSERT INTO branch_expense_request_stages
                (RequestID, Round, PolicyID, StageNo, StageName, ApproverType, ApproverRole, ApproverEmployeeID, IsMandatory, ApplyFromAmount,
                 EscalateAfterHours, EscalateToType, EscalateToRole, EscalateToEmployeeID, Status, Note, ActivatedAt, CreatedAt)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'iiiisssiisississss',
            array($requestId, $round, $route['policy']['ID'], $stage['StageNo'], $stage['StageName'], $stage['ApproverType'], $stage['ApproverRole'],
                  $stage['ApproverEmployeeID'], $stage['IsMandatory'], $stage['ApplyFromAmount'], $stage['EscalateAfterHours'], $stage['EscalateToType'],
                  $stage['EscalateToRole'], $stage['EscalateToEmployeeID'], $stage['Status'], $stage['Note'], $activatedAt, $now)
        );
    }
}

/**
 * Approval route snapshot of one submission round (default: the latest), with readable approver labels.
 */
function fp_request_route($conn, $request, $round = 0)
{
    $round = $round > 0 ? (int) $round : (int) ($request['SubmissionRound'] ?? 0);
    if ($round <= 0) {
        return array();
    }
    $stages = fp_db_rows(
        $conn,
        'SELECT StageNo, StageName, ApproverType, ApproverRole, ApproverEmployeeID, IsMandatory, EscalateAfterHours, EscalateToType, EscalateToRole, EscalateToEmployeeID,
                Status, Note, ActivatedAt, EscalatedAt, DecidedAt
         FROM branch_expense_request_stages WHERE RequestID = ? AND Round = ? ORDER BY StageNo',
        'ii',
        array((int) $request['ID'], $round)
    );
    $names = fp_policy_employee_names($conn, array_merge(array_column($stages, 'ApproverEmployeeID'), array_column($stages, 'EscalateToEmployeeID')));
    foreach ($stages as &$stage) {
        $stage['ApproverLabel'] = fp_policy_approver_label($stage['ApproverType'], $stage['ApproverRole'], $stage['ApproverEmployeeID'], $names);
        $stage['EscalationLabel'] = $stage['EscalateAfterHours'] !== null
            ? 'After ' . (int) $stage['EscalateAfterHours'] . ' h to ' . fp_policy_approver_label($stage['EscalateToType'], $stage['EscalateToRole'], $stage['EscalateToEmployeeID'], $names)
            : '';
    }
    unset($stage);
    return $stages;
}

/**
 * Policy tester: which policy and stages a request with these attributes would get. No requester is assumed.
 */
function fp_policy_test($conn, $params)
{
    $officeId = fp_input_int($params, 'OfficeID');
    $office = $officeId > 0 ? fp_db_rows($conn, 'SELECT CompanyID, StateID FROM branch_expense_offices WHERE ID = ?', 'i', array($officeId)) : array();
    $type = fp_input_string($params, 'RequestType');
    $amount = fp_parse_paise(fp_input_string($params, 'Amount'));
    $date = fp_parse_date(fp_input_string($params, 'Date')) ?: date('Y-m-d');
    $errors = array();
    if (!$office) {
        $errors['OfficeID'] = 'Choose a branch office.';
    }
    if (!isset(fp_request_types()[$type])) {
        $errors['RequestType'] = 'Choose an expense type.';
    }
    if ($amount === false || $amount <= 0) {
        $errors['Amount'] = 'Enter the request total.';
    }
    $categoryIds = array();
    foreach (explode(',', fp_input_string($params, 'CategoryIDs')) as $value) {
        if (ctype_digit(trim($value))) {
            $categoryIds[] = (int) $value;
        }
    }
    if (!$categoryIds) {
        $errors['CategoryIDs'] = 'Choose at least one category.';
    }
    if ($errors) {
        return fp_result(false, 422, 'Please complete the test request.', array('errors' => $errors));
    }

    $selection = fp_policy_select($conn, array(
        'CompanyID' => (int) $office[0]['CompanyID'], 'StateID' => (int) $office[0]['StateID'], 'OfficeID' => $officeId,
        'RequestType' => $type, 'TotalPaise' => $amount, 'CategoryIDs' => array_values(array_unique($categoryIds)), 'Date' => $date,
    ));
    $data = array('error' => $selection['error'], 'policy' => null, 'stages' => array(), 'candidates' => array());
    foreach ($selection['candidates'] as $candidate) {
        $data['candidates'][] = $candidate['PolicyCode'] . ' v' . $candidate['Version'] . ' (priority ' . $candidate['Priority'] . ')';
    }
    if ($selection['policy']) {
        $policy = $selection['policy'];
        list($plan, $error) = fp_policy_stage_plan($conn, fp_policy_stages($conn, $policy['ID']), $amount, 0);
        $names = fp_policy_employee_names($conn, array_column($plan, 'ApproverEmployeeID'));
        $data['policy'] = $policy['PolicyCode'] . ' v' . $policy['Version'] . ': ' . $policy['PolicyName'];
        $data['error'] = $error;
        foreach ($plan as $stage) {
            $data['stages'][] = array(
                'StageNo' => $stage['StageNo'], 'StageName' => $stage['StageName'], 'Status' => $stage['Status'], 'Note' => $stage['Note'],
                'IsMandatory' => $stage['IsMandatory'],
                'Approver' => fp_policy_approver_label($stage['ApproverType'], $stage['ApproverRole'], $stage['ApproverEmployeeID'], $names),
            );
        }
    }
    return fp_result(true, 200, 'OK', array('data' => $data));
}

/* ------------------------------------------------------------------
   Delegations
------------------------------------------------------------------ */

function fp_delegation_list($conn)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT d.*, a.Name AS DelegatorName, a.EmployeeNumber AS DelegatorNumber, b.Name AS DelegateName, b.EmployeeNumber AS DelegateNumber,
                u.UserName AS CreatedBy, cu.UserName AS CancelledBy
         FROM branch_expense_approval_delegations d
         LEFT JOIN employees a ON a.ID = d.DelegatorEmployeeID
         LEFT JOIN employees b ON b.ID = d.DelegateEmployeeID
         LEFT JOIN users u ON u.UserID = d.CreatedByUserID
         LEFT JOIN users cu ON cu.UserID = d.CancelledByUserID
         ORDER BY d.IsActive DESC, d.FromDate DESC, d.ID DESC'
    );
    $today = date('Y-m-d');
    $list = array();
    foreach ($rows as $row) {
        if ((int) $row['IsActive'] !== 1) {
            $status = 'cancelled';
        } elseif ($row['ToDate'] < $today) {
            $status = 'ended';
        } elseif ($row['FromDate'] > $today) {
            $status = 'scheduled';
        } else {
            $status = 'active';
        }
        $list[] = array(
            'ID'           => (int) $row['ID'],
            'Delegator'    => $row['DelegatorName'] . ($row['DelegatorNumber'] ? ' (' . $row['DelegatorNumber'] . ')' : ''),
            'Delegate'     => $row['DelegateName'] . ($row['DelegateNumber'] ? ' (' . $row['DelegateNumber'] . ')' : ''),
            'FromDate'     => $row['FromDate'],
            'ToDate'       => $row['ToDate'],
            'Reason'       => $row['Reason'],
            'Status'       => $status,
            'CreatedBy'    => (string) $row['CreatedBy'],
            'CreatedAt'    => $row['CreatedAt'],
            'CancelledBy'  => (string) $row['CancelledBy'],
            'CancelReason' => $row['CancelReason'],
            'CanCancel'    => in_array($status, array('active', 'scheduled'), true),
        );
    }
    return $list;
}

function fp_delegation_save($conn, $actor, $post)
{
    $delegator = fp_input_int($post, 'DelegatorEmployeeID');
    $delegate = fp_input_int($post, 'DelegateEmployeeID');
    $from = fp_parse_date(fp_input_string($post, 'FromDate'));
    $to = fp_parse_date(fp_input_string($post, 'ToDate'));
    $reason = preg_replace('/\s+/u', ' ', fp_input_string($post, 'Reason'));
    $today = date('Y-m-d');
    $errors = array();
    if ($delegator <= 0) {
        $errors['DelegatorEmployeeID'] = 'Choose the approver who is away.';
    }
    if ($delegate <= 0) {
        $errors['DelegateEmployeeID'] = 'Choose who approves on their behalf.';
    } elseif ($delegate === $delegator) {
        $errors['DelegateEmployeeID'] = 'Choose a different employee.';
    }
    if (!$from || $from < $today) {
        $errors['FromDate'] = 'Enter a start date from today onwards.';
    }
    if (!$to || ($from && $to < $from)) {
        $errors['ToDate'] = 'Enter an end date on or after the start.';
    } elseif ($from && (new DateTime($from))->diff(new DateTime($to))->days >= FP_DELEGATION_MAX_DAYS) {
        $errors['ToDate'] = 'A delegation can last at most ' . FP_DELEGATION_MAX_DAYS . ' days.';
    }
    if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
        $errors['Reason'] = 'Give a reason of 5-500 characters.';
    }
    if ($errors) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
    }

    return fp_policy_locked_transaction($conn, function () use ($conn, $actor, $delegator, $delegate, $from, $to, $reason) {
        $errors = array();
        $error = fp_policy_employee_approver_error($conn, $delegator);
        if ($error !== '') {
            $errors['DelegatorEmployeeID'] = 'This employee ' . $error . ', so there is nothing to delegate.';
        }
        $vendorFilter = fp_access_matrix()['exclude_vendor_employees'] ? " AND e.Vendor <> '1'" : '';
        if (!fp_db_rows($conn, 'SELECT e.ID FROM employees e INNER JOIN users u ON u.EmployeeID = e.ID AND u.IsActive = 1 WHERE e.ID = ? AND e.IsActive = 1' . $vendorFilter, 'i', array($delegate))) {
            $errors['DelegateEmployeeID'] = 'The delegate must be an active employee with an active login.';
        }
        if ($errors) {
            return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
        }
        $overlap = 'IsActive = 1 AND FromDate <= ? AND ToDate >= ?';
        if (fp_db_rows($conn, 'SELECT ID FROM branch_expense_approval_delegations WHERE DelegatorEmployeeID = ? AND ' . $overlap . ' FOR UPDATE', 'iss', array($delegator, $to, $from))) {
            return fp_result(false, 422, 'This approver already has a delegation in these dates. Cancel it first.');
        }
        if (fp_db_rows($conn, 'SELECT ID FROM branch_expense_approval_delegations WHERE DelegatorEmployeeID = ? AND ' . $overlap, 'iss', array($delegate, $to, $from))) {
            return fp_result(false, 422, 'The delegate has delegated their own approvals in these dates. Delegations cannot be chained.');
        }
        if (fp_db_rows($conn, 'SELECT ID FROM branch_expense_approval_delegations WHERE DelegateEmployeeID = ? AND ' . $overlap, 'iss', array($delegator, $to, $from))) {
            return fp_result(false, 422, 'This approver is acting as a delegate for someone else in these dates. Delegations cannot be chained.');
        }

        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            'INSERT INTO branch_expense_approval_delegations (DelegatorEmployeeID, DelegateEmployeeID, FromDate, ToDate, Reason, IsActive, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)',
            'iisssiis',
            array($delegator, $delegate, $from, $to, $reason, $userId, $employeeId, $now)
        );
        $id = (int) $conn->insert_id;
        fp_audit_log($conn, $actor, FP_DELEGATION_ENTITY, $id, 'create', null, array(
            'DelegatorEmployeeID' => $delegator, 'DelegateEmployeeID' => $delegate, 'FromDate' => $from, 'ToDate' => $to,
        ), $reason);
        return fp_result(true, 200, 'Delegation saved.', array('data' => array('ID' => $id)));
    });
}

function fp_delegation_cancel($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $reason = preg_replace('/\s+/u', ' ', fp_input_string($post, 'Reason'));
    if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'Please give a reason (5-500 characters).', array('errors' => array('Reason' => 'Required.')));
    }
    return fp_policy_locked_transaction($conn, function () use ($conn, $actor, $id, $reason) {
        $rows = fp_db_rows($conn, 'SELECT * FROM branch_expense_approval_delegations WHERE ID = ? FOR UPDATE', 'i', array($id));
        if (!$rows) {
            return fp_result(false, 404, 'This delegation no longer exists. Reload the page.');
        }
        if ((int) $rows[0]['IsActive'] !== 1 || $rows[0]['ToDate'] < date('Y-m-d')) {
            return fp_result(false, 422, 'This delegation has already ended or been cancelled.');
        }
        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            'UPDATE branch_expense_approval_delegations SET IsActive = 0, CancelledAt = ?, CancelledByUserID = ?, CancelReason = ? WHERE ID = ? AND IsActive = 1',
            'sisi',
            array($now, (int) $actor['user_id'], $reason, $id)
        );
        fp_audit_log($conn, $actor, FP_DELEGATION_ENTITY, $id, 'cancel', array('IsActive' => 1), array('IsActive' => 0), $reason);
        return fp_result(true, 200, 'Delegation cancelled.');
    });
}

/**
 * Employee acting for $delegatorEmployeeId on $date, or 0. For the approval workflow.
 */
function fp_delegation_delegate_for($conn, $delegatorEmployeeId, $date)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT DelegateEmployeeID FROM branch_expense_approval_delegations WHERE DelegatorEmployeeID = ? AND IsActive = 1 AND FromDate <= ? AND ToDate >= ? LIMIT 1',
        'iss',
        array((int) $delegatorEmployeeId, $date, $date)
    );
    return $rows ? (int) $rows[0]['DelegateEmployeeID'] : 0;
}
