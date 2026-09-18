<?php

include('../../controllers/common_controllers.php');

$conn = _connectodb();
if (isset($_POST['bannerID']) ) {
    $bannerID = $_POST['bannerID'];

    if(isset($_POST['bannerImg'] )!='')
    {
        $bannerImg = $_POST['bannerImg'];
        $path="../../media/promobanner/".$bannerImg;
        unlink($path);
    }

    
    mysqli_query($conn, "delete from citypromobanner where ID='$bannerID'");
   
}
