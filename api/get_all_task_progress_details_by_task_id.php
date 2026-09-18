<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/corporate-tickets/controller/corporate_tickets_controller.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (isset($data['TaskID'])) {
    $TaskID = $data['TaskID'];
    $conn = _connectodb();	
    $where="where TaskID=$TaskID And IsActive=1";
    $data=_getTableRecords($conn,'project_task_date_progress',$where);
    $response['data'] = $data;
} else {
    $response["error"] = true;
    $response["message"] = "Missing User Fields";
}

echo json_encode($response);
?>
