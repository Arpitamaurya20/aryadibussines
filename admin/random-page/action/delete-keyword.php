<?php

include('../../controllers/common_controllers.php');

include('../controller/random-page-controller.php');



$conn = _connectodb();
if (isset($_POST['KEYWORDID']) && $_POST['KEYWORDID'] > 0) {
    $KEYWORDID = $_POST['KEYWORDID'];
    mysqli_query($conn, "delete from random_keywords where keyword_id='$KEYWORDID'");
}
?>