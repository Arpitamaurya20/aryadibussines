<?php
declare(strict_types=1);

function ta_parse_date(?string $date, string $fallback): string
{
    if (!$date) {
        return $fallback;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d ? $d->format('Y-m-d') : $fallback;
}

/** 1 March of the current FY window: last March through today (if before March, previous calendar year's March). */
function ta_last_march_to_today_range(?DateTime $today = null): array
{
    $today = $today ?? new DateTime('today');
    $year = (int)$today->format('Y');
    $month = (int)$today->format('n');
    if ($month < 3) {
        $year--;
    }
    $start = DateTime::createFromFormat('Y-m-d', sprintf('%d-03-01', $year)) ?: clone $today;
    return [
        'start_date' => $start->format('Y-m-d'),
        'end_date' => $today->format('Y-m-d'),
    ];
}

function ta_default_filters(array $input): array
{
    $today = new DateTime('today');
    $startDefault = (clone $today)->modify('-29 days')->format('Y-m-d');
    $endDefault = $today->format('Y-m-d');

    $ticketTypes = [];
    if (isset($input['ticket_types'])) {
        $raw = is_array($input['ticket_types']) ? $input['ticket_types'] : explode(',', (string)$input['ticket_types']);
        foreach ($raw as $type) {
            $v = trim((string)$type);
            if ($v !== '' && strtoupper($v) !== 'ALL') {
                $ticketTypes[] = $v;
            }
        }
    }

    $singleType = trim((string)($input['ticket_type'] ?? 'ALL'));
    if (empty($ticketTypes) && $singleType !== '' && strtoupper($singleType) !== 'ALL') {
        $ticketTypes[] = $singleType;
    }

    $filters = [
        'start_date' => ta_parse_date($input['start_date'] ?? null, $startDefault),
        'end_date' => ta_parse_date($input['end_date'] ?? null, $endDefault),
        'ticket_type' => $singleType,
        'ticket_types' => array_values(array_unique($ticketTypes)),
        'status' => trim((string)($input['status'] ?? 'ALL')),
        'company_id' => (int)($input['company_id'] ?? 0),
        'branch_id' => (int)($input['branch_id'] ?? 0),
    ];

    if ($filters['start_date'] > $filters['end_date']) {
        $tmp = $filters['start_date'];
        $filters['start_date'] = $filters['end_date'];
        $filters['end_date'] = $tmp;
    }

    return $filters;
}

function ta_append_common_filters(array $filters, array &$params, string &$types): string
{
    $sql = '';

    if (!empty($filters['ticket_types'])) {
        $placeholders = implode(',', array_fill(0, count($filters['ticket_types']), '?'));
        $sql .= " AND t.ticket_type IN ({$placeholders})";
        foreach ($filters['ticket_types'] as $ticketType) {
            $params[] = $ticketType;
            $types .= 's';
        }
    } elseif ($filters['ticket_type'] !== '' && strtoupper($filters['ticket_type']) !== 'ALL') {
        $sql .= ' AND t.ticket_type = ?';
        $params[] = $filters['ticket_type'];
        $types .= 's';
    }

    if ($filters['status'] !== '' && strtoupper($filters['status']) !== 'ALL') {
        $sql .= ' AND t.status = ?';
        $params[] = $filters['status'];
        $types .= 's';
    }

    if (($filters['company_id'] ?? 0) > 0) {
        $sql .= ' AND t.corporate_id = ?';
        $params[] = (int)$filters['company_id'];
        $types .= 'i';
    }

    if (($filters['branch_id'] ?? 0) > 0) {
        $sql .= ' AND t.branch_id = ?';
        $params[] = (int)$filters['branch_id'];
        $types .= 'i';
    }

    return $sql;
}

function ta_bind_params(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '') {
        return;
    }
    $bind = [$types];
    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}
?>
