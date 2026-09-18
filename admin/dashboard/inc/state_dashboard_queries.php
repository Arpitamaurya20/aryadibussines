<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/ticket-analytics/wallboard/inc/wallboard_queries.php';
require_once __DIR__ . '/state_dashboard_scope.php';

/** Indian financial year: Apr 1 – Mar 31 */
function state_dashboard_current_fy_range(?string $asOf = null): array
{
    $asOf = $asOf ?: date('Y-m-d');
    $y = (int)date('Y', strtotime($asOf));
    $m = (int)date('n', strtotime($asOf));
    if ($m >= 4) {
        $start = sprintf('%d-04-01', $y);
        $endLabel = sprintf('%d-03-31', $y + 1);
    } else {
        $start = sprintf('%d-04-01', $y - 1);
        $endLabel = sprintf('%d-03-31', $y);
    }
    $end = min($asOf, $endLabel);
    return ['start' => $start, 'end' => $end, 'fy_label' => state_dashboard_fy_label($start)];
}

function state_dashboard_previous_fy_range(?string $asOf = null): array
{
    $current = state_dashboard_current_fy_range($asOf);
    $y = (int)date('Y', strtotime($current['start']));
    $start = sprintf('%d-04-01', $y - 1);
    $end = sprintf('%d-03-31', $y);
    return ['start' => $start, 'end' => $end, 'fy_label' => state_dashboard_fy_label($start)];
}

function state_dashboard_fy_label(string $fyStart): string
{
    $y = (int)date('Y', strtotime($fyStart));
    return 'FY ' . $y . '-' . substr((string)($y + 1), -2);
}

function state_dashboard_esc(mysqli $conn, string $v): string
{
    return mysqli_real_escape_string($conn, $v);
}

/** Quotation display date (revised QuotationDate when set, else record CreatedDate). */
function state_dashboard_quote_date_expr(string $alias = 'q'): string
{
    return "COALESCE(NULLIF(TRIM($alias.QuotationDate), ''), $alias.CreatedDate)";
}

function state_dashboard_quote_value_subquery(): string
{
    return "(
        SELECT QuotationID, COALESCE(SUM(TotalPrice), 0) AS quote_value
        FROM corporate_ticket_quotation_items
        WHERE IFNULL(IsActive, 1) = 1
        GROUP BY QuotationID
    )";
}

/** Final client approval event in period (matches when quote was approved, not when draft was created). */
function state_dashboard_quote_approved_in_period_sql(string $start, string $end): string
{
    return "EXISTS (
        SELECT 1 FROM corporate_ticket_quotation_history h
        WHERE h.QuotationID = q.ID
          AND h.QuotationStatus = 'Quote Approved'
          AND h.CreatedDate >= '$start'
          AND h.CreatedDate <= '$end'
    )";
}

function state_dashboard_quote_first_approval_subquery(): string
{
    return "(
        SELECT QuotationID, MIN(CreatedDate) AS approved_date
        FROM corporate_ticket_quotation_history
        WHERE QuotationStatus = 'Quote Approved'
        GROUP BY QuotationID
    )";
}

function state_dashboard_quote_pipeline_statuses_sql(): string
{
    return "'Quote Approved By State', 'Quote Approved By Finance'";
}

