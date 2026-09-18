<?php
declare(strict_types=1);

require_once __DIR__ . '/filters.php';

function ta_base_ticket_union_sql(): string
{
    return "
        SELECT
            c.ID AS ticket_pk,
            c.TicketID AS ticket_code,
            CASE
                WHEN c.Type = 'AMC' THEN 'AMC Breakdown'
                ELSE c.Type
            END AS ticket_type,
            c.Status AS status,
            c.CorporateID AS corporate_id,
            c.BranchID AS branch_id,
            c.AssignedTo AS assigned_to,
            NULLIF(c.Priority, '') AS priority,
            STR_TO_DATE(NULLIF(c.CreatedDate, ''), '%Y-%m-%d') AS created_on,
            STR_TO_DATE(NULLIF(c.DueDate, ''), '%Y-%m-%d') AS due_on,
            STR_TO_DATE(NULLIF(c.CloseDate, ''), '%Y-%m-%d') AS closed_on,
            CAST(NULLIF(c.CustumerPrice, '') AS DECIMAL(12,2)) AS customer_price,
            CAST(NULLIF(c.ExpensePrice, '') AS DECIMAL(12,2)) AS expense_price
        FROM corporate_tickets c
        WHERE c.IsActive = 1
          AND c.Type IN ('R&M', 'Projects', 'Supply', 'AMC')

        UNION ALL

        SELECT
            p.ID AS ticket_pk,
            p.TicketID AS ticket_code,
            'PPM' AS ticket_type,
            p.Status AS status,
            p.CorporateID AS corporate_id,
            p.BranchID AS branch_id,
            p.AssignedTo AS assigned_to,
            NULL AS priority,
            STR_TO_DATE(NULLIF(p.CreatedDate, ''), '%Y-%m-%d') AS created_on,
            STR_TO_DATE(NULLIF(p.DueDate, ''), '%Y-%m-%d') AS due_on,
            STR_TO_DATE(NULLIF(p.CloseDate, ''), '%Y-%m-%d') AS closed_on,
            NULL AS customer_price,
            NULL AS expense_price
        FROM ppm_tickets p
        WHERE p.IsActive = 1
    ";
}

function ta_fetch_kpi_summary(mysqli $conn, array $filters): array
{
    $types = 'ss';
    $params = [$filters['start_date'], $filters['end_date']];
    $extra = ta_append_common_filters($filters, $params, $types);

    $sql = "
        SELECT
            COUNT(*) AS total_tickets,
            SUM(CASE WHEN t.status = 'Closed' THEN 1 ELSE 0 END) AS closed_tickets,
            SUM(CASE WHEN t.status <> 'Closed' THEN 1 ELSE 0 END) AS open_tickets,
            AVG(
                CASE
                    WHEN t.status = 'Closed' AND t.closed_on IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, t.created_on, t.closed_on)
                    ELSE NULL
                END
            ) AS avg_tat_hours,
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
        FROM (" . ta_base_ticket_union_sql() . ") t
        WHERE t.created_on BETWEEN ? AND ? {$extra}
    ";

    $stmt = $conn->prepare($sql);
    ta_bind_params($stmt, $types, $params);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $total = (int)($data['total_tickets'] ?? 0);
    $closed = (int)($data['closed_tickets'] ?? 0);
    $sla = (int)($data['sla_breach_count'] ?? 0);

    $data['closure_rate_pct'] = $total > 0 ? round(($closed / $total) * 100, 2) : 0;
    $data['sla_breach_pct'] = $total > 0 ? round(($sla / $total) * 100, 2) : 0;

    return $data;
}
?>
