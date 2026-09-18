<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/quote_company_details_controller.php');

header('Content-Type: application/json');

if (!isset($_SESSION['pb_username'])) {
    echo json_encode(array('error' => true, 'message' => 'Unauthorized'));
    exit;
}

$id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
if ($id <= 0) {
    echo json_encode(array('error' => true, 'message' => 'Invalid ID.'));
    exit;
}

$conn = _connectodb();
$row = getQuoteCompanyDetailsById($conn, $id);
if (empty($row) || !isset($row['ID'])) {
    echo json_encode(array('error' => true, 'message' => 'Record not found.'));
    exit;
}

echo json_encode(array('error' => false, 'data' => $row));
