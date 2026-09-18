<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
setTimeZone();
require_once("../admin/corporate-tickets/controller/corporate_tickets_controller.php");
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
$conn = _connectodb();
$response = getCorporateTicketStatusArray($conn);
echo json_encode($response);
?>