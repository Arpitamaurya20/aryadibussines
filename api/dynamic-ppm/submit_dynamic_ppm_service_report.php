<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/dynamic-ppm/controller/dynamic_ppm_controller.php');
require_once('dynamic_ppm_helpers.php');

register_shutdown_function(function () {
    if (!empty($GLOBALS['_dynamic_ppm_api_responded'])) {
        return;
    }
    $lastError = error_get_last();
    if (!$lastError || !in_array($lastError['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        return;
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode(array(
        'error' => true,
        'message' => 'Server error during dynamic PPM submit.',
        'debug' => $lastError['message'] . ' in ' . basename($lastError['file']) . ':' . $lastError['line'],
    ));
});

setTimeZone();
$conn = _connectodb();
$data = dynamic_ppm_parse_input();

$ticketID = dynamic_ppm_int($data, array('TicketID', 'ticket_id', 'ServiceReportTicketID'), 0);
$createdBy = isset($data['CreatedBy']) ? trim((string) $data['CreatedBy']) : '';
if ($ticketID <= 0 || $createdBy === '') {
    dynamic_ppm_response(true, 'TicketID and CreatedBy are required.');
}

$checklistItems = array();
if (isset($data['ChecklistItems']) && is_array($data['ChecklistItems'])) {
    foreach ($data['ChecklistItems'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $checklistItems[] = array(
            'ChecklistItemID' => isset($row['ChecklistItemID']) ? (int) $row['ChecklistItemID'] : (isset($row['checklist_item_id']) ? (int) $row['checklist_item_id'] : 0),
            'ResponseValue' => isset($row['ResponseValue']) ? $row['ResponseValue'] : (isset($row['value']) ? $row['value'] : ''),
            'ResponseStatus' => isset($row['ResponseStatus']) ? $row['ResponseStatus'] : (isset($row['status']) ? $row['status'] : ''),
            'Remarks' => isset($row['Remarks']) ? $row['Remarks'] : (isset($row['remarks']) ? $row['remarks'] : ''),
            'ResponseJson' => isset($row['ResponseJson']) && is_array($row['ResponseJson']) ? $row['ResponseJson'] : array()
        );
    }
}

$generalDetails = array();
if (isset($data['GeneralDetails']) && is_array($data['GeneralDetails'])) {
    $generalDetails = $data['GeneralDetails'];
} else {
    $possibleKeys = array(
        'ProblemReportedByClient', 'Observation', 'ActionTaken', 'Remarks',
        'ClientRepresentative', 'ClientRepresentativeContact', 'ClientRepresentativeEmails',
        'ClientRepresentativeDesignation', 'EquipmentDetails', 'SerialNo', 'Capacity',
        'RefrigerantType', 'MakeModel', 'Latitude', 'Longitude'
    );
    foreach ($possibleKeys as $key) {
        if (isset($data[$key])) {
            $generalDetails[$key] = $data[$key];
        }
    }
}

$payload = array(
    'TicketID' => $ticketID,
    'CreatedBy' => $createdBy,
    'AssetCondition' => isset($data['AssetCondition']) ? $data['AssetCondition'] : '',
    'GeneralDetails' => $generalDetails,
    'ChecklistItems' => $checklistItems
);

$saveResponse = saveDynamicPPMServiceReport($conn, $payload);
if (isset($saveResponse['error']) && $saveResponse['error']) {
    dynamic_ppm_response(true, isset($saveResponse['message']) ? $saveResponse['message'] : 'Unable to save dynamic report.');
}

$flow = getDynamicPPMTicketFlowInfo($conn, $ticketID);
if (empty($flow['use_dynamic_ppm'])) {
    dynamic_ppm_response(true, 'This ticket uses legacy PPM flow. Submit via legacy category API.');
}

$autoGeneratePdf = true;
if (isset($data['AutoGeneratePdf'])) {
    $autoGeneratePdf = (int) $data['AutoGeneratePdf'] === 1;
}
if (isset($data['AutoDownloadPdf'])) {
    $autoGeneratePdf = (int) $data['AutoDownloadPdf'] === 1;
}

$responseData = array(
    'ticket_id' => $ticketID,
    'service_report_id' => isset($saveResponse['ServiceReportID']) ? (int) $saveResponse['ServiceReportID'] : -1,
    'general_service_report_id' => isset($saveResponse['GeneralServiceReportID']) ? (int) $saveResponse['GeneralServiceReportID'] : -1,
    'checklist_id' => isset($saveResponse['ChecklistID']) ? (int) $saveResponse['ChecklistID'] : -1,
    'pdf_generated' => 0,
    'pdfname' => '',
    'pdf_url' => '',
    'download_api' => dynamicPPMBuildReportDownloadApiUrl($ticketID),
);

if ($autoGeneratePdf) {
    try {
        $pdfResult = dynamicPPMGenerateReportPdf($conn, $ticketID, 'Download');
        if (empty($pdfResult['error'])) {
            $responseData['pdf_generated'] = 1;
            $responseData['pdfname'] = $pdfResult['pdfname'];
            $responseData['pdf_url'] = $pdfResult['pdf_url'];
            $responseData['download_api'] = $pdfResult['download_api'];
            $responseData['download_action'] = $pdfResult['download_action'];
        } else {
            $responseData['pdf_error'] = $pdfResult['message'];
        }
    } catch (Exception $e) {
        $responseData['pdf_error'] = 'PDF generation failed: ' . $e->getMessage();
    }
}

$GLOBALS['_dynamic_ppm_api_responded'] = true;
dynamic_ppm_response(false, 'Dynamic PPM report saved.', $responseData);

?>
