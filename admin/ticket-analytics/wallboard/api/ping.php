<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/wallboard_config.php';
wallboard_json_exit(['ok' => true, 'time' => date('c')]);
