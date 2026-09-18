<?php
include("../../controllers/common_controllers.php");
include('../controller/site_controller.php');
$conn = _connectodb();

$Site_name = $_POST["site_name"];


$site_query = "INSERT INTO site (site_name ) VALUES('$Site_name')";
$site_result = mysqli_query($conn, $site_query);
$site_response = array();


if ($site_result) {
    $site_response['error'] = false;
    $site_response['message'] = "Site Added to System";

} else {
    echo mysqli_error($conn);
    $site_response['error'] = true;
    $site_response['emessage'] = "Please Contact to Administrtor. There is some technical issue.";
}
echo json_encode($site_response);

?>