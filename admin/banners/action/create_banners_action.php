<?php

session_start();
include('../../controllers/common_controllers.php');
include('../controller/banners_controller.php');
$UserType = SessionCheck();
if (!($UserType == "Admin")) {
?>
	<script type="text/javascript">
		window.location.href = "../authentication/login.php";
	</script>
<?php
}
$conn = _connectodb();
setTimeZone();
$added_on = date("Y-m-d");

$banner = $_FILES['banner']['name'];
$location = "../../media/banners/" . $banner;
move_uploaded_file($_FILES["banner"]["tmp_name"], $location);

$sql = "INSERT into banners (
		banner,added_on
		
		)
		VALUES (
			'$banner',
		'$added_on'		
		)";
		
$result	= mysqli_query($conn, $sql);
header("location:../view-banners.php");

?>