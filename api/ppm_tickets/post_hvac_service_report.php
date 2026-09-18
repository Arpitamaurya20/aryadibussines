<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
setTimeZone();
$data = json_decode($data_raw, true);
$response = array();
if (isset($data['ProblemReportedByClient']) && isset($data['Observation']) && isset($data['GrillTemperature']) && isset($data['AmbientTemperature']) && isset($data['RoomTemperature']) && isset($data['IndoorFan']) && isset($data['ReturnAirTemperature']) && isset($data['SupplyAirTemperature']) && isset($data['Compressor']) && isset($data['Voltage']) && isset($data['TotalCurrent']) && isset($data['OutdoorFan']) && isset($data['CompressorSuction']) && isset($data['CompressorDischarge']) && isset($data['CondenserAirInlet']) && isset($data['CondenserAirOutlet'])&& isset($data['ServiceReportTicketID'])&& isset($data['CreatedBy']) && isset($data['ServiceReportTicketID'])) 
{
    $conn = _connectodb();
    extract($data);
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $CreatedBy = $data['CreatedBy'];
    $ClientRepresentative = "";
    if(isset($data['ClientRepresentative']))
    {
        $ClientRepresentative = $data['ClientRepresentative'];
    }
    $ClientRepresentativeContact = "";
    if(isset($data['ClientRepresentativeContact']))
    {
        $ClientRepresentativeContact = $data['ClientRepresentative'];
    }
    $ClientRepresentativeEmails = "";
    if(isset($data['ClientRepresentativeEmails']))
    {
        $ClientRepresentativeEmails = $data['ClientRepresentativeEmails'];
    }
    $ClientRepresentativeDesignation = "";
    if(isset($data['ClientRepresentativeDesignation']))
    {
        $ClientRepresentativeDesignation = $data['ClientRepresentativeDesignation'];
    }
    $AssetCondition = "";
    if(isset($data['Condition']))
    {
        $AssetCondition = $data['Condition'];
    }

    $SupplyVoltage = "";
        if (isset($data['SupplyVoltage'])) {
            $SupplyVoltage = $data['SupplyVoltage'];
        }

        $AFrequency = "";
        if (isset($data['AFrequency'])) {
            $AFrequency = $data['AFrequency'];
        }

        $AmbientAirTemperature = "";
        if (isset($data['AmbientAirTemperature'])) {
            $AmbientAirTemperature = $data['AmbientAirTemperature'];
        }

        $CompressorSuctionPressure = "";
        if (isset($data['CompressorSuctionPressure'])) {
            $CompressorSuctionPressure = $data['CompressorSuctionPressure'];
        }

        $DischargePressuret = "";
        if (isset($data['DischargePressuret'])) {
            $DischargePressuret = $data['DischargePressuret'];
        }

        $CompressorCurrent = "";
        if (isset($data['CompressorCurrent'])) {
            $CompressorCurrent = $data['CompressorCurrent'];
        }

        $IndoorFanMotorCurrent = "";
        if (isset($data['IndoorFanMotorCurrent'])) {
            $IndoorFanMotorCurrent = $data['IndoorFanMotorCurrent'];
        }

        $OutdoorFanMotorCurrent = "";
        if (isset($data['OutdoorFanMotorCurrent'])) {
            $OutdoorFanMotorCurrent = $data['OutdoorFanMotorCurrent'];
        }


    // Get Customer Signature 
    $filter = "WHERE TicketID = $ServiceReportTicketID AND IsActive = 1";
    $signature_details = _getTableDetails($conn, 'temp_client_signature', $filter);
    $ClientSignature = "";
    if ($signature_details != null) 
    {
        $ClientSignature = $signature_details['ClientSignature'];
        $update_signature_active_status = "IsActive = 0 WHERE TicketID = $ServiceReportTicketID";
        _UpdateTableRecords($conn, 'temp_client_signature', $update_signature_active_status);
    }

    if ($data['ServiceReportID'] != -1) 
    {
        // Update corporate_ticket_general_service_report
        $sql_update = "ProblemReportedByClient = '$ProblemReportedByClient',Observation = '$Observation',ActionTaken = '$ActionTaken',Remarks = '$Remarks',ClientRepresentative = '$ClientRepresentative',ClientRepresentativeContact = '$ClientRepresentativeContact',ClientRepresentativeEmails = '$ClientRepresentativeEmails',ClientRepresentativeDesignation = '$ClientRepresentativeDesignation',Latitude = '$Latitude',Longitude = '$Longitude' WHERE ID = $ServiceReportID";
        $response = _UpdateTableRecords($conn,'ppm_ticket_general_service_report',$sql_update);
        $response['ServiceReportID'] = $ServiceReportID;

        // Update hvac_general_service_report
        $sql_update_hvac = "AssetCondition = '$AssetCondition',GrillTemperature = '$GrillTemperature', AmbientTemperature = '$AmbientTemperature', RoomTemperature = '$RoomTemperature', IndoorFan = '$IndoorFan', ReturnAirTemperature = '$ReturnAirTemperature', SupplyAirTemperature = '$SupplyAirTemperature', Compressor = '$Compressor', Voltage = '$Voltage', TotalCurrent = '$TotalCurrent', OutdoorFan = '$OutdoorFan', CompressorSuction = '$CompressorSuction', CompressorDischarge = '$CompressorDischarge', CondenserAirInlet = '$CondenserAirInlet', CondenserAirOutlet = '$CondenserAirOutlet',CoolingCoilCleaningCondition='$CoolingCoilCleaningCondition',DrainPumpWorkingCondition='$DrainPumpWorkingCondition',DrainTrayCondition='$DrainTrayCondition',FanMotorCondition='$FanMotorCondition',FilterCleaningCondition='$FilterCleaningCondition',PCBPhysicalWorkingCondition='$PCBPhysicalWorkingCondition',CoolingLossPreventionCondition='$CoolingLossPreventionCondition',CondesnorCoilConditionCleaning='$CondesnorCoilConditionCleaning',CompressorWorkingCondition='$CompressorWorkingCondition',OutdoorInstallationCondition='$OutdoorInstallationCondition',FanMotorWorkingCondition='$FanMotorWorkingCondition',ElectricalTerminalCondition='$ElectricalTerminalCondition',SurroundingConditionsToWork='$SurroundingConditionsToWork',SupplyVoltage='$SupplyVoltage',AFrequency='$AFrequency',AmbientAirTemperature='$AmbientAirTemperature',CompressorSuctionPressure='$CompressorSuctionPressure',DischargePressuret='$DischargePressuret',CompressorCurrent='$CompressorCurrent',IndoorFanMotorCurrent='$IndoorFanMotorCurrent',OutdoorFanMotorCurrent='$OutdoorFanMotorCurrent' WHERE ServiceReportID = $ServiceReportID";
        _UpdateTableRecords($conn, 'ppm_hvac_service_report', $sql_update_hvac);

    } 
    else 
    {
        // Insert into corporate_ticket_general_service_report
        $sql = "INSERT INTO ppm_ticket_general_service_report(TicketID,ProblemReportedByClient,Observation,ActionTaken,Remarks,ClientRepresentative,ClientRepresentativeContact,ClientRepresentativeEmails,ClientRepresentativeDesignation,ClientSignature,Latitude,Longitude,CreatedDate,CreatedTime,CreatedBy) VALUES ($ServiceReportTicketID,'$ProblemReportedByClient','$Observation','$ActionTaken','$Remarks','$ClientRepresentative','$ClientRepresentativeContact','$ClientRepresentativeEmails','$ClientRepresentativeDesignation','$ClientSignature','$Latitude','$Longitude','$CreatedDate','$CreatedTime','$CreatedBy')";
        $response = _InsertTableRecords($conn, $sql);
        if ($response['error'] == false) 
        {
            $response['ServiceReportID'] = $response['last_insert_id'];
            $ServiceReportID = $response['ServiceReportID'];
            // Insert into hvac_general_service_report
            $sql_hvac = "INSERT INTO ppm_hvac_service_report(ServiceReportID, TicketID,AssetCondition, GrillTemperature, AmbientTemperature, RoomTemperature, IndoorFan, ReturnAirTemperature, SupplyAirTemperature, Compressor, Voltage, TotalCurrent, OutdoorFan, CompressorSuction, CompressorDischarge, CondenserAirInlet, CondenserAirOutlet,CoolingCoilCleaningCondition,DrainPumpWorkingCondition,DrainTrayCondition,FanMotorCondition,FilterCleaningCondition,PCBPhysicalWorkingCondition,CoolingLossPreventionCondition,CondesnorCoilConditionCleaning,CompressorWorkingCondition,OutdoorInstallationCondition,FanMotorWorkingCondition,ElectricalTerminalCondition,SurroundingConditionsToWork,SupplyVoltage,AFrequency,AmbientAirTemperature,CompressorSuctionPressure,DischargePressuret,CompressorCurrent,IndoorFanMotorCurrent,OutdoorFanMotorCurrent,CreatedBy, CreatedDate, CreatedTime) VALUES ($ServiceReportID,$ServiceReportTicketID,'$AssetCondition','$GrillTemperature','$AmbientTemperature','$RoomTemperature','$IndoorFan','$ReturnAirTemperature','$SupplyAirTemperature','$Compressor','$Voltage','$TotalCurrent','$OutdoorFan','$CompressorSuction','$CompressorDischarge','$CondenserAirInlet','$CondenserAirOutlet','$CoolingCoilCleaningCondition','$DrainPumpWorkingCondition','$DrainTrayCondition','$FanMotorCondition','$FilterCleaningCondition','$PCBPhysicalWorkingCondition','$CoolingLossPreventionCondition','$CondesnorCoilConditionCleaning','$CompressorWorkingCondition','$OutdoorInstallationCondition','$FanMotorWorkingCondition','$ElectricalTerminalCondition','$SurroundingConditionsToWork','$SupplyVoltage','$AFrequency','$AmbientAirTemperature','$CompressorSuctionPressure','$DischargePressuret','$CompressorCurrent','$IndoorFanMotorCurrent','$OutdoorFanMotorCurrent','$CreatedBy','$CreatedTime','$CreatedTime')";
            _InsertTableRecords($conn, $sql_hvac);
        }
    }
} else {
    $response["error"] = true;
    $response["message"] = "Missing User Fields";
}

echo json_encode($response);
?>
