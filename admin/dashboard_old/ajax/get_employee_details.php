<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);  // hide errors from browser
ini_set('log_errors', 1);      // but log them

require_once('../../controllers/common_controllers.php');
include("../../includes/autoloader.inc.php");

$conn = _connectodb();
$core = new Core();
$core->setTimeZone();
$response = ['success' => false];

if (isset($_GET['employee'])) {
    $id = intval($_GET['employee']);
    $stmt = $conn->prepare("SELECT EmployeeNumber,Name, Designation, Email, ContactNumber 
                            FROM employees 
                            WHERE ID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res) {
        $response['success'] = true;
        $response['data'] = $res;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
