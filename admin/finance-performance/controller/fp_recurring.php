<?php
/**
 * Recurring expenses (rent, internet, electricity...): rules that create a draft direct expense for each period,
 * a few days before the bill is due. Loaded by fp_requests.php.
 *
 * A rule only prepares drafts. The owner (a requester of the branch office) checks the amount, attaches the bill and
 * submits it; it is then approved and paid through the normal approval policies and payment register.
 * Nothing is submitted, approved or paid automatically. Rules are never deleted: they are paused or ended.
 */

const FP_RECURRING_ENTITY = 'recurring_rule';
const FP_RECURRING_CATCH_UP = 3;        // periods one automatic run may create per rule
const FP_RECURRING_MANUAL_DAYS = 60;    // "Create draft now" is allowed when the next due date is this close
const FP_RECURRING_REASON_MIN = 5;

function fp_recurring_frequencies()
{
    return array(
        'monthly'     => array('label' => 'Monthly', 'months' => 1),
        'quarterly'   => array('label' => 'Quarterly', 'months' => 3),
        'half_yearly' => array('label' => 'Half-yearly', 'months' => 6),
        'yearly'      => array('label' => 'Yearly', 'months' => 12),
    );
}

function fp_recurring_statuses()
{
    return array('active' => 'Active', 'paused' => 'Paused', 'ended' => 'Ended');
}

function fp_recurring_due_timings()
{
    return array(
        'advance' => 'In advance (due in the first month of the period, e.g. rent)',
        'arrears' => 'After the period (due in the month after it ends, e.g. electricity)',
    );
}

function fp_recurring_amount_types()
{
    return array('fixed' => 'Fixed amount', 'estimate' => 'Estimate (requester enters the actual bill)');
}

/* ------------------------------------------------------------------
   Periods
------------------------------------------------------------------ */

function fp_recurring_add_months($date, $months)
{
    return date('Y-m-d', strtotime(substr($date, 0, 7) . '-01 +' . (int) $months . ' months'));
}

/**
 * One period of a rule starting at $start (a first day of month): from, to, key (YYYY-MM), due and create dates.
 */
function fp_recurring_period($rule, $start)
{
    $months = fp_recurring_frequencies()[$rule['Frequency']]['months'] ?? 1;
    $next = fp_recurring_add_months($start, $months);
    $to = date('Y-m-d', strtotime($next . ' -1 day'));
    $dueMonth = $rule['DueTiming'] === 'arrears' ? $next : $start;
    $due = substr($dueMonth, 0, 8) . sprintf('%02d', (int) $rule['DueDay']);
    return array(
        'from'   => $start,
        'to'     => $to,
        'key'    => substr($start, 0, 7),
        'due'    => $due,
        'create' => date('Y-m-d', strtotime($due . ' -' . (int) $rule['ReminderDays'] . ' days')),
        'next'   => $next,
        'label'  => fp_recurring_period_label($start, $to),
    );
}

function fp_recurring_period_label($from, $to)
{
    if (!$from) {
        return '';
    }
    $fromLabel = date('M Y', strtotime($from));
    $toLabel = $to ? date('M Y', strtotime($to)) : $fromLabel;
    return $fromLabel === $toLabel ? $fromLabel : $fromLabel . ' - ' . $toLabel;
}

/**
 * $start, or null when the period would begin after the rule's end date.
 */
function fp_recurring_within_end($rule, $start)
{
    return $start !== null && (empty($rule['EndDate']) || $start <= $rule['EndDate']) ? $start : null;
}

/**
 * First period from $start whose due date is today or later (used when a paused rule is resumed).
 * Returns array(start or null, number of periods skipped).
 */
function fp_recurring_first_open_start($rule, $start, $today)
{
    $skipped = 0;
    while ($start !== null && $skipped < 600) {
        $period = fp_recurring_period($rule, $start);
        if ($period['due'] >= $today) {
            break;
        }
        $start = fp_recurring_within_end($rule, $period['next']);
        $skipped++;
    }
    return array($start, $skipped);
}

/**
 * The next few periods still to be created (for display).
 */
function fp_recurring_upcoming($rule, $count = 3)
{
    $out = array();
    $start = $rule['Status'] === 'ended' ? null : fp_recurring_within_end($rule, $rule['NextPeriodStart']);
    while ($start !== null && count($out) < $count) {
        $period = fp_recurring_period($rule, $start);
        $out[] = array('Period' => $period['label'], 'DueDate' => $period['due'], 'CreateOn' => $period['create']);
        $start = fp_recurring_within_end($rule, $period['next']);
    }
    return $out;
}

/* ------------------------------------------------------------------
   Loading and access
------------------------------------------------------------------ */

function fp_recurring_get($conn, $id, $lock = '')
{
    $rows = fp_db_rows(
        $conn,
        'SELECT rr.*, o.OfficeCode, o.OfficeName, o.IsActive AS OfficeIsActive, s.StateName, q.CompanyName, e.Name AS OwnerName, e.EmployeeNumber AS OwnerNumber,
                c.CategoryName, p.CategoryName AS ParentCategoryName
         FROM branch_expense_recurring_rules rr
         INNER JOIN branch_expense_offices o ON o.ID = rr.OfficeID
         LEFT JOIN state s ON s.ID = rr.StateID
         LEFT JOIN quote_company_details q ON q.ID = rr.CompanyID
         LEFT JOIN employees e ON e.ID = rr.OwnerEmployeeID
         LEFT JOIN branch_expense_categories c ON c.ID = rr.CategoryID
         LEFT JOIN branch_expense_categories p ON p.ID = c.ParentCategoryID
         WHERE rr.ID = ?' . ($lock === 'update' ? ' FOR UPDATE OF rr' : ''),
        'i',
        array((int) $id)
    );
    return $rows ? $rows[0] : null;
}

function fp_recurring_scope_record($rule)
{
    return array(
        'employee_id'            => (int) $rule['OwnerEmployeeID'],
        'created_by_employee_id' => (int) $rule['CreatedByEmployeeID'],
        'state_name'             => (string) ($rule['StateName'] ?? ''),
        'office_id'              => (int) $rule['OfficeID'],
    );
}

/**
 * Finance (pay permission) whose scope covers the office: may set up rules for any requester of that office.
 */
function fp_recurring_is_finance_for($actor, $officeId, $stateName)
{
    return (int) $actor['employee_id'] > 0
        && fp_record_in_scope($actor, 'pay', array('office_id' => (int) $officeId, 'state_name' => (string) $stateName));
}

