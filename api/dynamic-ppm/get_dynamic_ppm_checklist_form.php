<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/dynamic-ppm/controller/dynamic_ppm_controller.php');
require_once('dynamic_ppm_helpers.php');

setTimeZone();
$conn = _connectodb();
$data = dynamic_ppm_parse_input();

$ticketID = dynamic_ppm_int($data, array('TicketID', 'ticket_id', 'ServiceReportTicketID'), 0);
if ($ticketID <= 0) {
    dynamic_ppm_response(true, 'TicketID is required.');
}

$flow = getDynamicPPMTicketFlowInfo($conn, $ticketID);
if (!empty($flow['error'])) {
    dynamic_ppm_response(true, $flow['message']);
}

if (empty($flow['use_dynamic_ppm'])) {
    dynamic_ppm_response(false, $flow['message'], array_merge($flow, array(
        'items' => array(),
        'checklist' => null,
        'service_report_id' => -1,
        'common_fields' => array('AssetCondition' => ''),
        'general_details' => array(),
    )));
}

$checklist = getDynamicPPMChecklistForTicket($conn, $ticketID);
$checklistID = (int) $checklist['ID'];
$items = getDynamicPPMChecklistItems($conn, $checklistID);

$existingReport = getDynamicPPMServiceReportByTicket($conn, $ticketID);
$generalReport = getPPMGeneralServiceReportByTicket($conn, $ticketID);
$existingItemsMap = array();
$generalDetails = getDynamicPPMGeneralDetailsForTicket($conn, $ticketID);
$assetCondition = '';
$serviceReportID = -1;
$generalServiceReportID = $generalReport ? (int) $generalReport['ID'] : -1;
if ($existingReport) {
    $serviceReportID = (int) $existingReport['ID'];
    $assetCondition = (string) $existingReport['AssetCondition'];
    $existingItems = getDynamicPPMServiceReportItems($conn, $serviceReportID);
    foreach ($existingItems as $row) {
        $existingItemsMap[(int) $row['ChecklistItemID']] = $row;
    }
}

$formItems = array();
foreach ($items as $item) {
    $itemID = (int) $item['ID'];
    $saved = isset($existingItemsMap[$itemID]) ? $existingItemsMap[$itemID] : null;
    $options = array();
    if (isset($item['OptionsJson']) && trim((string) $item['OptionsJson']) !== '') {
        $decoded = json_decode($item['OptionsJson'], true);
        if (is_array($decoded)) {
            $options = $decoded;
        }
    }

    $formItems[] = array(
        'checklist_item_id' => $itemID,
        'item_code' => $item['ItemCode'],
        'item_name' => $item['ItemName'],
        'input_type' => $item['InputType'],
        'unit_name' => $item['UnitName'],
        'default_value' => isset($item['DefaultValue']) ? $item['DefaultValue'] : '',
        'is_mandatory' => (int) $item['IsMandatory'],
        'sort_order' => (int) $item['SortOrder'],
        'help_text' => $item['HelpText'],
        'options' => $options,
        'response' => array(
            'value' => $saved ? $saved['ResponseValue'] : (isset($item['DefaultValue']) ? $item['DefaultValue'] : ''),
            'status' => $saved ? $saved['ResponseStatus'] : '',
            'remarks' => $saved ? $saved['Remarks'] : ''
        )
    );
}

dynamic_ppm_response(false, 'Dynamic PPM checklist loaded.', array_merge($flow, array(
    'checklist' => array(
        'checklist_id' => $checklistID,
        'checklist_code' => $checklist['ChecklistCode'],
        'checklist_name' => $checklist['ChecklistName'],
        'version_no' => (int) $checklist['VersionNo']
    ),
    'service_report_id' => $serviceReportID,
    'general_service_report_id' => $generalServiceReportID,
    'common_fields' => array(
        'AssetCondition' => $assetCondition
    ),
    'general_details' => $generalDetails,
    'items' => $formItems
)));

?>