function state_dashboard_normalize_filters(array $input, array $scope, mysqli $conn): array
{
    $preset = isset($input['preset']) ? trim((string)$input['preset']) : 'current_fy';

    if ($preset === 'previous_fy') {
        $range = state_dashboard_previous_fy_range();
    } elseif ($preset === 'this_month') {
        $range = [
            'start' => date('Y-m-01'),
            'end' => date('Y-m-d'),
            'fy_label' => date('F Y'),
        ];
    } elseif ($preset === 'custom' && !empty($input['start_date']) && !empty($input['end_date'])) {
        $start = (string)$input['start_date'];
        $end = (string)$input['end_date'];
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }
        $range = ['start' => $start, 'end' => $end, 'fy_label' => $start . ' to ' . $end];
    } else {
        $range = state_dashboard_current_fy_range();
    }

    $scopeMode = $scope['mode'] ?? 'state';
    $allowedStateNames = $scope['state_names'] ?? [];
    $allowedBranchIds = $scope['branch_ids'] ?? [];

    $stateNames = $allowedStateNames;
    if ($scopeMode === 'state' && !empty($input['state']) && is_string($input['state'])) {
        $picked = trim($input['state']);
        if ($picked !== '' && $picked !== 'all' && in_array($picked, $allowedStateNames, true)) {
            $stateNames = [$picked];
        }
    }

    $corporateId = isset($input['corporate_id']) ? (int)$input['corporate_id'] : 0;
    $branchId = isset($input['branch_id']) ? (int)$input['branch_id'] : 0;
    if ($branchId > 0 && $scopeMode === 'branch' && !in_array($branchId, $allowedBranchIds, true)) {
        $branchId = 0;
    }
    $ticketType = isset($input['ticket_type']) ? trim((string)$input['ticket_type']) : '';

    $stateObject = new State($conn);
    $stateInClause = $scopeMode === 'state'
        ? $stateObject->buildBranchStateInClause($stateNames)
        : '';

    $branchInClause = $scopeMode === 'branch'
        ? manager_dashboard_build_branch_in_clause($allowedBranchIds)
        : '';

    return [
        'preset' => $preset,
        'start_date' => $range['start'],
        'end_date' => $range['end'],
        'period_label' => $range['fy_label'],
        'scope_mode' => $scopeMode,
        'state_names' => $stateNames,
        'state_in_clause' => $stateInClause,
        'branch_ids' => $allowedBranchIds,
        'branch_in_clause' => $branchInClause,
        'corporate_id' => max(0, $corporateId),
        'branch_id' => max(0, $branchId),
        'ticket_type' => $ticketType,
    ];
}

function state_dashboard_extra_ticket_where(array $filters, string $alias = 'ct'): string
{
    $parts = [];
    if ($filters['corporate_id'] > 0) {
        $parts[] = $alias . '.CorporateID = ' . (int)$filters['corporate_id'];
    }
    if ($filters['branch_id'] > 0) {
        $parts[] = $alias . '.BranchID = ' . (int)$filters['branch_id'];
    }
    if ($filters['ticket_type'] !== '' && $filters['ticket_type'] !== 'PPM') {
        $type = str_replace("'", "''", $filters['ticket_type']);
        if ($filters['ticket_type'] === 'AMC Breakdown') {
            $parts[] = $alias . ".Type = 'AMC'";
        } else {
            $parts[] = $alias . ".Type = '" . $type . "'";
        }
    }
    return $parts ? ' AND ' . implode(' AND ', $parts) : '';
}

