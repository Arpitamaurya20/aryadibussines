<?php
/**
 * Budgets (branch_expense_budgets) and how expense requests use them.
 * Loaded by fp_requests.php; requires fp_access.php and fp_audit.php.
 *
 * Overall and category budgets of the same scope (company, state or branch) and period:
 *   - the overall budget (no category) is the cap;
 *   - category budgets are carved out of it: they must lie inside its period and together not exceed it;
 *   - spend in a budgeted category counts against both the category budget and the overall budget;
 *   - money still reserved by category budgets is not available to other categories, so the overall budget is
 *     used by: committed spend + unused category reservations ("unallocated left" is what remains).
 * Company, state and branch budgets are separate levels; a request is checked against every level that covers it.
 *
 * Amounts are handled in paise (integers). A line counts in the period containing its expense date;
 * lines without one (advances) count on the request's submission date.
 */

const FP_BUDGET_ENTITY = 'expense_budget';

function fp_budget_levels()
{
    return array('company' => 'Company', 'state' => 'State', 'office' => 'Branch office');
}

function fp_budget_period_types()
{
    return array('month' => 'Month', 'quarter' => 'Quarter', 'year' => 'Financial year', 'custom' => 'Custom period');
}

function fp_budget_enforcements()
{
    return array('warn' => 'Warn', 'block' => 'Block');
}

function fp_budget_money($paise)
{
    return 'INR ' . number_format($paise / 100, 2);
}

/* ------------------------------------------------------------------
   Periods
------------------------------------------------------------------ */

/**
 * Returns array(from, to, errors). Quarters and years follow the April-March financial year.
 */
function fp_budget_resolve_period($type, $post)
{
    switch ($type) {
        case 'month':
            $month = fp_input_string($post, 'PeriodMonth');
            if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $month)) {
                return array(null, null, array('PeriodMonth' => 'Choose the month.'));
            }
            $from = $month . '-01';
            return array($from, date('Y-m-t', strtotime($from)), array());
        case 'quarter':
        case 'year':
            $fy = fp_input_int($post, 'FinancialYear');
            if ($fy < 2000 || $fy > 2099) {
                return array(null, null, array('FinancialYear' => 'Choose the financial year.'));
            }
            if ($type === 'year') {
                return array($fy . '-04-01', ($fy + 1) . '-03-31', array());
            }
            $quarter = fp_input_int($post, 'Quarter');
            if ($quarter < 1 || $quarter > 4) {
                return array(null, null, array('Quarter' => 'Choose the quarter.'));
            }
            $start = new DateTime($fy . '-04-01');
            $start->modify('+' . (($quarter - 1) * 3) . ' months');
            $end = clone $start;
            $end->modify('+3 months -1 day');
            return array($start->format('Y-m-d'), $end->format('Y-m-d'), array());
        case 'custom':
            $from = fp_parse_date(fp_input_string($post, 'PeriodFrom'));
            $to = fp_parse_date(fp_input_string($post, 'PeriodTo'));
            $errors = array();
            if (!$from) {
                $errors['PeriodFrom'] = 'Enter a valid start date.';
            }
            if (!$to) {
                $errors['PeriodTo'] = 'Enter a valid end date.';
            }
            if (!$errors && $from > $to) {
                $errors['PeriodTo'] = 'The period cannot end before it starts.';
            }
            if (!$errors && $to >= (new DateTime($from))->modify('+1 year')->format('Y-m-d')) {
                $errors['PeriodTo'] = 'A custom period can be at most one year. Create one budget per year.';
            }
            return $errors ? array(null, null, $errors) : array($from, $to, array());
    }
    return array(null, null, array('PeriodType' => 'Choose the period type.'));
}

function fp_budget_period_label($row)
{
    $from = (string) $row['PeriodFrom'];
    switch ($row['PeriodType']) {
        case 'month':
            return date('M Y', strtotime($from));
        case 'quarter':
            $quarter = intdiv(((int) date('n', strtotime($from)) + 8) % 12, 3) + 1;
            return 'Q' . $quarter . ' FY ' . fp_financial_year_label($from);
        case 'year':
            return 'FY ' . fp_financial_year_label($from);
    }
    return date('d M Y', strtotime($from)) . ' to ' . date('d M Y', strtotime((string) $row['PeriodTo']));
}

function fp_budget_within($inner, $outer)
{
    return $inner['PeriodFrom'] >= $outer['PeriodFrom'] && $inner['PeriodTo'] <= $outer['PeriodTo'];
}

/* ------------------------------------------------------------------
   Loading and labels
------------------------------------------------------------------ */

