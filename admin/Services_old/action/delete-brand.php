<?php

include('../../controllers/common_controllers.php');
include('../controller/service_controller.php');

$conn = _connectodb();
if (isset($_POST['brandID']) ) {
    $brandID = $_POST['brandID'];

    if(isset($_POST['brandImg'] )!='')
    {
        $brandImg = $_POST['brandImg'];
        $path="../../media/brand/".$brandImg;
        unlink($path);
    }

    
    mysqli_query($conn, "delete from service_brand where ID='$brandID'");
   
}
