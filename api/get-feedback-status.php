<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_setup_errors', 1);
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['TicketID']))
{
	$TicketID=$data['TicketID'];
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	$where="WHERE TicketID =$TicketID";
	$responce=$core->_getTableDetails($conn,'ticket_feedback',$where);
	 if ($responce == null || empty($responce)) {
        $responce = array();
        $responce['error'] = true;
    } else {
        $responce['error'] = false;
    }
	$responce['error']==false;
	echo json_encode($responce);
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
?>