<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_offices.php');
setTimeZone();

fp_run_action('list_office_assignments', function ($conn, $actor) {
    $officeId = fp_input_int($_GET, 'OfficeID');
    if ($officeId <= 0 || !fp_office_get($conn, $officeId)) {
        return fp_result(false, 404, 'This office no longer exists.');
    }
    return fp_result(true, 200, 'OK', array('data' => array('assignments' => fp_office_assignments($conn, $officeId))));
});