function fp_budget_select_sql()
{
    return 'SELECT b.*, q.CompanyName, s.StateName, o.OfficeName, o.OfficeCode, c.CategoryName, c.CategoryCode,
                   COALESCE(uu.UserName, cu.UserName) AS LastChangedBy
            FROM branch_expense_budgets b
            LEFT JOIN quote_company_details q ON q.ID = b.CompanyID
            LEFT JOIN state s ON s.ID = b.StateID
            LEFT JOIN branch_expense_offices o ON o.ID = b.OfficeID
            LEFT JOIN branch_expense_categories c ON c.ID = b.CategoryID
            LEFT JOIN users uu ON b.UpdatedByUserID > 0 AND uu.UserID = b.UpdatedByUserID
            LEFT JOIN users cu ON b.CreatedByUserID > 0 AND cu.UserID = b.CreatedByUserID';
}

/**
 * $lock: '' | 'update'. Locked reads touch only the budget table.
 */
function fp_budget_get($conn, $id, $lock = '')
{
    if ($lock === 'update') {
        $rows = fp_db_rows($conn, 'SELECT * FROM branch_expense_budgets WHERE ID = ? FOR UPDATE', 'i', array((int) $id));
        return $rows ? fp_budget_normalize($rows[0]) : null;
    }
    $rows = fp_db_rows($conn, fp_budget_select_sql() . ' WHERE b.ID = ?', 'i', array((int) $id));
    return $rows ? fp_budget_normalize($rows[0]) : null;
}

function fp_budget_normalize($row)
{
    foreach (array('ID', 'CompanyID', 'StateID', 'OfficeID', 'IsActive', 'RowVersion') as $field) {
        $row[$field] = (int) $row[$field];
    }
    $row['CategoryID'] = $row['CategoryID'] === null ? null : (int) $row['CategoryID'];
    return $row;
}

function fp_budget_level($row)
{
    return $row['OfficeID'] > 0 ? 'office' : ($row['StateID'] > 0 ? 'state' : 'company');
}

function fp_budget_scope_label($row)
{
    $company = (string) ($row['CompanyName'] ?? '') !== '' ? $row['CompanyName'] : 'Company #' . $row['CompanyID'];
    switch (fp_budget_level($row)) {
        case 'office':
            return 'Branch ' . ($row['OfficeName'] ?? '') . ' (' . ($row['OfficeCode'] ?? '') . ')';
        case 'state':
            return 'State ' . ($row['StateName'] ?? '') . ', ' . $company;
    }
    return 'Company ' . $company;
}

function fp_budget_category_label($row)
{
    return $row['CategoryID'] === null ? 'All categories' : (string) ($row['CategoryName'] ?? 'Category #' . $row['CategoryID']);
}

function fp_budget_label($row)
{
    return fp_budget_category_label($row) . ', ' . fp_budget_scope_label($row) . ', ' . fp_budget_period_label($row);
}

function fp_budget_snapshot($row)
{
    return array(
        'CompanyID'   => (int) $row['CompanyID'],
        'StateID'     => (int) $row['StateID'],
        'OfficeID'    => (int) $row['OfficeID'],
        'CategoryID'  => $row['CategoryID'] === null ? null : (int) $row['CategoryID'],
        'PeriodType'  => (string) $row['PeriodType'],
        'PeriodFrom'  => (string) $row['PeriodFrom'],
        'PeriodTo'    => (string) $row['PeriodTo'],
        'Amount'      => fp_paise_to_decimal(fp_decimal_to_paise($row['Amount'])),
        'Enforcement' => (string) $row['Enforcement'],
        'Notes'       => (string) $row['Notes'],
        'IsActive'    => (int) $row['IsActive'],
    );
}

/* ------------------------------------------------------------------
   Usage
------------------------------------------------------------------ */

/**
 * Spend counted against one budget, in paise:
 *   pending    = submitted, awaiting approval
 *   approved   = approved reimbursements and direct expenses; approved advances while not yet settled;
 *                after settlement, the expenses Finance accepted against the advance instead
 *   recognized = approved reimbursements and direct expenses, plus expenses accepted in verified advance
 *                settlements (the advance payment itself is never an expense)
 * $excludeRequestId leaves one request out (used when showing that request's own effect).
 */