function state_dashboard_fetch_snapshot(mysqli $conn, array $filters): array
{
    if (!state_dashboard_scope_is_valid($filters)) {
        return state_dashboard_empty_snapshot($filters);
    }

    $start = state_dashboard_esc($conn, $filters['start_date']);
    $end = state_dashboard_esc($conn, $filters['end_date']);
    $extraCt = state_dashboard_extra_ticket_where($filters, 'ct');
    $extraPt = state_dashboard_extra_ticket_where($filters, 'pt');
    $includePpm = ($filters['ticket_type'] === '' || $filters['ticket_type'] === 'PPM');
    $includeCorp = ($filters['ticket_type'] === '' || $filters['ticket_type'] !== 'PPM');

    $ticketStats = ['opened' => 0, 'closed' => 0, 'type_matrix' => []];
    if ($includeCorp) {
        $ticketStats = state_dashboard_ticket_stats_period($conn, $start, $end, $filters, $extraCt, $extraPt, $includePpm);
    } elseif ($includePpm) {
        $ticketStats = state_dashboard_ppm_stats_only($conn, $start, $end, $filters, $extraPt);
    }

    $quotes = state_dashboard_quote_metrics_period($conn, $start, $end, $filters);
    $histFilter = "h.CreatedDate >= '$start' AND h.CreatedDate <= '$end'";

    $statusMoves = state_dashboard_status_history_breakdown($conn, $histFilter, $filters);
    $statusUpdates = array_sum(array_column($statusMoves, 'value'));
    $workflow = wallboard_workflow_from_moves($statusMoves);

    $activityRows = [];
    if ($includeCorp) {
        $activityRows = array_merge(
            $activityRows,
            state_dashboard_fetch_activity_rows($conn, $start, $end, 'corporate_tickets', $filters, $extraCt)
        );
    }
    if ($includePpm) {
        $activityRows = array_merge(
            $activityRows,
            state_dashboard_fetch_activity_rows($conn, $start, $end, 'ppm_tickets', $filters, $extraPt)
        );
    }

    $aggregated = state_dashboard_aggregate_period_rows($activityRows, $filters['start_date'], $filters['end_date']);

    $typeMatrix = $ticketStats['type_matrix'];
    $typeOpened = [];
    $typeClosed = [];
    foreach ($typeMatrix as $row) {
        if ((int)$row['opened'] > 0) {
            $typeOpened[] = ['label' => $row['ticket_type'], 'value' => (int)$row['opened']];
        }
        if ((int)$row['closed'] > 0) {
            $typeClosed[] = ['label' => $row['ticket_type'], 'value' => (int)$row['closed']];
        }
    }

    $recentOpened = [];
    $recentClosed = [];
    foreach ($activityRows as $r) {
        $cd = (string)($r['created_date'] ?? '');
        $cld = (string)($r['close_date'] ?? '');
        if ($cd >= $filters['start_date'] && $cd <= $filters['end_date']) {
            $recentOpened[] = [
                'ticket_code' => $r['ticket_code'] ?? '',
                'ticket_type' => $r['ticket_type'] ?? 'R&M',
                'status' => $r['status'] ?? '',
                'assigned_to' => $r['employee_name'] ?? '-',
                'company_name' => $r['company_name'] ?? '-',
                'branch_name' => $r['branch_name'] ?? '-',
                'created_time' => $r['created_time'] ?? '',
                'created_date' => $cd,
            ];
        }
        if ((string)($r['status'] ?? '') === 'Closed' && $cld >= $filters['start_date'] && $cld <= $filters['end_date']) {
            $recentClosed[] = [
                'ticket_code' => $r['ticket_code'] ?? '',
                'ticket_type' => $r['ticket_type'] ?? 'R&M',
                'status' => $r['status'] ?? '',
                'assigned_to' => $r['employee_name'] ?? '-',
                'company_name' => $r['company_name'] ?? '-',
                'branch_name' => $r['branch_name'] ?? '-',
                'close_date' => $cld,
            ];
        }
    }
    usort($recentOpened, static fn($a, $b) => strcmp($b['created_date'] . $b['created_time'], $a['created_date'] . $a['created_time']));
    usort($recentClosed, static fn($a, $b) => strcmp($b['close_date'] ?? '', $a['close_date'] ?? ''));

    $statusHistoryFeed = state_dashboard_status_history_feed($conn, $histFilter, $filters);
    $context = state_dashboard_context_counts($conn, $filters);

    $summary = array_merge([
        'opened' => $ticketStats['opened'],
        'closed' => $ticketStats['closed'],
    ], $quotes);
    $summary['status_updates'] = $statusUpdates;

    require_once __DIR__ . '/state_dashboard_charts.php';
    $snapshotPartial = [
        'type_matrix' => $typeMatrix,
        'type_opened' => $typeOpened,
        'opened_by_status' => $aggregated['opened_by_status'],
        'workflow' => $workflow,
        'top_employees' => $aggregated['top_employees'],
        'top_companies' => $aggregated['top_companies'],
        'top_branches' => $aggregated['top_branches'],
    ];
    $charts = state_dashboard_build_charts($conn, $filters, $snapshotPartial);

    return [
        'period' => [
            'start' => $filters['start_date'],
            'end' => $filters['end_date'],
            'label' => $filters['period_label'],
            'preset' => $filters['preset'],
        ],
        'filters_applied' => [
            'scope_mode' => $filters['scope_mode'] ?? 'state',
            'states' => $filters['state_names'],
            'corporate_id' => $filters['corporate_id'],
            'branch_id' => $filters['branch_id'],
            'ticket_type' => $filters['ticket_type'],
        ],
        'generated_at' => date('Y-m-d H:i:s'),
        'scope_mode' => $filters['scope_mode'] ?? 'state',
        'states' => $filters['state_names'],
        'summary' => $summary,
        'context' => $context,
        'opened_by_status' => $aggregated['opened_by_status'],
        'closed_by_status' => $aggregated['closed_by_status'],
        'type_opened' => $typeOpened,
        'type_closed' => $typeClosed,
        'type_matrix' => $typeMatrix,
        'status_moves' => $statusMoves,
        'workflow' => $workflow,
        'top_employees' => $aggregated['top_employees'],
        'top_companies' => $aggregated['top_companies'],
        'top_branches' => $aggregated['top_branches'],
        'recent_opened' => array_slice($recentOpened, 0, 30),
        'recent_closed' => array_slice($recentClosed, 0, 30),
        'status_history_feed' => $statusHistoryFeed,
        'charts' => $charts,
    ];
}

