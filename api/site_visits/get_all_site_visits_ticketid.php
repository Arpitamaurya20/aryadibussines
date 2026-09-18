<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../common_api_header.php');
require_once('../../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['TicketID']))
{
	if(isset($data['start_counter']))
	{
		$start_counter = $data['start_counter'];
		$no_of_records = $data['no_of_records'];
		$filter_limit = " LIMIT $start_counter,$no_of_records ";
		$data['filter_limit'] = $filter_limit;
	}
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();

	$site_visits_obj = new Sitevisits($conn);
	$response = $site_visits_obj->GetAllSiteVisitsTicketID($data);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>