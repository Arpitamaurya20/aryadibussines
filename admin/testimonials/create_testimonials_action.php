<?php
session_start();
## Database configuration
include('../controllers/common_controllers.php');
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
$added_on = date("Y-m-d h:i:s");
$name = $_POST['name'];
$decs = $_POST['decs'];
$image = $_FILES['image']['name'];
$location = "../media/testimonials/" . $image;
move_uploaded_file($_FILES["image"]["tmp_name"], $location);

$sql = "INSERT into testimonials (ID ,
		name,
		decs,
		image,
		added_on
		)
		VALUES ('',
		'$name',
		'$decs',
		'$image','$added_on'
		)";
$result	= mysqli_query($conn, $sql);

header("location:view-testimonials");

?>