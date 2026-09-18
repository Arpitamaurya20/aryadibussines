<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../common_api_header.php');
// Retrieve the image data sent from the Ionic app
require_once('../../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$myfile = fopen("../logs/site_visit_logs.txt", "a") or die("Unable to open file!");
fwrite($myfile, "\n". $data_raw);
$conn = _connectodb();
setTimeZone();
$response = array();
$data = json_decode($data_raw);
$data = json_decode(json_encode($data->data), true);
//fwrite($myfile, "\n". $data);
if(isset($data['SiteVisitID']) && isset($data['imageData']) && isset($data['ObservationID']) && isset($data['CreatedBy']))
{
$CreatedBy = $data['CreatedBy'];
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');
$SiteVisitID = $data['SiteVisitID'];
$imageData = $data['imageData'];
$ObservationID = $data['ObservationID'];

$imageData = base64_decode($imageData);
// Generate a unique filename for the image

$filename = "sv_".$SiteVisitID."_".uniqid().'.jpg';

// Define the storage directory where the image will be saved
$storageDirectory = '../../admin/media/site_visits/';
file_put_contents($storageDirectory . $filename, $imageData);
$TempObservationID = -1;
if($ObservationID == -1)
{
	if(isset($data['TempObservationID']))
	{
		$TempObservationID = $data['TempObservationID'];
		if($TempObservationID == -1 || $TempObservationID == "")
		{
			$TempObservationID = time();
		}
	}
}

// Optionally, perform any additional processing or validation here
$ticket_media_query = "INSERT INTO site_observation_media (SiteVisitID,SiteObservationID,TempObservationID,Image,CreatedBy,CreatedDate,CreatedTime ) VALUES('$SiteVisitID','$ObservationID','$TempObservationID','$filename','$CreatedBy','$CreatedDate','$CreatedTime')";
$response = _InsertTableRecords($conn, $ticket_media_query);

//echo $ticket_media_query;
// Return a response to the Ionic app
$response['error'] = false;
$response['message'] = "Image saved successfully";
$response['TempObservationID'] = $TempObservationID;

}
else
{

$response['error'] = true;
$response['message'] = "Please Contact to Administrtor. There is some technical issue.";

}

echo json_encode($response);


?>