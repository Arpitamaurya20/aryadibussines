<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_arc_controller.php');
@session_start();
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $_POST['CreatedBy'] = $_SESSION['pb_username'];
    $CartID = -1;
    if(isset($_SESSION['CartID']))
    {
        $CartID = $_SESSION['CartID'];
    }
    else
    {
        // Find active Cart 
        $BranchID = $_POST['add_branch_id'];
        $where = " where BranchID = $BranchID and Status = 'Add to Cart'";
        $Cartdetails = _getTableDetails($conn,'temp_cart',$where);
        if(isset($Cartdetails['CartID']))
        {
            $CartID = $Cartdetails['CartID'];
        }
        else
        {
            $CartID = GenerateCartID($conn) + 1;
        }
        $_SESSION['CartID'] = $CartID;
    }
    $_POST['CartID'] = $CartID;
    $result = AddToCart($conn,$_POST);
    if($result == true)
    {
        $response['message'] = "ARC Item Added / Updated to Cart";
        $response['error'] = false;
    }
    else
    	$response['message'] = "Technical Problem. Please try again";
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>