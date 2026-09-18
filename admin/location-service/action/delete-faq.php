<?php

include('../../controllers/common_controllers.php');




$conn = _connectodb();
if (isset($_POST['FAQID']) && $_POST['FAQID'] > 0) {
    $FAQID = $_POST['FAQID'];
    mysqli_query($conn, "delete from locationfaq where ID='$FAQID'");
}
?>