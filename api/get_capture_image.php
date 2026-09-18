<?php
require_once('common_api_header.php');
// Retrieve the image data sent from the Ionic app
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$myfile = fopen("logs/logs.txt", "a") or die("Unable to open file!");
fwrite($myfile, "\n". $data_raw);
$conn = _connectodb();
setTimeZone();
$response = array();
$data = json_decode($data_raw);
$data = json_decode(json_encode($data->data), true);
//print_r($data);
//fwrite($myfile, "\n". $data);
if(isset($data['TicketID']) && isset($data['imageData']) && isset($data['Action']))
{
$CreatedBy = $data['CreatedBy'];
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');
$TicketID = $data['TicketID'];
$Action = $data['Action'];
$imageData = $data['imageData'];

$imageData = base64_decode($imageData);
// Generate a unique filename for the image

$filename = "tm_".$Action."_".$TickedID."_".uniqid().'.jpg';

$Type = "CT";
if(isset($data['Type']))
{
	$Type = $data['Type'];
	if($Type == "ppm_tickets")
	{
		$Type = "PPM";
	}
}

// Define the storage directory where the image will be saved
$storageDirectory = '../admin/media/ticket_media/';
if($Type == "PPM")
{
	$storageDirectory = '../admin/media/ppm_ticket_media/';
}
file_put_contents($storageDirectory . $filename, $imageData);

// Optionally, perform any additional processing or validation here
$ticket_media_query = "INSERT INTO ticket_media (TicketID,Action,Image,CreatedBy,CreatedDate,CreatedTime ) VALUES('$TicketID','$Action','$filename','$CreatedBy','$CreatedDate','$CreatedTime')";
if($Type == "PPM")
{
	$ticket_media_query = "INSERT INTO ppm_ticket_media (TicketID,Action,Image,CreatedBy,CreatedDate,CreatedTime ) VALUES('$TicketID','$Action','$filename','$CreatedBy','$CreatedDate','$CreatedTime')";
}
$response = _InsertTableRecords($conn, $ticket_media_query);

//echo $ticket_media_query;
// Return a response to the Ionic app
$response['error'] = false;
$response['message'] = "Image saved successfully";

}
else
{

$response['error'] = true;
$response['emessage'] = "Please Contact to Administrtor. There is some technical issue.";

}

echo json_encode($response);


?>