<?php
session_start();
## Database configuration
include('../../controllers/common_controllers.php');
include('../../../constant.inc.php');
include('../../../function.inc.php');
$UserType = SessionCheck();

$conn = _connectodb();

$title = $_POST['title'];
$ID = $_POST['ID'];
$meta_title = $_POST['meta_title'];
$meta_description = $_POST['meta_description'];
$description = $_POST['description'];
$meta_keyword = $_POST['meta_keyword'];
$url = $_POST['url'];
$url = "" . FRONT_SITE_PATH . "repair-service/" . $url . "";
$update_action = "UPDATE random_page SET title='$title',meta_title='$meta_title',description='$description',meta_keyword='$meta_keyword',url='$url' WHERE ID ='$ID' ";
$result  = mysqli_query($conn, $update_action);

redirect('../view-random-page.php');
