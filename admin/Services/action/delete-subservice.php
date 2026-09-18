<?php

include('../../controllers/common_controllers.php');

include('../controller/service_controller.php');

$conn = _connectodb();
if (isset($_POST['subservceId']) && $_POST['subservceId'] > 0) {
    $subservceId = $_POST['subservceId'];
    mysqli_query($conn, "delete from subservice where ID='$subservceId'");
}
?>