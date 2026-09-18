<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/rm_queries.php';

ta_require_ajax();

$filters = ta_default_filters($_GET);
$data = ta_executive_fetch_all($ta_conn, $filters, 'R&M');

ta_json_response([
    'error' => false,
    'filters' => $filters,
    'data' => $data,
]);
?>
