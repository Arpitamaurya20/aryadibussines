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
    $actor = fp_require_action($conn, 'list_approvals');
    try {
        fp_approval_process_escalations($conn);
    } catch (Throwable $e) {
        error_log('Finance & Performance escalations: ' . $e->getMessage());
    }
    $view = fp_input_string($_GET, 'view');
    $data = $view === 'decided' ? fp_approval_my_decisions($conn, $actor) : fp_approval_inbox($conn, $actor);
    fp_json_response(array('error' => false, 'message' => '', 'data' => $data));
} catch (Throwable $e) {
    error_log('Finance & Performance list_approvals: ' . $e->getMessage());
    fp_json_response(array('error' => true, 'message' => 'Technical problem. Please try again.', 'data' => array()), 500);
}
