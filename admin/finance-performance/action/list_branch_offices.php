<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_offices.php');
setTimeZone();

fp_run_action('list_offices', function ($conn, $actor) {
    return fp_result(true, 200, 'OK', array('data' => array_merge(array('offices' => fp_office_list($conn)), fp_office_lookups($conn))));
});
