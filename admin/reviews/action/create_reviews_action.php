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
$added_on = date("Y-m-d h:i:s");
$client_name = $_POST['client_name'];
$review = $_POST['review'];
$service_id = $_POST['service_id'];
$location_service = $_POST['location_service'];

$random_page= $_POST['random_page'];




$extn = explode('.', $_FILES["client_image"]["name"]);
$str = str_replace(' ', '-', strtolower($client_name));
$image   = $str . rand() . "." . $extn[1];
$upath = "../../media/reviews/" . $image;

move_uploaded_file($_FILES["client_image"]["tmp_name"], $upath);


// $sql = "INSERT into reviews (ID ,
// 		client_name,
// 		review,
// 		client_image,
// 		service_id,
// 		random_page,
// 		location_service,
// 		added_on
// 		)
// 		VALUES ('',
// 		'$client_name',
// 		'$review',
// 		'$image',
// 		'$service_id',
// 		'$random_page',
// 		'$location_service',
// 		'$added_on'
// 		)";
		
		
$sql = "INSERT into reviews (client_name,review,client_image,service_id,random_page,location_service,added_on) 	VALUES('$client_name','$review','$image','$service_id','$random_page','$location_service','$added_on')";
	
$result	= mysqli_query($conn, $sql);

header("location:../view-reviews.php");

?>