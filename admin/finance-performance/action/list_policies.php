<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_requests.php');
setTimeZone();
fp_run_action('list_policies', function ($conn, $actor) {
    return fp_result(true, 200, 'OK', array('data' => array_merge(
        array('policies' => fp_policy_list($conn)),
        fp_policy_lookups($conn)
    )));
});
