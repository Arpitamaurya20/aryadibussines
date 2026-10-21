<?php
/**
 * Direct expenses: requests paid by Head Office straight to a vendor or landlord (RequestType 'direct'),
 * with a due date, partial payments and reminders. Loaded by fp_requests.php.
 *
 * Approval and payment use the normal request workflow (approval policies, payment register); nothing here
 * submits, approves or pays a request.
 */

const FP_DIRECT_EXPORT_LIMIT = 10000;

function fp_direct_due_soon_days()
{
    return max(0, min(60, (int) (fp_access_matrix()['recurring_due_soon_days'] ?? 3)));
}

function fp_direct_due_states()
{
    return array(
        'overdue'  => 'Overdue',
        'due_soon' => 'Due soon',
        'upcoming' => 'Upcoming',
        'no_due'   => 'No due date',
        'paid'     => 'Paid',
        'closed'   => 'Closed',
    );
}

/**
 * Due state of one direct request row (Status, PaymentStatus, DueDate).
 */
function fp_direct_due_state($row, $today)
{
    if (in_array($row['Status'], array('rejected', 'cancelled'), true)) {
        return 'closed';
    }
    if ($row['PaymentStatus'] === 'paid') {
        return 'paid';
    }
    if (empty($row['DueDate'])) {
        return 'no_due';
    }
    if ($row['DueDate'] < $today) {
        return 'overdue';
    }
    return $row['DueDate'] <= date('Y-m-d', strtotime($today . ' +' . fp_direct_due_soon_days() . ' days')) ? 'due_soon' : 'upcoming';
}

/**
 * What has to happen next, in words.
 */
function fp_direct_next_step($row)
{
    switch ($row['Status']) {
        case 'draft':
            return 'Requester to complete and submit';
        case 'returned':
            return 'Returned: requester to correct and resubmit';
        case 'submitted':
            return 'Waiting for approval';
        case 'approved':
            return $row['PaymentStatus'] === 'paid' ? 'Paid' : ($row['PaymentStatus'] === 'partially_paid' ? 'Partly paid: balance with Finance' : 'Approved: waiting for payment by Finance');
    }
    return fp_request_statuses()[$row['Status']] ?? $row['Status'];
}

/* ------------------------------------------------------------------
   List and export
------------------------------------------------------------------ */

function fp_direct_list_filter($conn, $actor, $params)
{
    $me = (int) $actor['employee_id'];
    $base = "r.RequestType = 'direct' AND "
        . fp_scope_where($conn, $actor, 'view', array('employee_id' => 'r.EmployeeID', 'state_name' => 's.StateName', 'office_id' => 'r.OfficeID'))
        . " AND (r.Status <> 'draft' OR r.EmployeeID = " . $me . ')';
    $where = array($base);
    $types = '';
    $values = array();
    $today = date('Y-m-d');
    $soon = date('Y-m-d', strtotime('+' . fp_direct_due_soon_days() . ' days'));
    $open = "r.Status NOT IN ('rejected', 'cancelled') AND r.PaymentStatus <> 'paid'";

    switch (fp_input_string($params, 'due')) {
        case 'open':
            $where[] = $open;
            break;
        case 'overdue':
            $where[] = $open . ' AND r.DueDate < ?';
            $types .= 's';
            $values[] = $today;
            break;
        case 'due_soon':
            $where[] = $open . ' AND r.DueDate BETWEEN ? AND ?';
            $types .= 'ss';
            array_push($values, $today, $soon);
            break;
        case 'upcoming':
            $where[] = $open . ' AND r.DueDate > ?';
            $types .= 's';
            $values[] = $soon;
            break;
        case 'paid':
            $where[] = "r.PaymentStatus = 'paid'";
            break;
    }
    $status = fp_input_string($params, 'status');
    if (isset(fp_request_statuses()[$status])) {
        $where[] = 'r.Status = ?';
        $types .= 's';
        $values[] = $status;
    }
    $officeId = fp_input_int($params, 'office');
    if ($officeId > 0) {
        $where[] = 'r.OfficeID = ?';
        $types .= 'i';
        $values[] = $officeId;
    }
    $source = fp_input_string($params, 'source');
    if ($source === 'recurring') {
        $where[] = 'r.RecurringRuleID IS NOT NULL';
    } elseif ($source === 'one_off') {
        $where[] = 'r.RecurringRuleID IS NULL';
    }
    foreach (array('due_from' => '>=', 'due_to' => '<=') as $key => $operator) {
        $date = fp_parse_date(fp_input_string($params, $key));
        if ($date) {
            $where[] = 'r.DueDate ' . $operator . ' ?';
            $types .= 's';
            $values[] = $date;
        }
    }
    $search = mb_substr(fp_input_string($params, 'q'), 0, 100);
    if ($search !== '') {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $where[] = '(r.ExpenseNumber LIKE ? OR r.VendorName LIKE ? OR r.Purpose LIKE ? OR o.OfficeName LIKE ? OR rr.RuleNumber LIKE ?)';
        $types .= 'sssss';
        array_push($values, $like, $like, $like, $like, $like);
    }
    return array('base' => $base, 'where' => implode(' AND ', $where), 'types' => $types, 'values' => $values);
}

