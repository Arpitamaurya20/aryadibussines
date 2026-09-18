<?php
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporate_tickets_obj = new Corporateticket($conn);
$service_report_obj = new Servicereport($conn);
$response = array();
$response['error'] = true;
if (isset($_POST['TicketID'])) {
    $TicketID = $_POST['TicketID'];
    $response = $corporate_tickets_obj->GetTicketDetails($TicketID);
    $service_report = $service_report_obj->GetServiceReportDetails($TicketID);
    if($service_report == null)
    {
        $response['service_report_exist'] = false; 
    }
    else
    {
        $response['service_report_exist'] = true; 
        $response['service_report_details'] = $service_report; 
    }
    if(isset($response['ticket_details']))
    {
        $response['error'] = false;
    }
}
else
{
    $response['error'] = true;
}
echo json_encode($response);
?>