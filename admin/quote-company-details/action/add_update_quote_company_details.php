<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/quote_company_details_controller.php');

header('Content-Type: application/json');

if (!isset($_SESSION['pb_username'])) {
    echo json_encode(array('error' => true, 'message' => 'Unauthorized'));
    exit;
}

$response = array('error' => true, 'message' => 'Invalid request.');
if (!isset($_POST['form_action'])) {
    echo json_encode($response);
    exit;
}

$conn = _connectodb();
$form_action = $_POST['form_action'];

if ($form_action === 'add') {
    $response = insertQuoteCompanyDetails($conn, $_POST, $_FILES);
} elseif ($form_action === 'edit') {
    $response = updateQuoteCompanyDetails($conn, $_POST, $_FILES);
}

echo json_encode($response);
