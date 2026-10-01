<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
require_once('common_api_header.php');
// Retrieve the image data sent from the Ionic app
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
if (!is_dir('logs')) {
    @mkdir('logs', 0775, true);
}
$myfile = @fopen("logs/logs.txt", "a");
if ($myfile) {
    fwrite($myfile, "\n". $data_raw);
}
$conn = _connectodb();
setTimeZone();
$response = array();
$data = json_decode($data_raw);
$data = isset($data->data) ? json_decode(json_encode($data->data), true) : array();
//print_r($data);
//fwrite($myfile, "\n". $data);
if(isset($data['TicketID']) && isset($data['imageData']) && isset($data['Action']))
{
$CreatedBy = mysqli_real_escape_string($conn, (string)$data['CreatedBy']);
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');
$TicketID = (int)$data['TicketID'];
$Action = preg_replace('/[^A-Za-z0-9_ ]/', '', (string)$data['Action']);
$imageData = $data['imageData'];

$imageData = base64_decode($imageData);
// Generate a unique filename for the image

$filename = "tm_".$Action."_".$TicketID."_".uniqid().'.jpg';

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
if (empty($response['error'])) {
	$response['error'] = false;
	$response['message'] = "Image saved successfully";
	$response['ImageID'] = $response['last_insert_id'];
	$response['Image'] = $filename;
}

}
else
{

$response['error'] = true;
$response['message'] = "Please Contact to Administrtor. There is some technical issue.";
$response['emessage'] = $response['message'];

}

echo json_encode($response);


?>