function fp_budget_usage($conn, $budget, $excludeRequestId = 0)
{
    $scope = function ($dateSql, $categorySql) use ($budget, $excludeRequestId) {
        $sql = ' AND r.CompanyID = ? AND r.ID <> ? AND ' . $dateSql . ' BETWEEN ? AND ?';
        $types = 'iiss';
        $params = array($budget['CompanyID'], (int) $excludeRequestId, $budget['PeriodFrom'], $budget['PeriodTo']);
        if ($budget['OfficeID'] > 0) {
            $sql .= ' AND r.OfficeID = ?';
            $types .= 'i';
            $params[] = $budget['OfficeID'];
        } elseif ($budget['StateID'] > 0) {
            $sql .= ' AND r.StateID = ?';
            $types .= 'i';
            $params[] = $budget['StateID'];
        }
        if ($budget['CategoryID'] !== null) {
            $sql .= ' AND ' . $categorySql . ' = ?';
            $types .= 'i';
            $params[] = $budget['CategoryID'];
        }
        return array($sql, $types, $params);
    };

    list($where, $types, $params) = $scope('COALESCE(i.ExpenseDate, DATE(COALESCE(r.SubmittedAt, r.CreatedAt)))', 'i.CategoryID');
    $items = fp_db_rows(
        $conn,
        'SELECT
           COALESCE(SUM(CASE WHEN r.Status = \'submitted\' THEN i.Amount END), 0) AS Pending,
           COALESCE(SUM(CASE WHEN r.Status = \'approved\' AND (r.RequestType <> \'advance\' OR r.SettlementStatus <> \'settled\') THEN i.Amount END), 0) AS Approved,
           COALESCE(SUM(CASE WHEN r.Status = \'approved\' AND r.RequestType <> \'advance\' THEN i.Amount END), 0) AS Recognized
         FROM branch_expense_items i
         INNER JOIN branch_expense_requests r ON r.ID = i.RequestID
         WHERE r.Status IN (\'submitted\', \'approved\')' . $where,
        $types,
        $params
    )[0];

    list($where, $types, $params) = $scope('l.ExpenseDate', 'l.CategoryID');
    $settled = fp_db_rows(
        $conn,
        'SELECT
           COALESCE(SUM(CASE WHEN r.SettlementStatus = \'settled\' THEN l.AcceptedAmount END), 0) AS Approved,
           COALESCE(SUM(l.AcceptedAmount), 0) AS Recognized
         FROM branch_expense_settlement_lines l
         INNER JOIN branch_expense_settlements s ON s.ID = l.SettlementID
         INNER JOIN branch_expense_requests r ON r.ID = s.RequestID
         WHERE s.Status = \'verified\' AND l.Decision IN (\'accepted\', \'partial\') AND r.Status = \'approved\'' . $where,
        $types,
        $params
    )[0];

    return array(
        'pending'    => fp_decimal_to_paise($items['Pending']),
        'approved'   => fp_decimal_to_paise($items['Approved']) + fp_decimal_to_paise($settled['Approved']),
        'recognized' => fp_decimal_to_paise($items['Recognized']) + fp_decimal_to_paise($settled['Recognized']),
    );
}

function fp_budget_same_scope($a, $b)
{
    return $a['CompanyID'] === $b['CompanyID'] && $a['StateID'] === $b['StateID'] && $a['OfficeID'] === $b['OfficeID'];
}

/**
 * Unused amounts reserved by active category budgets inside an overall budget, given each budget's committed paise.
 */
function fp_budget_reserved($overall, $budgets, $committedById)
{
    $reserved = 0;
    $allocated = 0;
    foreach ($budgets as $budget) {
        if ($budget['CategoryID'] === null || !$budget['IsActive'] || !fp_budget_same_scope($budget, $overall) || !fp_budget_within($budget, $overall)) {
            continue;
        }
        $amount = fp_decimal_to_paise($budget['Amount']);
        $allocated += $amount;
        $reserved += max($amount - ($committedById[$budget['ID']] ?? 0), 0);
    }
    return array('allocated' => $allocated, 'reserved' => $reserved);
}

function fp_budget_list($conn)
{
    $budgets = array_map('fp_budget_normalize', fp_db_rows(
        $conn,
        fp_budget_select_sql() . ' ORDER BY b.IsActive DESC, b.PeriodFrom DESC, q.CompanyName, b.OfficeID > 0, b.StateID > 0, s.StateName, o.OfficeName, b.CategoryID IS NOT NULL, c.CategoryName'
    ));
    $usage = array();
    $committed = array();
    foreach ($budgets as $budget) {
        $usage[$budget['ID']] = fp_budget_usage($conn, $budget);
        $committed[$budget['ID']] = $usage[$budget['ID']]['pending'] + $usage[$budget['ID']]['approved'];
    }

    $today = date('Y-m-d');
    $levels = fp_budget_levels();
    $list = array();
    foreach ($budgets as $budget) {
        $id = $budget['ID'];
        $amount = fp_decimal_to_paise($budget['Amount']);
        $alloc = $budget['CategoryID'] === null && $budget['IsActive'] ? fp_budget_reserved($budget, $budgets, $committed) : array('allocated' => 0, 'reserved' => 0);
        $available = $amount - $committed[$id] - $alloc['reserved'];
        $list[] = array(
            'ID'            => $id,
            'RowVersion'    => $budget['RowVersion'],
            'Level'         => fp_budget_level($budget),
            'LevelLabel'    => $levels[fp_budget_level($budget)],
            'CompanyID'     => $budget['CompanyID'],
            'StateID'       => $budget['StateID'],
            'OfficeID'      => $budget['OfficeID'],
            'CategoryID'    => $budget['CategoryID'],
            'CompanyName'   => (string) $budget['CompanyName'],
            'ScopeLabel'    => fp_budget_scope_label($budget),
            'CategoryLabel' => fp_budget_category_label($budget),
            'PeriodType'    => $budget['PeriodType'],
            'PeriodFrom'    => $budget['PeriodFrom'],
            'PeriodTo'      => $budget['PeriodTo'],
            'PeriodLabel'   => fp_budget_period_label($budget),
            'IsCurrent'     => $budget['PeriodFrom'] <= $today && $budget['PeriodTo'] >= $today,
            'Amount'        => fp_paise_to_decimal($amount),
            'Pending'       => fp_paise_to_decimal($usage[$id]['pending']),
            'Approved'      => fp_paise_to_decimal($usage[$id]['approved']),
            'Recognized'    => fp_paise_to_decimal($usage[$id]['recognized']),
            'Committed'     => fp_paise_to_decimal($committed[$id]),
            'Allocated'     => fp_paise_to_decimal($alloc['allocated']),
            'Reserved'      => fp_paise_to_decimal($alloc['reserved']),
            'Available'     => ($available < 0 ? '-' : '') . fp_paise_to_decimal(abs($available)),
            'IsOver'        => $available < 0,
            'Enforcement'   => $budget['Enforcement'],
            'Notes'         => (string) $budget['Notes'],
            'IsActive'      => $budget['IsActive'],
            'LastChangedAt' => (string) ($budget['UpdatedAt'] ?: $budget['CreatedAt']),
            'LastChangedBy' => (string) ($budget['LastChangedBy'] ?: 'System'),
        );
    }
    return $list;
}

function fp_budget_lookups($conn)
{
    return array(
        'companies'  => fp_db_rows($conn, 'SELECT ID, CompanyName FROM quote_company_details WHERE IsActive = 1 ORDER BY CompanyName'),
        'states'     => fp_db_rows($conn, 'SELECT ID, StateName FROM state WHERE IsActive = 1 ORDER BY StateName'),
        'offices'    => fp_db_rows($conn, 'SELECT ID, OfficeCode, OfficeName, CompanyID, StateID FROM branch_expense_offices WHERE IsActive = 1 ORDER BY OfficeName'),
        'categories' => fp_db_rows($conn, 'SELECT ID, CategoryCode, CategoryName, CompanyID FROM branch_expense_categories WHERE IsActive = 1 ORDER BY SortOrder, CategoryName'),
    );
}

/* ------------------------------------------------------------------
   Rules: no overlaps, category budgets inside the overall budget
------------------------------------------------------------------ */

/**
 * Message when $budget (about to be active with its Amount) breaks a rule, otherwise ''.
 * Must run inside the budget write lock.
 */
function fp_budget_rule_error($conn, $budget)
{
    $rows = array_map('fp_budget_normalize', fp_db_rows(
        $conn,
        'SELECT * FROM branch_expense_budgets
         WHERE IsActive = 1 AND CompanyID = ? AND StateID = ? AND OfficeID = ? AND ID <> ? AND PeriodFrom <= ? AND PeriodTo >= ?',
        'iiiiss',
        array($budget['CompanyID'], $budget['StateID'], $budget['OfficeID'], (int) $budget['ID'], $budget['PeriodTo'], $budget['PeriodFrom'])
    ));
    $amount = fp_decimal_to_paise($budget['Amount']);
    $isOverall = $budget['CategoryID'] === null;

    foreach ($rows as $row) {
        if ($row['CategoryID'] === $budget['CategoryID']) {
            return 'An active budget for the same scope and category already covers part of this period (' . fp_budget_period_label($row)
                . '). Adjust that budget instead, or choose another period.';
        }
    }

    if ($isOverall) {
        $allocated = 0;
        foreach ($rows as $row) {
            if (!fp_budget_within($row, $budget)) {
                return 'A category budget (' . fp_budget_period_label($row) . ') falls partly outside this period. Category budgets must sit inside the overall budget period.';
            }
            $allocated += fp_decimal_to_paise($row['Amount']);
        }
        if ($allocated > $amount) {
            return 'Category budgets in this period already total ' . fp_budget_money($allocated) . ', so the overall budget cannot be less than that.';
        }
        return '';
    }

    foreach ($rows as $overall) {
        if ($overall['CategoryID'] !== null) {
            continue;
        }
        if (!fp_budget_within($budget, $overall)) {
            return 'This period falls partly outside the overall budget for ' . fp_budget_period_label($overall) . '. Category budgets must sit inside the overall budget period.';
        }
        $siblings = fp_db_rows(
            $conn,
            'SELECT COALESCE(SUM(Amount), 0) AS Total FROM branch_expense_budgets
             WHERE IsActive = 1 AND CategoryID IS NOT NULL AND CompanyID = ? AND StateID = ? AND OfficeID = ? AND ID <> ? AND PeriodFrom >= ? AND PeriodTo <= ?',
            'iiiiss',
            array($budget['CompanyID'], $budget['StateID'], $budget['OfficeID'], (int) $budget['ID'], $overall['PeriodFrom'], $overall['PeriodTo'])
        );
        $total = fp_decimal_to_paise($siblings[0]['Total']) + $amount;
        $overallAmount = fp_decimal_to_paise($overall['Amount']);
        if ($total > $overallAmount) {
            return 'Category budgets would total ' . fp_budget_money($total) . ', more than the overall budget of ' . fp_budget_money($overallAmount)
                . ' for ' . fp_budget_period_label($overall) . '. Raise the overall budget first.';
        }
    }
    return '';
}

/**
 * Budget writes run one at a time (named lock) so overlap and allocation checks cannot race.
 */
function fp_budget_locked_transaction($conn, $work)
{
    $got = fp_db_rows($conn, "SELECT GET_LOCK(CONCAT(DATABASE(), '.fp_budget_write'), 10) AS L");
    if ((int) ($got[0]['L'] ?? 0) !== 1) {
        return fp_result(false, 409, 'Another budget change is in progress. Please try again.');
    }
    try {
        return fp_transaction($conn, $work);
    } finally {
        fp_db_rows($conn, "SELECT RELEASE_LOCK(CONCAT(DATABASE(), '.fp_budget_write')) AS L");
    }
}

function fp_budget_add_adjustment($conn, $actor, $budgetId, $oldPaise, $newPaise, $reason, $now)
{
    $old = fp_paise_to_decimal($oldPaise);
    $new = fp_paise_to_decimal($newPaise);
    $change = ($newPaise < $oldPaise ? '-' : '') . fp_paise_to_decimal(abs($newPaise - $oldPaise));
    $userId = (int) $actor['user_id'];
    $employeeId = (int) $actor['employee_id'];
    $stmt = $conn->prepare(
        'INSERT INTO branch_expense_budget_adjustments (BudgetID, OldAmount, NewAmount, ChangeAmount, Reason, AdjustedByUserID, AdjustedByEmployeeID, AdjustedAt)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issssiis', $budgetId, $old, $new, $change, $reason, $userId, $employeeId, $now);
    $stmt->execute();
    $stmt->close();
}

/* ------------------------------------------------------------------
   Create / edit / adjust / status
------------------------------------------------------------------ */

/**
 * New budget: everything. Existing budget: only Enforcement and Notes; the amount changes through
 * fp_budget_adjust() and scope or period never change (deactivate and create a new budget instead).
 */
function fp_budget_save($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $enforcement = fp_input_string($post, 'Enforcement');
    $notes = preg_replace('/\s+/u', ' ', fp_input_string($post, 'Notes'));
    $errors = array();
    if (!isset(fp_budget_enforcements()[$enforcement])) {
        $errors['Enforcement'] = 'Choose what happens when the budget is exceeded.';
    }
    if (mb_strlen($notes) > 500) {
        $errors['Notes'] = 'Notes can be at most 500 characters.';
    }

    if ($id > 0) {
        if ($errors) {
            return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
        }
        $rowVersion = fp_input_int($post, 'RowVersion');
        return fp_budget_locked_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $enforcement, $notes) {
            $existing = fp_budget_get($conn, $id, 'update');
            if (!$existing) {
                return fp_result(false, 404, 'This budget no longer exists. Reload the page.');
            }
            if ($existing['RowVersion'] !== $rowVersion) {
                return fp_result(false, 409, 'Someone else changed this budget. Reload the page and try again.');
            }
            if ($existing['Enforcement'] === $enforcement && $existing['Notes'] === $notes) {
                return fp_result(true, 200, 'No changes to save.');
            }
            $userId = (int) $actor['user_id'];
            $employeeId = (int) $actor['employee_id'];
            $now = date('Y-m-d H:i:s');
            $stmt = $conn->prepare(
                'UPDATE branch_expense_budgets SET Enforcement = ?, Notes = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
                 WHERE ID = ? AND RowVersion = ?'
            );
            $stmt->bind_param('ssiisii', $enforcement, $notes, $userId, $employeeId, $now, $id, $rowVersion);
            $stmt->execute();
            $stmt->close();
            $new = $existing;
            $new['Enforcement'] = $enforcement;
            $new['Notes'] = $notes;
            fp_audit_log($conn, $actor, FP_BUDGET_ENTITY, $id, 'update', fp_budget_snapshot($existing), fp_budget_snapshot($new));
            return fp_result(true, 200, 'Budget updated.');
        });
    }

    $levels = fp_budget_levels();
    $level = fp_input_string($post, 'Level');
    $companyId = fp_input_int($post, 'CompanyID');
    $stateId = fp_input_int($post, 'StateID');
    $officeId = fp_input_int($post, 'OfficeID');
    $categoryId = fp_input_int($post, 'CategoryID');
    $periodType = fp_input_string($post, 'PeriodType');
    $amount = fp_parse_paise(fp_input_string($post, 'Amount'));

    if (!isset($levels[$level])) {
        $errors['Level'] = 'Choose the budget level.';
    }
    if ($companyId <= 0) {
        $errors['CompanyID'] = 'Choose the legal entity.';
    }
    if ($level === 'state' && $stateId <= 0) {
        $errors['StateID'] = 'Choose the state.';
    }
    if ($level === 'office' && $officeId <= 0) {
        $errors['OfficeID'] = 'Choose the branch office.';
    }
    if (!isset(fp_budget_period_types()[$periodType])) {
        $errors['PeriodType'] = 'Choose the period type.';
        $from = $to = null;
    } else {
        list($from, $to, $periodErrors) = fp_budget_resolve_period($periodType, $post);
        $errors = array_merge($errors, $periodErrors);
    }
    if ($amount === false || $amount <= 0) {
        $errors['Amount'] = 'Enter an amount greater than zero, with up to 2 decimals.';
    }
    if ($errors) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
    }

    return fp_budget_locked_transaction($conn, function () use ($conn, $actor, $level, $companyId, $stateId, $officeId, $categoryId, $periodType, $from, $to, $amount, $enforcement, $notes) {
        $errors = array();
        if (!fp_db_rows($conn, 'SELECT ID FROM quote_company_details WHERE ID = ? AND IsActive = 1', 'i', array($companyId))) {
            $errors['CompanyID'] = 'Choose an active legal entity.';
        }
        if ($level === 'company') {
            $stateId = 0;
            $officeId = 0;
        } elseif ($level === 'state') {
            $officeId = 0;
            if (!fp_db_rows($conn, 'SELECT ID FROM state WHERE ID = ? AND IsActive = 1', 'i', array($stateId))) {
                $errors['StateID'] = 'Choose an active state.';
            }
        } else {
            $office = fp_db_rows($conn, 'SELECT ID, CompanyID, StateID, IsActive FROM branch_expense_offices WHERE ID = ?', 'i', array($officeId));
            if (!$office || (int) $office[0]['IsActive'] !== 1) {
                $errors['OfficeID'] = 'Choose an active branch office.';
            } elseif ((int) $office[0]['CompanyID'] !== $companyId) {
                $errors['OfficeID'] = 'This office belongs to a different legal entity.';
            } else {
                $stateId = (int) $office[0]['StateID'];
            }
        }
        if ($categoryId > 0) {
            $category = fp_db_rows($conn, 'SELECT ID, CompanyID, IsActive FROM branch_expense_categories WHERE ID = ?', 'i', array($categoryId));
            if (!$category || (int) $category[0]['IsActive'] !== 1) {
                $errors['CategoryID'] = 'Choose an active category.';
            } elseif ((int) $category[0]['CompanyID'] !== 0 && (int) $category[0]['CompanyID'] !== $companyId) {
                $errors['CategoryID'] = 'This category is not available for the selected legal entity.';
            }
        }
        if ($errors) {
            return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
        }

        $budget = array(
            'ID' => 0, 'CompanyID' => $companyId, 'StateID' => $stateId, 'OfficeID' => $officeId,
            'CategoryID' => $categoryId > 0 ? $categoryId : null, 'PeriodType' => $periodType,
            'PeriodFrom' => $from, 'PeriodTo' => $to, 'Amount' => fp_paise_to_decimal($amount),
            'Enforcement' => $enforcement, 'Notes' => $notes, 'IsActive' => 1,
        );
        $ruleError = fp_budget_rule_error($conn, $budget);
        if ($ruleError !== '') {
            return fp_result(false, 422, $ruleError);
        }

        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $now = date('Y-m-d H:i:s');
        $categoryValue = $budget['CategoryID'];
        $amountValue = $budget['Amount'];
        $stmt = $conn->prepare(
            'INSERT INTO branch_expense_budgets
                (CompanyID, StateID, OfficeID, CategoryID, PeriodType, PeriodFrom, PeriodTo, Amount, Enforcement, Notes, IsActive, RowVersion, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?, ?)'
        );
        $stmt->bind_param('iiiissssssiis', $companyId, $stateId, $officeId, $categoryValue, $periodType, $from, $to, $amountValue, $enforcement, $notes, $userId, $employeeId, $now);
        $stmt->execute();
        $id = (int) $conn->insert_id;
        $stmt->close();
        fp_budget_add_adjustment($conn, $actor, $id, 0, $amount, 'Opening budget', $now);
        $saved = fp_budget_get($conn, $id);
        fp_audit_log($conn, $actor, FP_BUDGET_ENTITY, $id, 'create', null, fp_budget_snapshot($saved));
        return fp_result(true, 200, 'Budget created: ' . fp_budget_label($saved) . '.', array('data' => array('ID' => $id)));
    });
}

function fp_budget_adjust($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $newAmount = fp_parse_paise(fp_input_string($post, 'NewAmount'));
    $reason = preg_replace('/\s+/u', ' ', fp_input_string($post, 'Reason'));
    $errors = array();
    if ($id <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if ($newAmount === false || $newAmount <= 0) {
        $errors['NewAmount'] = 'Enter an amount greater than zero, with up to 2 decimals.';
    }
    if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
        $errors['Reason'] = 'Give a reason of 5-500 characters.';
    }
    if ($errors) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
    }

    return fp_budget_locked_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $newAmount, $reason) {
        $existing = fp_budget_get($conn, $id, 'update');
        if (!$existing) {
            return fp_result(false, 404, 'This budget no longer exists. Reload the page.');
        }
        if ($existing['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'Someone else changed this budget. Reload the page and try again.');
        }
        if (!$existing['IsActive']) {
            return fp_result(false, 422, 'Activate the budget before adjusting it.');
        }
        $oldAmount = fp_decimal_to_paise($existing['Amount']);
        if ($oldAmount === $newAmount) {
            return fp_result(false, 422, 'The new amount is the same as the current amount.', array('errors' => array('NewAmount' => 'Same as the current amount.')));
        }
        $candidate = $existing;
        $candidate['Amount'] = fp_paise_to_decimal($newAmount);
        $ruleError = fp_budget_rule_error($conn, $candidate);
        if ($ruleError !== '') {
            return fp_result(false, 422, $ruleError);
        }

        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $now = date('Y-m-d H:i:s');
        $amountValue = $candidate['Amount'];
        $stmt = $conn->prepare(
            'UPDATE branch_expense_budgets SET Amount = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND RowVersion = ?'
        );
        $stmt->bind_param('siisii', $amountValue, $userId, $employeeId, $now, $id, $rowVersion);
        $stmt->execute();
        $stmt->close();
        fp_budget_add_adjustment($conn, $actor, $id, $oldAmount, $newAmount, $reason, $now);
        fp_audit_log($conn, $actor, FP_BUDGET_ENTITY, $id, 'adjust', fp_budget_snapshot($existing), fp_budget_snapshot($candidate), $reason);

        $usage = fp_budget_usage($conn, $existing);
        $committed = $usage['pending'] + $usage['approved'];
        $message = 'Budget changed from ' . fp_budget_money($oldAmount) . ' to ' . fp_budget_money($newAmount) . '.';
        if ($committed > $newAmount) {
            $message .= ' Committed spend (' . fp_budget_money($committed) . ') is now above the budget.';
        }
        return fp_result(true, 200, $message);
    });
}

function fp_budget_set_status($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $status = fp_input_string($post, 'IsActive');
    $reason = preg_replace('/\s+/u', ' ', fp_input_string($post, 'Reason'));
    if ($id <= 0 || !in_array($status, array('0', '1'), true)) {
        return fp_result(false, 422, 'Invalid request.');
    }
    $activate = $status === '1';
    if (!$activate && mb_strlen($reason) < 5) {
        return fp_result(false, 422, 'Please give a reason for deactivating (at least 5 characters).', array('errors' => array('Reason' => 'Required.')));
    }
    if (mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'The reason can be at most 500 characters.', array('errors' => array('Reason' => 'Too long.')));
    }

    return fp_budget_locked_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $activate, $reason) {
        $existing = fp_budget_get($conn, $id, 'update');
        if (!$existing) {
            return fp_result(false, 404, 'This budget no longer exists. Reload the page.');
        }
        if ($existing['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'Someone else changed this budget. Reload the page and try again.');
        }
        if ($existing['IsActive'] === ($activate ? 1 : 0)) {
            return fp_result(false, 422, 'The budget is already ' . ($activate ? 'active' : 'inactive') . '.');
        }
        if ($activate) {
            $ruleError = fp_budget_rule_error($conn, $existing);
            if ($ruleError !== '') {
                return fp_result(false, 422, $ruleError);
            }
        }
        $isActive = $activate ? 1 : 0;
        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare(
            'UPDATE branch_expense_budgets SET IsActive = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND RowVersion = ?'
        );
        $stmt->bind_param('iiisii', $isActive, $userId, $employeeId, $now, $id, $rowVersion);
        $stmt->execute();
        $stmt->close();
        $new = $existing;
        $new['IsActive'] = $isActive;
        fp_audit_log($conn, $actor, FP_BUDGET_ENTITY, $id, $activate ? 'activate' : 'deactivate', fp_budget_snapshot($existing), fp_budget_snapshot($new), $reason);
        return fp_result(true, 200, $activate ? 'Budget activated.' : 'Budget deactivated. Requests are no longer checked against it.');
    });
}

function fp_budget_history($conn, $id)
{
    $budget = fp_budget_get($conn, $id);
    if (!$budget) {
        return fp_result(false, 404, 'This budget no longer exists.');
    }
    $adjustments = fp_db_rows(
        $conn,
        'SELECT a.OldAmount, a.NewAmount, a.ChangeAmount, a.Reason, a.AdjustedAt, COALESCE(u.UserName, \'System\') AS AdjustedBy
         FROM branch_expense_budget_adjustments a
         LEFT JOIN users u ON u.UserID = a.AdjustedByUserID
         WHERE a.BudgetID = ? ORDER BY a.ID DESC',
        'i',
        array($id)
    );
    return fp_result(true, 200, 'OK', array('data' => array(
        'budget'      => array('ID' => $budget['ID'], 'Label' => fp_budget_label($budget)),
        'adjustments' => $adjustments,
        'history'     => fp_audit_history($conn, FP_BUDGET_ENTITY, $id),
    )));
}

/* ------------------------------------------------------------------
   Effect of a request on budgets
------------------------------------------------------------------ */

/**
 * Every active budget covering $request that one of its lines falls into, with the request's effect.
 * Other requests' spend excludes $request itself. $submitDate dates undated lines (advances) at submission;
 * otherwise they use the request's submitted or created date. $lock = true serialises concurrent submissions.
 */
function fp_budget_impact($conn, $request, $items, $lock = false, $submitDate = null)
{
    $companyId = (int) $request['CompanyID'];
    $stateId = (int) $request['StateID'];
    $officeId = (int) $request['OfficeID'];
    $params = array($companyId, $stateId, $officeId);
    if ($lock) {
        fp_db_rows(
            $conn,
            'SELECT ID FROM branch_expense_budgets
             WHERE IsActive = 1 AND CompanyID = ? AND ((StateID = 0 AND OfficeID = 0) OR (StateID = ? AND OfficeID = 0) OR OfficeID = ?)
             FOR UPDATE',
            'iii',
            $params
        );
    }
    $budgets = array_map('fp_budget_normalize', fp_db_rows(
        $conn,
        fp_budget_select_sql() . '
         WHERE b.IsActive = 1 AND b.CompanyID = ? AND ((b.StateID = 0 AND b.OfficeID = 0) OR (b.StateID = ? AND b.OfficeID = 0) OR b.OfficeID = ?)
         ORDER BY b.OfficeID > 0 DESC, b.StateID > 0 DESC, b.CategoryID IS NULL, b.PeriodFrom',
        'iii',
        $params
    ));
    if (!$budgets) {
        return array();
    }

    $defaultDate = $submitDate ?: substr((string) ($request['SubmittedAt'] ?: $request['CreatedAt']), 0, 10);
    $lines = array();
    foreach ($items as $item) {
        $lines[] = array(
            'date'     => $item['ExpenseDate'] ? (string) $item['ExpenseDate'] : $defaultDate,
            'category' => (int) $item['CategoryID'],
            'amount'   => fp_decimal_to_paise($item['Amount']),
        );
    }

    $committed = array();
    $mine = array();
    foreach ($budgets as $budget) {
        $usage = fp_budget_usage($conn, $budget, (int) $request['ID']);
        $committed[$budget['ID']] = $usage['pending'] + $usage['approved'];
        $mine[$budget['ID']] = 0;
        foreach ($lines as $line) {
            if ($line['date'] >= $budget['PeriodFrom'] && $line['date'] <= $budget['PeriodTo'] && ($budget['CategoryID'] === null || $budget['CategoryID'] === $line['category'])) {
                $mine[$budget['ID']] += $line['amount'];
            }
        }
    }
    $committedAfter = array();
    foreach ($committed as $id => $value) {
        $committedAfter[$id] = $value + $mine[$id];
    }

    $result = array();
    foreach ($budgets as $budget) {
        $id = $budget['ID'];
        if ($mine[$id] === 0) {
            continue;
        }
        $amount = fp_decimal_to_paise($budget['Amount']);
        $reserved = $budget['CategoryID'] === null ? fp_budget_reserved($budget, $budgets, $committedAfter)['reserved'] : 0;
        $usedAfter = $committedAfter[$id] + $reserved;
        $result[] = array(
            'ID'          => $id,
            'Label'       => fp_budget_label($budget),
            'Enforcement' => $budget['Enforcement'],
            'Amount'      => $amount,
            'Committed'   => $committed[$id],
            'Reserved'    => $reserved,
            'ThisRequest' => $mine[$id],
            'LeftAfter'   => $amount - $usedAfter,
            'OverBy'      => max($usedAfter - $amount, 0),
            'Exceeded'    => $usedAfter > $amount,
        );
    }
    return $result;
}
