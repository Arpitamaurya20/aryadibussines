<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if($data['category_name'])
{
	$CategoryName = trim($data['category_name']);
	$categories_obj = new Categories($conn);
	$CategoryID = $categories_obj->getCategoryIDfromCategoryName($CategoryName);
	$sub_categories = $categories_obj->getAllSubCategoriesfromCategoryID($CategoryID);
	$response['data'] = array();
	foreach($sub_categories as $category)
	{
		array_push($response['data'],$category);
	}
	
	$response['error'] = false;
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing Category ID";
}
echo json_encode($response);
?>