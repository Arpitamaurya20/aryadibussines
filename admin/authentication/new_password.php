<?php session_start(); ?>

<!DOCTYPE html>

<html lang="en">
     <head>
        <title>
            Novologic Forget Password
        </title>
        <?php 
        include('../controllers/common_controllers.php');
        include('auth_controller/authentication_controller.php');
        $conn = _connectodb();
        include('../includes/common_head_content.php'); 
        ?>
    </head>

<?php
function getDataTempTable($conn,$UserID)
{
    $where = "WHERE UserID = '$UserID'";
    $response = _getTableDetails($conn, 'temp_table', $where);
    return $response;
}

 if ($_GET['secret']) {
    $UserID = $_GET['secret'];
    $GetDataTempTable = getDataTempTable($conn,$UserID);
    if($GetDataTempTable){
        $CretaedDate = $GetDataTempTable['CreatedDate'];
        date_default_timezone_set("Asia/Calcutta");
        $current_date = date('Y-m-d H:i:s');

