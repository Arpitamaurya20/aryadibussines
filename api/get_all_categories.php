<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(1)
{
	$categories_obj = new Categories($conn);
	$categories = $categories_obj->getAllCategories();
	$response['data'] = array();
	foreach($categories as $category)
	{
		array_push($response['data'],$category);
	}
	
	$response['error'] = false;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing Categories";
}
echo json_encode($response);
?>