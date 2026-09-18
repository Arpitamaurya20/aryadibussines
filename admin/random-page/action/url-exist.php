<?php
session_start();
## Database configuration
include('../../controllers/common_controllers.php');
include('../../../function.inc.php');
$UserType = SessionCheck();
$conn = _connectodb();
setTimeZone();
if (isset($_POST['check_url'])) {
    //check url exist
    $url = $_POST['url'];
    $url = "" . FRONT_SITE_PATH . "repair-service/" . $url . "";
    $sel = "select url from random_page where url='$url'";
    if (mysqli_num_rows(mysqli_query($conn, $sel)) > 0) {
        echo 'exist';
        exit;
    } else {
        echo 'yes';
        exit();
    }
}

if (isset($_POST['check_update_url'])) {
    //check url exist
    $url = $_POST['url'];
    $ID = $_POST['ID'];
    $sql = "select url from random_page where url='$url' and ID!='$ID'";
    if (mysqli_num_rows(mysqli_query($conn, $sql)) > 0) {
        echo 'exist';
        exit;
    } else {
        echo 'yes';
        exit();
    }
}
