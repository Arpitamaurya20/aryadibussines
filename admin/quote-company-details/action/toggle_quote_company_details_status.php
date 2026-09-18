<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/quote_company_details_controller.php');

header('Content-Type: application/json');

if (!isset($_SESSION['pb_username'])) {
    echo json_encode(array('error' => true, 'message' => 'Unauthorized'));
    exit;
}

if (!isset($_POST['ID'], $_POST['IsActive'])) {
    echo json_encode(array('error' => true, 'message' => 'Missing parameters.'));
    exit;
}

$conn = _connectodb();
$response = toggleQuoteCompanyDetailsActive($conn, $_POST['ID'], $_POST['IsActive']);
echo json_encode($response);
