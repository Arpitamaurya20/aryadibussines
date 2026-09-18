<?php

session_start();

## Database configuration

include('../../controllers/common_controllers.php');

include('../controller/cfl_controller.php');

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

$password = $_POST['password'];

$ResetId = $_POST['ResetId'];



$response_code = resetpassword($conn,$password,$ResetId);

echo json_encode($response_code);



?>