/**
 * Requesters may keep rules for themselves at an office where they raise requests.
 */
function fp_recurring_is_owner_requester($actor, $officeId)
{
    if ((int) $actor['employee_id'] <= 0 || !fp_can($actor, 'create')) {
        return false;
    }
    $offices = fp_requester_offices($actor);
    return $offices === true || in_array((int) $officeId, array_map('intval', $offices), true);
}

function fp_recurring_can_manage($actor, $rule)
{
    if (fp_recurring_is_finance_for($actor, $rule['OfficeID'], $rule['StateName'] ?? '')) {
        return true;
    }
    return (int) $rule['OwnerEmployeeID'] === (int) $actor['employee_id'] && fp_recurring_is_owner_requester($actor, $rule['OfficeID']);
}

function fp_recurring_can_view($actor, $rule)
{
    return fp_recurring_can_manage($actor, $rule)
        || (int) $rule['CreatedByEmployeeID'] === (int) $actor['employee_id'] && (int) $actor['employee_id'] > 0
        || fp_record_in_scope($actor, 'view', fp_recurring_scope_record($rule));
}

function fp_recurring_can_create($actor)
{
    return (int) $actor['employee_id'] > 0 && (fp_can($actor, 'pay') || fp_is_requester($actor));
}

/**
 * Offices where the actor may keep rules: array(officeId => office + 'Finance' flag).
 */
function fp_recurring_offices_for_actor($conn, $actor)
{
    if ((int) $actor['employee_id'] <= 0) {
        return array();
    }
    $rows = fp_db_rows(
        $conn,
        'SELECT o.ID, o.OfficeCode, o.OfficeName, o.CompanyID, o.StateID, q.CompanyName, s.StateName
         FROM branch_expense_offices o
         INNER JOIN quote_company_details q ON q.ID = o.CompanyID
         INNER JOIN state s ON s.ID = o.StateID
         WHERE o.IsActive = 1
         ORDER BY o.OfficeName'
    );
    $offices = array();
    foreach ($rows as $row) {
        $finance = fp_recurring_is_finance_for($actor, $row['ID'], $row['StateName']);
        if (!$finance && !fp_recurring_is_owner_requester($actor, $row['ID'])) {
            continue;
        }
        $offices[(int) $row['ID']] = array(
            'ID'          => (int) $row['ID'],
            'OfficeCode'  => $row['OfficeCode'],
            'OfficeName'  => $row['OfficeName'],
            'CompanyID'   => (int) $row['CompanyID'],
            'CompanyName' => $row['CompanyName'],
            'StateID'     => (int) $row['StateID'],
            'StateName'   => $row['StateName'],
            'Finance'     => $finance,
        );
    }
    return $offices;
}

/**
 * Active employees with an active "can raise requests" assignment at the office.
 */
function fp_recurring_office_requesters($conn, $officeIds)
{
    $ids = fp_unique_ints($officeIds);
    if (!$ids) {
        return array();
    }
    $rows = fp_db_rows(
        $conn,
        'SELECT oe.OfficeID, e.ID, e.Name, e.EmployeeNumber
         FROM branch_expense_office_employees oe
         INNER JOIN employees e ON e.ID = oe.EmployeeID AND e.IsActive = 1
         WHERE oe.IsActive = 1 AND oe.CanRequest = 1 AND oe.OfficeID IN (' . implode(',', $ids) . ')
         ORDER BY e.Name'
    );
    $out = array();
    foreach ($rows as $row) {
        $out[(int) $row['OfficeID']][] = array('ID' => (int) $row['ID'], 'Label' => $row['Name'] . ($row['EmployeeNumber'] ? ' (' . $row['EmployeeNumber'] . ')' : ''));
    }
    return $out;
}

function fp_recurring_owner_is_requester($conn, $officeId, $employeeId)
{
    $list = fp_recurring_office_requesters($conn, array($officeId))[(int) $officeId] ?? array();
    return in_array((int) $employeeId, array_column($list, 'ID'), true);
}

function fp_recurring_categories($conn)
{
    return array_filter(fp_request_categories($conn), function ($category) {
        return $category['RecurringAllowed'] === 1;
    });
}

function fp_recurring_generated_count($conn, $ruleId)
{
    return (int) fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt FROM branch_expense_requests WHERE RecurringRuleID = ?', 'i', array((int) $ruleId))[0]['Cnt'];
}

/* ------------------------------------------------------------------
   Create / edit
------------------------------------------------------------------ */

function fp_recurring_snapshot($rule)
{
    $snapshot = array();
    foreach (array('RuleNumber', 'Title', 'OfficeID', 'OwnerEmployeeID', 'CategoryID', 'LineDescription', 'VendorRegistrationID', 'VendorName', 'VendorGSTIN',
        'VendorAccountRef', 'Amount', 'AmountType', 'Frequency', 'StartDate', 'EndDate', 'DueDay', 'DueTiming', 'ReminderDays', 'NextPeriodStart', 'Status', 'Notes') as $field) {
        $snapshot[$field] = $rule[$field] ?? null;
    }
    return $snapshot;
}

function fp_recurring_input($post)
{
    return array(
        'ID'              => fp_input_int($post, 'ID'),
        'RowVersion'      => fp_input_int($post, 'RowVersion'),
        'Title'           => preg_replace('/\s+/u', ' ', fp_input_string($post, 'Title')),
        'OfficeID'        => fp_input_int($post, 'OfficeID'),
        'OwnerEmployeeID' => fp_input_int($post, 'OwnerEmployeeID'),
        'CategoryID'      => fp_input_int($post, 'CategoryID'),
        'LineDescription' => preg_replace('/\s+/u', ' ', fp_input_string($post, 'LineDescription')),
        'Amount'          => fp_input_string($post, 'Amount'),
        'AmountType'      => fp_input_string($post, 'AmountType'),
        'Frequency'       => fp_input_string($post, 'Frequency'),
        'StartDate'       => fp_input_string($post, 'StartDate'),
        'EndDate'         => fp_input_string($post, 'EndDate'),
        'DueDay'          => fp_input_int($post, 'DueDay'),
        'DueTiming'       => fp_input_string($post, 'DueTiming'),
        'ReminderDays'    => fp_input_string($post, 'ReminderDays') === '' ? -1 : fp_input_int($post, 'ReminderDays'),
        'Notes'           => fp_input_string($post, 'Notes'),
    ) + fp_vendor_input($post);
}

/**
 * Returns array(errors, clean). $existing is the stored rule when editing; $generated the number of drafts already created.
 */
