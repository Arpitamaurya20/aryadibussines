<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/rm_queries.php';
require_once __DIR__ . '/../inc/type_dashboard_queries.php';

ta_require_ajax();

$filters = ta_default_filters($_GET);
$type = ta_normalize_ticket_type((string)($_GET['type'] ?? 'R&M'));
$data = ta_executive_fetch_all($ta_conn, $filters, $type);

ta_json_response([
    'error' => false,
    'ticket_type' => $type,
    'filters' => $filters,
    'data' => $data,
]);
