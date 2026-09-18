<?php
declare(strict_types=1);

require_once __DIR__ . '/filters.php';
require_once __DIR__ . '/type_dashboard_queries.php';

function ta_executive_base_sql(string $ticketType): string
{
    return ta_type_base_sql(ta_normalize_ticket_type($ticketType));
}

/** @deprecated Use ta_executive_base_sql() */
function ta_rm_base_sql(): string
{
    return ta_executive_base_sql('R&M');
}

function ta_executive_summary(mysqli $conn, array $filters, string $ticketType): array
{
    $base = ta_executive_base_sql($ticketType);
    $sql = "
        SELECT
            COUNT(*) AS total_tickets,
            SUM(CASE WHEN t.status = 'Closed' THEN 1 ELSE 0 END) AS closed_tickets,
            SUM(CASE WHEN t.status <> 'Closed' THEN 1 ELSE 0 END) AS open_tickets,
            AVG(CASE WHEN t.status = 'Closed' AND t.closed_on IS NOT NULL THEN TIMESTAMPDIFF(HOUR, t.created_on, t.closed_on) END) AS avg_tat_hours,
            SUM(
                CASE
                    WHEN t.due_on IS NULL THEN 0
                    WHEN t.status = 'Closed' AND t.closed_on > t.due_on THEN 1
                    WHEN t.status <> 'Closed' AND CURDATE() > t.due_on THEN 1
                    ELSE 0
                END
            ) AS sla_breach_count,
            SUM(CASE WHEN t.status <> 'Closed' AND DATEDIFF(CURDATE(), t.created_on) BETWEEN 0 AND 2 THEN 1 ELSE 0 END) AS aging_0_2,
            SUM(CASE WHEN t.status <> 'Closed' AND DATEDIFF(CURDATE(), t.created_on) BETWEEN 3 AND 7 THEN 1 ELSE 0 END) AS aging_3_7,
            SUM(CASE WHEN t.status <> 'Closed' AND DATEDIFF(CURDATE(), t.created_on) BETWEEN 8 AND 15 THEN 1 ELSE 0 END) AS aging_8_15,
            SUM(CASE WHEN t.status <> 'Closed' AND DATEDIFF(CURDATE(), t.created_on) > 15 THEN 1 ELSE 0 END) AS aging_16_plus,
            COALESCE(SUM(t.customer_price), 0) AS customer_price_total,
            COALESCE(SUM(t.expense_price), 0) AS expense_price_total
        FROM ({$base}) t
        WHERE t.created_on BETWEEN ? AND ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $filters['start_date'], $filters['end_date']);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $data['total_tickets'] = (int)($data['total_tickets'] ?? 0);
    $data['closed_tickets'] = (int)($data['closed_tickets'] ?? 0);
    $data['open_tickets'] = (int)($data['open_tickets'] ?? 0);
    $data['sla_breach_count'] = (int)($data['sla_breach_count'] ?? 0);
    $data['aging_0_2'] = (int)($data['aging_0_2'] ?? 0);
    $data['aging_3_7'] = (int)($data['aging_3_7'] ?? 0);
    $data['aging_8_15'] = (int)($data['aging_8_15'] ?? 0);
    $data['aging_16_plus'] = (int)($data['aging_16_plus'] ?? 0);

    $total = $data['total_tickets'];
    $closed = $data['closed_tickets'];
    $sla = $data['sla_breach_count'];
    $customer = (float)($data['customer_price_total'] ?? 0);
    $expense = (float)($data['expense_price_total'] ?? 0);
    $data['closure_rate_pct'] = $total > 0 ? round(($closed / $total) * 100, 2) : 0.0;
    $data['sla_breach_pct'] = $total > 0 ? round(($sla / $total) * 100, 2) : 0.0;
    $data['margin_total'] = $customer - $expense;
    $data['cost_per_ticket'] = $total > 0 ? round($expense / $total, 2) : 0.0;

    return $data;
}

function ta_rm_summary(mysqli $conn, array $filters): array
{
    return ta_executive_summary($conn, $filters, 'R&M');
}

function ta_executive_dataset(mysqli $conn, string $sql, string $types, array $params): array
{
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return [];
    }
    ta_bind_params($stmt, $types, $params);
    if (!$stmt->execute()) {
        $stmt->close();
        return [];
    }
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($rows as &$row) {
        if (isset($row['value'])) {
            $row['value'] = (int)$row['value'];
        }
    }
    unset($row);
    return $rows ?: [];
}

/** @deprecated */
function ta_rm_dataset(mysqli $conn, string $sql, string $types, array $params): array
{
    return ta_executive_dataset($conn, $sql, $types, $params);
}

