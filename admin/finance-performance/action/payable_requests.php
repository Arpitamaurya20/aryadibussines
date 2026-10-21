<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_requests.php');
setTimeZone();

try {
    $conn = fp_db_connect();
    $actor = fp_require_action($conn, 'list_payable');
    fp_json_response(fp_payment_payable($conn, $actor, $_GET));
} catch (Throwable $e) {
    error_log('Finance & Performance list_payable: ' . $e->getMessage());
    fp_json_response(array('error' => true, 'message' => 'Technical problem. Please try again.'), 500);
}
