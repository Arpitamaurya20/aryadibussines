<?php
@session_start();
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/includes/autoloader.inc.php');
$core = new Core();
$data_raw = file_get_contents('php://input');
setTimeZone();
$data = json_decode($data_raw, true);
$response = array();
if (isset($data['ProblemReportedByClient']) && isset($data['Observation']) && isset($data['TypeCylinderCheck']) && isset($data['CylinderCapacityCheck']) && isset($data['GripCheck']) && isset($data['SafetyPinPositionCheck']) && isset($data['SafetySealAvailableCheck']) && isset($data['RefilledDateCheck']) && isset($data['DueDateCheck']) && isset($data['HydrostaticTestingDateCheck']) && isset($data['AccessClearnaceExtinguisherCheck']) && isset($data['VisibleInstructionLabelCheck']) && isset($data['ExtinguisherCleaning']) && isset($data['CO2WeightCheck']) && isset($data['PressureCheck']) && isset($data['CreatedBy']) && isset($data['ServiceReportTicketID'])) 
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

    if($Latitude == null)
    {
        $Latitude = "";
    }
    if($Longitude == null)
    {
        $Longitude = "";
    }

    if ($data['ServiceReportID'] != -1) 
    {
        $rowData = [
                'ProblemReportedByClient' => $ProblemReportedByClient,
                'Observation' => $Observation,
                'ActionTaken' => $ActionTaken,
                'Remarks' => $Remarks,
                'ClientRepresentative' => $ClientRepresentative,
                'ClientRepresentativeContact' => $ClientRepresentativeContact,
                'ClientRepresentativeEmails' => $ClientRepresentativeEmails,
                'ClientRepresentativeDesignation' => $ClientRepresentativeDesignation,
                'ClientSignature' => $ClientSignature,
                'Latitude' => $Latitude,
                'Longitude' => $Longitude
            ];
        $whereCondition = [
            'ID' => $ServiceReportID
        ];
        $response = $core->_UpdateTableRecords_prepare($conn, 'corporate_ticket_general_service_report', $rowData, $whereCondition);
        
        $rowData = [
                'AssetCondition' => $AssetCondition,
                'TypeCylinderCheck' => $TypeCylinderCheck,
                'CylinderCapacityCheck' => $CylinderCapacityCheck,
                'GripCheck' => $GripCheck,
                'SafetyPinPositionCheck' => $SafetyPinPositionCheck,
                'SafetySealAvailableCheck' => $SafetySealAvailableCheck,
                'RefilledDateCheck' => $RefilledDateCheck,
                'DueDateCheck' => $DueDateCheck,
                'HydrostaticTestingDateCheck' => $HydrostaticTestingDateCheck,
                'AccessClearnaceExtinguisherCheck' => $AccessClearnaceExtinguisherCheck,
                'VisibleInstructionLabelCheck' => $VisibleInstructionLabelCheck,
                'ExtinguisherCleaning' => $ExtinguisherCleaning,
                'CO2WeightCheck' => $CO2WeightCheck,
                'PressureCheck' => $PressureCheck
            ];
        $whereCondition = [
            'ServiceReportID' => $ServiceReportID
        ];

        $core->_UpdateTableRecords_prepare($conn, 'ppm_fire_extinguisher_service_report', $rowData, $whereCondition);

    } 
    else 
    {
        // Insert into corporate_ticket_general_service_report
        $rowData = [
                'TicketID' => $ServiceReportTicketID,
                'ProblemReportedByClient' => $ProblemReportedByClient,
                'Observation' => $Observation,
                'ActionTaken' => $ActionTaken,
                'Remarks' => $Remarks,
                'ClientRepresentative' => $ClientRepresentative,
                'ClientRepresentativeContact' => $ClientRepresentativeContact,
                'ClientRepresentativeEmails' => $ClientRepresentativeEmails,
                'ClientRepresentativeDesignation' => $ClientRepresentativeDesignation,
                'ClientSignature' => $ClientSignature,
                'Latitude' => $Latitude,
                'Longitude' => $Longitude,
                'CreatedDate' => $CreatedDate,
                'CreatedTime' => $CreatedTime,
                'CreatedBy' => $CreatedBy
            ];
        $response = $core->_InsertTableRecords_prepare($conn, 'corporate_ticket_general_service_report', $rowData);
        if($response['error'] == false) 
        {
            $response['ServiceReportID'] = $response['last_insert_id'];
            $ServiceReportID = $response['ServiceReportID'];
            // Insert into hvac_general_service_report

            $rowData = [
                'ServiceReportID' => $ServiceReportID,
                'TicketID' => $ServiceReportTicketID,
                'AssetCondition' => $AssetCondition,
                'TypeCylinderCheck' => $TypeCylinderCheck,
                'CylinderCapacityCheck' => $CylinderCapacityCheck,
                'GripCheck' => $GripCheck,
                'SafetyPinPositionCheck' => $SafetyPinPositionCheck,
                'SafetySealAvailableCheck' => $SafetySealAvailableCheck,
                'RefilledDateCheck' => $RefilledDateCheck,
                'DueDateCheck' => $DueDateCheck,
                'HydrostaticTestingDateCheck' => $HydrostaticTestingDateCheck,
                'AccessClearnaceExtinguisherCheck' => $AccessClearnaceExtinguisherCheck,
                'VisibleInstructionLabelCheck' => $VisibleInstructionLabelCheck,
                'ExtinguisherCleaning' => $ExtinguisherCleaning,
                'CO2WeightCheck' => $CO2WeightCheck,
                'PressureCheck' => $PressureCheck,
                'CreatedDate' => $CreatedDate,
                'CreatedTime' => $CreatedTime,
                'CreatedBy' => $CreatedBy
            ];
            $response = $core->_InsertTableRecords_prepare($conn, 'ppm_fire_extinguisher_service_report', $rowData);

        }
    }
} else {
    $response["error"] = true;
    $response["message"] = "Missing User Fields";
}

echo json_encode($response);
?>
