<?php
/**
 * Cron: process overdue corporate ticket escalations.
 * Schedule e.g. every hour: php process_due_date_escalations.php
 * Or hit via URL with a secret key for hosted cron services.
 */
require_once(__DIR__ . '/../../controllers/common_controllers.php');
require_once(__DIR__ . '/../../includes/autoloader.inc.php');
require_once(__DIR__ . '/../controller/ticket_escalation_controller.php');

$expectedKey = 'techx_escalation_cron_2026';
if (php_sapi_name() !== 'cli') {
    $providedKey = isset($_GET['key']) ? $_GET['key'] : '';
    if ($providedKey !== $expectedKey) {
        http_response_code(403);
        echo json_encode(array('error' => true, 'message' => 'Forbidden'));
        exit;
    }
    header('Content-Type: application/json');
}

$conn = _connectodb();
setTimeZone();
$result = te_processAllOverdueEscalations($conn, 'cron');
echo json_encode($result);
