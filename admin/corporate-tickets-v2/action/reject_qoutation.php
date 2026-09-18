<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
setTimeZone();
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    // $TicketID = $_POST['TicketID'];
    // $CallType = $_POST['CallType'];
    // $CustumerPrice = $_POST['CustumerPrice'];
    // $ExpensePrice = $_POST['ExpensePrice'];
    // $Description = $_POST['Description'];
    $_POST['CreatedBy'] = $_SESSION['pb_username'];
    
    $result = RejectQoutation($conn,$_POST);
    if($result == true)
    {
        $response['message'] = "Ticket Has been Rejected";
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