<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_requests.php');
setTimeZone();

fp_run_action('change_settlement_txn_status', function ($conn, $actor) {
    return fp_settlement_txn_change_status($conn, $actor, $_POST);
});
