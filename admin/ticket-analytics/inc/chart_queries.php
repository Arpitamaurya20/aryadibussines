<?php
declare(strict_types=1);

require_once __DIR__ . '/kpi_queries.php';

function ta_fetch_open_close_trend(mysqli $conn, array $filters): array
{
    $types = 'ss';
    $params = [$filters['start_date'], $filters['end_date']];
    $extra = ta_append_common_filters($filters, $params, $types);

    $sql = "
        SELECT
            x.period,
            SUM(x.opened_count) AS opened_count,
            SUM(x.closed_count) AS closed_count
        FROM (
            SELECT
                DATE_FORMAT(t.created_on, '%Y-%m') AS period,
                COUNT(*) AS opened_count,
                0 AS closed_count
            FROM (" . ta_base_ticket_union_sql() . ") t
            WHERE t.created_on BETWEEN ? AND ? {$extra}
            GROUP BY DATE_FORMAT(t.created_on, '%Y-%m')

            UNION ALL

            SELECT
                DATE_FORMAT(t.closed_on, '%Y-%m') AS period,
                0 AS opened_count,
                COUNT(*) AS closed_count
            FROM (" . ta_base_ticket_union_sql() . ") t
            WHERE t.closed_on BETWEEN ? AND ? {$extra}
              AND t.status = 'Closed'
            GROUP BY DATE_FORMAT(t.closed_on, '%Y-%m')
        ) x
        WHERE x.period IS NOT NULL AND x.period <> ''
        GROUP BY x.period
        ORDER BY x.period ASC
    ";

    $stmt = $conn->prepare($sql);
    $types = $types . $types;
    $params = array_merge($params, $params);
    ta_bind_params($stmt, $types, $params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

function ta_fetch_type_breakdown(mysqli $conn, array $filters): array
{
    $types = 'ss';
    $params = [$filters['start_date'], $filters['end_date']];
    $extra = ta_append_common_filters($filters, $params, $types);

    $sql = "
        SELECT
            t.ticket_type,
            COUNT(*) AS ticket_count,
            SUM(CASE WHEN t.status = 'Closed' THEN 1 ELSE 0 END) AS closed_count,
            COALESCE(SUM(t.expense_price), 0) AS expense_total
        FROM (" . ta_base_ticket_union_sql() . ") t
        WHERE t.created_on BETWEEN ? AND ? {$extra}
        GROUP BY t.ticket_type
        ORDER BY ticket_count DESC
    ";

    $stmt = $conn->prepare($sql);
    ta_bind_params($stmt, $types, $params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

function ta_fetch_drilldown_rows(mysqli $conn, array $filters, int $limit = 500): array
{
    $types = 'ss';
    $params = [$filters['start_date'], $filters['end_date']];
    $extra = ta_append_common_filters($filters, $params, $types);

    $sql = "
        SELECT
            t.ticket_code,
            t.ticket_type,
            t.status,
            t.priority,
            DATE_FORMAT(t.created_on, '%Y-%m-%d') AS created_on,
            DATE_FORMAT(t.due_on, '%Y-%m-%d') AS due_on,
            DATE_FORMAT(t.closed_on, '%Y-%m-%d') AS closed_on,
            COALESCE(comp.CompanyName, '-') AS company_name,
            COALESCE(br.BranchSite, '-') AS branch_name,
            COALESCE(emp.Name, '-') AS assigned_to_name,
            COALESCE(t.expense_price, 0) AS expense_price
        FROM (" . ta_base_ticket_union_sql() . ") t
        LEFT JOIN company comp ON comp.ID = t.corporate_id
        LEFT JOIN branch br ON br.ID = t.branch_id
        LEFT JOIN employees emp ON emp.ID = t.assigned_to
        WHERE t.created_on BETWEEN ? AND ? {$extra}
        ORDER BY t.created_on DESC, t.ticket_pk DESC
        LIMIT " . max(1, min($limit, 2000)) . "
    ";

    $stmt = $conn->prepare($sql);
    ta_bind_params($stmt, $types, $params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

function ta_fetch_status_breakdown(mysqli $conn, array $filters): array
{
    $types = 'ss';
    $params = [$filters['start_date'], $filters['end_date']];
    $extra = ta_append_common_filters($filters, $params, $types);

    $sql = "
        SELECT
            COALESCE(NULLIF(TRIM(t.status), ''), 'Unknown') AS status,
            COUNT(*) AS ticket_count
        FROM (" . ta_base_ticket_union_sql() . ") t
        WHERE t.created_on BETWEEN ? AND ? {$extra}
        GROUP BY COALESCE(NULLIF(TRIM(t.status), ''), 'Unknown')
        ORDER BY ticket_count DESC
        LIMIT 20
    ";

    $stmt = $conn->prepare($sql);
    ta_bind_params($stmt, $types, $params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}
?>