function fp_recurring_validate($conn, $actor, $in, $existing, $generated)
{
    $errors = array();
    $clean = array();
    $today = date('Y-m-d');

    $titleLength = mb_strlen($in['Title']);
    if ($titleLength < 3 || $titleLength > 150) {
        $errors['Title'] = 'Enter a name of 3-150 characters, e.g. "Office rent".';
    }
    $clean['Title'] = $in['Title'];

    $offices = fp_recurring_offices_for_actor($conn, $actor);
    $office = $offices[$in['OfficeID']] ?? null;
    if (!$office) {
        $errors['OfficeID'] = 'Choose a branch office you manage recurring expenses for.';
    } else {
        $clean['OfficeID'] = $office['ID'];
        $clean['CompanyID'] = $office['CompanyID'];
        $clean['StateID'] = $office['StateID'];
        $ownerId = $office['Finance'] ? $in['OwnerEmployeeID'] : (int) $actor['employee_id'];
        if ($ownerId <= 0) {
            $errors['OwnerEmployeeID'] = 'Choose who will check and submit the bills.';
        } elseif (!fp_recurring_owner_is_requester($conn, $office['ID'], $ownerId)) {
            $errors['OwnerEmployeeID'] = $office['Finance']
                ? 'This employee cannot raise requests at this office. Choose a requester of the office.'
                : 'You are not set up to raise requests at this office.';
        }
        $clean['OwnerEmployeeID'] = $ownerId;
    }

    $category = fp_recurring_categories($conn)[$in['CategoryID']] ?? null;
    if (!$category) {
        $errors['CategoryID'] = 'Choose an active category that allows recurring expenses.';
    } elseif ($office && $category['CompanyID'] !== 0 && $category['CompanyID'] !== $office['CompanyID']) {
        $errors['CategoryID'] = 'This category is not available for the office\'s legal entity.';
    }
    $clean['CategoryID'] = $in['CategoryID'];

    $description = $in['LineDescription'] !== '' ? $in['LineDescription'] : $in['Title'];
    if (mb_strlen($description) < 2 || mb_strlen($description) > 500) {
        $errors['LineDescription'] = 'Enter 2-500 characters.';
    }
    $clean['LineDescription'] = $description;

    list($vendorErrors, $vendor) = fp_vendor_resolve($conn, $in, true);
    $errors = array_merge($errors, $vendorErrors);
    $clean += $vendor;

    $amount = fp_parse_paise($in['Amount']);
    if ($amount === false || $amount <= 0 || $amount > FP_REQUEST_MAX_AMOUNT_PAISE) {
        $errors['Amount'] = 'Enter the amount per period (greater than zero, up to 2 decimals).';
        $amount = 0;
    }
    $clean['Amount'] = fp_paise_to_decimal($amount);
    if (!isset(fp_recurring_amount_types()[$in['AmountType']])) {
        $errors['AmountType'] = 'Choose whether the amount is fixed or an estimate.';
    }
    $clean['AmountType'] = $in['AmountType'];

    $locked = $existing && $generated > 0;
    if ($locked) {
        $clean['Frequency'] = $existing['Frequency'];
        $clean['StartDate'] = $existing['StartDate'];
        if ($in['Frequency'] !== $existing['Frequency'] || ($in['StartDate'] !== '' && $in['StartDate'] !== $existing['StartDate'])) {
            $errors['Frequency'] = 'Drafts were already created from this rule, so its frequency and start cannot change. End it and create a new rule instead.';
        }
    } else {
        if (!isset(fp_recurring_frequencies()[$in['Frequency']])) {
            $errors['Frequency'] = 'Choose how often the bill comes.';
        }
        $clean['Frequency'] = $in['Frequency'];
        $start = fp_parse_date($in['StartDate']);
        $earliest = date('Y-m-01', strtotime(date('Y-m-01') . ' -1 month'));
        if (!$start || substr($start, 8, 2) !== '01') {
            $errors['StartDate'] = 'Enter the first day of the month the first period starts.';
        } elseif ($start < $earliest && (!$existing || $start !== $existing['StartDate'])) {
            $errors['StartDate'] = 'The first period cannot start before ' . fp_timeline_date($earliest) . '. Book older bills as normal direct expenses.';
        } elseif ($start > date('Y-m-d', strtotime('+1 year'))) {
            $errors['StartDate'] = 'The first period must start within one year.';
        }
        $clean['StartDate'] = $start ?: null;
    }

    $end = fp_parse_date($in['EndDate']);
    if ($end === false) {
        $errors['EndDate'] = 'Enter a valid date.';
    } elseif ($end !== null && !empty($clean['StartDate']) && $end < $clean['StartDate']) {
        $errors['EndDate'] = 'The end date cannot be before the start.';
    }
    $clean['EndDate'] = $end ?: null;

    if ($in['DueDay'] < 1 || $in['DueDay'] > 28) {
        $errors['DueDay'] = 'Enter a day from 1 to 28.';
    }
    $clean['DueDay'] = $in['DueDay'];
    if (!isset(fp_recurring_due_timings()[$in['DueTiming']])) {
        $errors['DueTiming'] = 'Choose when the bill is due.';
    }
    $clean['DueTiming'] = $in['DueTiming'];
    if ($in['ReminderDays'] < 0 || $in['ReminderDays'] > 60) {
        $errors['ReminderDays'] = 'Enter 0-60 days.';
    }
    $clean['ReminderDays'] = max(0, $in['ReminderDays']);
    if (mb_strlen($in['Notes']) > 1000) {
        $errors['Notes'] = 'At most 1000 characters.';
    }
    $clean['Notes'] = $in['Notes'];

    if (!$errors) {
        if (!$locked) {
            $clean['NextPeriodStart'] = fp_recurring_within_end($clean, $clean['StartDate']);
        } else {
            $next = $existing['NextPeriodStart'];
            if ($next === null) {
                $last = fp_db_rows($conn, 'SELECT MAX(PeriodFrom) AS Last FROM branch_expense_requests WHERE RecurringRuleID = ?', 'i', array((int) $existing['ID']))[0]['Last'];
                $next = $last ? fp_recurring_add_months($last, fp_recurring_frequencies()[$clean['Frequency']]['months']) : $clean['StartDate'];
                if ($existing['Status'] === 'active') {
                    $next = fp_recurring_first_open_start($clean, $next, $today)[0];
                }
            }
            $clean['NextPeriodStart'] = fp_recurring_within_end($clean, $next);
        }
    }
    return array($errors, $clean);
}

