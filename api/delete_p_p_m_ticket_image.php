<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
// Retrieve the image data sent from the Ionic app
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$myfile = @fopen("logs/logs.txt", "a");
if ($myfile) {
    fwrite($myfile, "\n". $data_raw);
}
$conn = _connectodb();
setTimeZone();
$response = array();
$data = json_decode($data_raw, true);
if(isset($data['TicketID']) && isset($data['ImageID']))
{
$dbh = new Dbh();
$conn = $dbh->_connectodb();
 $TicketID = (int)$data['TicketID'];
 $ImageID = (int)$data['ImageID'];
 updatecaputesignatureppm($conn,$ImageID,$TicketID);
 $response['error'] = false;
 $response['message'] = "Image Deleted successfully";

}
else
{

$response['error'] = true;
$response['message'] = "Please Contact to Administrtor. There is some technical issue.";
$response['emessage'] = $response['message'];

}

echo json_encode($response);


?>
