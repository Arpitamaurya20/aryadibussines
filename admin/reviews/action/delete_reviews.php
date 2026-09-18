<?php

include('../../controllers/common_controllers.php');

$conn = _connectodb();

if(isset($_POST['deleteid']) && isset($_POST['clientImg']))
{
    $deleteid=mysqli_real_escape_string($conn,$_POST['deleteid']);
    $clientImg=mysqli_real_escape_string($conn,$_POST['clientImg']);
    mysqli_query($conn, "delete from reviews where ID='$deleteid'");
    $path="../../media/reviews/".$clientImg;
    unlink($path);
	echo 'success';
}

?>

