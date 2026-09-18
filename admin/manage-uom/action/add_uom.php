<?php
include("../../controllers/common_controllers.php");
include('../controller/uom_controller.php');
$conn = _connectodb();

$uom_name = $_POST["uom_name"];


$site_query = "INSERT INTO manage_uom (UOMName) VALUES('$uom_name')";
$site_result = mysqli_query($conn, $site_query);
$site_response = array();


if ($site_result) {
    $site_response['error'] = false;
    $site_response['message'] = "UOM Added to System";

} else {
    echo mysqli_error($conn);
    $site_response['error'] = true;
    $site_response['emessage'] = "Please Contact to Administrtor. There is some technical issue.";
}
echo json_encode($site_response);

?>