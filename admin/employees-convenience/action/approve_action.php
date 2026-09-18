<?php
require_once('../../includes/autoloader.inc.php');
$data = $_POST;
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$employee_convenience = new Employeeconvenience($conn);
$response = $employee_convenience->UpdateConvenienceApproval($data);
echo json_encode($response);
?>