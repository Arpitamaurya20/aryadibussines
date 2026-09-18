<?php
@session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$response = ['error' => true, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	$response['message'] = 'POST required';
	echo json_encode($response);
	exit;
}

$slipId = isset($_POST['slip_id']) ? intval($_POST['slip_id']) : 0;
$companyId = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
$sentBy = isset($_SESSION['pp_email']) ? $_SESSION['pp_email'] : 'admin';

if ($slipId <= 0 || $companyId <= 0) {
	$response['message'] = 'Salary slip and company are required';
	echo json_encode($response);
	exit;
}

$salary = new Salarypayroll($conn);
$result = $salary->sendPayslipToEmployee($slipId, $companyId, $sentBy);
echo json_encode($result);
exit;
