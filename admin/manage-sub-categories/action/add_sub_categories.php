<?php
include("../../controllers/common_controllers.php");
include('../controller/sub_categories_controller.php');
$conn = _connectodb();

$subcategories_name = $_POST["subcategories_name"];
$categories = $_POST["categories"];


$site_query = "INSERT INTO manage_subcategories (Categories,SubCategoriesName) VALUES('$categories','$subcategories_name')";
$site_result = mysqli_query($conn, $site_query);
$site_response = array();


if ($site_result) {
    $site_response['error'] = false;
    $site_response['message'] = "Sub-Categories Added to System";

} else {
    $site_response['error'] = true;
    $site_response['emessage'] = "Please Contact to Administrtor. There is some technical issue.";
}
echo json_encode($site_response);

?>