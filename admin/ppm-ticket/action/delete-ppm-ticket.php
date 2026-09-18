<?php
require_once('../../includes/autoloader.inc.php');
$core = new Core();
$UserType = $core->SessionCheck();
$response = array();
$response['error'] = true;
if($UserType !== "Admin")
{
    $response['error'] = true;
    $response['message'] = "Admin Access required to delete ticket!";
}
else
{
    if(isset($_POST))
    {
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();
        $ppm_ticket_obj = new Ppmtickets($conn);
        // $result = $ppm_ticket_obj->DeletePPMTicket($_POST);
        if($result == true)
        {
            $response['message'] = "PPM Ticket Deleted";
            $response['error'] = false;
        }
        else
        	$response['message'] = "Technical Problem. Please try again";
    }
    else
    {
        $response['message'] = "Technical Problem. Please try again";
    }
}
echo json_encode($response);
?>