<?php

include('../../controllers/common_controllers.php');

include('../controller/random-page-controller.php');
$conn = _connectodb();


if(isset($_POST['delete_btn_set']))
{
    $del=$_POST['delete_id'];
    mysqli_query($conn, "delete from random_page where ID='$del'");
}
?>