function fp_recurring_save($conn, $actor, $post)
{
    $in = fp_recurring_input($post);
    if (!fp_recurring_can_create($actor)) {
        return fp_result(false, 403, 'You do not have permission to manage recurring expenses.');
    }
    return fp_transaction($conn, function () use ($conn, $actor, $in) {
        $existing = null;
        $generated = 0;
        if ($in['ID'] > 0) {
            $existing = fp_recurring_get($conn, $in['ID'], 'update');
            if (!$existing || !fp_recurring_can_view($actor, $existing)) {
                return fp_result(false, 404, 'Recurring expense not found.');
            }
            if (!fp_recurring_can_manage($actor, $existing)) {
                return fp_result(false, 403, 'You cannot change this recurring expense.');
            }
            if ($existing['Status'] === 'ended') {
                return fp_result(false, 422, 'This recurring expense has ended and cannot be changed.');
            }
            if ((int) $existing['RowVersion'] !== $in['RowVersion']) {
                return fp_result(false, 409, 'This recurring expense was changed in another window. Reload and try again.');
            }
            $generated = fp_recurring_generated_count($conn, $existing['ID']);
        }
        list($errors, $clean) = fp_recurring_validate($conn, $actor, $in, $existing, $generated);
        if ($errors) {
            return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
        }
        $now = date('Y-m-d H:i:s');
        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];

        if (!$existing) {
            $number = fp_sequence_next($conn, 'RR', $now);
            fp_policy_exec(
                $conn,
                'INSERT INTO branch_expense_recurring_rules
                    (RuleNumber, Title, CompanyID, StateID, OfficeID, OwnerEmployeeID, CategoryID, LineDescription, VendorRegistrationID, VendorName, VendorGSTIN,
                     VendorAccountRef, Amount, AmountType, Frequency, StartDate, EndDate, DueDay, DueTiming, ReminderDays, NextPeriodStart, Status, Notes,
                     RowVersion, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'active\', ?, 1, ?, ?, ?)',
                'ssiiiiisissssssssisissiis',
                array($number, $clean['Title'], $clean['CompanyID'], $clean['StateID'], $clean['OfficeID'], $clean['OwnerEmployeeID'], $clean['CategoryID'],
                      $clean['LineDescription'], $clean['VendorRegistrationID'], $clean['VendorName'], $clean['VendorGSTIN'], $clean['VendorAccountRef'],
                      $clean['Amount'], $clean['AmountType'], $clean['Frequency'], $clean['StartDate'], $clean['EndDate'], $clean['DueDay'], $clean['DueTiming'],
                      $clean['ReminderDays'], $clean['NextPeriodStart'], $clean['Notes'], $userId, $employeeId, $now)
            );
            $id = (int) $conn->insert_id;
            $saved = fp_recurring_get($conn, $id);
            fp_audit_log($conn, $actor, FP_RECURRING_ENTITY, $id, 'create', null, fp_recurring_snapshot($saved));
            $first = $saved['NextPeriodStart'] ? fp_recurring_period($saved, $saved['NextPeriodStart']) : null;
            $message = 'Recurring expense ' . $number . ' saved.'
                . ($first ? ' The first draft (' . $first['label'] . ', due ' . fp_timeline_date($first['due']) . ') will be created on ' . fp_timeline_date(max($first['create'], date('Y-m-d'))) . '.' : '');
            return fp_result(true, 200, $message, array('data' => array('ID' => $id, 'RuleNumber' => $number)));
        }

        $old = fp_recurring_snapshot($existing);
        $affected = fp_policy_exec(
            $conn,
            'UPDATE branch_expense_recurring_rules
             SET Title = ?, CompanyID = ?, StateID = ?, OfficeID = ?, OwnerEmployeeID = ?, CategoryID = ?, LineDescription = ?, VendorRegistrationID = ?,
                 VendorName = ?, VendorGSTIN = ?, VendorAccountRef = ?, Amount = ?, AmountType = ?, Frequency = ?, StartDate = ?, EndDate = ?, DueDay = ?,
                 DueTiming = ?, ReminderDays = ?, NextPeriodStart = ?, Notes = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND RowVersion = ? AND Status <> \'ended\'',
            'siiiiisissssssssisissiisii',
            array($clean['Title'], $clean['CompanyID'], $clean['StateID'], $clean['OfficeID'], $clean['OwnerEmployeeID'], $clean['CategoryID'], $clean['LineDescription'],
                  $clean['VendorRegistrationID'], $clean['VendorName'], $clean['VendorGSTIN'], $clean['VendorAccountRef'], $clean['Amount'], $clean['AmountType'],
                  $clean['Frequency'], $clean['StartDate'], $clean['EndDate'], $clean['DueDay'], $clean['DueTiming'], $clean['ReminderDays'], $clean['NextPeriodStart'],
                  $clean['Notes'], $userId, $employeeId, $now, $existing['ID'], $in['RowVersion'])
        );
        if ($affected !== 1) {
            return fp_result(false, 409, 'This recurring expense was changed in another window. Reload and try again.');
        }
        $new = fp_recurring_snapshot(fp_recurring_get($conn, $existing['ID']));
        if (fp_audit_changed_fields($old, $new)) {
            fp_audit_log($conn, $actor, FP_RECURRING_ENTITY, $existing['ID'], 'update', $old, $new);
        }
        return fp_result(true, 200, 'Recurring expense ' . $existing['RuleNumber'] . ' saved. Drafts already created are not changed.', array('data' => array('ID' => (int) $existing['ID'])));
    });
}

/**
 * Pause, resume or end a rule. Post: ID, RowVersion, Status (active | paused | ended), Reason.
 */
