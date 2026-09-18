<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/ticket-analytics/wallboard/inc/wallboard_queries.php';
require_once __DIR__ . '/state_dashboard_scope.php';

function state_dashboard_empty_charts(): array
{
    return [
        'monthly_trend' => ['labels' => [], 'opened' => [], 'closed' => []],
        'monthly_quotes' => ['labels' => [], 'approved_value' => [], 'sent_value' => []],
        'ticket_type_pie' => [],
        'quote_value_pie' => [],
        'opened_status_pie' => [],
        'type_comparison' => ['labels' => [], 'opened' => [], 'closed' => []],
        'top_branches' => [],
        'top_companies' => [],
        'top_technicians' => [],
        'workflow_bar' => [],
    ];
}

function state_dashboard_month_keys(string $startDate, string $endDate): array
{
    $keys = [];
    $labels = [];
    try {
        $cur = new DateTime(substr($startDate, 0, 7) . '-01');
        $end = new DateTime(substr($endDate, 0, 7) . '-01');
    } catch (Exception $e) {
        return ['keys' => [], 'labels' => []];
    }
    while ($cur <= $end) {
        $key = $cur->format('Y-m');
        $keys[] = $key;
        $labels[] = $cur->format('M Y');
        $cur->modify('+1 month');
    }
    return ['keys' => $keys, 'labels' => $labels];
}

function state_dashboard_map_month_series(array $monthKeys, array $rows, string $valueKey = 'value'): array
{
    $map = [];
    foreach ($rows as $r) {
        $map[(string)($r['month_key'] ?? '')] = (float)($r[$valueKey] ?? 0);
    }
    $out = [];
    foreach ($monthKeys as $k) {
        $out[] = (float)($map[$k] ?? 0);
    }
    return $out;
}

