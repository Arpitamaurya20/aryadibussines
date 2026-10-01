<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
require_once('../common_api_header.php');
require_once('../../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
if(isset($data['imageData']) && isset($data['SiteVisitID']))
{
	$imageData = $data['imageData'];
	$SiteVisitID = $data['SiteVisitID'];
	$imageData = base64_decode($imageData);
	// Generate a unique filename for the image
	$filename = "cs_".$SiteVisitID."_".uniqid().'.jpg';
	// Define the storage directory where the image will be saved
	$storageDirectory = '../../admin/media/signature/';
	if (!is_dir($storageDirectory)) {
		mkdir($storageDirectory, 0777, true);
	}

	if (file_put_contents($storageDirectory.$filename, $imageData) === false) {
		$response['error'] = true;
		$response['message'] = "Unable to store the signature.";
		echo json_encode($response);
		exit;
	}
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();
	$site_visits_obj = new Sitevisits($conn);
	$response = $site_visits_obj->UpdateSVSignature($filename,$data);
	if($response['error'] == false)
	{
		$response['message'] = "Customer Signature updated";
	}
}
else
{
	$response['error'] = true;
    $response['message'] = "Missing User Field";
}
echo json_encode($response);
?>