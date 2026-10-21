<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_requests.php');
setTimeZone();
fp_run_action('new_policy_version', function ($conn, $actor) {
    return fp_policy_new_version($conn, $actor, $_POST);
});
