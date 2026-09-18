<?php
declare(strict_types=1);

require_once __DIR__ . '/filters.php';

function ta_allowed_ticket_types(): array
{
    return ['PPM', 'R&M', 'Projects', 'Supply', 'AMC Breakdown'];
}

function ta_normalize_ticket_type(string $type): string
{
    $type = trim($type);
    return in_array($type, ta_allowed_ticket_types(), true) ? $type : 'R&M';
}

function ta_type_base_sql(string $ticketType): string
{
    if ($ticketType === 'PPM') {
        return "
            SELECT
                p.ID AS ticket_pk,
                p.TicketID AS ticket_code,
                'PPM' AS ticket_type,
                p.Status AS status,
                p.CorporateID AS corporate_id,
                p.BranchID AS branch_id,
                p.AssignedTo AS assigned_to,
                'Unknown' AS priority,
                'PPM' AS service_name,
                'Unknown' AS subservice_name,
                STR_TO_DATE(NULLIF(p.CreatedDate, ''), '%Y-%m-%d') AS created_on,
                STR_TO_DATE(NULLIF(p.DueDate, ''), '%Y-%m-%d') AS due_on,
                STR_TO_DATE(NULLIF(p.CloseDate, ''), '%Y-%m-%d') AS closed_on,
                NULL AS customer_price,
                NULL AS expense_price
            FROM ppm_tickets p
            WHERE p.IsActive = 1
        ";
    }

    $dbType = $ticketType === 'AMC Breakdown' ? 'AMC' : $ticketType;
    $safeType = addslashes($dbType);
    return "
        SELECT
            c.ID AS ticket_pk,
            c.TicketID AS ticket_code,
            '" . addslashes($ticketType) . "' AS ticket_type,
            c.Status AS status,
            c.CorporateID AS corporate_id,
            c.BranchID AS branch_id,
            c.AssignedTo AS assigned_to,
            COALESCE(NULLIF(TRIM(c.Priority), ''), 'Unknown') AS priority,
            COALESCE(NULLIF(TRIM(c.Service), ''), 'Unknown') AS service_name,
            COALESCE(NULLIF(TRIM(c.Subservice), ''), 'Unknown') AS subservice_name,
            STR_TO_DATE(NULLIF(c.CreatedDate, ''), '%Y-%m-%d') AS created_on,
            STR_TO_DATE(NULLIF(c.DueDate, ''), '%Y-%m-%d') AS due_on,
            STR_TO_DATE(NULLIF(c.CloseDate, ''), '%Y-%m-%d') AS closed_on,
            CAST(NULLIF(c.CustumerPrice, '') AS DECIMAL(12,2)) AS customer_price,
            CAST(NULLIF(c.ExpensePrice, '') AS DECIMAL(12,2)) AS expense_price
        FROM corporate_tickets c
        WHERE c.IsActive = 1
          AND c.Type = '{$safeType}'
    ";
}

function ta_type_bind_date(mysqli_stmt $stmt, array $filters): void
{
    $stmt->bind_param('ss', $filters['start_date'], $filters['end_date']);
}

