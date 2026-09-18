<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
include('../../corporate-users/controller/corporate_users_controller.php');
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
    
    $result = QuotationUpload($conn,$_POST);

    // Check and Send message to approver
    if($result['error'] == false)
    {
        $TicketID = $_POST['TicketID'];
        $data = array();
        $data['TicketID'] = $TicketID;
        $ticket_details = getCorporateTicketDetail($conn,$data)['data'];
        $Message_TicketID = $ticket_details['TicketID'];

        $customer_price = $ticket_details['CustumerPrice'];
        $approver_details = GetCorporateUserByCustomerPrice($conn,$customer_price);
        if(isset($approver_details['Name']))
        {
            $ApproverName = $approver_details['Name'];
            $message = "Hello $ApproverName,\n\n Quotation for Ticket - $Message_TicketID has been uploaded\n\nKindly check and approve!\n\nRegards,\nTechXpert Team";
            $ApproverPhone = "+91".$approver_details['Phonenumber'];
            sendWhatsAppMessage($ApproverPhone,$message);
        }
    }

    if($result['error'] == false)
    {
        $response['message'] = "Ticket Qoutation Has been Uploaded";
        $response['error'] = false;
    }
    else
    {
        $response['error'] = true;
        $response['message'] = "Technical Problem. Please try again";
    }
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>