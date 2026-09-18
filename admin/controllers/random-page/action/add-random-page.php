<?php
session_start();
## Database configuration
include('../../controllers/common_controllers.php');
include('../../../constant.inc.php');
include('../../../function.inc.php');
$UserType = SessionCheck();
$conn = _connectodb();
$title = $_POST['title'];
$meta_title = $_POST['meta_title'];
$meta_description = $_POST['meta_description'];
$description = $_POST['description'];
$meta_keyword = $_POST['meta_keyword'];
$url = $_POST['url'];
$url = "" . FRONT_SITE_PATH . "repair-service/" . $url . "";

$sql = "INSERT into random_page (
    title,
    meta_title,
    description,
    meta_description,
    meta_keyword,
    url
    )
    VALUES (
    '$title',
    '$meta_title',
    '$description',
    '$meta_description',
    '$meta_keyword',
    '$url'
    )";


// echo $sql;
$result  = mysqli_query($conn, $sql);

redirect('../view-random-page.php');