function ta_type_dashboard_data(mysqli $conn, array $filters, string $ticketType): array
{
    $base = ta_type_base_sql($ticketType);

    $summarySql = "
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
            ) AS sla_breach_count
        FROM ({$base}) t
        WHERE t.created_on BETWEEN ? AND ?
    ";
    $stmt = $conn->prepare($summarySql);
    ta_type_bind_date($stmt, $filters);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    $total = (int)($summary['total_tickets'] ?? 0);
    $summary['closure_rate_pct'] = $total > 0 ? round(((int)$summary['closed_tickets'] / $total) * 100, 2) : 0;
    $summary['sla_breach_pct'] = $total > 0 ? round(((int)$summary['sla_breach_count'] / $total) * 100, 2) : 0;

    $statusSql = "
        SELECT
            COALESCE(NULLIF(TRIM(t.status), ''), 'Unknown') AS label,
            COUNT(*) AS value
        FROM ({$base}) t
        WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(NULLIF(TRIM(t.status), ''), 'Unknown')
        ORDER BY value DESC
        LIMIT 18
    ";
    $stmt = $conn->prepare($statusSql);
    ta_type_bind_date($stmt, $filters);
    $stmt->execute();
    $status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();
    foreach ($status as &$row) {
        $row['pct'] = $total > 0 ? round(((int)$row['value'] / $total) * 100, 2) : 0;
    }
    unset($row);

    $trendSql = "
        SELECT x.period, SUM(x.opened_count) AS opened_count, SUM(x.closed_count) AS closed_count
        FROM (
          SELECT DATE_FORMAT(t.created_on, '%Y-%m') AS period, COUNT(*) AS opened_count, 0 AS closed_count
          FROM ({$base}) t WHERE t.created_on BETWEEN ? AND ? GROUP BY DATE_FORMAT(t.created_on, '%Y-%m')
          UNION ALL
          SELECT DATE_FORMAT(t.closed_on, '%Y-%m') AS period, 0 AS opened_count, COUNT(*) AS closed_count
          FROM ({$base}) t WHERE t.closed_on BETWEEN ? AND ? AND t.status='Closed' GROUP BY DATE_FORMAT(t.closed_on, '%Y-%m')
        ) x
        WHERE x.period IS NOT NULL AND x.period <> ''
        GROUP BY x.period
        ORDER BY x.period
    ";
    $stmt = $conn->prepare($trendSql);
    $stmt->bind_param('ssss', $filters['start_date'], $filters['end_date'], $filters['start_date'], $filters['end_date']);
    $stmt->execute();
    $trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();

    $stateSql = "
        SELECT COALESCE(NULLIF(TRIM(b.BranchState), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM ({$base}) t
        LEFT JOIN branch b ON b.ID = t.branch_id
        WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(NULLIF(TRIM(b.BranchState), ''), 'Unknown')
        ORDER BY value DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($stateSql);
    ta_type_bind_date($stmt, $filters);
    $stmt->execute();
    $states = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();

    $companySql = "
        SELECT COALESCE(NULLIF(TRIM(c.CompanyName), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM ({$base}) t
        LEFT JOIN company c ON c.ID = t.corporate_id
        WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(NULLIF(TRIM(c.CompanyName), ''), 'Unknown')
        ORDER BY value DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($companySql);
    ta_type_bind_date($stmt, $filters);
    $stmt->execute();
    $companies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();

    $branchSql = "
        SELECT COALESCE(NULLIF(TRIM(b.BranchSite), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM ({$base}) t
        LEFT JOIN branch b ON b.ID = t.branch_id
        WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(NULLIF(TRIM(b.BranchSite), ''), 'Unknown')
        ORDER BY value DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($branchSql);
    ta_type_bind_date($stmt, $filters);
    $stmt->execute();
    $branches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();

    $assigneeSql = "
        SELECT COALESCE(NULLIF(TRIM(e.Name), ''), 'Unassigned') AS label, COUNT(*) AS value
        FROM ({$base}) t
        LEFT JOIN employees e ON e.ID = t.assigned_to
        WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(NULLIF(TRIM(e.Name), ''), 'Unassigned')
        ORDER BY value DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($assigneeSql);
    ta_type_bind_date($stmt, $filters);
    $stmt->execute();
    $assignees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();

    $prioritySql = "
        SELECT COALESCE(NULLIF(TRIM(t.priority), ''), 'Unknown') AS label, COUNT(*) AS value
        FROM ({$base}) t
        WHERE t.created_on BETWEEN ? AND ?
        GROUP BY COALESCE(NULLIF(TRIM(t.priority), ''), 'Unknown')
        ORDER BY value DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($prioritySql);
    ta_type_bind_date($stmt, $filters);
    $stmt->execute();
    $priorities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();

    $drillSql = "
        SELECT
            t.ticket_code, t.status, t.priority,
            DATE_FORMAT(t.created_on, '%Y-%m-%d') AS created_on,
            DATE_FORMAT(t.due_on, '%Y-%m-%d') AS due_on,
            DATE_FORMAT(t.closed_on, '%Y-%m-%d') AS closed_on,
            COALESCE(NULLIF(TRIM(c.CompanyName), ''), '-') AS company_name,
            COALESCE(NULLIF(TRIM(b.BranchSite), ''), '-') AS branch_name,
            COALESCE(NULLIF(TRIM(e.Name), ''), '-') AS assigned_to
        FROM ({$base}) t
        LEFT JOIN company c ON c.ID = t.corporate_id
        LEFT JOIN branch b ON b.ID = t.branch_id
        LEFT JOIN employees e ON e.ID = t.assigned_to
        WHERE t.created_on BETWEEN ? AND ?
        ORDER BY t.created_on DESC, t.ticket_pk DESC
        LIMIT 100
    ";
    $stmt = $conn->prepare($drillSql);
    ta_type_bind_date($stmt, $filters);
    $stmt->execute();
    $drilldown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt->close();

    return [
        'summary' => $summary,
        'status_cards' => $status,
        'status_distribution' => $status,
        'trend' => $trend,
        'regional_distribution' => $states,
        'top_companies' => $companies,
        'top_branches' => $branches,
        'top_assignees' => $assignees,
        'top_priorities' => $priorities,
        'drilldown' => $drilldown,
    ];
}
?>
