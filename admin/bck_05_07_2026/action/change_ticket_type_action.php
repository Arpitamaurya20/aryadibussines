<?php
require_once('../../includes/autoloader.inc.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $corporateticket = new Corporateticket($conn);

    $TicketID = $_POST['TicketID'];
    $TicketType = $_POST['service_type'];
    $result = $corporateticket->UpdateTicketType($_POST);
    if($result == true)
    {
        $response['message'] = "Ticket Type has been Updated";
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