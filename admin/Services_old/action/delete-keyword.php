<?php

include('../../controllers/common_controllers.php');

include('../controller/service_controller.php');



$conn = _connectodb();
if (isset($_POST['KEYWORDID']) && $_POST['KEYWORDID'] > 0) {
    $KEYWORDID = $_POST['KEYWORDID'];
    mysqli_query($conn, "delete from keywords where keyword_id='$KEYWORDID'");
}
?>