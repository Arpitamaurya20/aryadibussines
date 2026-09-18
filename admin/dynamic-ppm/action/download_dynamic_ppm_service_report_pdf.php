<?php
@session_start();
require_once __DIR__ . '/../../controllers/common_controllers.php';
require_once __DIR__ . '/../controller/dynamic_ppm_controller.php';

$conn = _connectodb();
setTimeZone();
SessionCheck();

$ticketID = isset($_REQUEST['TicketID']) ? (int) $_REQUEST['TicketID'] : 0;
$serviceReportID = isset($_REQUEST['ServiceReportID']) ? (int) $_REQUEST['ServiceReportID'] : 0;

if ($ticketID <= 0 && $serviceReportID > 0) {
    $generalReport = _getTableDetails($conn, 'ppm_ticket_general_service_report', "WHERE ID = $serviceReportID");
    if ($generalReport) {
        $ticketID = (int) $generalReport['TicketID'];
    }
}

if ($ticketID <= 0) {
    header('HTTP/1.1 400 Bad Request');
    echo 'TicketID is required.';
    exit;
}

dynamicPPMStreamReportPdfDownload($conn, $ticketID);

?>