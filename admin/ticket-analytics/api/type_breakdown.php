<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/chart_queries.php';

ta_require_ajax();

$filters = ta_default_filters($_GET);
$breakdown = ta_fetch_type_breakdown($ta_conn, $filters);

ta_json_response([
    'error' => false,
    'filters' => $filters,
    'data' => $breakdown,
]);
?>
