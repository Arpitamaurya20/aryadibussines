<?php 
include('../../controllers/common_controllers.php');
include('authentication_controller.php');
$UserType = SessionCheck();
$conn = _connectodb();

function UpdateLoginPassword($conn,$UserID,$password_hash)
{
    $update_employee = "Password='$password_hash' WHERE UserID = '$UserID'";
    $response = _UpdateTableRecords($conn,'users', $update_employee);
    return $response;
}

function DeleteDataTempTable($conn,$UserID)
{
    $query_parameter = " where UserID = $UserID";
    delete_identity_filter($conn,"temp_table",$query_parameter);
}


$UserID = $_POST['user_id'];
$Password = $_POST['password'];
$ConfirmPassword = $_POST['confirm_password'];
if ($Password == $ConfirmPassword) {
    $password_hash = md5($Password);
    $response = UpdateLoginPassword($conn,$UserID,$password_hash);
    $response['error'] = false;
    $response['message'] = "Password Updated Successfully";
    $DeleteDataTempTable = DeleteDataTempTable($conn,$UserID);
    // header('location:../../dashboard/admin_dashboard.php');
     // header("Location:../../dashboard/admin_dashboard.php");
}
else
{
      $response['error'] = true;
    $response['message'] = "Password Not Matched";
}
echo json_encode($response);
     // header("Location:../../dashboard/admin_dashboard.php");

?>
