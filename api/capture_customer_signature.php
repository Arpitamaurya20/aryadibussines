<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
if(isset($data['imageData']) && isset($data['TicketID']) && isset($data['GeneralServiceReportID']))
{
	$imageData = $data['imageData'];
	$GeneralServiceReportID = $data['GeneralServiceReportID'];
	$TicketID = $data['TicketID'];
	$imageData = base64_decode($imageData);
	// Generate a unique filename for the image
	$filename = "cs_".$TicketID."_".uniqid().'.jpg';
	// Define the storage directory where the image will be saved
	$storageDirectory = '../admin/media/signature/';

	file_put_contents($storageDirectory.$filename, $imageData);
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();
	$service_report_obj = new Servicereport($conn);
	$Type = "CT";
	if(isset($data['Type']))
	{
		$Type = $data['Type'];
	}
	if($Type == "ppm_tickets")
	{
		$response = $service_report_obj->UpdatePPMSignature($filename,$data);
	}
	else
	{
		$response = $service_report_obj->UpdateSignature($filename,$data);
	}
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