function state_dashboard_build_charts(mysqli $conn, array $filters, array $snapshot): array
{
    $start = state_dashboard_esc($conn, $filters['start_date']);
    $end = state_dashboard_esc($conn, $filters['end_date']);
    $extraCt = state_dashboard_extra_ticket_where($filters, 'ct');
    $extraPt = state_dashboard_extra_ticket_where($filters, 'pt');
    $includePpm = ($filters['ticket_type'] === '' || $filters['ticket_type'] === 'PPM');
    $includeCorp = ($filters['ticket_type'] === '' || $filters['ticket_type'] !== 'PPM');

    $months = state_dashboard_month_keys($filters['start_date'], $filters['end_date']);
    $monthKeys = $months['keys'];
    $monthLabels = $months['labels'];

    $openedRows = [];
    $closedRows = [];
    if ($includeCorp) {
        $openedRows = array_merge($openedRows, state_dashboard_monthly_count_rows(
            $conn, $start, $end, $filters, $extraCt, 'corporate_tickets', 'CreatedDate'
        ));
        $closedRows = array_merge($closedRows, state_dashboard_monthly_count_rows(
            $conn, $start, $end, $filters, $extraCt, 'corporate_tickets', 'CloseDate', "ct.Status = 'Closed'"
        ));
    }
    if ($includePpm) {
        $openedRows = state_dashboard_merge_month_counts($openedRows, state_dashboard_monthly_count_rows(
            $conn, $start, $end, $filters, $extraPt, 'ppm_tickets', 'CreatedDate'
        ));
        $closedRows = state_dashboard_merge_month_counts($closedRows, state_dashboard_monthly_count_rows(
            $conn, $start, $end, $filters, $extraPt, 'ppm_tickets', 'CloseDate', "pt.Status = 'Closed'"
        ));
    }

    $quoteMonthly = ($filters['ticket_type'] !== 'PPM')
        ? state_dashboard_monthly_quote_values($conn, $start, $end, $filters)
        : ['approved' => [], 'sent' => []];

    $typeMatrix = $snapshot['type_matrix'] ?? [];
    $typeLabels = [];
    $typeOpened = [];
    $typeClosed = [];
    foreach ($typeMatrix as $row) {
        $typeLabels[] = (string)($row['ticket_type'] ?? 'Unknown');
        $typeOpened[] = (int)($row['opened'] ?? 0);
        $typeClosed[] = (int)($row['closed'] ?? 0);
    }

    $ticketTypePie = [];
    foreach ($snapshot['type_opened'] ?? [] as $row) {
        if ((int)($row['value'] ?? 0) > 0) {
            $ticketTypePie[] = ['label' => (string)$row['label'], 'value' => (int)$row['value']];
        }
    }

    $quoteValuePie = state_dashboard_quote_value_pie($conn, $start, $end, $filters);

    $openedStatusPie = array_slice($snapshot['opened_by_status'] ?? [], 0, 10);

    $topTech = [];
    foreach (array_slice($snapshot['top_employees'] ?? [], 0, 10) as $emp) {
        $topTech[] = [
            'label' => (string)($emp['label'] ?? 'Unknown'),
            'value' => (int)($emp['opened'] ?? 0) + (int)($emp['closed'] ?? 0),
            'opened' => (int)($emp['opened'] ?? 0),
            'closed' => (int)($emp['closed'] ?? 0),
        ];
    }

    $workflowBar = array_slice($snapshot['workflow'] ?? [], 0, 12);

    return [
        'monthly_trend' => [
            'labels' => $monthLabels,
            'opened' => state_dashboard_map_month_series($monthKeys, $openedRows),
            'closed' => state_dashboard_map_month_series($monthKeys, $closedRows),
        ],
        'monthly_quotes' => [
            'labels' => $monthLabels,
            'approved_value' => state_dashboard_map_month_series($monthKeys, $quoteMonthly['approved']),
            'sent_value' => state_dashboard_map_month_series($monthKeys, $quoteMonthly['sent']),
        ],
        'ticket_type_pie' => $ticketTypePie,
        'quote_value_pie' => $quoteValuePie,
        'opened_status_pie' => $openedStatusPie,
        'type_comparison' => [
            'labels' => $typeLabels,
            'opened' => $typeOpened,
            'closed' => $typeClosed,
        ],
        'top_branches' => array_slice($snapshot['top_branches'] ?? [], 0, 10),
        'top_companies' => array_slice($snapshot['top_companies'] ?? [], 0, 10),
        'top_technicians' => $topTech,
        'workflow_bar' => $workflowBar,
    ];
}

function state_dashboard_merge_month_counts(array $a, array $b): array
{
    $map = [];
    foreach (array_merge($a, $b) as $row) {
        $k = (string)($row['month_key'] ?? '');
        $map[$k] = ($map[$k] ?? 0) + (float)($row['value'] ?? 0);
    }
    $out = [];
    foreach ($map as $monthKey => $value) {
        $out[] = ['month_key' => $monthKey, 'value' => $value];
    }
    return $out;
}

