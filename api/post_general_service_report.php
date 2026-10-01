<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
@session_start();
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
setTimeZone();
$data = json_decode($data_raw,true);
$response = array();

if(isset($data['ProblemReportedByClient']) && isset($data['Observation']) && isset($data['ActionTaken']) && isset($data['Remarks']) && isset($data['ClientRepresentative']) && isset($data['ClientRepresentativeContact']) && isset($data['ClientRepresentativeEmails']) && isset($data['ClientRepresentativeDesignation']) && isset($data['Remarks']) && isset($data['ServiceReportID']) && isset($data['ServiceReportTicketID'])  && isset($data['CreatedBy']))
{
    
	$conn = _connectodb();
	extract($data);
    $Latitude = isset($Latitude) ? $Latitude : '';
    $Longitude = isset($Longitude) ? $Longitude : '';
    foreach (array('ProblemReportedByClient','Observation','ActionTaken','Remarks','ClientRepresentative','ClientRepresentativeContact','ClientRepresentativeEmails','ClientRepresentativeDesignation','Latitude','Longitude','CreatedBy') as $escapeField) {
        $$escapeField = mysqli_real_escape_string($conn, (string)$$escapeField);
    }
    $ServiceReportID = (int)$ServiceReportID;
    $ServiceReportTicketID = (int)$ServiceReportTicketID;
	$CreatedDate = date('Y-m-d');
	$CreatedTime = date('H:i:s');
	$CreatedBy = $data['CreatedBy'];
    $Type = "CT";
    if(isset($data['Type']))
    {
        $Type = $data['Type'];
    }

    // Get Customer Signature 
    if($Type == "CT")
    {
        $filter = " where TicketID = $ServiceReportTicketID and IsActive = 1";
    }
    else
    {
        $filter = " where TicketID = $ServiceReportTicketID and Type = 'PPM' and IsActive = 1";
    }
    $siganture_details = _getTableDetails($conn,'temp_client_signature',$filter);
    $ClientSignature = "";
    if($siganture_details != null)
    {
        $ClientSignature = $siganture_details['ClientSignature'];
        $update_signature_active_status = " IsActive = 0 where TicketID = $ServiceReportTicketID";
        if($Type == "PPM")
        {
            $update_signature_active_status = " IsActive = 0 where TicketID = $ServiceReportTicketID and Type='PPM'";
        }
        _UpdateTableRecords($conn,'temp_client_signature',$update_signature_active_status);
    }


    if($data['ServiceReportID'] != -1)
    {

        // Update
        $sql_update = "ProblemReportedByClient = '$ProblemReportedByClient',Observation = '$Observation',ActionTaken = '$ActionTaken',Remarks = '$Remarks',ClientRepresentative = '$ClientRepresentative',ClientRepresentativeContact = '$ClientRepresentativeContact',ClientRepresentativeEmails = '$ClientRepresentativeEmails',ClientRepresentativeDesignation = '$ClientRepresentativeDesignation',Latitude = '$Latitude',Longitude = '$Longitude' WHERE ID = $ServiceReportID";
        if($Type == "CT")
        {
            $response = _UpdateTableRecords($conn,'corporate_ticket_general_service_report',$sql_update);
        }
        else
        {
            $response = _UpdateTableRecords($conn,'ppm_ticket_general_service_report',$sql_update);
        }
        $response['ServiceReportID'] = $ServiceReportID;
        

    }
    else
    {
        
        // Insert
        if($Type == "CT")
        {
            $sql = "INSERT INTO corporate_ticket_general_service_report(TicketID,ProblemReportedByClient,Observation,ActionTaken,Remarks,ClientRepresentative,ClientRepresentativeContact,ClientRepresentativeEmails,ClientRepresentativeDesignation,ClientSignature,Latitude,Longitude,CreatedDate,CreatedTime,CreatedBy) VALUES ($ServiceReportTicketID,'$ProblemReportedByClient','$Observation','$ActionTaken','$Remarks','$ClientRepresentative','$ClientRepresentativeContact','$ClientRepresentativeEmails','$ClientRepresentativeDesignation','$ClientSignature','$Latitude','$Longitude','$CreatedDate','$CreatedTime','$CreatedBy')";
        }
        else
        {
            $sql = "INSERT INTO ppm_ticket_general_service_report(TicketID,ProblemReportedByClient,Observation,ActionTaken,Remarks,ClientRepresentative,ClientRepresentativeContact,ClientRepresentativeEmails,ClientRepresentativeDesignation,ClientSignature,Latitude,Longitude,CreatedDate,CreatedTime,CreatedBy) VALUES ($ServiceReportTicketID,'$ProblemReportedByClient','$Observation','$ActionTaken','$Remarks','$ClientRepresentative','$ClientRepresentativeContact','$ClientRepresentativeEmails','$ClientRepresentativeDesignation','$ClientSignature','$Latitude','$Longitude','$CreatedDate','$CreatedTime','$CreatedBy')";
        }
        $response = _InsertTableRecords($conn,$sql);
        if($response['error'] == false)
        {
             $response['ServiceReportID'] = $response['last_insert_id'];
        }
    }
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);

?>