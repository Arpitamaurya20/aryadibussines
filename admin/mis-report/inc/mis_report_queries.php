<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/dashboard/inc/state_dashboard_queries.php';

/** Convenience status codes (mirrors convenience_controller — avoid loading heavy controller in AJAX). */
const MIS_CONV_STATUS_HR_APPROVED = '5';
const MIS_CONV_STATUS_PAID = '6';

function mis_report_list_state_names(mysqli $conn): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    if (!class_exists('State')) {
        require_once dirname(__DIR__, 2) . '/includes/autoloader.inc.php';
    }
    $stateObject = new State($conn);
    $statesRaw = $stateObject->setStateArray('Active');
    $names = [];
    foreach ($statesRaw as $stateRow) {
        $name = trim((string)($stateRow['StateName'] ?? ''));
        if ($name !== '' && !in_array($name, $names, true)) {
            $names[] = $name;
        }
    }
    sort($names);
    $cached = $names;
    return $names;
}

function mis_report_validate_state_name(mysqli $conn, string $stateName): bool
{
    $stateName = trim($stateName);
    if ($stateName === '') {
        return false;
    }
    return in_array($stateName, mis_report_list_state_names($conn), true);
}

/**
 * Single-state scope for ticket/branch queries (reuses state dashboard engine).
 */
function mis_report_scope_for_state(mysqli $conn, array $session, string $stateName): array
{
    $employeeId = isset($session['Roles']['EmployeeID']) ? (int)$session['Roles']['EmployeeID'] : 0;
    $stateName = trim($stateName);

    if (!class_exists('State')) {
        require_once dirname(__DIR__, 2) . '/includes/autoloader.inc.php';
    }

    $stateObject = new State($conn);

    return [
        'mode' => 'state',
        'employee_id' => $employeeId,
        'state_names' => [$stateName],
        'state_in_clause' => $stateObject->buildBranchStateInClause([$stateName]),
        'branch_ids' => [],
        'branch_in_clause' => '',
        'branches' => [],
        'selected_state' => $stateName,
        'page_title' => 'MIS Report',
        'breadcrumb' => 'MIS Report',
        'is_mis_report' => true,
    ];
}

function mis_report_normalize_filters(array $input, array $scope, mysqli $conn): array
{
    $stateName = trim((string)($input['state'] ?? ''));
    if ($stateName === '' || $stateName === 'all') {
        throw new InvalidArgumentException('State is required.');
    }
    if (!mis_report_validate_state_name($conn, $stateName)) {
        throw new InvalidArgumentException('Invalid state selected.');
    }

    $scope = mis_report_scope_for_state($conn, $scope, $stateName);
    return state_dashboard_normalize_filters($input, $scope, $conn);
}

function mis_report_load_filter_options(mysqli $conn, string $stateName): array
{
    if (!mis_report_validate_state_name($conn, $stateName)) {
        return ['companies' => [], 'branches' => []];
    }
    $scope = mis_report_scope_for_state($conn, [], $stateName);
    $filters = state_dashboard_normalize_filters(['state' => $stateName], $scope, $conn);
    return state_dashboard_load_filter_options($conn, $filters);
}

function mis_report_esc(mysqli $conn, string $v): string
{
    return mysqli_real_escape_string($conn, $v);
}