function fp_recurring_set_status($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $status = fp_input_string($post, 'Status');
    $reason = preg_replace('/[ \t]+/', ' ', fp_input_string($post, 'Reason'));
    if ($id <= 0 || !isset(fp_recurring_statuses()[$status])) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => array('Reason' => 'At most 500 characters.')));
    }
    if ($status === 'ended' && mb_strlen($reason) < FP_RECURRING_REASON_MIN) {
        return fp_result(false, 422, 'Please write why the recurring expense is ended.', array('errors' => array('Reason' => 'Write at least ' . FP_RECURRING_REASON_MIN . ' characters.')));
    }
    return fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $status, $reason) {
        $rule = fp_recurring_get($conn, $id, 'update');
        if (!$rule || !fp_recurring_can_view($actor, $rule)) {
            return fp_result(false, 404, 'Recurring expense not found.');
        }
        if (!fp_recurring_can_manage($actor, $rule)) {
            return fp_result(false, 403, 'You cannot change this recurring expense.');
        }
        if ((int) $rule['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This recurring expense was changed in another window. Reload and try again.');
        }
        if ($rule['Status'] === 'ended') {
            return fp_result(false, 422, 'This recurring expense has already ended.');
        }
        if ($rule['Status'] === $status) {
            return fp_result(false, 422, 'This recurring expense is already ' . strtolower(fp_recurring_statuses()[$status]) . '.');
        }
        $next = $rule['NextPeriodStart'];
        $skipped = 0;
        if ($status === 'active') {
            list($next, $skipped) = fp_recurring_first_open_start($rule, fp_recurring_within_end($rule, $next), date('Y-m-d'));
        } elseif ($status === 'ended') {
            $next = null;
        }
        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            'UPDATE branch_expense_recurring_rules SET Status = ?, StatusReason = ?, NextPeriodStart = ?, LastRunNote = \'\', RowVersion = RowVersion + 1,
                 UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ? AND RowVersion = ?',
            'sssiisii',
            array($status, $reason, $next, (int) $actor['user_id'], (int) $actor['employee_id'], $now, $id, $rowVersion)
        );
        $new = array('Status' => $status, 'NextPeriodStart' => $next);
        if ($skipped) {
            $new['SkippedPeriods'] = $skipped;
        }
        $verb = array('active' => 'resumed', 'paused' => 'paused', 'ended' => 'ended');
        fp_audit_log($conn, $actor, FP_RECURRING_ENTITY, $id, $status === 'active' ? 'resume' : ($status === 'paused' ? 'pause' : 'end'),
            array('Status' => $rule['Status'], 'NextPeriodStart' => $rule['NextPeriodStart']), $new, $reason);
        $message = $rule['RuleNumber'] . ' ' . $verb[$status] . '.';
        if ($skipped) {
            $message .= ' ' . $skipped . ' period(s) already past their due date were skipped; book them as normal direct expenses if they are still unpaid.';
        }
        if ($status !== 'active') {
            $message .= ' Drafts already created are not changed; cancel them from My Requests if they are not needed.';
        }
        return fp_result(true, 200, $message);
    });
}

/* ------------------------------------------------------------------
   Draft generation
------------------------------------------------------------------ */

/**
 * Why a draft cannot be created for the rule now, or '' when it can.
 */
function fp_recurring_blocker($conn, $rule)
{
    if ((int) $rule['OfficeIsActive'] !== 1) {
        return 'The branch office is inactive.';
    }
    if (!fp_recurring_owner_is_requester($conn, $rule['OfficeID'], $rule['OwnerEmployeeID'])) {
        return 'The owner can no longer raise requests at this office. Choose another owner.';
    }
    $category = fp_recurring_categories($conn)[(int) $rule['CategoryID']] ?? null;
    if (!$category || ($category['CompanyID'] !== 0 && $category['CompanyID'] !== (int) $rule['CompanyID'])) {
        return 'The category is inactive or no longer allows recurring expenses. Choose another category.';
    }
    if ((int) $rule['VendorRegistrationID'] > 0 && !fp_vendor_get($conn, $rule['VendorRegistrationID'])) {
        return 'The vendor is no longer an approved, active vendor in the vendor master.';
    }
    return '';
}

/**
 * Creates the draft for the rule's next period. Call inside a transaction with the rule row locked.
 * Returns fp_result; on success data has ID, ExpenseNumber and Period.
 */
