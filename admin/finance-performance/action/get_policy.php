<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_requests.php');
setTimeZone();
fp_run_action('get_policy', function ($conn, $actor) {
    return fp_policy_detail($conn, fp_input_int($_GET, 'ID'));
});
