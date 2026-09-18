<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/dynamic-ppm/controller/dynamic_ppm_controller.php');
require_once('dynamic_ppm_helpers.php');

setTimeZone();
$conn = _connectodb();
$data = dynamic_ppm_parse_input();

$ticketID = dynamic_ppm_int($data, array('TicketID', 'ticket_id', 'ServiceReportTicketID'), 0);
if ($ticketID <= 0) {
    dynamic_ppm_response(true, 'TicketID is required.');
}

$flow = getDynamicPPMTicketFlowInfo($conn, $ticketID);
if (!empty($flow['error'])) {
    dynamic_ppm_response(true, $flow['message']);
}

dynamic_ppm_response(false, $flow['message'], $flow);

?>
