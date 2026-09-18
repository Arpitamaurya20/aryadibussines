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

// Required fields for CCTV
if (
    isset($data['ProblemReportedByClient']) &&
    isset($data['Observation']) &&
    isset($data['AssetCondition']) &&
    isset($data['RemoveDustFromPanel']) &&
    isset($data['CheckIndicationLamps']) &&
    isset($data['CheckVoltmeterAmmeter']) &&
    isset($data['CheckSelectorSwitches']) &&
    isset($data['CorrectLooseConnection']) &&
    isset($data['CheckTPN']) &&
    isset($data['RemoveSignboard']) &&
    isset($data['EnsureNoToolsLeft']) &&
    isset($data['ApplyLockTagNotice']) &&
    isset($data['PersonalProtectiveEmergency']) &&
    isset($data['WasteClothes']) &&
    isset($data['RustCleaningAgent']) &&
    isset($data['Multimeter']) &&
    isset($data['ToolSet']) &&
    isset($data['EarthingResistance'])&&
    isset($data['Frequency'])&&
    isset($data['Current'])&&
    isset($data['PowerFactor'])&&
    isset($data['Supply1PhaseVoltage'])&&
    isset($data['Supply3PhaseVoltage'])&&

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
        // Update General Service Report
        $sql_update_general = "
            ProblemReportedByClient = '$ProblemReportedByClient',
            Observation = '$Observation',
            ActionTaken = '$ActionTaken',
            Remarks = '$Remarks',
            ClientRepresentative = '$ClientRepresentative',
            ClientRepresentativeContact = '$ClientRepresentativeContact',
            ClientRepresentativeEmails = '$ClientRepresentativeEmails',
            ClientRepresentativeDesignation = '$ClientRepresentativeDesignation',
            EquipmentDetails='$EquipmentDetails',
            SerialNo='$SerialNo',
            Capacity='$Capacity',
            RefrigerantType='$RefrigerantType',
            MakeModel='$MakeModel',
            Latitude = '$Latitude',
            Longitude = '$Longitude'
            WHERE ID = $ServiceReportID
        ";
        $response = _UpdateTableRecords($conn, 'ppm_ticket_general_service_report', $sql_update_general);
        $response['ServiceReportID'] = $ServiceReportID;

        // Update CCTV Service Report
        $sql_update_ep = "
            AssetCondition = '$AssetCondition',
            RemoveDustFromPanel = '$RemoveDustFromPanel',
            CheckIndicationLamps = '$CheckIndicationLamps',
            CheckVoltmeterAmmeter = '$CheckVoltmeterAmmeter',
            CheckSelectorSwitches = '$CheckSelectorSwitches',
            CorrectLooseConnection = '$CorrectLooseConnection',
            CheckTPN                = '$CheckTPN',
            RemoveSignboard = '$RemoveSignboard',
            EnsureNoToolsLeft = '$EnsureNoToolsLeft',
            ApplyLockTagNotice = '$ApplyLockTagNotice',
            PersonalProtectiveEmergency = '$PersonalProtectiveEmergency',
            WasteClothes = '$WasteClothes',
            RustCleaningAgent = '$RustCleaningAgent',
            Multimeter = '$Multimeter',
            ToolSet = '$ToolSet',
            EarthingResistance = '$EarthingResistance',
            Frequency = '$Frequency',
            Current = '$Current',
            PowerFactor = '$PowerFactor',
            Supply1PhaseVoltage = '$Supply1PhaseVoltage',
            Supply3PhaseVoltage = '$Supply3PhaseVoltage'
            WHERE ServiceReportID = $ServiceReportID
        ";
        _UpdateTableRecords($conn, 'ppm_ep_service_report', $sql_update_ep);

    } else {
        // ---------------- INSERT ----------------
        // Insert into General Service Report
        $sql_general = "
            INSERT INTO ppm_ticket_general_service_report (
                TicketID, ProblemReportedByClient, Observation, ActionTaken, Remarks,
                ClientRepresentative, ClientRepresentativeContact, ClientRepresentativeEmails,
                ClientRepresentativeDesignation, ClientSignature,EquipmentDetails,SerialNo,Capacity,RefrigerantType,MakeModel,Latitude, Longitude,
                CreatedDate, CreatedTime, CreatedBy
            ) VALUES (
                $ServiceReportTicketID, '$ProblemReportedByClient', '$Observation', '$ActionTaken', '$Remarks',
                '$ClientRepresentative', '$ClientRepresentativeContact', '$ClientRepresentativeEmails',
                '$ClientRepresentativeDesignation', '$ClientSignature','$EquipmentDetails','$SerialNo','$Capacity','$RefrigerantType','$MakeModel','$Latitude', '$Longitude','$CreatedDate', '$CreatedTime', '$CreatedBy'
            )
        ";
        $response = _InsertTableRecords($conn, $sql_general);

        if ($response['error'] == false) {
            $ServiceReportID = $response['last_insert_id'];
            $response['ServiceReportID'] = $ServiceReportID;

            // Insert into CCTV Service Report
            $sql_cctv = "
                INSERT INTO ppm_ep_service_report (
                    ServiceReportID, TicketID, AssetCondition,
                    RemoveDustFromPanel,CheckIndicationLamps, CheckVoltmeterAmmeter,
                    CheckSelectorSwitches, CorrectLooseConnection,CheckTPN, RemoveSignboard,
                    EnsureNoToolsLeft, ApplyLockTagNotice, PersonalProtectiveEmergency,
                    WasteClothes, RustCleaningAgent,
                    Multimeter, ToolSet,EarthingResistance,Frequency,Current,PowerFactor,Supply1PhaseVoltage,Supply3PhaseVoltage,
                    CreatedDate, CreatedTime, CreatedBy
                ) VALUES (
                    $ServiceReportID, $ServiceReportTicketID, '$AssetCondition',
                    '$RemoveDustFromPanel', '$CheckIndicationLamps', '$CheckVoltmeterAmmeter',
                    '$CheckSelectorSwitches', '$CorrectLooseConnection','$CheckTPN','$RemoveSignboard',
                    '$EnsureNoToolsLeft', '$ApplyLockTagNotice', '$PersonalProtectiveEmergency',
                    '$WasteClothes', '$RustCleaningAgent',
                    '$Multimeter', '$ToolSet','$EarthingResistance','$Frequency','$Current','$PowerFactor','$Supply1PhaseVoltage','$Supply3PhaseVoltage',
                    '$CreatedDate', '$CreatedTime', '$CreatedBy'
                )
            ";
            _InsertTableRecords($conn, $sql_cctv);
        }
    }
} else {
    $response["error"] = true;
    $response["message"] = "Missing required CCTV checklist fields.";
}

echo json_encode($response);
?>
