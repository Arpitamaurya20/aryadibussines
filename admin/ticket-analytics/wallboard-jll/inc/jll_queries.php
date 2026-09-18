<?php
declare(strict_types=1);

require_once __DIR__ . '/jll_config.php';
require_once __DIR__ . '/jll_cache.php';

function jll_today(): string
{
    return date('Y-m-d');
}

function jll_query_rows(mysqli $conn, string $sql): array
{
    $res = @mysqli_query($conn, $sql);
    if (!$res) {
        return [];
    }
    $rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_free_result($res);
    return $rows ?: [];
}

function jll_esc_date(mysqli $conn, string $date): string
{
    return mysqli_real_escape_string($conn, $date);
}

function jll_esc_ids(array $ids): string
{
    $clean = array_filter(array_map('intval', $ids));
    return $clean ? implode(',', $clean) : '0';
}

function jll_label_count_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'label' => (string)($r['label'] ?? 'Unknown'),
            'value' => (int)($r['value'] ?? 0),
        ];
    }
    return $out;
}

function jll_table_id_floor(mysqli $conn, string $table, int $lookback = 50000): int
{
    static $cache = [];
    $safe = preg_replace('/[^a-z_]/', '', $table);
    if ($safe === '') {
        return 1;
    }
    $cacheKey = $safe . ':' . $lookback;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    $activeTables = ['corporate_tickets', 'ppm_tickets'];
    $where = in_array($safe, $activeTables, true) ? 'WHERE IsActive = 1' : '';
    $row = jll_query_rows($conn, "SELECT MAX(ID) AS max_id FROM `{$safe}` {$where}");
    $cache[$cacheKey] = max(1, (int)($row[0]['max_id'] ?? 0) - $lookback);
    return $cache[$cacheKey];
}

function jll_corp_in_clause(array $companyIds): string
{
    return 'CorporateID IN (' . jll_esc_ids($companyIds) . ')';
}

/** Statuses excluded from open-backlog counts. */
function jll_closed_status_sql(): string
{
    return "Status NOT IN ('Closed','Cancelled','Cancel')";
}

function jll_workflow_bucket(string $status): string
{
    $status = trim($status);
    if ($status === 'Closed') {
        return 'Closed';
    }
    if ($status === 'Quote Approved') {
        return 'Quote Approved';
    }
    if (stripos($status, 'Quote Sent') !== false || stripos($status, 'Approval Pending') !== false) {
        return 'Quote / Approval';
    }
    if (in_array($status, ['Assigned', 'Raised'], true)) {
        return 'Assigned / Raised';
    }
    if (in_array($status, ['Work in Progress', 'Work In Progress', 'Generate OTP to Start'], true)) {
        return 'Work in Progress';
    }
    if (in_array($status, ['Planned', 'Scheduled'], true)) {
        return 'Planned';
    }
    return $status !== '' ? $status : 'Other';
}

function jll_workflow_from_moves(array $statusMoves): array
{
    $buckets = [];
    foreach ($statusMoves as $row) {
        $label = jll_workflow_bucket((string)$row['label']);
        $buckets[$label] = ($buckets[$label] ?? 0) + (int)$row['value'];
    }
    $out = [];
    foreach ($buckets as $label => $value) {
        $out[] = ['label' => $label, 'value' => $value];
    }
    usort($out, static fn($a, $b) => $b['value'] <=> $a['value']);
    return $out;
}

function jll_aggregate_ticket_rows(array $rows, string $today): array
{
    $openedStatus = [];
    $closedStatus = [];
    $states = [];
    $contracts = [];
    $branches = [];
    $employees = [];

    foreach ($rows as $r) {
        $status = trim((string)($r['status'] ?? '')) ?: 'Unknown';
        $isOpened = ((string)($r['created_date'] ?? '')) === $today;
        $isClosed = ((string)($r['status'] ?? '')) === 'Closed' && ((string)($r['close_date'] ?? '')) === $today;

        if ($isOpened) {
            $openedStatus[$status] = ($openedStatus[$status] ?? 0) + 1;
        }
        if ($isClosed) {
            $closedStatus[$status] = ($closedStatus[$status] ?? 0) + 1;
        }
        if (!$isOpened && !$isClosed) {
            continue;
        }

        $state = trim((string)($r['branch_state'] ?? '')) ?: 'Unknown';
        $contract = trim((string)($r['company_name'] ?? '')) ?: 'Unknown';
        $branch = trim((string)($r['branch_name'] ?? '')) ?: 'Unknown';
        $emp = trim((string)($r['employee_name'] ?? '')) ?: 'Unassigned';

        $states[$state] = ($states[$state] ?? 0) + 1;
        $contracts[$contract] = ($contracts[$contract] ?? 0) + 1;
        $branches[$branch] = ($branches[$branch] ?? 0) + 1;

        if (!isset($employees[$emp])) {
            $employees[$emp] = ['label' => $emp, 'opened_today' => 0, 'closed_today' => 0];
        }
        if ($isOpened) {
            $employees[$emp]['opened_today']++;
        }
        if ($isClosed) {
            $employees[$emp]['closed_today']++;
        }
    }

    $toChips = static function (array $map): array {
        $out = [];
        foreach ($map as $label => $value) {
            $out[] = ['label' => (string)$label, 'value' => (int)$value];
        }
        usort($out, static fn($a, $b) => $b['value'] <=> $a['value']);
        return $out;
    };

    $empOut = array_values($employees);
    usort($empOut, static fn($a, $b) => ($b['opened_today'] + $b['closed_today']) <=> ($a['opened_today'] + $a['closed_today']));

    return [
        'opened_today_by_status' => $toChips($openedStatus),
        'closed_today_by_status' => $toChips($closedStatus),
        'top_states_today' => array_slice($toChips($states), 0, 12),
        'top_contracts_today' => array_slice($toChips($contracts), 0, 10),
        'top_branches_today' => array_slice($toChips($branches), 0, 10),
        'top_employees_today' => array_slice($empOut, 0, 12),
    ];
}

