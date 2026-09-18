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

// Required fields for UPS checklist
if (
    isset($data['ProblemReportedByClient']) &&
    isset($data['Observation']) &&
    isset($data['AssetCondition']) &&
    isset($data['CheckLooseConnection']) &&
    isset($data['CheckCleaning']) &&
    isset($data['CheckChargingStatus']) &&
    isset($data['CheckBypassSwitch']) &&
    isset($data['CheckWireColorChange']) &&
    isset($data['CheckBatteryTerminals']) &&
    isset($data['CheckBatteryVoltage']) &&
    isset($data['CheckIRValue']) &&
    isset($data['CheckEarthingVoltage']) &&
    isset($data['CheckOutputVoltage']) &&
    isset($data['CheckChargingVoltage']) &&
    isset($data['CheckCurrentLoad']) &&

    isset($data['ServiceReportTicketID']) &&
    isset($data['EquipmentDetails']) &&
    isset($data['SerialNo']) &&
    isset($data['Capacity']) &&
    isset($data['RefrigerantType']) &&
    isset($data['MakeModel']) &&
    isset($data['AttendedBy']) &&
    isset($data['CreatedBy'])
) {
    $conn = _connectodb();
    extract($data);
    
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $CreatedBy = $data['CreatedBy'];

    // Optional fields
    $ClientRepresentative = $data['ClientRepresentative'] ?? '';
    $ClientRepresentativeContact = $data['ClientRepresentativeContact'] ?? '';
    $ClientRepresentativeEmails = $data['ClientRepresentativeEmails'] ?? '';
    $ClientRepresentativeDesignation = $data['ClientRepresentativeDesignation'] ?? '';

    // Get Customer Signature
    $filter = "WHERE TicketID = $ServiceReportTicketID AND IsActive = 1";
    $signature_details = _getTableDetails($conn, 'temp_client_signature', $filter);
    $ClientSignature = "";
    if ($signature_details != null) {
        $ClientSignature = $signature_details['ClientSignature'];
        $update_signature_active_status = "IsActive = 0 WHERE TicketID = $ServiceReportTicketID";
        _UpdateTableRecords($conn, 'temp_client_signature', $update_signature_active_status);
    }

    if ($data['ServiceReportID'] != -1) {
        // ---------------- UPDATE ----------------
        $sql_update_general = "
            ProblemReportedByClient = '$ProblemReportedByClient',
            Observation = '$Observation',
            ActionTaken = '$ActionTaken',
            Remarks = '$Remarks',
            ClientRepresentative = '$ClientRepresentative',
            ClientRepresentativeContact = '$ClientRepresentativeContact',
            ClientRepresentativeEmails = '$ClientRepresentativeEmails',
            ClientRepresentativeDesignation = '$ClientRepresentativeDesignation',
            EquipmentDetails = '$EquipmentDetails',
            SerialNo = '$SerialNo',
            Capacity = '$Capacity',
            MakeModel = '$MakeModel',
            Latitude = '$Latitude',
            Longitude = '$Longitude'
            WHERE ID = $ServiceReportID
        ";
        $response = _UpdateTableRecords($conn, 'ppm_ticket_general_service_report', $sql_update_general);
        $response['ServiceReportID'] = $ServiceReportID;

        // Update UPS Service Report
        $sql_update_ups = "
            AssetCondition = '$AssetCondition',
            CheckLooseConnection = '$CheckLooseConnection',
            CheckCleaning = '$CheckCleaning',
            CheckChargingStatus = '$CheckChargingStatus',
            CheckBypassSwitch = '$CheckBypassSwitch',
            CheckWireColorChange = '$CheckWireColorChange',
            CheckBatteryTerminals = '$CheckBatteryTerminals',
            CheckBatteryVoltage = '$CheckBatteryVoltage',
            CheckIRValue = '$CheckIRValue',
            CheckEarthingVoltage = '$CheckEarthingVoltage',
            CheckOutputVoltage = '$CheckOutputVoltage',
            CheckChargingVoltage = '$CheckChargingVoltage',
            CheckCurrentLoad = '$CheckCurrentLoad',
            IRValue = '$IRValue',
            EarthingVoltage = '$EarthingVoltage',
            OutputVoltage = '$OutputVoltage',
            ChargingVoltage = '$ChargingVoltage',
            CurrentLoad = '$CurrentLoad'
            WHERE ServiceReportID = $ServiceReportID
        ";
        _UpdateTableRecords($conn, 'ppm_ups_service_report', $sql_update_ups);

    } else {
        // ---------------- INSERT ----------------
        $sql_general = "
            INSERT INTO ppm_ticket_general_service_report (
                TicketID, ProblemReportedByClient, Observation, ActionTaken, Remarks,
                ClientRepresentative, ClientRepresentativeContact, ClientRepresentativeEmails,
                ClientRepresentativeDesignation, ClientSignature, EquipmentDetails, SerialNo, Capacity, MakeModel, Latitude, Longitude,
                CreatedDate, CreatedTime, CreatedBy
            ) VALUES (
                $ServiceReportTicketID, '$ProblemReportedByClient', '$Observation', '$ActionTaken', '$Remarks',
                '$ClientRepresentative', '$ClientRepresentativeContact', '$ClientRepresentativeEmails',
                '$ClientRepresentativeDesignation', '$ClientSignature', '$EquipmentDetails', '$SerialNo', '$Capacity', '$MakeModel', '$Latitude', '$Longitude',
                '$CreatedDate', '$CreatedTime', '$CreatedBy'
            )
        ";
        $response = _InsertTableRecords($conn, $sql_general);

        if ($response['error'] == false) {
            $ServiceReportID = $response['last_insert_id'];
            $response['ServiceReportID'] = $ServiceReportID;

            // Insert into UPS Service Report
            $sql_ups = "
                INSERT INTO ppm_ups_service_report (
                    ServiceReportID, TicketID, AssetCondition,
                    CheckLooseConnection, CheckCleaning, CheckChargingStatus, CheckBypassSwitch, CheckWireColorChange,
                    CheckBatteryTerminals, CheckBatteryVoltage, CheckIRValue, CheckEarthingVoltage, CheckOutputVoltage,
                    CheckChargingVoltage, CheckCurrentLoad, IRValue, EarthingVoltage, OutputVoltage, ChargingVoltage, CurrentLoad,
                    CreatedDate, CreatedTime, CreatedBy, IsActive
                ) VALUES (
                    $ServiceReportID, $ServiceReportTicketID, '$AssetCondition',
                    '$CheckLooseConnection', '$CheckCleaning', '$CheckChargingStatus', '$CheckBypassSwitch', '$CheckWireColorChange',
                    '$CheckBatteryTerminals', '$CheckBatteryVoltage', '$CheckIRValue', '$CheckEarthingVoltage', '$CheckOutputVoltage',
                    '$CheckChargingVoltage', '$CheckCurrentLoad', '$IRValue', '$EarthingVoltage', '$OutputVoltage', '$ChargingVoltage', '$CurrentLoad',
                    '$CreatedDate', '$CreatedTime', '$CreatedBy', 1
                )
            ";
            _InsertTableRecords($conn, $sql_ups);
        }
    }
} else {
    $response["error"] = true;
    $response["message"] = "Missing required UPS checklist fields.";
}

echo json_encode($response);
?>
