<?php
@session_start();
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
if(isset($data['imageData']))
{
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $imageData = $data['imageData'];
    $imageData = base64_decode($imageData);
    // Generate a unique filename for the image
    $filename = "tm_".uniqid().'.jpg';
    // Define the storage directory where the image will be saved
    $storageDirectory = '../admin/media/ticket_media/';
    file_put_contents($storageDirectory . $filename, $imageData);
    // Optionally, perform any additional processing or validation here
    // if isset temp id then no need to create
    // else create using max logic 
    // return id to api
    if(isset($data['TempImageID']))
    {
        if($data['TempImageID'] == "")
        {
            $TempImageID = GenerateTempImageID($conn) + 1;
        }
        else
        {
            $TempImageID = $data['TempImageID'];
        }
    }
    else
       {
            $TempImageID = GenerateTempImageID($conn) + 1;
            $response['TempImageID'] = $TempImageID;
       }
    $ticket_media_query = "INSERT INTO temp_capture_image (TempImageID,Image,CreatedBy,CreatedDate,CreatedTime ) VALUES('$TempImageID','$filename','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $ticket_media_query);
    $response['TempImageID'] = $TempImageID;
    // Return a response to the Ionic app
    $response['error'] = false;
    $response['message'] = "Image saved successfully";
}
else{
    $response['error'] = true;
    $response['message'] = "Please Contact to Administrtor. There is some technical issue.";
}
echo json_encode($response);
?>