function state_dashboard_monthly_count_rows(
    mysqli $conn,
    string $start,
    string $end,
    array $filters,
    string $extra,
    string $table,
    string $dateCol,
    string $extraWhere = ''
): array {
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $alias = $safe === 'ppm_tickets' ? 'pt' : 'ct';
    $whereExtra = $extraWhere !== '' ? ' AND ' . $extraWhere : '';
    $branchJoin = state_dashboard_branch_join_on($filters, 'b', $alias . '.BranchID');
    return wallboard_query_rows($conn, "
        SELECT DATE_FORMAT($alias.$dateCol, '%Y-%m') AS month_key, COUNT(*) AS value
        FROM $safe $alias
        $branchJoin
        WHERE $alias.IsActive = 1
          AND $alias.$dateCol >= '$start' AND $alias.$dateCol <= '$end'
          $extra $whereExtra
        GROUP BY DATE_FORMAT($alias.$dateCol, '%Y-%m')
        ORDER BY month_key
    ");
}

function state_dashboard_monthly_quote_values(
    mysqli $conn,
    string $start,
    string $end,
    array $filters
): array {
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

    $branchJoin = state_dashboard_branch_join_on($filters, 'b', 'ct.BranchID');
    $quoteDate = state_dashboard_quote_date_expr('q');
    $qv = state_dashboard_quote_value_subquery();
    $approvalDates = state_dashboard_quote_first_approval_subquery();

    $approved = wallboard_query_rows($conn, "
        SELECT DATE_FORMAT(h.approved_date, '%Y-%m') AS month_key,
            COALESCE(SUM(qv.quote_value), 0) AS value
        FROM corporate_ticket_quotation q
        INNER JOIN $qv qv ON qv.QuotationID = q.ID
        INNER JOIN $approvalDates h ON h.QuotationID = q.ID
        INNER JOIN corporate_tickets ct ON ct.ID = q.TicketID AND ct.IsActive = 1
        $branchJoin
        WHERE IFNULL(q.IsActive, 1) = 1 AND q.QuotationStatus = 'Quote Approved'
          AND h.approved_date >= '$start' AND h.approved_date <= '$end' $extra
        GROUP BY DATE_FORMAT(h.approved_date, '%Y-%m')
    ");

    $sent = wallboard_query_rows($conn, "
        SELECT DATE_FORMAT($quoteDate, '%Y-%m') AS month_key,
            COALESCE(SUM(qv.quote_value), 0) AS value
        FROM corporate_ticket_quotation q
        INNER JOIN $qv qv ON qv.QuotationID = q.ID
        INNER JOIN corporate_tickets ct ON ct.ID = q.TicketID AND ct.IsActive = 1
        $branchJoin
        WHERE IFNULL(q.IsActive, 1) = 1 AND q.QuotationStatus = 'Quote Sent Approval Pending'
          AND $quoteDate >= '$start' AND $quoteDate <= '$end' $extra
        GROUP BY DATE_FORMAT($quoteDate, '%Y-%m')
    ");

    return ['approved' => $approved, 'sent' => $sent];
}

function state_dashboard_quote_value_pie(
    mysqli $conn,
    string $start,
    string $end,
    array $filters
): array {
    if ($filters['ticket_type'] === 'PPM') {
        return [];
    }
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

    $branchJoin = state_dashboard_branch_join_on($filters, 'b', 'ct.BranchID');
    $quoteDate = state_dashboard_quote_date_expr('q');
    $qv = state_dashboard_quote_value_subquery();
    $pipelineStatuses = state_dashboard_quote_pipeline_statuses_sql();
    $rows = wallboard_query_rows($conn, "
        SELECT
            CASE
                WHEN q.QuotationStatus = 'Quote Approved' THEN 'Approved'
                WHEN q.QuotationStatus = 'Quote Sent Approval Pending' THEN 'Sent for approval'
                WHEN q.QuotationStatus LIKE '%Reject%' THEN 'Rejected'
                WHEN q.QuotationStatus IN ($pipelineStatuses) THEN 'In pipeline'
                WHEN q.QuotationStatus IN ('Draft', '') OR q.QuotationStatus IS NULL THEN 'Draft'
                ELSE 'Other'
            END AS label,
            COALESCE(SUM(qv.quote_value), 0) AS value
        FROM corporate_ticket_quotation q
        INNER JOIN $qv qv ON qv.QuotationID = q.ID
        INNER JOIN corporate_tickets ct ON ct.ID = q.TicketID AND ct.IsActive = 1
        $branchJoin
        WHERE IFNULL(q.IsActive, 1) = 1
          AND $quoteDate >= '$start' AND $quoteDate <= '$end' $extra
        GROUP BY label
        HAVING value > 0
        ORDER BY value DESC
    ");

    $out = [];
    foreach ($rows as $r) {
        $out[] = ['label' => (string)($r['label'] ?? 'Other'), 'value' => (float)($r['value'] ?? 0)];
    }
    return $out;
}
