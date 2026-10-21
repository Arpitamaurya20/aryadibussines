<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_categories.php');
setTimeZone();

fp_run_action('set_category_status', function ($conn, $actor) {
    return fp_category_set_status($conn, $actor, $_POST);
});
