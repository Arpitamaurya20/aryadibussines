<?php
session_start();
## Database configuration
include('../../controllers/common_controllers.php');
include('../controller/schema_controller.php');
$UserType = SessionCheck();

$conn = _connectodb();
setTimeZone();
$current_date = date("Y-m-d");
$title = $_POST['title'];
$description = $_POST['description'];
$shortdescription = $_POST['shortdescription'];
$schemaurl = $_POST['schemaurl'];
$SchemaId = $_POST['SchemaId'];
$response_block_code = UpdateSchema($conn,$title,$description,$shortdescription,$schemaurl,$current_date,$SchemaId);
echo json_encode($response_block_code);
?>