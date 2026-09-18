<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
require_once('../../includes/autoloader.inc.php');
$data = $_POST;
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$data['CreatedBy'] = $_SESSION['pb_username'];
$corporateticket_obj = new Corporateticket($conn);
$response = $corporateticket_obj->UpdateTicketFinances($data);
echo json_encode($response);
?>