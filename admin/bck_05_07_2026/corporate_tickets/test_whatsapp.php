<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("../controllers/common_controllers.php");
include('controller/corporate_tickets_controller.php');
include('../branch/controller/branch_controller.php');
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$testData = [
    'phonenumber'   => '8948975967',   // your WhatsApp number
    'ticket_number' => 'CS-Project-1001',
    'otp'           => '123456'
];

$response = sendWhatsAppMessageIs($testData);

echo "<pre>";
print_r($response);

?>