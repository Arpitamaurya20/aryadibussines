<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$data = $_POST;
$file_data = $_FILES;
$data['CreatedBy'] = $_SESSION['pb_username'];
$data['CreatedDate'] = date('Y-m-d');
$data['CreatedTime'] = date('H:i:s');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$ppm_ticket_obj = new Ppmtickets($conn);
$response = $ppm_ticket_obj->UploadTicketMedia($data,$file_data);
if($response['error'] == false)
{
    $response['message'] = "Media Uploaded";
}
echo json_encode($response);
?>