function state_dashboard_empty_snapshot(array $filters = []): array
{
    if (!function_exists('state_dashboard_empty_charts')) {
        require_once __DIR__ . '/state_dashboard_charts.php';
    }
    $period = state_dashboard_current_fy_range();
    return [
        'period' => [
            'start' => $filters['start_date'] ?? $period['start'],
            'end' => $filters['end_date'] ?? $period['end'],
            'label' => $filters['period_label'] ?? $period['fy_label'],
            'preset' => $filters['preset'] ?? 'current_fy',
        ],
        'filters_applied' => ['states' => [], 'corporate_id' => 0, 'branch_id' => 0, 'ticket_type' => ''],
        'generated_at' => date('Y-m-d H:i:s'),
        'states' => [],
        'summary' => [
            'opened' => 0, 'closed' => 0,
            'quote_sent' => 0, 'quote_approved' => 0, 'quote_rejected' => 0, 'quote_pending' => 0,
            'quote_sent_amount' => 0, 'quote_approved_amount' => 0, 'quote_rejected_amount' => 0,
            'quote_pending_amount' => 0, 'status_updates' => 0,
        ],
        'context' => ['branches' => 0, 'companies' => 0],
        'opened_by_status' => [], 'closed_by_status' => [],
        'type_opened' => [], 'type_closed' => [], 'type_matrix' => [],
        'status_moves' => [], 'workflow' => [],
        'top_employees' => [], 'top_companies' => [], 'top_branches' => [],
        'recent_opened' => [], 'recent_closed' => [], 'status_history_feed' => [],
        'charts' => state_dashboard_empty_charts(),
    ];
}

