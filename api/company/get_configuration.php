<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../common_api_header.php');
require_once('../../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['CorporateID']))
{
	$CorporateID = $data['CorporateID'];
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();

	$company_obj = new Company($conn);
	$response = $company_obj->GetCompanyConfiguration($data);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>