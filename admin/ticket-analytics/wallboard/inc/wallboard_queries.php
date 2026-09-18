<?php
declare(strict_types=1);

/**
 * Fast today-only wallboard queries — equality on date columns only (no LEFT() scans).
 */
function wallboard_today(): string
{
    return date('Y-m-d');
}

function wallboard_query_rows(mysqli $conn, string $sql): array
{
    $res = @mysqli_query($conn, $sql);
    if (!$res) {
        return [];
    }
    $rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_free_result($res);
    return $rows ?: [];
}

function wallboard_label_count_rows(array $rows): array
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

function wallboard_count_today(mysqli $conn, string $sql): int
{
    $row = wallboard_query_rows($conn, $sql);
    return (int)($row[0]['cnt'] ?? 0);
}

function wallboard_esc_date(mysqli $conn, string $today): string
{
    return mysqli_real_escape_string($conn, $today);
}

/** Scan only recent rows when date columns are not indexed (cached per request). */
function wallboard_table_id_floor(mysqli $conn, string $table, int $lookback = 50000): int
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
    $row = wallboard_query_rows($conn, "SELECT MAX(ID) AS max_id FROM `{$safe}` {$where}");
    $cache[$cacheKey] = max(1, (int)($row[0]['max_id'] ?? 0) - $lookback);
    return $cache[$cacheKey];
}

function wallboard_status_history_id_floor(mysqli $conn): int
{
    return wallboard_table_id_floor($conn, 'corporate_ticket_status_history', 40000);
}

