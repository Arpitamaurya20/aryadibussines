<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
require_once('../admin/customer/controller/customer_controller.php');
require_once('../admin/authentication/auth_controller/authentication_controller.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
setTimeZone();
$response = array();

if(isset($data['phonenumber']) && isset($data['TicketID']))
{
    $phonenumber = $data['phonenumber'];
    $PhoneEnc = base64_encode($phonenumber);
    $TicketID = $data['TicketID'];
    $conn = _connectodb();
    $TicketID = base64_encode($TicketID);
    $url = "https://techxpertindia.in/customer-service-rating?service-ticket-id=$TicketID&by=$PhoneEnc";
    $message = "Dear Customer,\n\nThank you for using our services. We value your feedback and request you to kindly share your experience by clicking the link below:\n\n$url\n\nYour feedback helps us serve you better.\n\n- Team TechXpert";
    sendWhatsAppMessage($phonenumber, $message);
    $response["error"] = false;
    $response["message"] = "Feedback link sent successfully.";
}
else 
{
    $response["error"] = true;
    $response["message"] = "Missing User Fields";
}

echo json_encode($response);
?>