function mis_report_table_exists(mysqli $conn, string $table): bool
{
    static $known = [];
    if (array_key_exists($table, $known)) {
        return $known[$table];
    }
    $safe = mis_report_esc($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$safe'");
    $known[$table] = $result && mysqli_num_rows($result) > 0;
    return $known[$table];
}

function mis_report_amount_sql(string $alias = 'a'): string
{
    return "CASE
        WHEN COALESCE(NULLIF(TRIM($alias.ApprovedAmount), ''), '') <> '' AND TRIM($alias.ApprovedAmount) <> '0'
        THEN CAST(REPLACE(REPLACE(TRIM($alias.ApprovedAmount), ',', ''), ' ', '') AS DECIMAL(14,2))
        WHEN COALESCE(NULLIF(TRIM($alias.ConvenienceAmount), ''), '') <> ''
        THEN CAST(REPLACE(REPLACE(TRIM($alias.ConvenienceAmount), ',', ''), ' ', '') AS DECIMAL(14,2))
        ELSE 0 END";
}

function mis_report_convenience_totals(mysqli $conn, string $stateName, string $start, string $end): array
{
    $stateEsc = mis_report_esc($conn, $stateName);
    $startEsc = mis_report_esc($conn, $start);
    $endEsc = mis_report_esc($conn, $end);
    $amountSql = mis_report_amount_sql('a');
    $paidStatus = MIS_CONV_STATUS_PAID;
    $hrApproved = MIS_CONV_STATUS_HR_APPROVED;

    $rows = wallboard_query_rows($conn, "
        SELECT
            COUNT(*) AS total_count,
            COALESCE(SUM($amountSql), 0) AS total_amount,
            SUM(CASE WHEN CAST(a.Status AS CHAR) = '$paidStatus' THEN 1 ELSE 0 END) AS payment_done_count,
            COALESCE(SUM(CASE WHEN CAST(a.Status AS CHAR) = '$paidStatus' THEN $amountSql ELSE 0 END), 0) AS payment_done_amount,
            SUM(CASE WHEN CAST(a.Status AS CHAR) = '$hrApproved' THEN 1 ELSE 0 END) AS pending_payment_count,
            COALESCE(SUM(CASE WHEN CAST(a.Status AS CHAR) = '$hrApproved' THEN $amountSql ELSE 0 END), 0) AS pending_payment_amount
        FROM employee_convenience a
        INNER JOIN employees e ON e.ID = a.EmployeeID AND e.IsActive = 1
        WHERE e.State = '$stateEsc'
          AND a.ConvenienceDate >= '$startEsc' AND a.ConvenienceDate <= '$endEsc'
    ");

    $row = $rows[0] ?? [];
    return [
        'total_count' => (int)($row['total_count'] ?? 0),
        'total_amount' => round((float)($row['total_amount'] ?? 0), 2),
        'payment_done_count' => (int)($row['payment_done_count'] ?? 0),
        'payment_done_amount' => round((float)($row['payment_done_amount'] ?? 0), 2),
        'pending_payment_count' => (int)($row['pending_payment_count'] ?? 0),
        'pending_payment_amount' => round((float)($row['pending_payment_amount'] ?? 0), 2),
    ];
}

function mis_report_employee_state_where(mysqli $conn, string $stateName, string $alias = 'e'): string
{
    $state = mis_report_esc($conn, $stateName);
    return "$alias.IsActive = 1 AND IFNULL($alias.Vendor, 0) = 0 AND $alias.State = '$state'";
}

function mis_report_fetch_workforce_summary(mysqli $conn, string $stateName, string $start, string $end): array
{
    $where = mis_report_employee_state_where($conn, $stateName, 'e');
    $startEsc = mis_report_esc($conn, $start);
    $endEsc = mis_report_esc($conn, $end);

    $empRow = wallboard_query_rows($conn, "
        SELECT
            COUNT(*) AS total_employees,
            SUM(CASE WHEN EXISTS (
                SELECT 1 FROM user_roles ur WHERE ur.EmployeeID = e.ID AND ur.Role = 'Technician'
            ) THEN 1 ELSE 0 END) AS technician_count,
            SUM(
                CASE WHEN COALESCE(NULLIF(TRIM(e.InHandSalary), ''), '') <> ''
                THEN CAST(REPLACE(REPLACE(TRIM(e.InHandSalary), ',', ''), ' ', '') AS DECIMAL(14,2))
                ELSE 0 END
            ) AS total_inhand_salary
        FROM employees e
        WHERE $where
    ");

    $convSummary = mis_report_convenience_totals($conn, $stateName, $start, $end);

    $payrollPaid = 0.0;
    $payrollSlipCount = 0;
    if (mis_report_table_exists($conn, 'hrms_salary_slip')) {
        $payRows = wallboard_query_rows($conn, "
            SELECT COUNT(*) AS slip_count, COALESCE(SUM(s.NetSalary), 0) AS net_total
            FROM hrms_salary_slip s
            INNER JOIN employees e ON e.ID = s.EmployeeID
            WHERE $where
              AND (
                (s.PayrollYear > YEAR('$startEsc') OR (s.PayrollYear = YEAR('$startEsc') AND s.PayrollMonth >= MONTH('$startEsc')))
                AND
                (s.PayrollYear < YEAR('$endEsc') OR (s.PayrollYear = YEAR('$endEsc') AND s.PayrollMonth <= MONTH('$endEsc')))
              )
        ");
        $payrollPaid = (float)($payRows[0]['net_total'] ?? 0);
        $payrollSlipCount = (int)($payRows[0]['slip_count'] ?? 0);
    }

    return [
        'total_employees' => (int)($empRow[0]['total_employees'] ?? 0),
        'technician_count' => (int)($empRow[0]['technician_count'] ?? 0),
        'total_inhand_salary' => round((float)($empRow[0]['total_inhand_salary'] ?? 0), 2),
        'convenience' => [
            'total_count' => (int)($convSummary['total_count'] ?? 0),
            'total_amount' => round((float)($convSummary['total_amount'] ?? 0), 2),
            'payment_done_count' => (int)($convSummary['payment_done_count'] ?? 0),
            'payment_done_amount' => round((float)($convSummary['payment_done_amount'] ?? 0), 2),
            'pending_payment_count' => (int)($convSummary['pending_payment_count'] ?? 0),
            'pending_payment_amount' => round((float)($convSummary['pending_payment_amount'] ?? 0), 2),
        ],
        'payroll_net_paid' => round($payrollPaid, 2),
        'payroll_slip_count' => $payrollSlipCount,
    ];
}

function mis_report_fetch_employee_rows(mysqli $conn, string $stateName, string $start, string $end, array $filters): array
{
    $where = mis_report_employee_state_where($conn, $stateName, 'e');

    $employees = wallboard_query_rows($conn, "
        SELECT
            e.ID AS employee_id,
            e.Name AS name,
            e.EmployeeNumber AS employee_number,
            COALESCE(e.Designation, '') AS designation,
            COALESCE(e.Department, '') AS department,
            CASE WHEN EXISTS (
                SELECT 1 FROM user_roles ur WHERE ur.EmployeeID = e.ID AND ur.Role = 'Technician'
            ) THEN 1 ELSE 0 END AS is_technician,
            CASE WHEN COALESCE(NULLIF(TRIM(e.InHandSalary), ''), '') <> ''
                THEN CAST(REPLACE(REPLACE(TRIM(e.InHandSalary), ',', ''), ' ', '') AS DECIMAL(14,2))
                ELSE 0 END AS in_hand_salary
        FROM employees e
        WHERE $where
        ORDER BY e.Name ASC
        LIMIT 500
    ");

    if (!$employees) {
        return [];
    }

    $ticketStats = mis_report_fetch_employee_ticket_stats($conn, $employees, $start, $end, $filters);
    $convMap = mis_report_fetch_employee_convenience_map($conn, $stateName, $start, $end, $employees);

    $out = [];
    foreach ($employees as $emp) {
        $id = (int)$emp['employee_id'];
        $stats = $ticketStats[$id] ?? ['assigned' => 0, 'closed' => 0, 'wip' => 0];
        $conv = $convMap[$id] ?? ['count' => 0, 'amount' => 0.0];
        $out[] = [
            'employee_id' => $id,
            'name' => (string)$emp['name'],
            'employee_number' => (string)$emp['employee_number'],
            'designation' => (string)$emp['designation'],
            'department' => (string)$emp['department'],
            'is_technician' => (int)$emp['is_technician'] === 1,
            'role_label' => (int)$emp['is_technician'] === 1 ? 'Technician' : 'Employee',
            'in_hand_salary' => round((float)$emp['in_hand_salary'], 2),
            'tickets_assigned' => (int)$stats['assigned'],
            'tickets_closed' => (int)$stats['closed'],
            'tickets_wip' => (int)$stats['wip'],
            'convenience_count' => (int)$conv['count'],
            'convenience_amount' => (float)$conv['amount'],
        ];
    }

    usort($out, static function ($a, $b) {
        $scoreA = $a['tickets_assigned'] + $a['tickets_closed'] + $a['tickets_wip'];
        $scoreB = $b['tickets_assigned'] + $b['tickets_closed'] + $b['tickets_wip'];
        if ($scoreB !== $scoreA) {
            return $scoreB <=> $scoreA;
        }
        return strcmp($a['name'], $b['name']);
    });

    return $out;
}

function mis_report_fetch_employee_convenience_map(
    mysqli $conn,
    string $stateName,
    string $start,
    string $end,
    array $employees
): array {
    if (!$employees) {
        return [];
    }
    $ids = array_map(static fn($r) => (int)$r['employee_id'], $employees);
    $idList = implode(',', $ids);
    $stateEsc = mis_report_esc($conn, $stateName);
    $startEsc = mis_report_esc($conn, $start);
    $endEsc = mis_report_esc($conn, $end);
    $amountSql = mis_report_amount_sql('a');

    $convRows = wallboard_query_rows($conn, "
        SELECT a.EmployeeID AS employee_id,
            COUNT(*) AS claim_count,
            COALESCE(SUM($amountSql), 0) AS claim_amount
        FROM employee_convenience a
        INNER JOIN employees e ON e.ID = a.EmployeeID
        WHERE a.EmployeeID IN ($idList)
          AND e.State = '$stateEsc'
          AND a.ConvenienceDate >= '$startEsc' AND a.ConvenienceDate <= '$endEsc'
        GROUP BY a.EmployeeID
    ");

    $map = [];
    foreach ($convRows as $r) {
        $map[(int)$r['employee_id']] = [
            'count' => (int)($r['claim_count'] ?? 0),
            'amount' => round((float)($r['claim_amount'] ?? 0), 2),
        ];
    }
    return $map;
}

function mis_report_fetch_employee_ticket_stats(
    mysqli $conn,
    array $employees,
    string $start,
    string $end,
    array $filters
): array {
    $ids = array_map(static fn($r) => (int)$r['employee_id'], $employees);
    $idList = implode(',', $ids);
    $startEsc = mis_report_esc($conn, $start);
    $endEsc = mis_report_esc($conn, $end);
    $branchScope = state_dashboard_scope_branch_where($filters, 'b');
    $extraCt = state_dashboard_extra_ticket_where($filters, 'ct');
    $extraPt = state_dashboard_extra_ticket_where($filters, 'pt');

    $rows = wallboard_query_rows($conn, "
        SELECT employee_id,
            SUM(assigned_cnt) AS assigned,
            SUM(closed_cnt) AS closed,
            SUM(wip_cnt) AS wip
        FROM (
            SELECT ct.AssignedTo AS employee_id,
                SUM(CASE WHEN ct.CreatedDate >= '$startEsc' AND ct.CreatedDate <= '$endEsc' THEN 1 ELSE 0 END) AS assigned_cnt,
                SUM(CASE WHEN ct.Status = 'Closed' AND ct.CloseDate >= '$startEsc' AND ct.CloseDate <= '$endEsc' THEN 1 ELSE 0 END) AS closed_cnt,
                SUM(CASE WHEN ct.Status <> 'Closed' THEN 1 ELSE 0 END) AS wip_cnt
            FROM corporate_tickets ct
            INNER JOIN branch b ON b.ID = ct.BranchID AND b.IsActive = 1
            WHERE ct.IsActive = 1 AND ct.AssignedTo IN ($idList)
              AND $branchScope
              $extraCt
            GROUP BY ct.AssignedTo
            UNION ALL
            SELECT pt.AssignedTo AS employee_id,
                SUM(CASE WHEN pt.CreatedDate >= '$startEsc' AND pt.CreatedDate <= '$endEsc' THEN 1 ELSE 0 END),
                SUM(CASE WHEN pt.Status = 'Closed' AND pt.CloseDate >= '$startEsc' AND pt.CloseDate <= '$endEsc' THEN 1 ELSE 0 END),
                SUM(CASE WHEN pt.Status <> 'Closed' THEN 1 ELSE 0 END)
            FROM ppm_tickets pt
            INNER JOIN branch b ON b.ID = pt.BranchID AND b.IsActive = 1
            WHERE pt.IsActive = 1 AND pt.AssignedTo IN ($idList)
              AND $branchScope
              $extraPt
            GROUP BY pt.AssignedTo
        ) stats
        GROUP BY employee_id
    ");

    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['employee_id']] = [
            'assigned' => (int)($r['assigned'] ?? 0),
            'closed' => (int)($r['closed'] ?? 0),
            'wip' => (int)($r['wip'] ?? 0),
        ];
    }
    return $map;
}

function mis_report_build_workforce_snapshot(mysqli $conn, array $filters, string $stateName): array
{
    return [
        'generated_at' => date('Y-m-d H:i:s'),
        'selected_state' => $stateName,
        'period' => [
            'start' => $filters['start_date'],
            'end' => $filters['end_date'],
            'label' => $filters['period_label'],
            'preset' => $filters['preset'],
        ],
        'workforce' => mis_report_fetch_workforce_summary(
            $conn,
            $stateName,
            $filters['start_date'],
            $filters['end_date']
        ),
        'employees' => mis_report_fetch_employee_rows(
            $conn,
            $stateName,
            $filters['start_date'],
            $filters['end_date'],
            $filters
        ),
    ];
}

function mis_report_build_ticket_snapshot(mysqli $conn, array $filters, string $stateName, int $employeeId = 0): array
{
    if (!function_exists('state_dashboard_cached_snapshot')) {
        require_once dirname(__DIR__, 2) . '/dashboard/inc/state_dashboard_cache.php';
    }
    $cacheUid = 900000000 + max(0, $employeeId);
    $snapshot = state_dashboard_cached_snapshot($conn, $cacheUid, $filters, false);
    $snapshot['selected_state'] = $stateName;
    return $snapshot;
}

function mis_report_build_snapshot(mysqli $conn, array $filters, string $stateName): array
{
    $workforce = mis_report_build_workforce_snapshot($conn, $filters, $stateName);
    $tickets = mis_report_build_ticket_snapshot($conn, $filters, $stateName);
    $merged = $tickets;
    $merged['workforce'] = $workforce['workforce'] ?? mis_report_empty_workforce();
    $merged['employees'] = $workforce['employees'] ?? [];
    $merged['selected_state'] = $stateName;
    $merged['generated_at'] = $workforce['generated_at'] ?? date('Y-m-d H:i:s');
    return $merged;
}

function mis_report_empty_workforce(): array
{
    return [
        'total_employees' => 0,
        'technician_count' => 0,
        'total_inhand_salary' => 0,
        'convenience' => [
            'total_count' => 0,
            'total_amount' => 0,
            'payment_done_count' => 0,
            'payment_done_amount' => 0,
            'pending_payment_count' => 0,
            'pending_payment_amount' => 0,
        ],
        'payroll_net_paid' => 0,
        'payroll_slip_count' => 0,
    ];
}
