<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
setTimeZone();
require_once("../admin/branch/controller/branch_controller.php");
require_once("../admin/corporate-tickets/controller/corporate_tickets_controller.php");
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');

// Log request for debugging
$filename = 'ticket.logs';
$file = fopen($filename, 'a');
fwrite($file, $data_raw . PHP_EOL);
fclose($file);

$data = json_decode($data_raw, true);
$response = array();

if (isset($data['TicketID']) && 
    isset($data['Is_Uniform']) && 
    isset($data['Has_Jacket']) && 
    isset($data['Has_Toolkit_Isolated']) && 
    isset($data['Has_Safety_Shoes']) && 
    isset($data['Has_Ppe_Kit']) 
) {
    $conn = _connectodb();
    $data['CreatedDate'] = date("Y-m-d");
    $data['CreatedTime'] = date("H:i:s");
    $response = CheckTechnicianSafty($conn, $data);
} else {
    $response['error'] = true;
    $response['message'] = "Missing required fields!";
}

header('Content-Type: application/json');
echo json_encode($response);
?>