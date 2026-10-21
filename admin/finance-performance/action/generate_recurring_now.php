<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_requests.php');
setTimeZone();

fp_run_action('generate_recurring_now', function ($conn, $actor) {
    return fp_recurring_generate_now($conn, $actor, $_POST);
});
