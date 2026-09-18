<?php 
include('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
include('../controller/employee_controller.php');
setTimeZone();
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$config = new Config($conn);
$setting_roles = $config->setSettingRoles();
$response = array();
if(isset($_POST))
{
	$_POST['pb_username'] = $username;
    $_POST['CreatedDate'] = date("Y-m-d");
    $_POST['CreatedTime'] = date("H:i:s");
    $conn = _connectodb();
    $response = ManageRole_Supervisor($conn,$_POST,$setting_roles);
}
echo json_encode($response);
?>