function state_dashboard_quote_metrics_period(mysqli $conn, string $start, string $end, array $filters): array
{
    $extra = '';
    if ($filters['corporate_id'] > 0) {
        $extra .= ' AND ct.CorporateID = ' . (int)$filters['corporate_id'];
    }
    if ($filters['branch_id'] > 0) {
        $extra .= ' AND ct.BranchID = ' . (int)$filters['branch_id'];
    }
    if ($filters['ticket_type'] !== '' && $filters['ticket_type'] !== 'PPM') {
        $type = $filters['ticket_type'] === 'AMC Breakdown' ? 'AMC' : $filters['ticket_type'];
        $extra .= " AND ct.Type = '" . state_dashboard_esc($conn, $type) . "'";
    }
    if ($filters['ticket_type'] === 'PPM') {
        return state_dashboard_empty_quote_metrics();
    }

    $branchJoin = state_dashboard_branch_join_on($filters, 'b', 'ct.BranchID');
    $quoteDate = state_dashboard_quote_date_expr('q');
    $approvedInPeriod = state_dashboard_quote_approved_in_period_sql($start, $end);
    $pipelineStatuses = state_dashboard_quote_pipeline_statuses_sql();
    $qv = state_dashboard_quote_value_subquery();

    $row = wallboard_query_rows($conn, "
        SELECT
            SUM(CASE WHEN q.QuotationStatus = 'Quote Sent Approval Pending'
                AND $quoteDate >= '$start' AND $quoteDate <= '$end' THEN 1 ELSE 0 END) AS sent_cnt,
            SUM(CASE WHEN q.QuotationStatus = 'Quote Sent Approval Pending'
                AND $quoteDate >= '$start' AND $quoteDate <= '$end' THEN qv.quote_value ELSE 0 END) AS sent_amt,
            SUM(CASE WHEN q.QuotationStatus = 'Quote Approved' AND $approvedInPeriod THEN 1 ELSE 0 END) AS approved_cnt,
            SUM(CASE WHEN q.QuotationStatus = 'Quote Approved' AND $approvedInPeriod THEN qv.quote_value ELSE 0 END) AS approved_amt,
            SUM(CASE WHEN q.QuotationStatus LIKE '%Reject%'
                AND $quoteDate >= '$start' AND $quoteDate <= '$end' THEN 1 ELSE 0 END) AS rejected_cnt,
            SUM(CASE WHEN q.QuotationStatus LIKE '%Reject%'
                AND $quoteDate >= '$start' AND $quoteDate <= '$end' THEN qv.quote_value ELSE 0 END) AS rejected_amt,
            SUM(CASE WHEN q.QuotationStatus IN ($pipelineStatuses)
                AND $quoteDate >= '$start' AND $quoteDate <= '$end' THEN 1 ELSE 0 END) AS pending_cnt,
            SUM(CASE WHEN q.QuotationStatus IN ($pipelineStatuses)
                AND $quoteDate >= '$start' AND $quoteDate <= '$end' THEN qv.quote_value ELSE 0 END) AS pending_amt
        FROM corporate_ticket_quotation q
        INNER JOIN $qv qv ON qv.QuotationID = q.ID
        INNER JOIN corporate_tickets ct ON ct.ID = q.TicketID AND ct.IsActive = 1
        $branchJoin
        WHERE IFNULL(q.IsActive, 1) = 1
          $extra
    ");

    $r = $row[0] ?? [];
    return [
        'quote_sent' => (int)($r['sent_cnt'] ?? 0),
        'quote_sent_amount' => (float)($r['sent_amt'] ?? 0),
        'quote_approved' => (int)($r['approved_cnt'] ?? 0),
        'quote_approved_amount' => (float)($r['approved_amt'] ?? 0),
        'quote_rejected' => (int)($r['rejected_cnt'] ?? 0),
        'quote_rejected_amount' => (float)($r['rejected_amt'] ?? 0),
        'quote_pending' => (int)($r['pending_cnt'] ?? 0),
        'quote_pending_amount' => (float)($r['pending_amt'] ?? 0),
    ];
}

function state_dashboard_empty_quote_metrics(): array
{
    return [
        'quote_sent' => 0,
        'quote_sent_amount' => 0.0,
        'quote_approved' => 0,
        'quote_approved_amount' => 0.0,
        'quote_rejected' => 0,
        'quote_rejected_amount' => 0.0,
        'quote_pending' => 0,
        'quote_pending_amount' => 0.0,
    ];
}

function state_dashboard_ticket_stats_period(mysqli $conn, string $start, string $end, array $filters, string $extraCt, string $extraPt, bool $includePpm): array
{
    $branchJoinCt = state_dashboard_branch_join_on($filters, 'b', 'ct.BranchID');
    $corpRows = wallboard_query_rows($conn, "
        SELECT
            CASE WHEN ct.Type = 'AMC' THEN 'AMC Breakdown' ELSE ct.Type END AS ticket_type,
            SUM(CASE WHEN ct.CreatedDate >= '$start' AND ct.CreatedDate <= '$end' THEN 1 ELSE 0 END) AS opened,
            SUM(CASE WHEN ct.Status = 'Closed' AND ct.CloseDate >= '$start' AND ct.CloseDate <= '$end' THEN 1 ELSE 0 END) AS closed
        FROM corporate_tickets ct
        $branchJoinCt
        WHERE ct.IsActive = 1 $extraCt
        GROUP BY CASE WHEN ct.Type = 'AMC' THEN 'AMC Breakdown' ELSE ct.Type END
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

    if ($includePpm) {
        $branchJoinPt = state_dashboard_branch_join_on($filters, 'b', 'pt.BranchID');
        $ppmRows = wallboard_query_rows($conn, "
            SELECT
                SUM(CASE WHEN pt.CreatedDate >= '$start' AND pt.CreatedDate <= '$end' THEN 1 ELSE 0 END) AS opened,
                SUM(CASE WHEN pt.Status = 'Closed' AND pt.CloseDate >= '$start' AND pt.CloseDate <= '$end' THEN 1 ELSE 0 END) AS closed
            FROM ppm_tickets pt
            $branchJoinPt
            WHERE pt.IsActive = 1 $extraPt
        ");
        $ppmOpened = (int)($ppmRows[0]['opened'] ?? 0);
        $ppmClosed = (int)($ppmRows[0]['closed'] ?? 0);
        if ($ppmOpened > 0 || $ppmClosed > 0) {
            $map['PPM'] = ['ticket_type' => 'PPM', 'opened' => $ppmOpened, 'closed' => $ppmClosed];
            $openedTotal += $ppmOpened;
            $closedTotal += $ppmClosed;
        }
    }

    return ['opened' => $openedTotal, 'closed' => $closedTotal, 'type_matrix' => array_values($map)];
}

function state_dashboard_ppm_stats_only(mysqli $conn, string $start, string $end, array $filters, string $extraPt): array
{
    $branchJoinPt = state_dashboard_branch_join_on($filters, 'b', 'pt.BranchID');
    $ppmRows = wallboard_query_rows($conn, "
        SELECT
            SUM(CASE WHEN pt.CreatedDate >= '$start' AND pt.CreatedDate <= '$end' THEN 1 ELSE 0 END) AS opened,
            SUM(CASE WHEN pt.Status = 'Closed' AND pt.CloseDate >= '$start' AND pt.CloseDate <= '$end' THEN 1 ELSE 0 END) AS closed
        FROM ppm_tickets pt
        $branchJoinPt
        WHERE pt.IsActive = 1 $extraPt
    ");
    $o = (int)($ppmRows[0]['opened'] ?? 0);
    $c = (int)($ppmRows[0]['closed'] ?? 0);
    return [
        'opened' => $o,
        'closed' => $c,
        'type_matrix' => ($o > 0 || $c > 0) ? [['ticket_type' => 'PPM', 'opened' => $o, 'closed' => $c]] : [],
    ];
}

function state_dashboard_status_history_breakdown(mysqli $conn, string $histFilter, array $filters): array
{
    $extra = state_dashboard_extra_ticket_where($filters, 'ct');
    if ($filters['ticket_type'] === 'PPM') {
        return [];
    }
    $branchJoin = state_dashboard_branch_join_on($filters, 'b', 'ct.BranchID');
    return wallboard_label_count_rows(wallboard_query_rows($conn, "
        SELECT COALESCE(NULLIF(TRIM(h.Status), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM corporate_ticket_status_history h
        INNER JOIN corporate_tickets ct ON ct.ID = h.TicketID AND ct.IsActive = 1
        $branchJoin
        WHERE $histFilter $extra
        GROUP BY COALESCE(NULLIF(TRIM(h.Status), ''), 'Unknown')
        ORDER BY value DESC LIMIT 20
    "));
}

function state_dashboard_status_history_feed(mysqli $conn, string $histFilter, array $filters): array
{
    if ($filters['ticket_type'] === 'PPM') {
        return [];
    }
    $extra = state_dashboard_extra_ticket_where($filters, 'ct');
    $branchJoin = state_dashboard_branch_join_on($filters, 'b', 'ct.BranchID');
    return wallboard_query_rows($conn, "
        SELECT ct.TicketID AS ticket_code,
            CASE WHEN ct.Type = 'AMC' THEN 'AMC Breakdown' ELSE COALESCE(ct.Type, 'R&M') END AS ticket_type,
            h.Status AS status, COALESCE(e.Name, '-') AS assigned_to,
            COALESCE(c.CompanyName, '-') AS company_name,
            COALESCE(b.BranchSite, '-') AS branch_name,
            h.CreatedDate AS event_date, h.CreatedTime AS event_time
        FROM corporate_ticket_status_history h
        INNER JOIN corporate_tickets ct ON ct.ID = h.TicketID AND ct.IsActive = 1
        $branchJoin
        LEFT JOIN company c ON c.ID = ct.CorporateID
        LEFT JOIN employees e ON e.ID = h.AssignedTo
        WHERE $histFilter $extra
        ORDER BY h.ID DESC LIMIT 40
    ");
}

function state_dashboard_fetch_activity_rows(
    mysqli $conn, string $start, string $end, string $table, array $filters, string $extra
): array {
    $safe = preg_replace('/[^a-z_]/', '', $table);
    if ($safe === 'ppm_tickets') {
        $branchJoin = state_dashboard_branch_join_on($filters, 'b', 'pt.BranchID');
        return wallboard_query_rows($conn, "
            SELECT pt.TicketID AS ticket_code, 'PPM' AS ticket_type, pt.Status AS status,
                pt.CreatedDate AS created_date, pt.CloseDate AS close_date, pt.CreatedTime AS created_time,
                COALESCE(c.CompanyName, 'Unknown') AS company_name,
                COALESCE(b.BranchSite, 'Unknown') AS branch_name,
                COALESCE(e.Name, 'Unassigned') AS employee_name
            FROM ppm_tickets pt
            $branchJoin
            LEFT JOIN company c ON c.ID = pt.CorporateID
            LEFT JOIN employees e ON e.ID = pt.AssignedTo
            WHERE pt.IsActive = 1 $extra
              AND ((pt.CreatedDate >= '$start' AND pt.CreatedDate <= '$end')
                OR (pt.CloseDate >= '$start' AND pt.CloseDate <= '$end'))
        ");
    }
    $branchJoin = state_dashboard_branch_join_on($filters, 'b', 'ct.BranchID');
    return wallboard_query_rows($conn, "
        SELECT ct.TicketID AS ticket_code,
            CASE WHEN ct.Type = 'AMC' THEN 'AMC Breakdown' ELSE ct.Type END AS ticket_type,
            ct.Status AS status, ct.CreatedDate AS created_date, ct.CloseDate AS close_date,
            ct.CreatedTime AS created_time,
            COALESCE(c.CompanyName, 'Unknown') AS company_name,
            COALESCE(b.BranchSite, 'Unknown') AS branch_name,
            COALESCE(e.Name, 'Unassigned') AS employee_name
        FROM corporate_tickets ct
        $branchJoin
        LEFT JOIN company c ON c.ID = ct.CorporateID
        LEFT JOIN employees e ON e.ID = ct.AssignedTo
        WHERE ct.IsActive = 1 $extra
          AND ((ct.CreatedDate >= '$start' AND ct.CreatedDate <= '$end')
            OR (ct.CloseDate >= '$start' AND ct.CloseDate <= '$end'))
    ");
}

function state_dashboard_aggregate_period_rows(array $rows, string $startDate, string $endDate): array
{
    $openedStatus = [];
    $closedStatus = [];
    $companies = [];
    $branches = [];
    $employees = [];

    foreach ($rows as $r) {
        $status = trim((string)($r['status'] ?? '')) ?: 'Unknown';
        $cd = (string)($r['created_date'] ?? '');
        $cld = (string)($r['close_date'] ?? '');
        $isOpened = ($cd >= $startDate && $cd <= $endDate);
        $isClosed = ((string)($r['status'] ?? '') === 'Closed' && $cld >= $startDate && $cld <= $endDate);

        if ($isOpened) {
            $openedStatus[$status] = ($openedStatus[$status] ?? 0) + 1;
        }
        if ($isClosed) {
            $closedStatus[$status] = ($closedStatus[$status] ?? 0) + 1;
        }
        if (!$isOpened && !$isClosed) {
            continue;
        }

        $company = trim((string)($r['company_name'] ?? '')) ?: 'Unknown';
        $branch = trim((string)($r['branch_name'] ?? '')) ?: 'Unknown';
        $emp = trim((string)($r['employee_name'] ?? '')) ?: 'Unassigned';

        if ($isOpened || $isClosed) {
            $companies[$company] = ($companies[$company] ?? 0) + 1;
            $branches[$branch] = ($branches[$branch] ?? 0) + 1;
        }
        if (!isset($employees[$emp])) {
            $employees[$emp] = ['label' => $emp, 'opened' => 0, 'closed' => 0];
        }
        if ($isOpened) {
            $employees[$emp]['opened']++;
        }
        if ($isClosed) {
            $employees[$emp]['closed']++;
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
    usort($empOut, static fn($a, $b) => ($b['opened'] + $b['closed']) <=> ($a['opened'] + $a['closed']));

    return [
        'opened_by_status' => $toChips($openedStatus),
        'closed_by_status' => $toChips($closedStatus),
        'top_companies' => array_slice($toChips($companies), 0, 12),
        'top_branches' => array_slice($toChips($branches), 0, 12),
        'top_employees' => array_slice($empOut, 0, 15),
    ];
}

function state_dashboard_context_counts(mysqli $conn, array $filters): array
{
    $where = state_dashboard_scope_branch_where($filters);
    $branchRow = wallboard_query_rows($conn, "
        SELECT COUNT(DISTINCT b.ID) AS cnt FROM branch b
        WHERE $where
    ");
    $companyRow = wallboard_query_rows($conn, "
        SELECT COUNT(DISTINCT b.CompanyID) AS cnt FROM branch b
        WHERE $where
    ");
    return [
        'branches' => (int)($branchRow[0]['cnt'] ?? 0),
        'companies' => (int)($companyRow[0]['cnt'] ?? 0),
    ];
}

function state_dashboard_load_filter_options(mysqli $conn, array $filters): array
{
    if (!state_dashboard_scope_is_valid($filters)) {
        return ['companies' => [], 'branches' => []];
    }

    $where = state_dashboard_scope_branch_where($filters, 'b');
    $companies = wallboard_query_rows($conn, "
        SELECT DISTINCT c.ID AS id, c.CompanyName AS name
        FROM company c
        INNER JOIN branch b ON b.CompanyID = c.ID AND b.IsActive = 1
        WHERE c.IsActive = 1 AND $where
        ORDER BY c.CompanyName LIMIT 500
    ");
    $branches = wallboard_query_rows($conn, "
        SELECT b.ID AS id, b.BranchSite AS name, b.CompanyID AS corporate_id, b.BranchState AS state_name
        FROM branch b
        WHERE $where
        ORDER BY b.BranchSite LIMIT 2000
    ");
    return ['companies' => $companies, 'branches' => $branches];
}