function jll_fetch_corporate_meta(mysqli $conn): array
{
    $hqId = (int)JLL_CORPORATE_HQ_ID;
    $rows = jll_query_rows($conn, "
        SELECT ID, CorporateName, CorporateGST, CoporateAddress, IsActive
        FROM corporate
        WHERE ID = $hqId
        LIMIT 1
    ");
    if (!$rows) {
        return [
            'id' => $hqId,
            'name' => JLL_BRAND_NAME,
            'gst' => '',
            'address' => '',
            'is_active' => 1,
        ];
    }
    $r = $rows[0];
    return [
        'id' => (int)($r['ID'] ?? $hqId),
        'name' => (string)($r['CorporateName'] ?? JLL_BRAND_NAME),
        'gst' => (string)($r['CorporateGST'] ?? ''),
        'address' => (string)($r['CoporateAddress'] ?? ''),
        'is_active' => (int)($r['IsActive'] ?? 1),
    ];
}

function jll_backlog_summary(mysqli $conn, array $companyIds, int $corpMin, int $ppmMin): array
{
    $cached = jll_cache_read('backlog_summary', 300);
    if (is_array($cached)) {
        return $cached;
    }

    $corpIn = jll_corp_in_clause($companyIds);
    $closed = jll_closed_status_sql();

    $corpRow = jll_query_rows($conn, "
        SELECT
            SUM(CASE WHEN $closed THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN Status IN ('Work in Progress','Work In Progress') THEN 1 ELSE 0 END) AS wip
        FROM corporate_tickets
        WHERE IsActive = 1 AND $corpIn
    ");
    $ppmRow = jll_query_rows($conn, "
        SELECT
            SUM(CASE WHEN $closed THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN Status IN ('Work in Progress','Work In Progress') THEN 1 ELSE 0 END) AS wip
        FROM ppm_tickets
        WHERE IsActive = 1 AND $corpIn
    ");

    $result = [
        'corporate_active' => (int)($corpRow[0]['active'] ?? 0),
        'corporate_wip' => (int)($corpRow[0]['wip'] ?? 0),
        'ppm_active' => (int)($ppmRow[0]['active'] ?? 0),
        'ppm_wip' => (int)($ppmRow[0]['wip'] ?? 0),
        'total_active' => (int)($corpRow[0]['active'] ?? 0) + (int)($ppmRow[0]['active'] ?? 0),
    ];
    jll_cache_write('backlog_summary', $result, 300);
    return $result;
}

function jll_ticket_stats_today(mysqli $conn, string $today, array $companyIds, int $corpMin, int $ppmMin): array
{
    $t = jll_esc_date($conn, $today);
    $corpIn = jll_corp_in_clause($companyIds);

    $corpRows = jll_query_rows($conn, "
        SELECT
            CASE WHEN Type = 'AMC' THEN 'AMC Breakdown' ELSE Type END AS ticket_type,
            SUM(CASE WHEN CreatedDate = '$t' THEN 1 ELSE 0 END) AS opened,
            SUM(CASE WHEN Status = 'Closed' AND CloseDate = '$t' THEN 1 ELSE 0 END) AS closed
        FROM corporate_tickets
        WHERE IsActive = 1 AND ID >= $corpMin AND $corpIn
        GROUP BY CASE WHEN Type = 'AMC' THEN 'AMC Breakdown' ELSE Type END
    ");
    $ppmRows = jll_query_rows($conn, "
        SELECT
            SUM(CASE WHEN CreatedDate = '$t' THEN 1 ELSE 0 END) AS opened,
            SUM(CASE WHEN Status = 'Closed' AND CloseDate = '$t' THEN 1 ELSE 0 END) AS closed
        FROM ppm_tickets
        WHERE IsActive = 1 AND ID >= $ppmMin AND $corpIn
    ");

    $map = [];
    $openedTotal = 0;
    $closedTotal = 0;
    foreach ($corpRows as $r) {
        $type = $r['ticket_type'] ?? 'Unknown';
        $o = (int)($r['opened'] ?? 0);
        $c = (int)($r['closed'] ?? 0);
        $map[$type] = ['ticket_type' => $type, 'opened' => $o, 'closed' => $c];
        $openedTotal += $o;
        $closedTotal += $c;
    }
    $ppmOpened = (int)($ppmRows[0]['opened'] ?? 0);
    $ppmClosed = (int)($ppmRows[0]['closed'] ?? 0);
    if ($ppmOpened > 0 || $ppmClosed > 0) {
        $map['PPM'] = ['ticket_type' => 'PPM', 'opened' => $ppmOpened, 'closed' => $ppmClosed];
        $openedTotal += $ppmOpened;
        $closedTotal += $ppmClosed;
    }

    return [
        'opened_today' => $openedTotal,
        'closed_today' => $closedTotal,
        'type_matrix' => array_values($map),
    ];
}

function jll_ppm_month_metrics(mysqli $conn, array $companyIds, int $ppmMin): array
{
    $corpIn = jll_corp_in_clause($companyIds);
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    $today = jll_today();

    $row = jll_query_rows($conn, "
        SELECT
            SUM(CASE WHEN PPMDate BETWEEN '$monthStart' AND '$monthEnd' THEN 1 ELSE 0 END) AS scheduled_month,
            SUM(CASE WHEN PPMDate BETWEEN '$monthStart' AND '$monthEnd' AND Status = 'Closed' THEN 1 ELSE 0 END) AS completed_month,
            SUM(CASE WHEN PPMDate < '$today' AND Status NOT IN ('Closed','Cancelled') THEN 1 ELSE 0 END) AS overdue,
            SUM(CASE WHEN PPMDate BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 7 DAY) AND Status NOT IN ('Closed','Cancelled') THEN 1 ELSE 0 END) AS due_week
        FROM ppm_tickets
        WHERE IsActive = 1 AND ID >= $ppmMin AND $corpIn
    ");

    $scheduled = (int)($row[0]['scheduled_month'] ?? 0);
    $completed = (int)($row[0]['completed_month'] ?? 0);
    $compliance = $scheduled > 0 ? round(($completed / $scheduled) * 100, 1) : 0.0;

    return [
        'scheduled_month' => $scheduled,
        'completed_month' => $completed,
        'overdue' => (int)($row[0]['overdue'] ?? 0),
        'due_this_week' => (int)($row[0]['due_week'] ?? 0),
        'compliance_pct' => $compliance,
    ];
}

function jll_status_distribution(mysqli $conn, array $companyIds, int $corpMin, int $ppmMin): array
{
    $cached = jll_cache_read('status_distribution', 300);
    if (is_array($cached)) {
        return $cached;
    }

    $corpIn = jll_corp_in_clause($companyIds);
    $closed = jll_closed_status_sql();

    $corp = jll_label_count_rows(jll_query_rows($conn, "
        SELECT COALESCE(NULLIF(TRIM(Status), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM corporate_tickets
        WHERE IsActive = 1 AND $corpIn AND $closed
        GROUP BY COALESCE(NULLIF(TRIM(Status), ''), 'Unknown')
        ORDER BY value DESC LIMIT 12
    "));
    $ppm = jll_label_count_rows(jll_query_rows($conn, "
        SELECT COALESCE(NULLIF(TRIM(Status), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM ppm_tickets
        WHERE IsActive = 1 AND $corpIn AND $closed
        GROUP BY COALESCE(NULLIF(TRIM(Status), ''), 'Unknown')
        ORDER BY value DESC LIMIT 12
    "));

    $result = ['corporate' => $corp, 'ppm' => $ppm];
    jll_cache_write('status_distribution', $result, 300);
    return $result;
}

function jll_type_backlog(mysqli $conn, array $companyIds, int $corpMin): array
{
    $cached = jll_cache_read('type_backlog', 300);
    if (is_array($cached)) {
        return $cached;
    }

    $corpIn = jll_corp_in_clause($companyIds);
    $closed = jll_closed_status_sql();
    $result = jll_label_count_rows(jll_query_rows($conn, "
        SELECT
            CASE WHEN Type = 'AMC' THEN 'AMC Breakdown' ELSE Type END AS label,
            COUNT(*) AS value
        FROM corporate_tickets
        WHERE IsActive = 1 AND $corpIn AND $closed
        GROUP BY CASE WHEN Type = 'AMC' THEN 'AMC Breakdown' ELSE Type END
        ORDER BY value DESC
    "));
    jll_cache_write('type_backlog', $result, 300);
    return $result;
}

function jll_seven_day_trend(mysqli $conn, array $companyIds, int $corpMin, int $ppmMin): array
{
    $corpIn = jll_corp_in_clause($companyIds);
    $days = [];
    for ($i = 6; $i >= 0; $i--) {
        $days[] = date('Y-m-d', strtotime("-$i days"));
    }
    $inDates = "'" . implode("','", array_map(static fn($d) => addslashes($d), $days)) . "'";

    $corpOpened = [];
    $corpClosed = [];
    foreach (jll_query_rows($conn, "
        SELECT CreatedDate AS d, COUNT(*) AS cnt
        FROM corporate_tickets
        WHERE IsActive = 1 AND ID >= $corpMin AND $corpIn AND CreatedDate IN ($inDates)
        GROUP BY CreatedDate
    ") as $r) {
        $corpOpened[$r['d']] = (int)$r['cnt'];
    }
    foreach (jll_query_rows($conn, "
        SELECT CloseDate AS d, COUNT(*) AS cnt
        FROM corporate_tickets
        WHERE IsActive = 1 AND ID >= $corpMin AND $corpIn
          AND Status = 'Closed' AND CloseDate IN ($inDates)
        GROUP BY CloseDate
    ") as $r) {
        $corpClosed[$r['d']] = (int)$r['cnt'];
    }

    $ppmOpened = [];
    $ppmClosed = [];
    foreach (jll_query_rows($conn, "
        SELECT CreatedDate AS d, COUNT(*) AS cnt
        FROM ppm_tickets
        WHERE IsActive = 1 AND ID >= $ppmMin AND $corpIn AND CreatedDate IN ($inDates)
        GROUP BY CreatedDate
    ") as $r) {
        $ppmOpened[$r['d']] = (int)$r['cnt'];
    }
    foreach (jll_query_rows($conn, "
        SELECT CloseDate AS d, COUNT(*) AS cnt
        FROM ppm_tickets
        WHERE IsActive = 1 AND ID >= $ppmMin AND $corpIn
          AND Status = 'Closed' AND CloseDate IN ($inDates)
        GROUP BY CloseDate
    ") as $r) {
        $ppmClosed[$r['d']] = (int)$r['cnt'];
    }

    $labels = [];
    $opened = [];
    $closed = [];
    foreach ($days as $d) {
        $labels[] = date('d M', strtotime($d));
        $opened[] = ($corpOpened[$d] ?? 0) + ($ppmOpened[$d] ?? 0);
        $closed[] = ($corpClosed[$d] ?? 0) + ($ppmClosed[$d] ?? 0);
    }

    return ['labels' => $labels, 'opened' => $opened, 'closed' => $closed];
}

function jll_fetch_activity_rows(mysqli $conn, string $t, array $companyIds, int $idMin, string $table): array
{
    $corpIn = jll_corp_in_clause($companyIds);
    $safe = preg_replace('/[^a-z_]/', '', $table);

    if ($safe === 'ppm_tickets') {
        return jll_query_rows($conn, "
            SELECT pt.TicketID AS ticket_code, 'PPM' AS ticket_type, pt.Status AS status,
                'PPM' AS priority, pt.CreatedDate AS created_date, pt.CloseDate AS close_date,
                pt.CreatedTime AS created_time, pt.PPMDate AS ppm_date,
                COALESCE(NULLIF(TRIM(b.BranchState), ''), 'Unknown') AS branch_state,
                COALESCE(c.CompanyName, 'Unknown') AS company_name,
                COALESCE(b.BranchSite, 'Unknown') AS branch_name,
                COALESCE(e.Name, 'Unassigned') AS employee_name
            FROM ppm_tickets pt
            LEFT JOIN branch b ON b.ID = pt.BranchID
            LEFT JOIN company c ON c.ID = pt.CorporateID
            LEFT JOIN employees e ON e.ID = pt.AssignedTo
            WHERE pt.IsActive = 1 AND pt.ID >= $idMin AND pt.$corpIn
              AND (pt.CreatedDate = '$t' OR pt.CloseDate = '$t')
        ");
    }

    return jll_query_rows($conn, "
        SELECT ct.TicketID AS ticket_code,
            CASE WHEN ct.Type = 'AMC' THEN 'AMC Breakdown' ELSE ct.Type END AS ticket_type,
            ct.Status AS status, ct.Priority AS priority,
            ct.CreatedDate AS created_date, ct.CloseDate AS close_date, ct.CreatedTime AS created_time,
            '' AS ppm_date,
            COALESCE(NULLIF(TRIM(b.BranchState), ''), 'Unknown') AS branch_state,
            COALESCE(c.CompanyName, 'Unknown') AS company_name,
            COALESCE(b.BranchSite, 'Unknown') AS branch_name,
            COALESCE(e.Name, 'Unassigned') AS employee_name
        FROM corporate_tickets ct
        LEFT JOIN branch b ON b.ID = ct.BranchID
        LEFT JOIN company c ON c.ID = ct.CorporateID
        LEFT JOIN employees e ON e.ID = ct.AssignedTo
        WHERE ct.IsActive = 1 AND ct.ID >= $idMin AND ct.$corpIn
          AND (ct.CreatedDate = '$t' OR ct.CloseDate = '$t')
    ");
}

function jll_status_history_feed(mysqli $conn, string $histFilter, array $companyIds): array
{
    $corpIn = 'ct.' . jll_corp_in_clause($companyIds);
    return jll_query_rows($conn, "
        SELECT h.TicketID AS ticket_id, ct.TicketID AS ticket_code,
            CASE WHEN ct.Type = 'AMC' THEN 'AMC Breakdown' ELSE ct.Type END AS ticket_type,
            h.Status AS status,
            COALESCE(e.Name, '—') AS assigned_to,
            COALESCE(c.CompanyName, '—') AS company_name,
            COALESCE(b.BranchState, '—') AS branch_state,
            h.CreatedTime AS event_time, h.Remarks AS remarks
        FROM corporate_ticket_status_history h
        INNER JOIN corporate_tickets ct ON ct.ID = h.TicketID
        LEFT JOIN company c ON c.ID = ct.CorporateID
        LEFT JOIN branch b ON b.ID = ct.BranchID
        LEFT JOIN employees e ON e.ID = h.AssignedTo
        WHERE $histFilter AND $corpIn
        ORDER BY h.ID DESC
        LIMIT 40
    ");
}

function jll_branch_account_count(mysqli $conn, array $companyIds): int
{
    $cached = jll_cache_read('branch_account_count', 600);
    if (is_array($cached) && isset($cached['count'])) {
        return (int)$cached['count'];
    }
    $corpIn = 'c.ID IN (' . jll_esc_ids($companyIds) . ')';
    $row = jll_query_rows($conn, "
        SELECT COUNT(DISTINCT b.ID) AS cnt
        FROM branch b
        INNER JOIN company c ON c.ID = b.CompanyID
        WHERE b.IsActive = 1 AND $corpIn
    ");
    $count = (int)($row[0]['cnt'] ?? 0);
    jll_cache_write('branch_account_count', ['count' => $count], 600);
    return $count;
}

function jll_branch_backlog_rows(mysqli $conn, array $companyIds): array
{
    $cached = jll_cache_read('branch_backlog_rows', 300);
    if (is_array($cached)) {
        return $cached;
    }

    $corpIn = jll_corp_in_clause($companyIds);
    $closed = jll_closed_status_sql();

    $rows = jll_query_rows($conn, "
        SELECT
            b.ID AS branch_id,
            COALESCE(NULLIF(TRIM(b.BranchSite), ''), 'Unknown') AS branch_name,
            COALESCE(NULLIF(TRIM(b.BranchCode), ''), '—') AS branch_code,
            COALESCE(NULLIF(TRIM(b.BranchCity), ''), '—') AS branch_city,
            COALESCE(NULLIF(TRIM(b.BranchState), ''), 'Unknown') AS branch_state,
            COALESCE(c.CompanyName, 'Unknown') AS contract_name,
            c.ID AS contract_id,
            SUM(CASE WHEN x.ticket_kind = 'corp' THEN 1 ELSE 0 END) AS corp_active,
            SUM(CASE WHEN x.ticket_kind = 'ppm' THEN 1 ELSE 0 END) AS ppm_active,
            COUNT(*) AS total_active
        FROM (
            SELECT BranchID, CorporateID, 'corp' AS ticket_kind
            FROM corporate_tickets
            WHERE IsActive = 1 AND $corpIn AND $closed
            UNION ALL
            SELECT BranchID, CorporateID, 'ppm' AS ticket_kind
            FROM ppm_tickets
            WHERE IsActive = 1 AND $corpIn AND $closed
        ) x
        INNER JOIN branch b ON b.ID = x.BranchID AND b.IsActive = 1
        INNER JOIN company c ON c.ID = x.CorporateID
        GROUP BY b.ID, b.BranchSite, b.BranchCode, b.BranchCity, b.BranchState, c.CompanyName, c.ID
        ORDER BY total_active DESC, branch_name ASC
        LIMIT 50
    ");

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'branch_id' => (int)($r['branch_id'] ?? 0),
            'branch_name' => (string)($r['branch_name'] ?? 'Unknown'),
            'branch_code' => (string)($r['branch_code'] ?? '—'),
            'branch_city' => (string)($r['branch_city'] ?? '—'),
            'branch_state' => (string)($r['branch_state'] ?? 'Unknown'),
            'contract_name' => (string)($r['contract_name'] ?? 'Unknown'),
            'contract_id' => (int)($r['contract_id'] ?? 0),
            'corp_active' => (int)($r['corp_active'] ?? 0),
            'ppm_active' => (int)($r['ppm_active'] ?? 0),
            'total_active' => (int)($r['total_active'] ?? 0),
            'opened_today' => 0,
            'closed_today' => 0,
        ];
    }
    jll_cache_write('branch_backlog_rows', $out, 300);
    return $out;
}

function jll_branch_today_counts(mysqli $conn, array $companyIds, string $today): array
{
    $t = jll_esc_date($conn, $today);
    $corpIn = jll_corp_in_clause($companyIds);
    $map = [];

    foreach (jll_query_rows($conn, "
        SELECT BranchID AS branch_id, COUNT(*) AS cnt
        FROM corporate_tickets
        WHERE IsActive = 1 AND $corpIn AND CreatedDate = '$t'
        GROUP BY BranchID
    ") as $r) {
        $id = (int)($r['branch_id'] ?? 0);
        if (!isset($map[$id])) {
            $map[$id] = ['opened_today' => 0, 'closed_today' => 0];
        }
        $map[$id]['opened_today'] += (int)($r['cnt'] ?? 0);
    }
    foreach (jll_query_rows($conn, "
        SELECT BranchID AS branch_id, COUNT(*) AS cnt
        FROM corporate_tickets
        WHERE IsActive = 1 AND $corpIn AND Status = 'Closed' AND CloseDate = '$t'
        GROUP BY BranchID
    ") as $r) {
        $id = (int)($r['branch_id'] ?? 0);
        if (!isset($map[$id])) {
            $map[$id] = ['opened_today' => 0, 'closed_today' => 0];
        }
        $map[$id]['closed_today'] += (int)($r['cnt'] ?? 0);
    }
    foreach (jll_query_rows($conn, "
        SELECT BranchID AS branch_id, COUNT(*) AS cnt
        FROM ppm_tickets
        WHERE IsActive = 1 AND $corpIn AND CreatedDate = '$t'
        GROUP BY BranchID
    ") as $r) {
        $id = (int)($r['branch_id'] ?? 0);
        if (!isset($map[$id])) {
            $map[$id] = ['opened_today' => 0, 'closed_today' => 0];
        }
        $map[$id]['opened_today'] += (int)($r['cnt'] ?? 0);
    }
    foreach (jll_query_rows($conn, "
        SELECT BranchID AS branch_id, COUNT(*) AS cnt
        FROM ppm_tickets
        WHERE IsActive = 1 AND $corpIn AND Status = 'Closed' AND CloseDate = '$t'
        GROUP BY BranchID
    ") as $r) {
        $id = (int)($r['branch_id'] ?? 0);
        if (!isset($map[$id])) {
            $map[$id] = ['opened_today' => 0, 'closed_today' => 0];
        }
        $map[$id]['closed_today'] += (int)($r['cnt'] ?? 0);
    }

    return $map;
}

function jll_merge_branch_today(array $branchRows, array $todayMap): array
{
    foreach ($branchRows as &$row) {
        $id = (int)($row['branch_id'] ?? 0);
        $today = $todayMap[$id] ?? ['opened_today' => 0, 'closed_today' => 0];
        $row['opened_today'] = (int)$today['opened_today'];
        $row['closed_today'] = (int)$today['closed_today'];
    }
    unset($row);
    return $branchRows;
}

function jll_contract_branch_summary(array $branchRows): array
{
    $contracts = [];
    foreach ($branchRows as $row) {
        $cid = (int)($row['contract_id'] ?? 0);
        $key = $cid > 0 ? (string)$cid : (string)($row['contract_name'] ?? 'Unknown');
        if (!isset($contracts[$key])) {
            $contracts[$key] = [
                'contract_id' => $cid,
                'contract_name' => (string)($row['contract_name'] ?? 'Unknown'),
                'branch_count' => 0,
                'corp_active' => 0,
                'ppm_active' => 0,
                'total_active' => 0,
                'opened_today' => 0,
                'closed_today' => 0,
                'branches' => [],
            ];
        }
        $contracts[$key]['branch_count']++;
        $contracts[$key]['corp_active'] += (int)($row['corp_active'] ?? 0);
        $contracts[$key]['ppm_active'] += (int)($row['ppm_active'] ?? 0);
        $contracts[$key]['total_active'] += (int)($row['total_active'] ?? 0);
        $contracts[$key]['opened_today'] += (int)($row['opened_today'] ?? 0);
        $contracts[$key]['closed_today'] += (int)($row['closed_today'] ?? 0);
        $contracts[$key]['branches'][] = [
            'branch_name' => (string)($row['branch_name'] ?? ''),
            'branch_code' => (string)($row['branch_code'] ?? ''),
            'branch_state' => (string)($row['branch_state'] ?? ''),
            'total_active' => (int)($row['total_active'] ?? 0),
            'opened_today' => (int)($row['opened_today'] ?? 0),
            'closed_today' => (int)($row['closed_today'] ?? 0),
        ];
    }

    $out = array_values($contracts);
    foreach ($out as &$c) {
        usort($c['branches'], static fn($a, $b) => $b['total_active'] <=> $a['total_active']);
        $c['branches'] = array_slice($c['branches'], 0, 8);
    }
    unset($c);
    usort($out, static fn($a, $b) => $b['total_active'] <=> $a['total_active']);
    return array_slice($out, 0, 20);
}

function jll_branch_account_breakdown(mysqli $conn, array $companyIds, string $today): array
{
    $backlogRows = jll_branch_backlog_rows($conn, $companyIds);
    $todayMap = jll_branch_today_counts($conn, $companyIds, $today);
    $merged = jll_merge_branch_today($backlogRows, $todayMap);

    $todayOnly = [];
    foreach ($todayMap as $branchId => $counts) {
        if (($counts['opened_today'] ?? 0) > 0 || ($counts['closed_today'] ?? 0) > 0) {
            $todayOnly[] = [
                'branch_id' => (int)$branchId,
                'opened_today' => (int)$counts['opened_today'],
                'closed_today' => (int)$counts['closed_today'],
            ];
        }
    }

    $chartTop = array_slice($merged, 0, 15);
    $chartLabels = array_map(static fn($r) => $r['branch_name'], $chartTop);
    $chartValues = array_map(static fn($r) => (int)$r['total_active'], $chartTop);

    return [
        'branch_accounts' => $merged,
        'contract_summary' => jll_contract_branch_summary($merged),
        'chart_top_branches' => [
            'labels' => $chartLabels,
            'values' => $chartValues,
        ],
        'branches_with_activity_today' => count($todayOnly),
    ];
}

function jll_ppm_upcoming(mysqli $conn, array $companyIds, int $ppmMin): array
{
    $corpIn = jll_corp_in_clause($companyIds);
    $today = jll_today();
    return jll_query_rows($conn, "
        SELECT pt.TicketID AS ticket_code, pt.Status AS status, pt.PPMDate AS ppm_date,
            COALESCE(c.CompanyName, '—') AS company_name,
            COALESCE(b.BranchSite, '—') AS branch_name,
            COALESCE(b.BranchState, '—') AS branch_state,
            COALESCE(e.Name, 'Unassigned') AS assigned_to
        FROM ppm_tickets pt
        LEFT JOIN company c ON c.ID = pt.CorporateID
        LEFT JOIN branch b ON b.ID = pt.BranchID
        LEFT JOIN employees e ON e.ID = pt.AssignedTo
        WHERE pt.IsActive = 1 AND pt.ID >= $ppmMin AND pt.$corpIn
          AND pt.Status NOT IN ('Closed','Cancelled')
          AND pt.PPMDate BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 14 DAY)
        ORDER BY pt.PPMDate ASC, pt.ID DESC
        LIMIT 20
    ");
}

function jll_fetch_live_snapshot(mysqli $conn): array
{
    $today = jll_today();
    $t = jll_esc_date($conn, $today);

    $companyMeta = jll_cached_company_ids($conn);
    $companyIds = $companyMeta['ids'] ?? [];
    if (empty($companyIds)) {
        return [
            'today' => $today,
            'generated_at' => date('Y-m-d H:i:s'),
            'corporate' => jll_fetch_corporate_meta($conn),
            'contracts' => [],
            'summary' => [
                'opened_today' => 0, 'closed_today' => 0, 'status_updates_today' => 0,
                'total_active' => 0, 'corporate_active' => 0, 'ppm_active' => 0,
                'ppm_overdue' => 0, 'ppm_compliance_pct' => 0,
            ],
            'message' => 'No active JLL contracts found for corporate ID ' . JLL_CORPORATE_HQ_ID,
        ];
    }

    $floors = jll_cached_id_floors($conn);
    $corpMin = (int)$floors['corp'];
    $ppmMin = (int)$floors['ppm'];
    $histIdFloor = (int)$floors['hist'];

    $ticketStats = jll_ticket_stats_today($conn, $today, $companyIds, $corpMin, $ppmMin);
    $backlog = jll_backlog_summary($conn, $companyIds, $corpMin, $ppmMin);
    $ppmMetrics = jll_ppm_month_metrics($conn, $companyIds, $ppmMin);
    $statusDist = jll_status_distribution($conn, $companyIds, $corpMin, $ppmMin);
    $typeBacklog = jll_type_backlog($conn, $companyIds, $corpMin);
    $trend7 = jll_seven_day_trend($conn, $companyIds, $corpMin, $ppmMin);

    $histFilter = "h.ID >= $histIdFloor AND h.CreatedDate = '$t'";
    $statusMovesToday = jll_label_count_rows(jll_query_rows($conn, "
        SELECT COALESCE(NULLIF(TRIM(h.Status), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM corporate_ticket_status_history h
        INNER JOIN corporate_tickets ct ON ct.ID = h.TicketID
        WHERE $histFilter AND ct." . jll_corp_in_clause($companyIds) . "
        GROUP BY COALESCE(NULLIF(TRIM(h.Status), ''), 'Unknown')
        ORDER BY value DESC LIMIT 20
    "));
    $statusUpdatesToday = array_sum(array_column($statusMovesToday, 'value'));
    $workflowToday = jll_workflow_from_moves($statusMovesToday);

    $activityRows = array_merge(
        jll_fetch_activity_rows($conn, $t, $companyIds, $corpMin, 'corporate_tickets'),
        jll_fetch_activity_rows($conn, $t, $companyIds, $ppmMin, 'ppm_tickets')
    );
    $aggregated = jll_aggregate_ticket_rows($activityRows, $today);

    $typeMatrix = $ticketStats['type_matrix'];
    $typeOpenedToday = [];
    $typeClosedToday = [];
    foreach ($typeMatrix as $row) {
        if ((int)$row['opened'] > 0) {
            $typeOpenedToday[] = ['label' => $row['ticket_type'], 'value' => (int)$row['opened']];
        }
        if ((int)$row['closed'] > 0) {
            $typeClosedToday[] = ['label' => $row['ticket_type'], 'value' => (int)$row['closed']];
        }
    }

    $recentOpened = [];
    $recentClosed = [];
    foreach ($activityRows as $r) {
        if ((string)($r['created_date'] ?? '') === $today) {
            $recentOpened[] = [
                'ticket_code' => $r['ticket_code'] ?? '',
                'ticket_type' => $r['ticket_type'] ?? 'R&M',
                'status' => $r['status'] ?? '',
                'priority' => $r['priority'] ?? '-',
                'assigned_to' => $r['employee_name'] ?? '-',
                'company_name' => $r['company_name'] ?? '-',
                'branch_name' => $r['branch_name'] ?? '-',
                'branch_state' => $r['branch_state'] ?? '-',
                'created_time' => $r['created_time'] ?? '',
            ];
        }
        if ((string)($r['status'] ?? '') === 'Closed' && (string)($r['close_date'] ?? '') === $today) {
            $recentClosed[] = [
                'ticket_code' => $r['ticket_code'] ?? '',
                'ticket_type' => $r['ticket_type'] ?? 'R&M',
                'status' => $r['status'] ?? '',
                'assigned_to' => $r['employee_name'] ?? '-',
                'company_name' => $r['company_name'] ?? '-',
                'branch_name' => $r['branch_name'] ?? '-',
                'branch_state' => $r['branch_state'] ?? '-',
            ];
        }
    }

    $summary = [
        'opened_today' => $ticketStats['opened_today'],
        'closed_today' => $ticketStats['closed_today'],
        'status_updates_today' => $statusUpdatesToday,
        'total_active' => $backlog['total_active'],
        'corporate_active' => $backlog['corporate_active'],
        'corporate_wip' => $backlog['corporate_wip'],
        'ppm_active' => $backlog['ppm_active'],
        'ppm_wip' => $backlog['ppm_wip'],
        'ppm_scheduled_month' => $ppmMetrics['scheduled_month'],
        'ppm_completed_month' => $ppmMetrics['completed_month'],
        'ppm_overdue' => $ppmMetrics['overdue'],
        'ppm_due_week' => $ppmMetrics['due_this_week'],
        'ppm_compliance_pct' => $ppmMetrics['compliance_pct'],
        'contract_count' => count($companyIds),
        'branch_account_count' => jll_branch_account_count($conn, $companyIds),
    ];

    $branchBreakdown = jll_branch_account_breakdown($conn, $companyIds, $today);
    $summary['branches_with_activity_today'] = $branchBreakdown['branches_with_activity_today'];

    return [
        'today' => $today,
        'generated_at' => date('Y-m-d H:i:s'),
        'corporate' => jll_fetch_corporate_meta($conn),
        'contracts' => $companyMeta['contracts'] ?? [],
        'summary' => $summary,
        'opened_today_by_status' => $aggregated['opened_today_by_status'],
        'closed_today_by_status' => $aggregated['closed_today_by_status'],
        'type_opened_today' => $typeOpenedToday,
        'type_closed_today' => $typeClosedToday,
        'type_matrix' => $typeMatrix,
        'type_backlog' => $typeBacklog,
        'status_moves_today' => $statusMovesToday,
        'workflow_today' => $workflowToday,
        'status_distribution' => $statusDist,
        'trend_7day' => $trend7,
        'top_states_today' => $aggregated['top_states_today'],
        'top_employees_today' => $aggregated['top_employees_today'],
        'top_contracts_today' => $aggregated['top_contracts_today'],
        'top_branches_today' => $aggregated['top_branches_today'],
        'recent_opened' => $recentOpened,
        'recent_closed' => $recentClosed,
        'status_history_feed' => jll_status_history_feed($conn, $histFilter, $companyIds),
        'ppm_upcoming' => jll_ppm_upcoming($conn, $companyIds, $ppmMin),
        'branch_accounts' => $branchBreakdown['branch_accounts'],
        'contract_branch_summary' => $branchBreakdown['contract_summary'],
        'chart_top_branches' => $branchBreakdown['chart_top_branches'],
    ];
}
