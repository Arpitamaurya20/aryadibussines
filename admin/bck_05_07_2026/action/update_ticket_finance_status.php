<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$data = $_POST;
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$data['CreatedBy'] = $_SESSION['pb_username'];
$corporateticket_obj = new Corporateticket($conn);
$response = $corporateticket_obj->UpdateTicketFinanceStatus($data);
echo json_encode($response);
?>