function fp_recurring_generate_next($conn, $actor, $rule)
{
    if ($rule['Status'] !== 'active' || !$rule['NextPeriodStart']) {
        return fp_result(false, 422, 'There is no period left to create for this recurring expense.');
    }
    $blocker = fp_recurring_blocker($conn, $rule);
    if ($blocker !== '') {
        return fp_result(false, 422, $blocker);
    }
    $period = fp_recurring_period($rule, $rule['NextPeriodStart']);
    $now = date('Y-m-d H:i:s');
    $today = substr($now, 0, 10);
    $next = fp_recurring_within_end($rule, $period['next']);

    $exists = fp_db_rows($conn, 'SELECT ExpenseNumber FROM branch_expense_requests WHERE RecurringRuleID = ? AND RecurringPeriod = ?', 'is', array((int) $rule['ID'], $period['key']));
    if (!$exists) {
        $number = fp_request_next_number($conn, $now);
        $purpose = mb_substr($rule['Title'] . ' - ' . $period['label'], 0, 1000);
        $remarks = 'Created automatically from recurring expense ' . $rule['RuleNumber'] . ' for ' . $period['label'] . '.'
            . ($rule['AmountType'] === 'estimate' ? ' The amount is an estimate: enter the actual bill amount.' : '')
            . ' Attach the bill and submit for approval.';
        fp_policy_exec(
            $conn,
            'INSERT INTO branch_expense_requests
                (ExpenseNumber, RequestType, CompanyID, StateID, OfficeID, EmployeeID, Purpose, PeriodFrom, PeriodTo, IsRecurring, Remarks,
                 VendorRegistrationID, VendorName, VendorGSTIN, VendorAccountRef, DueDate, RecurringRuleID, RecurringPeriod,
                 TotalAmount, ItemCount, Status, PaymentStatus, SettlementStatus, RowVersion, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
             VALUES (?, \'direct\', ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, \'draft\', \'unpaid\', \'not_applicable\', 1, ?, 0, ?)',
            'siiiissssissssissis',
            array($number, (int) $rule['CompanyID'], (int) $rule['StateID'], (int) $rule['OfficeID'], (int) $rule['OwnerEmployeeID'], $purpose,
                  $period['from'], $period['to'], $remarks, $rule['VendorRegistrationID'] === null ? null : (int) $rule['VendorRegistrationID'],
                  $rule['VendorName'], $rule['VendorGSTIN'], $rule['VendorAccountRef'], $period['due'], (int) $rule['ID'], $period['key'],
                  $rule['Amount'], (int) $actor['user_id'], $now)
        );
        $requestId = (int) $conn->insert_id;
        fp_request_write_items($conn, $requestId, array(array(
            'ItemID' => 0, 'CategoryID' => (int) $rule['CategoryID'], 'Description' => mb_substr($rule['LineDescription'] . ' (' . $period['label'] . ')', 0, 500),
            'ExpenseDate' => $today, 'VendorName' => $rule['VendorName'], 'VendorGSTIN' => $rule['VendorGSTIN'], 'InvoiceNumber' => '', 'InvoiceDate' => null,
            'Amount' => $rule['Amount'], 'TaxAmount' => '0.00',
        )), array());
        $snapshot = fp_request_snapshot(fp_request_get($conn, $requestId), fp_request_items($conn, $requestId));
        $snapshot['RecurringRule'] = $rule['RuleNumber'] . ' ' . $rule['Title'] . ' (' . $period['label'] . ')';
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $requestId, 'recurring_create', null, $snapshot);
        fp_audit_log($conn, $actor, FP_RECURRING_ENTITY, $rule['ID'], 'generate', null, array('Period' => $period['label'], 'ExpenseNumber' => $number, 'DueDate' => $period['due'], 'NextPeriodStart' => $next));
        fp_notice_add('recurring', $requestId);
    } else {
        $number = $exists[0]['ExpenseNumber'];
        $requestId = 0;
    }
    fp_policy_exec(
        $conn,
        'UPDATE branch_expense_recurring_rules SET NextPeriodStart = ?, LastGeneratedAt = ?, LastRunNote = \'\', RowVersion = RowVersion + 1 WHERE ID = ?',
        'ssi',
        array($next, $now, (int) $rule['ID'])
    );
    return fp_result(true, 200, 'Draft ' . $number . ' created for ' . $period['label'] . ' (due ' . fp_timeline_date($period['due']) . '). The owner must check it, attach the bill and submit it for approval.', array(
        'data' => array('ID' => $requestId, 'ExpenseNumber' => $number, 'Period' => $period['label']),
    ));
}

/**
 * "Create draft now" for the next period, allowed up to FP_RECURRING_MANUAL_DAYS before it is due. Post: ID, RowVersion.
 */
function fp_recurring_generate_now($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    if ($id <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    $result = fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion) {
        $rule = fp_recurring_get($conn, $id, 'update');
        if (!$rule || !fp_recurring_can_view($actor, $rule)) {
            return fp_result(false, 404, 'Recurring expense not found.');
        }
        if (!fp_recurring_can_manage($actor, $rule)) {
            return fp_result(false, 403, 'You cannot create drafts for this recurring expense.');
        }
        if ((int) $rule['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This recurring expense was changed in another window. Reload and try again.');
        }
        if ($rule['Status'] !== 'active') {
            return fp_result(false, 422, 'Only active recurring expenses create drafts.');
        }
        if (!$rule['NextPeriodStart']) {
            return fp_result(false, 422, 'There is no period left to create for this recurring expense.');
        }
        $period = fp_recurring_period($rule, $rule['NextPeriodStart']);
        $limit = date('Y-m-d', strtotime('+' . FP_RECURRING_MANUAL_DAYS . ' days'));
        if ($period['due'] > $limit) {
            return fp_result(false, 422, 'The next bill (' . $period['label'] . ') is due on ' . fp_timeline_date($period['due']) . '. Drafts can be created at most ' . FP_RECURRING_MANUAL_DAYS . ' days before the due date.');
        }
        return fp_recurring_generate_next($conn, $actor, $rule);
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/**
 * Daily run: creates the drafts that are due to be prepared (at most FP_RECURRING_CATCH_UP periods per rule).
 * Returns array(created, blocked).
 */
function fp_recurring_process_due($conn, $today, $limit = 500)
{
    $system = array('user_id' => 0, 'employee_id' => 0, 'username' => 'System (recurring expenses)');
    $rules = fp_db_rows(
        $conn,
        "SELECT ID FROM branch_expense_recurring_rules WHERE Status = 'active' AND NextPeriodStart IS NOT NULL ORDER BY NextPeriodStart, ID LIMIT " . (int) $limit
    );
    $created = 0;
    $blocked = 0;
    foreach ($rules as $row) {
        for ($i = 0; $i < FP_RECURRING_CATCH_UP; $i++) {
            $note = '';
            $result = fp_transaction($conn, function () use ($conn, $system, $row, $today, &$note) {
                $rule = fp_recurring_get($conn, $row['ID'], 'update');
                if (!$rule || $rule['Status'] !== 'active' || !$rule['NextPeriodStart']) {
                    return fp_result(false, 204, '');
                }
                if (fp_recurring_period($rule, $rule['NextPeriodStart'])['create'] > $today) {
                    return fp_result(false, 204, '');
                }
                $generated = fp_recurring_generate_next($conn, $system, $rule);
                if (!$generated['ok']) {
                    $note = $generated['message'];
                }
                return $generated;
            });
            fp_notice_finish($conn, $result);
            if ($result['ok']) {
                $created++;
                continue;
            }
            if ($note !== '') {
                fp_policy_exec($conn, 'UPDATE branch_expense_recurring_rules SET LastRunNote = ? WHERE ID = ? AND LastRunNote <> ?', 'sis', array(mb_substr($note, 0, 500), (int) $row['ID'], mb_substr($note, 0, 500)));
                $blocked++;
            }
            break;
        }
    }
    return array($created, $blocked);
}

/**
 * Generated drafts keep the rule's type, office, period and recurring flag.
 */
function fp_recurring_lock_draft_input($request, $in)
{
    $in['RequestType'] = 'direct';
    $in['OfficeID'] = (int) $request['OfficeID'];
    $in['PeriodFrom'] = (string) $request['PeriodFrom'];
    $in['PeriodTo'] = (string) $request['PeriodTo'];
    $in['IsRecurring'] = 1;
    return $in;
}

/* ------------------------------------------------------------------
   Lists and detail
------------------------------------------------------------------ */

function fp_recurring_list_filter($conn, $actor, $params)
{
    $me = (int) $actor['employee_id'];
    $parts = array(
        fp_scope_where($conn, $actor, 'view', array('employee_id' => 'rr.OwnerEmployeeID', 'state_name' => 's.StateName', 'office_id' => 'rr.OfficeID')),
        fp_scope_where($conn, $actor, 'pay', array('state_name' => 's.StateName', 'office_id' => 'rr.OfficeID')),
    );
    if ($me > 0) {
        $parts[] = 'rr.CreatedByEmployeeID = ' . $me;
    }
    $base = '(' . implode(' OR ', $parts) . ')';
    $where = array($base);
    $types = '';
    $values = array();
    $status = fp_input_string($params, 'status');
    if (isset(fp_recurring_statuses()[$status])) {
        $where[] = 'rr.Status = ?';
        $types .= 's';
        $values[] = $status;
    }
    $frequency = fp_input_string($params, 'frequency');
    if (isset(fp_recurring_frequencies()[$frequency])) {
        $where[] = 'rr.Frequency = ?';
        $types .= 's';
        $values[] = $frequency;
    }
    $officeId = fp_input_int($params, 'office');
    if ($officeId > 0) {
        $where[] = 'rr.OfficeID = ?';
        $types .= 'i';
        $values[] = $officeId;
    }
    if (fp_input_string($params, 'attention') === '1') {
        $where[] = "rr.LastRunNote <> ''";
    }
    $search = mb_substr(fp_input_string($params, 'q'), 0, 100);
    if ($search !== '') {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $where[] = '(rr.RuleNumber LIKE ? OR rr.Title LIKE ? OR rr.VendorName LIKE ? OR o.OfficeName LIKE ? OR e.Name LIKE ?)';
        $types .= 'sssss';
        array_push($values, $like, $like, $like, $like, $like);
    }
    return array('base' => $base, 'where' => implode(' AND ', $where), 'types' => $types, 'values' => $values);
}

function fp_recurring_list_from()
{
    return ' FROM branch_expense_recurring_rules rr
             INNER JOIN branch_expense_offices o ON o.ID = rr.OfficeID
             LEFT JOIN state s ON s.ID = rr.StateID
             LEFT JOIN employees e ON e.ID = rr.OwnerEmployeeID ';
}

function fp_recurring_list($conn, $actor, $params)
{
    $filter = fp_recurring_list_filter($conn, $actor, $params);
    $start = max(0, fp_input_int($params, 'start'));
    $length = fp_input_int($params, 'length');
    $length = $length <= 0 ? 25 : min($length, 100);
    $total = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt' . fp_recurring_list_from() . 'WHERE ' . $filter['base']);
    $filtered = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt' . fp_recurring_list_from() . 'WHERE ' . $filter['where'], $filter['types'], $filter['values']);
    $rows = fp_db_rows(
        $conn,
        'SELECT rr.*, o.OfficeCode, o.OfficeName, s.StateName, e.Name AS OwnerName,
                (SELECT COUNT(*) FROM branch_expense_requests r WHERE r.RecurringRuleID = rr.ID) AS DraftCount,
                (SELECT COUNT(*) FROM branch_expense_requests r WHERE r.RecurringRuleID = rr.ID AND r.Status NOT IN (\'rejected\', \'cancelled\') AND r.PaymentStatus <> \'paid\') AS OpenCount'
        . fp_recurring_list_from() . 'WHERE ' . $filter['where']
        . ' ORDER BY FIELD(rr.Status, \'active\', \'paused\', \'ended\'), rr.NextPeriodStart IS NULL, rr.NextPeriodStart, rr.ID DESC LIMIT ? OFFSET ?',
        $filter['types'] . 'ii',
        array_merge($filter['values'], array($length, $start))
    );
    $data = array();
    foreach ($rows as $row) {
        $next = $row['Status'] !== 'ended' && $row['NextPeriodStart'] ? fp_recurring_period($row, $row['NextPeriodStart']) : null;
        $data[] = array(
            'ID'          => (int) $row['ID'],
            'RuleNumber'  => $row['RuleNumber'],
            'Title'       => $row['Title'],
            'Vendor'      => $row['VendorName'],
            'Registered'  => (int) $row['VendorRegistrationID'] > 0,
            'Office'      => $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')',
            'Owner'       => (string) $row['OwnerName'],
            'Amount'      => $row['Amount'],
            'AmountType'  => $row['AmountType'],
            'Frequency'   => fp_recurring_frequencies()[$row['Frequency']]['label'] ?? $row['Frequency'],
            'Status'      => $row['Status'],
            'StatusLabel' => fp_recurring_statuses()[$row['Status']] ?? $row['Status'],
            'NextPeriod'  => $next ? $next['label'] : '',
            'NextDue'     => $next ? $next['due'] : '',
            'NextCreate'  => $next ? $next['create'] : '',
            'DraftCount'  => (int) $row['DraftCount'],
            'OpenCount'   => (int) $row['OpenCount'],
            'LastRunNote' => $row['LastRunNote'],
            'RowVersion'  => (int) $row['RowVersion'],
            'CanManage'   => fp_recurring_can_manage($actor, $row),
        );
    }
    return array(
        'draw'            => fp_input_int($params, 'draw'),
        'recordsTotal'    => (int) $total[0]['Cnt'],
        'recordsFiltered' => (int) $filtered[0]['Cnt'],
        'data'            => $data,
    );
}

function fp_recurring_detail($conn, $actor, $ruleId)
{
    $rule = $ruleId > 0 ? fp_recurring_get($conn, $ruleId) : null;
    if (!$rule || !fp_recurring_can_view($actor, $rule)) {
        return fp_result(false, 404, 'Recurring expense not found.');
    }
    $requests = array();
    foreach (fp_db_rows(
        $conn,
        'SELECT r.ID, r.ExpenseNumber, r.Status, r.PaymentStatus, r.PeriodFrom, r.PeriodTo, r.DueDate, r.TotalAmount, r.EmployeeID, r.CreatedByEmployeeID, r.OfficeID,
                COALESCE((SELECT SUM(p.Amount) FROM branch_expense_payments p WHERE p.RequestID = r.ID AND p.Status = \'completed\'), 0) AS PaidAmount
         FROM branch_expense_requests r WHERE r.RecurringRuleID = ? ORDER BY r.PeriodFrom DESC, r.ID DESC LIMIT 60',
        'i',
        array((int) $rule['ID'])
    ) as $row) {
        $requests[] = array(
            'ID'            => (int) $row['ID'],
            'ExpenseNumber' => $row['ExpenseNumber'],
            'Period'        => fp_recurring_period_label($row['PeriodFrom'], $row['PeriodTo']),
            'DueDate'       => (string) $row['DueDate'],
            'TotalAmount'   => $row['TotalAmount'],
            'PaidAmount'    => fp_paise_to_decimal(fp_decimal_to_paise($row['PaidAmount'])),
            'StatusLabel'   => fp_request_statuses()[$row['Status']] ?? $row['Status'],
            'PaymentStatusLabel' => fp_payment_request_statuses()[$row['PaymentStatus']] ?? $row['PaymentStatus'],
            'NextStep'      => fp_direct_next_step($row),
            'CanOpen'       => fp_request_can_view($actor, $row + array('StateName' => (string) $rule['StateName'])),
        );
    }
    $vendorLabel = '';
    if ((int) $rule['VendorRegistrationID'] > 0) {
        $label = fp_vendor_labels($conn, array($rule['VendorRegistrationID']))[(int) $rule['VendorRegistrationID']] ?? null;
        $vendorLabel = $label ? $label['Code'] . ($label['Approved'] ? '' : ' (no longer approved)') : '';
    }
    $canManage = fp_recurring_can_manage($actor, $rule);
    $next = $rule['Status'] === 'active' && $rule['NextPeriodStart'] ? fp_recurring_period($rule, $rule['NextPeriodStart']) : null;
    $history = array();
    foreach (fp_audit_history($conn, FP_RECURRING_ENTITY, $rule['ID']) as $entry) {
        $history[] = array(
            'Action'    => $entry['Action'],
            'Actor'     => $entry['ActorUserName'],
            'At'        => $entry['CreatedAt'],
            'Remarks'   => $entry['Remarks'],
            'Changed'   => $entry['ChangedFields'],
            'Old'       => $entry['OldValues'],
            'New'       => $entry['NewValues'],
        );
    }
    return fp_result(true, 200, 'OK', array('data' => array(
        'rule' => array(
            'ID'          => (int) $rule['ID'],
            'RuleNumber'  => $rule['RuleNumber'],
            'Title'       => $rule['Title'],
            'Office'      => $rule['OfficeName'] . ' (' . $rule['OfficeCode'] . ')',
            'State'       => (string) $rule['StateName'],
            'Company'     => (string) $rule['CompanyName'],
            'Owner'       => trim($rule['OwnerName'] . ($rule['OwnerNumber'] ? ' (' . $rule['OwnerNumber'] . ')' : '')),
            'Category'    => ($rule['ParentCategoryName'] ? $rule['ParentCategoryName'] . ' > ' : '') . $rule['CategoryName'],
            'LineDescription' => $rule['LineDescription'],
            'Vendor'      => $rule['VendorName'],
            'VendorCode'  => $vendorLabel,
            'VendorGSTIN' => $rule['VendorGSTIN'],
            'VendorAccountRef' => $rule['VendorAccountRef'],
            'Amount'      => $rule['Amount'],
            'AmountType'  => fp_recurring_amount_types()[$rule['AmountType']] ?? $rule['AmountType'],
            'Frequency'   => fp_recurring_frequencies()[$rule['Frequency']]['label'] ?? $rule['Frequency'],
            'StartDate'   => $rule['StartDate'],
            'EndDate'     => (string) $rule['EndDate'],
            'DueDay'      => (int) $rule['DueDay'],
            'DueTiming'   => fp_recurring_due_timings()[$rule['DueTiming']] ?? $rule['DueTiming'],
            'ReminderDays' => (int) $rule['ReminderDays'],
            'Status'      => $rule['Status'],
            'StatusLabel' => fp_recurring_statuses()[$rule['Status']] ?? $rule['Status'],
            'StatusReason' => $rule['StatusReason'],
            'Notes'       => $rule['Notes'],
            'LastRunNote' => $rule['LastRunNote'],
            'LastGeneratedAt' => (string) $rule['LastGeneratedAt'],
            'RowVersion'  => (int) $rule['RowVersion'],
        ),
        'upcoming'     => fp_recurring_upcoming($rule),
        'requests'     => $requests,
        'history'      => $history,
        'can_manage'   => $canManage,
        'can_generate' => $canManage && $next !== null && $next['due'] <= date('Y-m-d', strtotime('+' . FP_RECURRING_MANUAL_DAYS . ' days')),
    )));
}

/**
 * Data for the rule form: offices the actor may manage, requesters per office (Finance only), categories and choices.
 */
function fp_recurring_form_data($conn, $actor, $ruleId)
{
    if (!fp_recurring_can_create($actor)) {
        return fp_result(false, 403, 'You do not have permission to manage recurring expenses.');
    }
    $offices = fp_recurring_offices_for_actor($conn, $actor);
    $financeOffices = array_keys(array_filter($offices, function ($office) {
        return $office['Finance'];
    }));
    $categories = array();
    foreach (fp_recurring_categories($conn) as $category) {
        $categories[] = array('ID' => $category['ID'], 'Label' => $category['Label'], 'CompanyID' => $category['CompanyID']);
    }
    $data = array(
        'offices'     => array_values($offices),
        'owners'      => fp_recurring_office_requesters($conn, $financeOffices),
        'me'          => array('ID' => (int) $actor['employee_id'], 'Label' => (string) $actor['employee_name']),
        'categories'  => $categories,
        'frequencies' => array_map(function ($frequency) {
            return $frequency['label'];
        }, fp_recurring_frequencies()),
        'timings'     => fp_recurring_due_timings(),
        'amountTypes' => fp_recurring_amount_types(),
        'vendorMode'  => fp_vendor_master_mode(),
        'earliestStart' => date('Y-m-01', strtotime(date('Y-m-01') . ' -1 month')),
        'rule'        => null,
        'locked'      => false,
    );
    if ($ruleId > 0) {
        $rule = fp_recurring_get($conn, $ruleId);
        if (!$rule || !fp_recurring_can_view($actor, $rule)) {
            return fp_result(false, 404, 'Recurring expense not found.');
        }
        if (!fp_recurring_can_manage($actor, $rule) || $rule['Status'] === 'ended') {
            return fp_result(false, 403, 'You cannot change this recurring expense.');
        }
        $data['rule'] = array(
            'ID' => (int) $rule['ID'], 'RuleNumber' => $rule['RuleNumber'], 'RowVersion' => (int) $rule['RowVersion'], 'Title' => $rule['Title'],
            'OfficeID' => (int) $rule['OfficeID'], 'OwnerEmployeeID' => (int) $rule['OwnerEmployeeID'], 'OwnerLabel' => (string) $rule['OwnerName'],
            'CategoryID' => (int) $rule['CategoryID'], 'LineDescription' => $rule['LineDescription'],
            'VendorRegistrationID' => (int) $rule['VendorRegistrationID'], 'VendorName' => $rule['VendorName'], 'VendorGSTIN' => $rule['VendorGSTIN'],
            'VendorAccountRef' => $rule['VendorAccountRef'], 'Amount' => $rule['Amount'], 'AmountType' => $rule['AmountType'], 'Frequency' => $rule['Frequency'],
            'StartDate' => $rule['StartDate'], 'EndDate' => (string) $rule['EndDate'], 'DueDay' => (int) $rule['DueDay'], 'DueTiming' => $rule['DueTiming'],
            'ReminderDays' => (int) $rule['ReminderDays'], 'Notes' => $rule['Notes'],
        );
        $data['locked'] = fp_recurring_generated_count($conn, $rule['ID']) > 0;
    }
    return fp_result(true, 200, 'OK', array('data' => $data));
}
