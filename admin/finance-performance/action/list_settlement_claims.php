<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_requests.php');
setTimeZone();

fp_run_action('list_settlement_claims', function ($conn, $actor) {
    return fp_settlement_claim_list($conn, $actor, $_GET);
});
