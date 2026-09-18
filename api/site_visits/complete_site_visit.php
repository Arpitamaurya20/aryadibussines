<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../common_api_header.php');
require_once('../../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
if(isset($data['Summary']) && isset($data['CreatedBy']) && isset($data['SiteVisitID']))
{
	$core = new Core();
	$core->setTimeZone();
	
	$SiteVisitID = $data['SiteVisitID'];
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();
	$site_visits_obj = new Sitevisits($conn);
	$response = $site_visits_obj->CompleteSiteVisit($data);
	if($response['error'] == false)
	{
		$core = new Core();
		$url = "https://techxpertindia.in/admin/site_visits/action/generate_site_visit_pdf.php";
		// Define the POST data
		$postData = [
		    'SiteVisitID' => $SiteVisitID,
		    'Action' => 'Send'
		];
		$response_generate_pdf = $core->sendCurlRequest($postData,$url);
		$response['message'] = "Site Visit Completed";
	}
}
else
{
	$response['error'] = true;
    $response['message'] = "Missing User Field";
}
echo json_encode($response);
?>