<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../common_api_header.php');
require_once('../../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['ReportNumber']) && isset($data['CreatedBy']) && isset($data['BranchID']) && isset($data['VisitTitle']) && isset($data['ContactPerson']) && isset($data['ContactPersonPhone']) && isset($data['ContactPersonEmail']))
{
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();

	$branch_obj = new Branch($conn);
	$branch_details = $branch_obj->getBranchDetailsbyID($data);
	$data['CorporateID'] = $branch_details['CompanyID'];

	$site_visits_obj = new Sitevisits($conn);
	$response = $site_visits_obj->InitiateSiteVisit($data);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>