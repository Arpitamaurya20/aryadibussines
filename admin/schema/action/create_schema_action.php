<?php
session_start();
## Database configuration
include('../../controllers/common_controllers.php');
include('../controller/schema_controller.php');
$UserType = SessionCheck();
if(!($UserType == "Admin"))
{
	?>
		<script type="text/javascript">
		window.location.href = "../authentication/login.php";
		</script>
		<?php
}
$conn = _connectodb();
setTimeZone();
$current_date = date("Y-m-d");
$title = $_POST['title'];
$description = $_POST['description'];
$shortdescription = $_POST['shortdescription'];
$schemaurl = $_POST['schemaurl'];


$response_block_code = CreateSchema($conn,$title,$description,$shortdescription,$schemaurl,$current_date);
echo json_encode($response_block_code);

?>