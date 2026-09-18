<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/dynamic-ppm/controller/dynamic_ppm_controller.php');
require_once('dynamic_ppm_helpers.php');

setTimeZone();
$conn = _connectodb();
$data = dynamic_ppm_parse_input();

$ticketID = dynamic_ppm_int($data, array('TicketID', 'ticket_id', 'ServiceReportTicketID'), 0);
$serviceReportID = dynamic_ppm_int($data, array('ServiceReportID', 'general_service_report_id'), 0);

if ($ticketID <= 0 && $serviceReportID > 0) {
    $generalReport = _getTableDetails($conn, 'ppm_ticket_general_service_report', "WHERE ID = $serviceReportID");
    if ($generalReport) {
        $ticketID = (int) $generalReport['TicketID'];
    }
}

if ($ticketID <= 0) {
    dynamic_ppm_response(true, 'TicketID is required.');
}

$stream = isset($data['stream']) ? (int) $data['stream'] : 1;
if ($stream === 1) {
    dynamicPPMStreamReportPdfDownload($conn, $ticketID);
}

$pdfResult = dynamicPPMGenerateReportPdf($conn, $ticketID, 'Download');
if (!empty($pdfResult['error'])) {
    dynamic_ppm_response(true, $pdfResult['message']);
}

dynamic_ppm_response(false, 'Dynamic PPM PDF generated.', array(
    'ticket_id' => $ticketID,
    'pdf_generated' => 1,
    'pdfname' => $pdfResult['pdfname'],
    'pdf_url' => $pdfResult['pdf_url'],
    'download_api' => $pdfResult['download_api'],
));

?>