function fp_direct_list_from()
{
    return ' FROM branch_expense_requests r
             INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
             LEFT JOIN state s ON s.ID = r.StateID
             LEFT JOIN employees e ON e.ID = r.EmployeeID
             LEFT JOIN branch_expense_recurring_rules rr ON rr.ID = r.RecurringRuleID ';
}

function fp_direct_list_columns()
{
    return 'r.ID, r.ExpenseNumber, r.Status, r.PaymentStatus, r.Purpose, r.PeriodFrom, r.PeriodTo, r.TotalAmount, r.DueDate,
            r.VendorName, r.VendorRegistrationID, r.VendorGSTIN, r.EmployeeID, r.CreatedByEmployeeID, r.OfficeID, r.CreatedAt, r.SubmittedAt, r.ApprovedAt,
            o.OfficeCode, o.OfficeName, s.StateName, e.Name AS EmployeeName, rr.RuleNumber,
            COALESCE((SELECT SUM(p.Amount) FROM branch_expense_payments p WHERE p.RequestID = r.ID AND p.Status = \'completed\'), 0) AS PaidAmount';
}

function fp_direct_row($actor, $row, $today)
{
    $totalPaise = fp_decimal_to_paise($row['TotalAmount']);
    $paidPaise = fp_decimal_to_paise($row['PaidAmount']);
    $dueState = fp_direct_due_state($row, $today);
    $me = (int) $actor['employee_id'];
    return array(
        'ID'            => (int) $row['ID'],
        'ExpenseNumber' => $row['ExpenseNumber'],
        'Vendor'        => (string) $row['VendorName'],
        'Registered'    => (int) $row['VendorRegistrationID'] > 0,
        'Purpose'       => $row['Purpose'],
        'Period'        => $row['PeriodFrom'] ? fp_recurring_period_label($row['PeriodFrom'], $row['PeriodTo']) : '',
        'Office'        => $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')',
        'State'         => (string) $row['StateName'],
        'Employee'      => (string) $row['EmployeeName'],
        'DueDate'       => (string) $row['DueDate'],
        'DueState'      => $dueState,
        'DueStateLabel' => fp_direct_due_states()[$dueState],
        'TotalAmount'   => fp_paise_to_decimal($totalPaise),
        'PaidAmount'    => fp_paise_to_decimal($paidPaise),
        'Balance'       => fp_paise_to_decimal(in_array($dueState, array('closed', 'paid'), true) ? 0 : max(0, $totalPaise - $paidPaise)),
        'Status'        => $row['Status'],
        'StatusLabel'   => fp_request_statuses()[$row['Status']] ?? $row['Status'],
        'PaymentStatus' => $row['PaymentStatus'],
        'PaymentStatusLabel' => fp_payment_request_statuses()[$row['PaymentStatus']] ?? $row['PaymentStatus'],
        'NextStep'      => fp_direct_next_step($row),
        'RuleNumber'    => (string) $row['RuleNumber'],
        'IsMine'        => (int) $row['EmployeeID'] === $me,
        'CanEdit'       => (int) $row['EmployeeID'] === $me && in_array($row['Status'], fp_request_editable_statuses(), true) && fp_can($actor, 'edit'),
        'CanPay'        => $row['Status'] === 'approved' && $row['PaymentStatus'] !== 'paid' && $me > 0
            && fp_can_act_on_record($actor, 'pay', fp_request_scope_record($row, (string) $row['StateName']))['allowed'],
    );
}

function fp_direct_list($conn, $actor, $params)
{
    $filter = fp_direct_list_filter($conn, $actor, $params);
    $start = max(0, fp_input_int($params, 'start'));
    $length = fp_input_int($params, 'length');
    $length = $length <= 0 ? 25 : min($length, 100);
    $today = date('Y-m-d');
    $soon = date('Y-m-d', strtotime('+' . fp_direct_due_soon_days() . ' days'));

    $total = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt' . fp_direct_list_from() . 'WHERE ' . $filter['base']);
    $filtered = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt' . fp_direct_list_from() . 'WHERE ' . $filter['where'], $filter['types'], $filter['values']);
    $summary = fp_db_rows(
        $conn,
        "SELECT
            COALESCE(SUM(x.Open), 0) AS OpenCount, COALESCE(SUM(x.Open * x.Balance), 0) AS OpenBalance,
            COALESCE(SUM(x.Open * (x.DueDate < ?)), 0) AS OverdueCount, COALESCE(SUM(x.Open * (x.DueDate < ?) * x.Balance), 0) AS OverdueBalance,
            COALESCE(SUM(x.Open * (x.DueDate BETWEEN ? AND ?)), 0) AS DueSoonCount
         FROM (SELECT r.DueDate, (r.Status NOT IN ('rejected', 'cancelled') AND r.PaymentStatus <> 'paid') AS Open,
                      r.TotalAmount - COALESCE((SELECT SUM(p.Amount) FROM branch_expense_payments p WHERE p.RequestID = r.ID AND p.Status = 'completed'), 0) AS Balance
               " . fp_direct_list_from() . 'WHERE ' . $filter['base'] . ') x',
        'ssss',
        array($today, $today, $today, $soon)
    );
    $rows = fp_db_rows(
        $conn,
        'SELECT ' . fp_direct_list_columns() . fp_direct_list_from() . 'WHERE ' . $filter['where']
            . ' ORDER BY (r.Status IN (\'rejected\', \'cancelled\') OR r.PaymentStatus = \'paid\'), r.DueDate IS NULL, r.DueDate, r.ID DESC LIMIT ? OFFSET ?',
        $filter['types'] . 'ii',
        array_merge($filter['values'], array($length, $start))
    );
    $data = array();
    foreach ($rows as $row) {
        $data[] = fp_direct_row($actor, $row, $today);
    }
    $sum = $summary ? $summary[0] : array();
    return array(
        'draw'            => fp_input_int($params, 'draw'),
        'recordsTotal'    => (int) $total[0]['Cnt'],
        'recordsFiltered' => (int) $filtered[0]['Cnt'],
        'data'            => $data,
        'summary'         => array(
            'OpenCount'      => (int) ($sum['OpenCount'] ?? 0),
            'OpenBalance'    => fp_paise_to_decimal(fp_decimal_to_paise((string) ($sum['OpenBalance'] ?? '0'))),
            'OverdueCount'   => (int) ($sum['OverdueCount'] ?? 0),
            'OverdueBalance' => fp_paise_to_decimal(fp_decimal_to_paise((string) ($sum['OverdueBalance'] ?? '0'))),
            'DueSoonCount'   => (int) ($sum['DueSoonCount'] ?? 0),
            'DueSoonDays'    => fp_direct_due_soon_days(),
        ),
    );
}

function fp_direct_export_csv($conn, $actor, $params)
{
    $filter = fp_direct_list_filter($conn, $actor, $params);
    $rows = fp_db_rows(
        $conn,
        'SELECT ' . fp_direct_list_columns() . fp_direct_list_from() . 'WHERE ' . $filter['where'] . ' ORDER BY r.DueDate IS NULL, r.DueDate, r.ID LIMIT ' . FP_DIRECT_EXPORT_LIMIT,
        $filter['types'],
        $filter['values']
    );
    $today = date('Y-m-d');
    $cell = function ($value) {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    };
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="direct-expenses-' . date('Ymd-His') . '.csv"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('Expense No.', 'Vendor / payee', 'Vendor GSTIN', 'Registered vendor', 'Purpose', 'Period', 'Office', 'State', 'Requester',
        'Due date', 'Due state', 'Status', 'Payment status', 'Total (INR)', 'Paid (INR)', 'Balance (INR)', 'Recurring rule', 'Created', 'Approved'));
    foreach ($rows as $row) {
        $item = fp_direct_row($actor, $row, $today);
        fputcsv($out, array_map($cell, array(
            $item['ExpenseNumber'], $item['Vendor'], $row['VendorGSTIN'], $item['Registered'] ? 'Yes' : 'No', $item['Purpose'], $item['Period'],
            $item['Office'], $item['State'], $item['Employee'], $item['DueDate'], $item['DueStateLabel'], $item['StatusLabel'], $item['PaymentStatusLabel'],
            $item['TotalAmount'], $item['PaidAmount'], $item['Balance'], $item['RuleNumber'], $row['CreatedAt'], (string) $row['ApprovedAt'],
        )));
    }
    fclose($out);
    exit;
}

/* ------------------------------------------------------------------
   Reminders (run by cron/process_recurring.php)
------------------------------------------------------------------ */

/**
 * Reminds the requester once when a direct expense is due soon but not submitted, and once when it is overdue and unpaid.
 * Returns the number of reminders queued.
 */
function fp_direct_process_reminders($conn, $today, $limit = 500)
{
    $soon = date('Y-m-d', strtotime($today . ' +' . fp_direct_due_soon_days() . ' days'));
    $checks = array(
        'due_soon' => array("r.Status IN ('draft', 'returned') AND r.DueDate BETWEEN ? AND ?", 'ss', array($today, $soon)),
        'overdue'  => array("r.Status IN ('draft', 'returned', 'submitted', 'approved') AND r.PaymentStatus <> 'paid' AND r.DueDate < ?", 's', array($today)),
    );
    $count = 0;
    $now = date('Y-m-d H:i:s');
    foreach ($checks as $kind => $check) {
        $rows = fp_db_rows(
            $conn,
            "SELECT r.ID FROM branch_expense_requests r
             WHERE r.RequestType = 'direct' AND " . $check[0] . '
               AND NOT EXISTS (SELECT 1 FROM branch_expense_reminders m WHERE m.RequestID = r.ID AND m.ReminderType = ?)
             ORDER BY r.DueDate, r.ID LIMIT ' . (int) $limit,
            $check[1] . 's',
            array_merge($check[2], array($kind))
        );
        foreach ($rows as $row) {
            if (fp_policy_exec($conn, 'INSERT IGNORE INTO branch_expense_reminders (RequestID, ReminderType, SentAt) VALUES (?, ?, ?)', 'iss', array((int) $row['ID'], $kind, $now)) === 1) {
                fp_notice_add('reminder', (int) $row['ID'], array('kind' => $kind));
                $count++;
            }
        }
    }
    fp_notice_send($conn);
    return $count;
}

/**
 * Notification texts for 'recurring' (a draft was created) and 'reminder' notices. Sent to the requester.
 */
function fp_direct_notice_messages($conn, $notice, $request, $summary)
{
    $vendor = (string) $request['VendorName'] !== '' ? ' to ' . $request['VendorName'] : '';
    $due = $request['DueDate'] ? fp_timeline_date($request['DueDate']) : '';
    $editable = in_array($request['Status'], fp_request_editable_statuses(), true);
    $url = $editable ? 'finance-performance/view-expense-request-form.php?id=' . (int) $request['ID'] : 'finance-performance/view-expense-request.php?id=' . (int) $request['ID'];

    if ($notice['type'] === 'recurring') {
        if ($request['Status'] !== 'draft') {
            return array();
        }
        return array(array(
            'employee_id' => (int) $request['EmployeeID'],
            'title'       => 'Recurring expense draft ready',
            'body'        => $summary . $vendor . ' is due on ' . $due . '. Check the amount, attach the bill and submit it for approval. Nothing is paid until it is approved.',
            'url'         => $url,
        ));
    }

    $kind = $notice['kind'] ?? '';
    if ($kind === 'due_soon') {
        if (!$editable) {
            return array();
        }
        return array(array(
            'employee_id' => (int) $request['EmployeeID'],
            'title'       => 'Direct expense due soon',
            'body'        => $summary . $vendor . ' is due on ' . $due . ' but is not submitted for approval yet.',
            'url'         => $url,
        ));
    }
    if ($kind === 'overdue') {
        if ($request['PaymentStatus'] === 'paid' || in_array($request['Status'], array('rejected', 'cancelled'), true)) {
            return array();
        }
        return array(array(
            'employee_id' => (int) $request['EmployeeID'],
            'title'       => 'Direct expense overdue',
            'body'        => $summary . $vendor . ' was due on ' . $due . '. Current step: ' . strtolower(fp_direct_next_step($request)) . '.',
            'url'         => $url,
        ));
    }
    return array();
}
