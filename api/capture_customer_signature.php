<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['imageData']) && isset($data['TicketID']) && isset($data['GeneralServiceReportID']))
{
	$data['TicketID'] = (int)$data['TicketID'];
	$data['GeneralServiceReportID'] = (int)$data['GeneralServiceReportID'];
	if($data['GeneralServiceReportID'] <= 0)
	{
		$data['GeneralServiceReportID'] = -1;
	}
	$TicketID = $data['TicketID'];
	$imageData = preg_replace('/^data:image\/\w+;base64,/', '', (string)$data['imageData']);
	$imageData = base64_decode($imageData, true);
	if($TicketID <= 0 || $imageData === false || $imageData === '')
	{
		$response['error'] = true;
		$response['message'] = "Invalid signature data";
		echo json_encode($response);
		exit;
	}
	// Generate a unique filename for the image
	$filename = "cs_".$TicketID."_".uniqid().'.jpg';
	// Define the storage directory where the image will be saved
	$storageDirectory = '../admin/media/signature/';
	if(!is_dir($storageDirectory))
	{
		@mkdir($storageDirectory, 0775, true);
	}

	if(@file_put_contents($storageDirectory.$filename, $imageData) === false)
	{
		$response['error'] = true;
		$response['message'] = "Could not store the signature";
		echo json_encode($response);
		exit;
	}
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
		$response['ClientSignature'] = $filename;
	}
}
else
{
	$response['error'] = true;
    $response['message'] = "Missing User Field";
}
echo json_encode($response);
?>
