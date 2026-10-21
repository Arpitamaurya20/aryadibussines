<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_requests.php');
setTimeZone();

fp_run_action('get_settlement_form', function ($conn, $actor) {
    return fp_settlement_form_data($conn, $actor, fp_input_int($_GET, 'request'), fp_input_int($_GET, 'id'));
});
