<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_categories.php');
setTimeZone();

fp_run_action('get_category_audit', function ($conn, $actor) {
    $id = fp_input_int($_GET, 'ID');
    if ($id <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    return fp_category_history($conn, $id);
});