function ta_executive_fetch_all(mysqli $conn, array $filters, string $ticketType): array
{
    $ticketType = ta_normalize_ticket_type($ticketType);
    $base = ta_executive_base_sql($ticketType);
    $dates = [$filters['start_date'], $filters['end_date']];

    $trend = ta_executive_dataset($conn, "
        SELECT x.period, SUM(x.opened_count) AS opened_count, SUM(x.closed_count) AS closed_count
        FROM (
            SELECT DATE_FORMAT(t.created_on, '%Y-%m') AS period, COUNT(*) AS opened_count, 0 AS closed_count
            FROM ({$base}) t WHERE t.created_on BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(t.created_on, '%Y-%m')
            UNION ALL
            SELECT DATE_FORMAT(t.closed_on, '%Y-%m') AS period, 0 AS opened_count, COUNT(*) AS closed_count
            FROM ({$base}) t WHERE t.closed_on BETWEEN ? AND ? AND t.status = 'Closed'
            GROUP BY DATE_FORMAT(t.closed_on, '%Y-%m')
        ) x
        WHERE x.period IS NOT NULL AND x.period <> ''
        GROUP BY x.period ORDER BY x.period
    ", 'ssss', array_merge($dates, $dates));

    $status = ta_executive_dataset($conn, "
        SELECT COALESCE(NULLIF(TRIM(t.status), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM ({$base}) t WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(NULLIF(TRIM(t.status), ''), 'Unknown')
        ORDER BY value DESC LIMIT 15
    ", 'ss', $dates);

    $priority = ta_executive_dataset($conn, "
        SELECT t.priority AS label, COUNT(*) AS value
        FROM ({$base}) t WHERE t.created_on BETWEEN ? AND ?
        GROUP BY t.priority ORDER BY value DESC LIMIT 10
    ", 'ss', $dates);

    $company = ta_executive_dataset($conn, "
        SELECT COALESCE(c.CompanyName, 'Unknown') AS label, COUNT(*) AS value
        FROM ({$base}) t
        LEFT JOIN company c ON c.ID = t.corporate_id
        WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(c.CompanyName, 'Unknown')
        ORDER BY value DESC LIMIT 10
    ", 'ss', $dates);

    $branch = ta_executive_dataset($conn, "
        SELECT COALESCE(b.BranchSite, 'Unknown') AS label, COUNT(*) AS value
        FROM ({$base}) t
        LEFT JOIN branch b ON b.ID = t.branch_id
        WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(b.BranchSite, 'Unknown')
        ORDER BY value DESC LIMIT 10
    ", 'ss', $dates);

    $assignee = ta_executive_dataset($conn, "
        SELECT COALESCE(e.Name, 'Unassigned') AS label, COUNT(*) AS value
        FROM ({$base}) t
        LEFT JOIN employees e ON e.ID = t.assigned_to
        WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(e.Name, 'Unassigned')
        ORDER BY value DESC LIMIT 10
    ", 'ss', $dates);

    $service = ta_executive_dataset($conn, "
        SELECT t.service_name AS label, COUNT(*) AS value
        FROM ({$base}) t WHERE t.created_on BETWEEN ? AND ?
        GROUP BY t.service_name ORDER BY value DESC LIMIT 10
    ", 'ss', $dates);

    $drilldown = ta_executive_dataset($conn, "
        SELECT t.ticket_code, t.status, t.priority, t.service_name, t.subservice_name,
            DATE_FORMAT(t.created_on, '%Y-%m-%d') AS created_on,
            DATE_FORMAT(t.due_on, '%Y-%m-%d') AS due_on,
            DATE_FORMAT(t.closed_on, '%Y-%m-%d') AS closed_on,
            COALESCE(c.CompanyName, '-') AS company_name,
            COALESCE(b.BranchSite, '-') AS branch_name,
            COALESCE(e.Name, '-') AS assigned_to_name,
            COALESCE(t.expense_price, 0) AS expense_price,
            COALESCE(t.customer_price, 0) AS customer_price
        FROM ({$base}) t
        LEFT JOIN company c ON c.ID = t.corporate_id
        LEFT JOIN branch b ON b.ID = t.branch_id
        LEFT JOIN employees e ON e.ID = t.assigned_to
        WHERE t.created_on BETWEEN ? AND ?
        ORDER BY t.created_on DESC, t.ticket_pk DESC LIMIT 200
    ", 'ss', $dates);

    $aggregate = static function (array $rows, string $key, int $limit = 10): array {
        $map = [];
        foreach ($rows as $row) {
            $label = isset($row[$key]) ? trim((string)$row[$key]) : '';
            if ($label === '') {
                $label = 'Unknown';
            }
            $map[$label] = ($map[$label] ?? 0) + 1;
        }
        arsort($map);
        $out = [];
        $count = 0;
        foreach ($map as $label => $value) {
            $out[] = ['label' => $label, 'value' => $value];
            if (++$count >= $limit) {
                break;
            }
        }
        return $out;
    };

    if (empty($status) && !empty($drilldown)) {
        $status = $aggregate($drilldown, 'status', 15);
    }
    if (empty($priority) && !empty($drilldown)) {
        $priority = $aggregate($drilldown, 'priority', 10);
    }
    if (empty($company) && !empty($drilldown)) {
        $company = $aggregate($drilldown, 'company_name', 10);
    }
    if (empty($branch) && !empty($drilldown)) {
        $branch = $aggregate($drilldown, 'branch_name', 10);
    }
    if (empty($assignee) && !empty($drilldown)) {
        $assignee = $aggregate($drilldown, 'assigned_to_name', 10);
    }
    if (empty($service) && !empty($drilldown)) {
        $service = $aggregate($drilldown, 'service_name', 10);
    }

    return [
        'ticket_type' => $ticketType,
        'summary' => ta_executive_summary($conn, $filters, $ticketType),
        'trend' => $trend,
        'status' => $status,
        'priority' => $priority,
        'company' => $company,
        'branch' => $branch,
        'assignee' => $assignee,
        'service' => $service,
        'drilldown' => $drilldown,
    ];
}

function ta_rm_fetch_all(mysqli $conn, array $filters): array
{
    return ta_executive_fetch_all($conn, $filters, 'R&M');
}
