<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporate_tickets_obj = new Corporateticket($conn);
$response = array();
$core = new Core();
$core->setTimeZone();
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');
$CreatedBy = $_SESSION['pb_username'];
//echo json_encode($response);
if(isset($_POST))
{
    extract($_POST);
    if($_POST['ServiceReportID'] != -1)
    {

        // Update
        $sql_update = "ProblemReportedByClient = '$ProblemReportedByClient',Observation = '$ser_observation',ActionTaken = '$ActionTaken',Remarks = '$Remarks',ClientRepresentative = '$ClientRepresentative',ClientRepresentativeContact = '$ClientRepresentativeContact',ClientRepresentativeEmails = '$ClientRepresentativeEmails',ClientRepresentativeDesignation = '$ClientRepresentativeDesignation' WHERE ID = $ServiceReportID";
        $response = $core->_UpdateTableRecords($conn,'corporate_ticket_general_service_report',$sql_update);
        $response['ServiceReportID'] = $ServiceReportID;

    }
    else
    {
        // Insert
        $sql = "INSERT INTO corporate_ticket_general_service_report(TicketID,ProblemReportedByClient,Observation,ActionTaken,Remarks,ClientRepresentative,ClientRepresentativeContact,ClientRepresentativeEmails,ClientRepresentativeDesignation,CreatedDate,CreatedTime,CreatedBy) VALUES ($ServiceReportTicketID,'$ProblemReportedByClient','$ser_observation','$ActionTaken','$Remarks','$ClientRepresentative','$ClientRepresentativeContact','$ClientRepresentativeEmails','$ClientRepresentativeDesignation','$CreatedDate','$CreatedTime','$CreatedBy')";
        $response = $core->_InsertTableRecords($conn,$sql);
        if($response['error'] == false)
        {
             $response['ServiceReportID'] = $response['last_insert_id'];
        }
    }
    echo json_encode($response);

}
?>