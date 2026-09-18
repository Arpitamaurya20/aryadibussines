<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
include('../controller/state_manager_verification_controller.php');

header('Content-Type: application/json');

$response = array(
    'error' => true,
    'message' => 'Invalid request.',
);

$conn = _connectodb();
setTimeZone();
SessionCheck();

if (!psmv_canUserVerifyTicket($_SESSION)) {
    $response['message'] = 'You are not authorized to verify PPM tickets. Only State Manager can verify.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['TicketPK'])) {
    echo json_encode($response);
    exit;
}

$createdBy = isset($_SESSION['pb_username']) ? $_SESSION['pb_username'] : '';
$data = $_POST;
$data['CreatedBy'] = $createdBy;
$data['CreatedDate'] = date('Y-m-d');
$data['CreatedTime'] = date('H:i:s');

$response = psmv_saveTicketVerification($conn, $data);
echo json_encode($response);
