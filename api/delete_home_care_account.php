<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['phonenumber']))
{
	$phonenumber = $data['phonenumber'];
   $dbh = new Dbh();
	$conn = $dbh->_connectodb();
   $core = new Core();
   $where = " where CreatedBy = '$phonenumber'";
   $core->delete_identity_filter($conn,'confirm_booking',$where);
   $where = " where PhoneNumber = '$phonenumber'";
   $core->delete_identity_filter($conn,'customers',$where);
   $response['error'] = false;
    $response['emessage'] = "Account has been deleted!";
}
else
{
    $response['error'] = true;
    $response['emessage'] = "Missing User Field!";
}

echo json_encode($response);

?>