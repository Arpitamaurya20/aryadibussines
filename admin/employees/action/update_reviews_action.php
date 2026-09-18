<?php
session_start();
## Database configuration
include('../../controllers/common_controllers.php');
$UserType = SessionCheck();
if (!($UserType == "Admin")) {
?>
	<script type="text/javascript">
		window.location.href = "../../authentication/login.php";
	</script>
<?php
}
$conn = _connectodb();
setTimeZone();

$client_name = $_POST['client_name'];
$review = $_POST['review'];
$id    = $_POST['txtId'];
$service_id = $_POST['service_id'];
$location_service = $_POST['location_service'];
$random_page= $_POST['random_page'];
$update_review = "UPDATE reviews SET client_name='$client_name',location_service='$location_service',random_page='$random_page',service_id='$service_id',review='$review' WHERE ID ='$id' ";
$result_review 	= mysqli_query($conn, $update_review);

if (isset($_FILES['client_image']['name']) && $_FILES['client_image']['name'] != '') {

	$extn = explode('.', $_FILES["client_image"]["name"]);
	$str = str_replace(' ', '-', strtolower($client_name));
	$image   = $str . rand() . "." . $extn[1];
	$upath = "../../media/reviews/" . $image;
	move_uploaded_file($_FILES["client_image"]["tmp_name"], $upath);

	$update_review = "UPDATE reviews SET client_image='$image' WHERE ID ='$id' ";
	$result_review 	= mysqli_query($conn, $update_review);
}


header("location:../view-reviews.php");

?>