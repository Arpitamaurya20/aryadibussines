<?php

include('../../controllers/common_controllers.php');

include('../controller/service_controller.php');



$conn = _connectodb();

$serviceID = $_POST['serviceID'];

 $sql = "SELECT * FROM location_services where service=" . $serviceID;
 $result = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($result);
$no_of_location_service = mysqli_num_rows($result);


if ($no_of_location_service == 0) {
	echo "SUCCESS";
} else {
	echo "UNSUCCESS";
}