function wallboard_quote_metrics_today(mysqli $conn, string $today): array
{
    $t = wallboard_esc_date($conn, $today);
    $row = wallboard_query_rows($conn, "
        SELECT
            SUM(CASE WHEN QuotationStatus = 'Quote Sent Approval Pending' THEN 1 ELSE 0 END) AS sent,
            SUM(CASE WHEN QuotationStatus = 'Quote Approved' THEN 1 ELSE 0 END) AS approved
        FROM corporate_ticket_quotation
        WHERE IFNULL(IsActive, 1) = 1 AND CreatedDate = '$t'
    ");
    $amountRow = wallboard_query_rows($conn, "
        SELECT COALESCE(SUM(qi.TotalPrice), 0) AS amount
        FROM corporate_ticket_quotation q
        INNER JOIN corporate_ticket_quotation_items qi
            ON qi.QuotationID = q.ID AND IFNULL(qi.IsActive, 1) = 1
        WHERE IFNULL(q.IsActive, 1) = 1
          AND q.QuotationStatus = 'Quote Approved'
          AND q.CreatedDate = '$t'
    ");
    return [
        'quote_sent_approval_today' => (int)($row[0]['sent'] ?? 0),
        'quote_approved_today' => (int)($row[0]['approved'] ?? 0),
        'quote_approved_amount' => (float)($amountRow[0]['amount'] ?? 0),
    ];
}

/** One pass per ticket table for KPI totals + type matrix. */
function wallboard_ticket_stats_today(mysqli $conn, string $today, int $corpMin, int $ppmMin): array
{
    $t = wallboard_esc_date($conn, $today);

    $corpRows = wallboard_query_rows($conn, "
        SELECT
            CASE WHEN Type = 'AMC' THEN 'AMC Breakdown' ELSE Type END AS ticket_type,
            SUM(CASE WHEN CreatedDate = '$t' THEN 1 ELSE 0 END) AS opened,
            SUM(CASE WHEN Status = 'Closed' AND CloseDate = '$t' THEN 1 ELSE 0 END) AS closed
        FROM corporate_tickets
        WHERE IsActive = 1 AND ID >= $corpMin
        GROUP BY CASE WHEN Type = 'AMC' THEN 'AMC Breakdown' ELSE Type END
    ");
    $ppmRows = wallboard_query_rows($conn, "
        SELECT
            SUM(CASE WHEN CreatedDate = '$t' THEN 1 ELSE 0 END) AS opened,
            SUM(CASE WHEN Status = 'Closed' AND CloseDate = '$t' THEN 1 ELSE 0 END) AS closed
        FROM ppm_tickets
        WHERE IsActive = 1 AND ID >= $ppmMin
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

function wallboard_status_by_table_today(
    mysqli $conn,
    string $table,
    string $today,
    int $idMin,
    bool $closedOnly = false
): array {
    $t = wallboard_esc_date($conn, $today);
    $where = "IsActive = 1 AND ID >= $idMin";
    if ($closedOnly) {
        $where .= " AND Status = 'Closed' AND CloseDate = '$t'";
    } else {
        $where .= " AND CreatedDate = '$t'";
    }
    return wallboard_label_count_rows(wallboard_query_rows($conn, "
        SELECT COALESCE(NULLIF(TRIM(Status), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM $table WHERE $where
        GROUP BY COALESCE(NULLIF(TRIM(Status), ''), 'Unknown')
        ORDER BY value DESC LIMIT 15
    "));
}

function wallboard_status_history_breakdown(mysqli $conn, string $histFilter): array
{
    return wallboard_label_count_rows(wallboard_query_rows($conn, "
        SELECT COALESCE(NULLIF(TRIM(h.Status), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM corporate_ticket_status_history h
        WHERE $histFilter
        GROUP BY COALESCE(NULLIF(TRIM(h.Status), ''), 'Unknown')
        ORDER BY value DESC
        LIMIT 20
    "));
}

function wallboard_workflow_bucket(string $status): string
{
    $status = trim($status);
    if ($status === 'Closed') {
        return 'Closed (movement)';
    }
    if ($status === 'Quote Approved') {
        return 'Quote Approved';
    }
    if (stripos($status, 'Quote Sent') !== false || stripos($status, 'Approval Pending') !== false) {
        return 'Quote sent / approval';
    }
    if (in_array($status, ['Assigned', 'Raised'], true)) {
        return 'Assigned / Raised';
    }
    if (in_array($status, ['Work in Progress', 'Work In Progress', 'Generate OTP to Start'], true)) {
        return 'Work in progress';
    }
    return $status !== '' ? $status : 'Other';
}

function wallboard_workflow_from_moves(array $statusMoves): array
{
    $buckets = [];
    foreach ($statusMoves as $row) {
        $label = wallboard_workflow_bucket((string)$row['label']);
        $buckets[$label] = ($buckets[$label] ?? 0) + (int)$row['value'];
    }
    $out = [];
    foreach ($buckets as $label => $value) {
        $out[] = ['label' => $label, 'value' => $value];
    }
    usort($out, static fn($a, $b) => $b['value'] <=> $a['value']);
    return $out;
}

/** One joined pass per ticket table; aggregate panels in PHP. */
function wallboard_aggregate_ticket_rows(array $rows, string $today): array
{
    $openedStatus = [];
    $closedStatus = [];
    $states = [];
    $companies = [];
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
        $company = trim((string)($r['company_name'] ?? '')) ?: 'Unknown';
        $branch = trim((string)($r['branch_name'] ?? '')) ?: 'Unknown';
        $emp = trim((string)($r['employee_name'] ?? '')) ?: 'Unassigned';

        if ($isOpened || $isClosed) {
            $states[$state] = ($states[$state] ?? 0) + 1;
            $companies[$company] = ($companies[$company] ?? 0) + 1;
            $branches[$branch] = ($branches[$branch] ?? 0) + 1;
        }
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
        'top_states_today' => array_slice($toChips($states), 0, 16),
        'top_companies_today' => array_slice($toChips($companies), 0, 12),
        'top_branches_today' => array_slice($toChips($branches), 0, 12),
        'top_employees_today' => array_slice($empOut, 0, 15),
    ];
}

function wallboard_fetch_ticket_activity_rows(
    mysqli $conn,
    string $t,
    int $idMin,
    string $table
): array {
    $safe = preg_replace('/[^a-z_]/', '', $table);
    if ($safe === 'ppm_tickets') {
        return wallboard_query_rows($conn, "
            SELECT pt.TicketID AS ticket_code, 'PPM' AS ticket_type, pt.Status AS status,
                'PPM' AS priority, pt.CreatedDate AS created_date, pt.CloseDate AS close_date,
                pt.CreatedTime AS created_time,
                COALESCE(NULLIF(TRIM(b.BranchState), ''), 'Unknown') AS branch_state,
                COALESCE(c.CompanyName, 'Unknown') AS company_name,
                COALESCE(b.BranchSite, 'Unknown') AS branch_name,
                COALESCE(e.Name, 'Unassigned') AS employee_name
            FROM ppm_tickets pt
            LEFT JOIN branch b ON b.ID = pt.BranchID
            LEFT JOIN company c ON c.ID = pt.CorporateID
            LEFT JOIN employees e ON e.ID = pt.AssignedTo
            WHERE pt.IsActive = 1 AND pt.ID >= $idMin
              AND (pt.CreatedDate = '$t' OR pt.CloseDate = '$t')
        ");
    }
    return wallboard_query_rows($conn, "
        SELECT ct.TicketID AS ticket_code,
            CASE WHEN ct.Type = 'AMC' THEN 'AMC Breakdown' ELSE ct.Type END AS ticket_type,
            ct.Status AS status, ct.Priority AS priority,
            ct.CreatedDate AS created_date, ct.CloseDate AS close_date, ct.CreatedTime AS created_time,
            COALESCE(NULLIF(TRIM(b.BranchState), ''), 'Unknown') AS branch_state,
            COALESCE(c.CompanyName, 'Unknown') AS company_name,
            COALESCE(b.BranchSite, 'Unknown') AS branch_name,
            COALESCE(e.Name, 'Unassigned') AS employee_name
        FROM corporate_tickets ct
        LEFT JOIN branch b ON b.ID = ct.BranchID
        LEFT JOIN company c ON c.ID = ct.CorporateID
        LEFT JOIN employees e ON e.ID = ct.AssignedTo
        WHERE ct.IsActive = 1 AND ct.ID >= $idMin
          AND (ct.CreatedDate = '$t' OR ct.CloseDate = '$t')
    ");
}

function wallboard_merge_counts(array ...$lists): array
{
    $map = [];
    foreach ($lists as $list) {
        foreach ($list as $row) {
            $label = $row['label'];
            $map[$label] = ($map[$label] ?? 0) + (int)$row['value'];
        }
    }
    $out = [];
    foreach ($map as $label => $value) {
        $out[] = ['label' => $label, 'value' => $value];
    }
    usort($out, static fn($a, $b) => $b['value'] <=> $a['value']);
    return $out;
}

function wallboard_fetch_live_snapshot(mysqli $conn): array
{
    require_once __DIR__ . '/wallboard_cache.php';

    $today = wallboard_today();
    $t = wallboard_esc_date($conn, $today);

    $floors = wallboard_cached_id_floors($conn);
    $corpMin = (int)$floors['corp'];
    $ppmMin = (int)$floors['ppm'];
    $histIdFloor = (int)$floors['hist'];

    $ticketStats = wallboard_ticket_stats_today($conn, $today, $corpMin, $ppmMin);
    $quotes = wallboard_quote_metrics_today($conn, $today);
    $histFilter = "h.ID >= $histIdFloor AND h.CreatedDate = '$t'";

    $statusMovesToday = wallboard_status_history_breakdown($conn, $histFilter);
    $statusUpdatesToday = array_sum(array_column($statusMovesToday, 'value'));
    $workflowToday = wallboard_workflow_from_moves($statusMovesToday);

    $activityRows = array_merge(
        wallboard_fetch_ticket_activity_rows($conn, $t, $corpMin, 'corporate_tickets'),
        wallboard_fetch_ticket_activity_rows($conn, $t, $ppmMin, 'ppm_tickets')
    );
    $aggregated = wallboard_aggregate_ticket_rows($activityRows, $today);
    $openedTodayByStatus = $aggregated['opened_today_by_status'];
    $closedTodayByStatus = $aggregated['closed_today_by_status'];
    $topStatesToday = $aggregated['top_states_today'];
    $topCompaniesToday = $aggregated['top_companies_today'];
    $topBranchesToday = $aggregated['top_branches_today'];
    $topEmployeesToday = $aggregated['top_employees_today'];

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

    $statusHistoryFeed = wallboard_query_rows($conn, "
        SELECT TicketID AS ticket_code, 'R&M' AS ticket_type, Status AS status,
            '-' AS assigned_to, '-' AS company_name, '-' AS branch_state, CreatedTime AS event_time
        FROM corporate_ticket_status_history h
        WHERE $histFilter
        ORDER BY h.ID DESC
        LIMIT 35
    ");

    $summary = array_merge([
        'opened_today' => $ticketStats['opened_today'],
        'closed_today' => $ticketStats['closed_today'],
    ], $quotes);
    $summary['status_updates_today'] = $statusUpdatesToday;

    return [
        'today' => $today,
        'generated_at' => date('Y-m-d H:i:s'),
        'summary' => $summary,
        'opened_today_by_status' => $openedTodayByStatus,
        'closed_today_by_status' => $closedTodayByStatus,
        'type_opened_today' => $typeOpenedToday,
        'type_closed_today' => $typeClosedToday,
        'type_matrix' => $typeMatrix,
        'status_moves_today' => $statusMovesToday,
        'workflow_today' => $workflowToday,
        'top_states_today' => $topStatesToday,
        'top_employees_today' => $topEmployeesToday,
        'top_companies_today' => $topCompaniesToday,
        'top_branches_today' => $topBranchesToday,
        'recent_opened' => $recentOpened,
        'recent_closed' => $recentClosed,
        'status_history_feed' => $statusHistoryFeed,
    ];
}
