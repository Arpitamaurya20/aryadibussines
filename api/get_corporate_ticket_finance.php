<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['TicketID']))
{
	$TicketID = $data['TicketID'];
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();

	$corporateticket = new Corporateticket($conn);
	$response = $corporateticket->GetCorporateFinancebyTicketID($data);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>