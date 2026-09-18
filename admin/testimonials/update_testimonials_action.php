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

$name = $_POST['name'];
$decs = $_POST['decs'];
$id    = $_POST['txtId'];
$update_story = "UPDATE testimonials SET name='$name', decs='$decs' WHERE id ='$id' ";
$result_story 	= mysqli_query($conn, $update_story);

if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {

	$image = $_FILES['image']['name'];
	$location = "../media/testimonials/" . $image;
	move_uploaded_file($_FILES["image"]["tmp_name"], $location);


	$update_story = "UPDATE testimonials SET image='$image' WHERE id ='$id' ";
	$result_story 	= mysqli_query($conn, $update_story);
}


//$response_block_code = CreateStory($conn,$Name,$Title,$Location,$Story);
header("location:view-testimonials");
//echo json_encode($response_block_code);

?>