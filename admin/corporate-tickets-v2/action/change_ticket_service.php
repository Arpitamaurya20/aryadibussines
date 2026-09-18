<?php
require_once('../../includes/autoloader.inc.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $corporateticket = new Corporateticket($conn);
    $result = $corporateticket->UpdateTicketService($_POST);
    if($result['error'] == false)
    {
        $response['message'] = "Ticket Service has been Updated";
        $response['error'] = false;
    }
    else
    {
    	$response['message'] = "Technical Problem. Please try again";
        $response['error'] = true;
    }
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>