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

// Required fields for Fire Extinguisher (FAS)
if (
    isset($data['ProblemReportedByClient']) &&
    isset($data['Observation']) &&
    isset($data['AssetCondition']) &&
    isset($data['TypeCylinderCheck']) &&
    isset($data['CylinderCapacityCheck']) &&
    isset($data['GripCheck']) &&
    isset($data['SafetyPinPositionCheck']) &&
    isset($data['SafetySealAvailableCheck']) &&
    isset($data['RefilledDateCheck']) &&
    isset($data['DueDateCheck']) &&
    isset($data['HydrostaticTestingDateCheck']) &&
    isset($data['AccessClearnaceExtinguisherCheck']) &&
    isset($data['VisibleInstructionLabelCheck']) &&
    isset($data['ExtinguisherCleaning']) &&
    isset($data['CO2WeightCheck']) &&
    isset($data['PressureCheck']) &&
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

        // Update Fire Extinguisher Service Report
        $sql_update_fas = "
            AssetCondition = '$AssetCondition',
            TypeCylinderCheck = '$TypeCylinderCheck',
            CylinderCapacityCheck = '$CylinderCapacityCheck',
            GripCheck = '$GripCheck',
            SafetyPinPositionCheck = '$SafetyPinPositionCheck',
            SafetySealAvailableCheck = '$SafetySealAvailableCheck',
            RefilledDateCheck = '$RefilledDateCheck',
            DueDateCheck = '$DueDateCheck',
            HydrostaticTestingDateCheck = '$HydrostaticTestingDateCheck',
            AccessClearnaceExtinguisherCheck = '$AccessClearnaceExtinguisherCheck',
            VisibleInstructionLabelCheck = '$VisibleInstructionLabelCheck',
            ExtinguisherCleaning = '$ExtinguisherCleaning',
            CO2WeightCheck = '$CO2WeightCheck',
            PressureCheck = '$PressureCheck'
            WHERE ServiceReportID = $ServiceReportID
        ";
        _UpdateTableRecords($conn, 'ppm_fire_extinguisher_service_report', $sql_update_fas);

    } else {
        // ---------------- INSERT ----------------
        // Insert into General Service Report
        $sql_general = "
            INSERT INTO ppm_ticket_general_service_report (
                TicketID, ProblemReportedByClient, Observation, ActionTaken, Remarks,
                ClientRepresentative, ClientRepresentativeContact, ClientRepresentativeEmails,
                ClientRepresentativeDesignation, ClientSignature, EquipmentDetails, SerialNo,
                Capacity, RefrigerantType, MakeModel, Latitude, Longitude,
                CreatedDate, CreatedTime, CreatedBy
            ) VALUES (
                $ServiceReportTicketID, '$ProblemReportedByClient', '$Observation', '$ActionTaken', '$Remarks',
                '$ClientRepresentative', '$ClientRepresentativeContact', '$ClientRepresentativeEmails',
                '$ClientRepresentativeDesignation', '$ClientSignature', '$EquipmentDetails', '$SerialNo',
                '$Capacity', '$RefrigerantType', '$MakeModel', '$Latitude', '$Longitude',
                '$CreatedDate', '$CreatedTime', '$CreatedBy'
            )
        ";
        $response = _InsertTableRecords($conn, $sql_general);

        if ($response['error'] == false) {
            $ServiceReportID = $response['last_insert_id'];
            $response['ServiceReportID'] = $ServiceReportID;

            // Insert into Fire Extinguisher Service Report
            $sql_fas = "
                INSERT INTO ppm_fire_extinguisher_service_report (
                    ServiceReportID, TicketID, AssetCondition,
                    TypeCylinderCheck, CylinderCapacityCheck, GripCheck,
                    SafetyPinPositionCheck, SafetySealAvailableCheck,
                    RefilledDateCheck, DueDateCheck, HydrostaticTestingDateCheck,
                    AccessClearnaceExtinguisherCheck, VisibleInstructionLabelCheck,
                    ExtinguisherCleaning, CO2WeightCheck, PressureCheck,
                    CreatedDate, CreatedTime, CreatedBy
                ) VALUES (
                    $ServiceReportID, $ServiceReportTicketID, '$AssetCondition',
                    '$TypeCylinderCheck', '$CylinderCapacityCheck', '$GripCheck',
                    '$SafetyPinPositionCheck', '$SafetySealAvailableCheck',
                    '$RefilledDateCheck', '$DueDateCheck', '$HydrostaticTestingDateCheck',
                    '$AccessClearnaceExtinguisherCheck', '$VisibleInstructionLabelCheck',
                    '$ExtinguisherCleaning', '$CO2WeightCheck', '$PressureCheck',
                    '$CreatedDate', '$CreatedTime', '$CreatedBy'
                )
            ";
            _InsertTableRecords($conn, $sql_fas);
        }
    }
} else {
    $response["error"] = true;
    $response["message"] = "Missing required FAS checklist fields.";
}

echo json_encode($response);
?>
