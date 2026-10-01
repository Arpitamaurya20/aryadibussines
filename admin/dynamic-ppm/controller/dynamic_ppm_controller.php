<?php

function dynamicPPMClean($value)
{
    if (function_exists('cleantext')) {
        return cleantext($value);
    }
    return trim((string) $value);
}

function dynamicPPMGetSessionUser()
{
    if (!empty($_SESSION['pb_username'])) {
        return dynamicPPMClean($_SESSION['pb_username']);
    }
    if (!empty($_SESSION['Name'])) {
        return dynamicPPMClean($_SESSION['Name']);
    }
    return 'System';
}

function getDynamicPPMChecklistById($conn, $checklistID)
{
    $checklistID = (int) $checklistID;
    if ($checklistID <= 0) {
        return null;
    }
    return _getTableDetails($conn, 'ppm_dynamic_checklist_master', "WHERE ID = $checklistID AND IsActive = 1");
}

function getDynamicPPMChecklistMasterById($conn, $checklistID)
{
    $checklistID = (int) $checklistID;
    if ($checklistID <= 0) {
        return null;
    }
    return _getTableDetails($conn, 'ppm_dynamic_checklist_master', "WHERE ID = $checklistID");
}

function getDynamicPPMChecklistItemsForReport($conn, $checklistID)
{
    $checklistID = (int) $checklistID;
    if ($checklistID <= 0) {
        return array();
    }
    return _getTableRecords($conn, 'ppm_dynamic_checklist_items', "WHERE ChecklistID = $checklistID ORDER BY SortOrder ASC, ID ASC");
}

function getDynamicPPMChecklistItems($conn, $checklistID)
{
    $checklistID = (int) $checklistID;
    if ($checklistID <= 0) {
        return array();
    }
    return _getTableRecords($conn, 'ppm_dynamic_checklist_items', "WHERE ChecklistID = $checklistID AND IsActive = 1 ORDER BY SortOrder ASC, ID ASC");
}

function getDynamicPPMChecklistForCompanyCategory($conn, $corporateID, $categoryID)
{
    $corporateID = (int) $corporateID;
    $categoryID = (int) $categoryID;
    if ($corporateID <= 0 || $categoryID <= 0) {
        return null;
    }

    $today = date('Y-m-d');
    $sql = "SELECT m.* 
            FROM ppm_dynamic_company_checklist_map cm
            INNER JOIN ppm_dynamic_checklist_master m ON cm.ChecklistID = m.ID
            WHERE cm.CorporateID = $corporateID
              AND cm.CategoryID = $categoryID
              AND cm.IsActive = 1
              AND m.IsActive = 1
              AND (cm.EffectiveFrom IS NULL OR cm.EffectiveFrom <= '$today')
              AND (cm.EffectiveTo IS NULL OR cm.EffectiveTo >= '$today')
            ORDER BY cm.ID DESC
            LIMIT 1";

    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

function getDynamicPPMChecklistForTicket($conn, $ticketID)
{
    $ticketID = (int) $ticketID;
    if ($ticketID <= 0) {
        return null;
    }

    $ticket = _getTableDetails($conn, 'ppm_tickets', "WHERE ID = $ticketID AND IsActive = 1");
    if (!$ticket) {
        return null;
    }

    $branchAssetID = (int) $ticket['BranchAssetID'];
    $branchAsset = _getTableDetails($conn, 'branch_assets', "WHERE ID = $branchAssetID");
    if (!$branchAsset) {
        return null;
    }

    $categoryID = (int) $branchAsset['Category'];
    $corporateID = (int) $ticket['CorporateID'];
    return getDynamicPPMChecklistForCompanyCategory($conn, $corporateID, $categoryID);
}

function ensureDynamicPPMAssetChecklistMapTable($conn)
{
    static $ready = false;
    if ($ready) {
        return true;
    }
    $sql = "CREATE TABLE IF NOT EXISTS `ppm_dynamic_asset_checklist_map` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `BranchAssetID` int(11) NOT NULL,
        `CategoryID` int(11) NOT NULL DEFAULT 0,
        `ChecklistID` int(11) NOT NULL,
        `CreatedBy` varchar(100) DEFAULT NULL,
        `CreatedDate` date DEFAULT NULL,
        `CreatedTime` time DEFAULT NULL,
        `UpdatedBy` varchar(100) DEFAULT NULL,
        `UpdatedDate` date DEFAULT NULL,
        `UpdatedTime` time DEFAULT NULL,
        `IsActive` tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`ID`),
        KEY `idx_pdacm_asset` (`BranchAssetID`, `IsActive`),
        KEY `idx_pdacm_checklist` (`ChecklistID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql);
    $ready = true;
    return true;
}

function getDynamicPPMChecklistsByCategory($conn, $categoryID)
{
    $categoryID = (int) $categoryID;
    if ($categoryID <= 0) {
        return array();
    }
    $sql = "SELECT ID, ChecklistCode, ChecklistName, VersionNo
            FROM ppm_dynamic_checklist_master
            WHERE CategoryID = $categoryID AND IsActive = 1
            ORDER BY ChecklistName ASC, ID DESC";
    $result = mysqli_query($conn, $sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function getDynamicPPMAssetChecklistMapping($conn, $branchAssetID)
{
    ensureDynamicPPMAssetChecklistMapTable($conn);
    $branchAssetID = (int) $branchAssetID;
    if ($branchAssetID <= 0) {
        return null;
    }
    return _getTableDetails($conn, 'ppm_dynamic_asset_checklist_map', "WHERE BranchAssetID = $branchAssetID AND IsActive = 1 ORDER BY ID DESC");
}

function getDynamicPPMChecklistForAsset($conn, $branchAssetID)
{
    $map = getDynamicPPMAssetChecklistMapping($conn, $branchAssetID);
    if (!$map) {
        return null;
    }
    return getDynamicPPMChecklistById($conn, (int) $map['ChecklistID']);
}

function getDynamicPPMChecklistForTicketByAsset($conn, $ticketID)
{
    $ticketID = (int) $ticketID;
    if ($ticketID <= 0) {
        return null;
    }
    $ticket = _getTableDetails($conn, 'ppm_tickets', "WHERE ID = $ticketID AND IsActive = 1");
    if (!$ticket) {
        return null;
    }
    $branchAssetID = (int) $ticket['BranchAssetID'];
    if ($branchAssetID <= 0) {
        return null;
    }
    return getDynamicPPMChecklistForAsset($conn, $branchAssetID);
}

function getDynamicPPMMappedChecklistForTicket($conn, $ticketID)
{
    $checklist = getDynamicPPMChecklistForTicketByAsset($conn, $ticketID);
    if ($checklist) {
        $checklist['_mapping_mode'] = 'asset';
        return $checklist;
    }
    $checklist = getDynamicPPMChecklistForTicket($conn, $ticketID);
    if ($checklist) {
        $checklist['_mapping_mode'] = 'company';
        return $checklist;
    }
    return null;
}

function dynamicPPMFormatChecklistResponseValue($item)
{
    $value = trim((string) (isset($item['response_value']) ? $item['response_value'] : ''));
    $type = strtolower(trim((string) (isset($item['input_type']) ? $item['input_type'] : '')));
    if ($value === '') {
        return '';
    }
    if ($type === 'boolean' || $type === 'checkbox') {
        $lower = strtolower($value);
        if (in_array($lower, array('1', 'true', 'yes', 'ok', 'pass', 'y'), true)) {
            $value = 'Yes';
        } elseif (in_array($lower, array('0', 'false', 'no', 'fail', 'n'), true)) {
            $value = 'No';
        }
    }
    $unit = trim((string) (isset($item['unit_name']) ? $item['unit_name'] : ''));
    if ($unit !== '') {
        return $value . ' ' . $unit;
    }
    return $value;
}

function mapDynamicPPMAssetChecklist($conn, $branchAssetID, $categoryID, $checklistID, $createdBy = '')
{
    ensureDynamicPPMAssetChecklistMapTable($conn);
    $branchAssetID = (int) $branchAssetID;
    $categoryID = (int) $categoryID;
    $checklistID = (int) $checklistID;
    $createdBy = dynamicPPMClean($createdBy);
    $response = array('error' => false, 'changed' => false, 'message' => 'Asset checklist mapping unchanged.');

    if ($branchAssetID <= 0) {
        return array('error' => true, 'changed' => false, 'message' => 'Asset ID is required for checklist mapping.');
    }

    $existing = getDynamicPPMAssetChecklistMapping($conn, $branchAssetID);
    $existingChecklistID = $existing ? (int) $existing['ChecklistID'] : 0;

    if ($checklistID <= 0) {
        if ($existingChecklistID > 0) {
            _UpdateTableRecords(
                $conn,
                'ppm_dynamic_asset_checklist_map',
                "IsActive = 0 WHERE BranchAssetID = $branchAssetID AND IsActive = 1"
            );
            $response['changed'] = true;
            $response['message'] = 'Asset checklist mapping removed.';
        }
        return $response;
    }

    $checklist = getDynamicPPMChecklistById($conn, $checklistID);
    if (!$checklist) {
        return array('error' => true, 'changed' => false, 'message' => 'Selected checklist was not found.');
    }

    if ($existingChecklistID === $checklistID) {
        return $response;
    }

    _UpdateTableRecords(
        $conn,
        'ppm_dynamic_asset_checklist_map',
        "IsActive = 0 WHERE BranchAssetID = $branchAssetID AND IsActive = 1"
    );

    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');
    $createdByEsc = mysqli_real_escape_string($conn, $createdBy);
    $sql = "INSERT INTO ppm_dynamic_asset_checklist_map
        (BranchAssetID, CategoryID, ChecklistID, CreatedBy, CreatedDate, CreatedTime, IsActive)
        VALUES
        ($branchAssetID, $categoryID, $checklistID, '$createdByEsc', '$createdDate', '$createdTime', 1)";
    $insert = _InsertTableRecords($conn, $sql);
    if (isset($insert['error']) && $insert['error']) {
        return $insert;
    }
    $insert['changed'] = true;
    $insert['message'] = 'Asset checklist mapping saved.';
    return $insert;
}

function getDynamicPPMTicketFlowInfoByAsset($conn, $ticketID)
{
    $context = getDynamicPPMTicketContext($conn, $ticketID);
    if (!$context['ticket']) {
        return array(
            'error' => true,
            'message' => 'PPM ticket not found.',
        );
    }

    $branchAssetID = isset($context['branch_asset']['ID']) ? (int) $context['branch_asset']['ID'] : 0;
    $checklist = $branchAssetID > 0 ? getDynamicPPMChecklistForAsset($conn, $branchAssetID) : null;
    $useDynamic = $checklist ? 1 : 0;
    $legacyConfig = getLegacyPPMFlowConfig($context['category_name'], $context['category_id']);

    $response = array(
        'error' => false,
        'message' => $useDynamic
            ? 'Dynamic PPM checklist is mapped for this asset.'
            : 'No asset checklist mapped. Continue with legacy PPM flow or company mapping API.',
        'mapping_mode' => 'asset',
        'ticket_id' => (int) $ticketID,
        'ticket_number' => $context['ticket']['TicketID'],
        'branch_asset_id' => $branchAssetID,
        'corporate_id' => $context['corporate_id'],
        'category_id' => $context['category_id'],
        'category_name' => $context['category_name'],
        'use_dynamic_ppm' => $useDynamic,
        'use_legacy_ppm' => $useDynamic ? 0 : 1,
        'dynamic_checklist' => null,
        'legacy_flow' => $useDynamic ? null : $legacyConfig,
        'api_instructions' => $useDynamic
            ? 'Call api/dynamic-ppm/get_dynamic_ppm_asset_checklist_form.php and submit via api/dynamic-ppm/submit_dynamic_ppm_asset_service_report.php'
            : 'No asset mapping found. Use company APIs (get_dynamic_ppm_checklist_form.php) or legacy category APIs.',
        'pdf_generator_hint' => $useDynamic
            ? 'admin/dynamic-ppm/action/generate_dynamic_ppm_service_report_pdf.php'
            : (isset($legacyConfig['pdf_generator_hint']) ? $legacyConfig['pdf_generator_hint'] : ''),
    );

    if ($checklist) {
        $response['dynamic_checklist'] = array(
            'checklist_id' => (int) $checklist['ID'],
            'checklist_code' => $checklist['ChecklistCode'],
            'checklist_name' => $checklist['ChecklistName'],
            'version_no' => (int) $checklist['VersionNo'],
        );
    }

    return $response;
}

function buildDynamicPPMChecklistFormPayload($conn, $ticketID, $mappingMode = 'company')
{
    $mappingMode = ($mappingMode === 'asset') ? 'asset' : 'company';
    $flow = ($mappingMode === 'asset')
        ? getDynamicPPMTicketFlowInfoByAsset($conn, $ticketID)
        : getDynamicPPMTicketFlowInfo($conn, $ticketID);

    if (!empty($flow['error'])) {
        return $flow;
    }

    if (empty($flow['use_dynamic_ppm'])) {
        return array_merge($flow, array(
            'items' => array(),
            'checklist' => null,
            'service_report_id' => -1,
            'common_fields' => array('AssetCondition' => ''),
            'general_details' => array(),
        ));
    }

    $checklist = ($mappingMode === 'asset')
        ? getDynamicPPMChecklistForTicketByAsset($conn, $ticketID)
        : getDynamicPPMChecklistForTicket($conn, $ticketID);

    if (!$checklist) {
        return array_merge($flow, array(
            'items' => array(),
            'checklist' => null,
            'service_report_id' => -1,
            'common_fields' => array('AssetCondition' => ''),
            'general_details' => array(),
        ));
    }

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

    return array_merge($flow, array(
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
    ));
}

function getDynamicPPMTicketContext($conn, $ticketID)
{
    $ticketID = (int) $ticketID;
    $context = array(
        'ticket_id' => $ticketID,
        'ticket' => null,
        'branch_asset' => null,
        'category_id' => 0,
        'category_name' => '',
        'corporate_id' => 0,
    );

    if ($ticketID <= 0) {
        return $context;
    }

    $ticket = _getTableDetails($conn, 'ppm_tickets', "WHERE ID = $ticketID AND IsActive = 1");
    if (!$ticket) {
        return $context;
    }

    $context['ticket'] = $ticket;
    $context['corporate_id'] = (int) $ticket['CorporateID'];

    $branchAssetID = (int) $ticket['BranchAssetID'];
    $branchAsset = _getTableDetails($conn, 'branch_assets', "WHERE ID = $branchAssetID");
    if ($branchAsset) {
        $context['branch_asset'] = $branchAsset;
        $context['category_id'] = (int) $branchAsset['Category'];
        $category = getDynamicPPMCategoryById($conn, $context['category_id']);
        if ($category && isset($category['CategoriesName'])) {
            $context['category_name'] = $category['CategoriesName'];
        }
    }

    return $context;
}

function getLegacyPPMFlowConfig($categoryName, $categoryID = 0)
{
    $name = strtoupper(trim((string) $categoryName));
    $config = array(
        'flow_type' => 'legacy',
        'category_id' => (int) $categoryID,
        'category_name' => $categoryName,
        'get_service_report_api' => 'get_general_service_report_details.php',
        'submit_service_report_api' => 'ppm_tickets/post_ep_service_report.php',
        'legacy_checklist_key' => 'general',
        'pdf_generator_hint' => 'generate_ppm_service_report_pdf.php',
    );

    if (strpos($name, 'HVAC') !== false || (int) $categoryID === 34) {
        $config['submit_service_report_api'] = 'ppm_tickets/post_hvac_service_report.php';
        $config['get_checklist_api'] = 'get_hvac_ticket_details.php';
        $config['legacy_checklist_key'] = 'hvac';
        $config['pdf_generator_hint'] = 'generate_ppm_hvac_service_report_pdf.php';
    } elseif (strpos($name, 'CCTV') !== false) {
        $config['submit_service_report_api'] = 'ppm_tickets/post_cctv_service_report.php';
        $config['legacy_checklist_key'] = 'cctv';
    } elseif (strpos($name, 'FAS') !== false || strpos($name, 'FIRE') !== false) {
        $config['submit_service_report_api'] = 'ppm_tickets/post_fas_service_report.php';
        $config['legacy_checklist_key'] = 'fas';
        $config['pdf_generator_hint'] = 'generate_ppm_fas_service_report_pdf.php';
    } elseif (strpos($name, 'UPS') !== false) {
        $config['submit_service_report_api'] = 'ppm_tickets/post_ups_service_report.php';
        $config['legacy_checklist_key'] = 'ups';
        $config['pdf_generator_hint'] = 'generate_ppm_ups_service_report_pdf.php';
    } elseif (strpos($name, 'ELECTRICAL') !== false || strpos($name, 'PANEL') !== false || strpos($name, 'EP') !== false) {
        $config['submit_service_report_api'] = 'ppm_tickets/post_ep_service_report.php';
        $config['legacy_checklist_key'] = 'ep';
        $config['pdf_generator_hint'] = 'generate_ppm_ep_service_report_pdf.php';
    }

    return $config;
}

function getDynamicPPMTicketFlowInfo($conn, $ticketID)
{
    $context = getDynamicPPMTicketContext($conn, $ticketID);
    if (!$context['ticket']) {
        return array(
            'error' => true,
            'message' => 'PPM ticket not found.',
        );
    }

    $checklist = null;
    if ($context['corporate_id'] > 0 && $context['category_id'] > 0) {
        $checklist = getDynamicPPMChecklistForCompanyCategory($conn, $context['corporate_id'], $context['category_id']);
    }

    $useDynamic = $checklist ? 1 : 0;
    $legacyConfig = getLegacyPPMFlowConfig($context['category_name'], $context['category_id']);

    $response = array(
        'error' => false,
        'message' => $useDynamic
            ? 'Dynamic PPM checklist is mapped for this company/category.'
            : 'No dynamic checklist mapped. Continue with legacy PPM flow.',
        'ticket_id' => (int) $ticketID,
        'ticket_number' => $context['ticket']['TicketID'],
        'corporate_id' => $context['corporate_id'],
        'category_id' => $context['category_id'],
        'category_name' => $context['category_name'],
        'use_dynamic_ppm' => $useDynamic,
        'use_legacy_ppm' => $useDynamic ? 0 : 1,
        'dynamic_checklist' => null,
        'legacy_flow' => $useDynamic ? null : $legacyConfig,
        'api_instructions' => $useDynamic
            ? 'Call api/dynamic-ppm/get_dynamic_ppm_checklist_form.php and submit via api/dynamic-ppm/submit_dynamic_ppm_service_report.php'
            : 'Use existing legacy APIs based on category (for example post_hvac_service_report.php / post_ep_service_report.php).',
        'pdf_generator_hint' => $useDynamic
            ? 'admin/dynamic-ppm/action/generate_dynamic_ppm_service_report_pdf.php'
            : (isset($legacyConfig['pdf_generator_hint']) ? $legacyConfig['pdf_generator_hint'] : ''),
    );

    if ($checklist) {
        $response['dynamic_checklist'] = array(
            'checklist_id' => (int) $checklist['ID'],
            'checklist_code' => $checklist['ChecklistCode'],
            'checklist_name' => $checklist['ChecklistName'],
            'version_no' => (int) $checklist['VersionNo'],
        );
    }

    return $response;
}

function dynamicPPMGeneralReportFieldKeys()
{
    return array(
        'ProblemReportedByClient',
        'Observation',
        'ActionTaken',
        'Remarks',
        'ClientRepresentative',
        'ClientRepresentativeContact',
        'ClientRepresentativeEmails',
        'ClientRepresentativeDesignation',
        'EquipmentDetails',
        'SerialNo',
        'Capacity',
        'RefrigerantType',
        'MakeModel',
        'Latitude',
        'Longitude',
    );
}

function getPPMGeneralServiceReportByTicket($conn, $ticketID)
{
    $ticketID = (int) $ticketID;
    if ($ticketID <= 0) {
        return null;
    }
    return _getTableDetails($conn, 'ppm_ticket_general_service_report', "WHERE TicketID = $ticketID");
}

function dynamicPPMGetDefaultGeneralDetails($conn, $ticketID)
{
    $defaults = array();
    foreach (dynamicPPMGeneralReportFieldKeys() as $key) {
        $defaults[$key] = '';
    }

    $context = getDynamicPPMTicketContext($conn, $ticketID);
    $ticket = $context['ticket'];
    if (!$ticket) {
        return $defaults;
    }

    $defaults['ProblemReportedByClient'] = isset($ticket['Message']) ? (string) $ticket['Message'] : '';

    $branchID = (int) $ticket['BranchID'];
    if ($branchID > 0) {
        $branch = _getTableDetails($conn, 'branch', "WHERE ID = $branchID");
        if ($branch) {
            $defaults['ClientRepresentative'] = isset($branch['SiteIncharge']) ? (string) $branch['SiteIncharge'] : '';
            $defaults['ClientRepresentativeContact'] = isset($branch['BranchMobile']) ? (string) $branch['BranchMobile'] : '';
        }
    }

    $branchAsset = $context['branch_asset'];
    if ($branchAsset) {
        $defaults['EquipmentDetails'] = isset($branchAsset['EquipmentName']) ? (string) $branchAsset['EquipmentName'] : '';
        $defaults['SerialNo'] = isset($branchAsset['SNo']) ? (string) $branchAsset['SNo'] : '';
        $make = isset($branchAsset['Make']) ? trim((string) $branchAsset['Make']) : '';
        $model = isset($branchAsset['Model']) ? trim((string) $branchAsset['Model']) : '';
        $defaults['MakeModel'] = trim($make . ' ' . $model);
        if (isset($branchAsset['Capacity'])) {
            $defaults['Capacity'] = (string) $branchAsset['Capacity'];
        }
    }

    return $defaults;
}

function getDynamicPPMGeneralDetailsForTicket($conn, $ticketID)
{
    $details = dynamicPPMGetDefaultGeneralDetails($conn, $ticketID);
    $generalReport = getPPMGeneralServiceReportByTicket($conn, $ticketID);
    if ($generalReport) {
        foreach (dynamicPPMGeneralReportFieldKeys() as $key) {
            if (isset($generalReport[$key]) && trim((string) $generalReport[$key]) !== '') {
                $details[$key] = $generalReport[$key];
            }
        }
        if (isset($generalReport['ClientSignature'])) {
            $details['ClientSignature'] = $generalReport['ClientSignature'];
        }
    }

    $dynamicReport = getDynamicPPMServiceReportByTicket($conn, $ticketID);
    if ($dynamicReport && !empty($dynamicReport['GeneralDetailsJson'])) {
        $jsonDetails = json_decode((string) $dynamicReport['GeneralDetailsJson'], true);
        if (is_array($jsonDetails)) {
            foreach ($jsonDetails as $key => $value) {
                if ($value !== null && trim((string) $value) !== '') {
                    $details[$key] = $value;
                }
            }
        }
    }

    return $details;
}

function dynamicPPMResolveClientSignature($conn, $ticketID, $consumeTemp = true)
{
    $ticketID = (int) $ticketID;
    if ($ticketID <= 0) {
        return '';
    }

    $filter = "WHERE TicketID = $ticketID AND Type = 'PPM' AND IsActive = 1";
    $signatureDetails = _getTableDetails($conn, 'temp_client_signature', $filter);
    if (!$signatureDetails || empty($signatureDetails['ClientSignature'])) {
        $filter = "WHERE TicketID = $ticketID AND IsActive = 1";
        $signatureDetails = _getTableDetails($conn, 'temp_client_signature', $filter);
    }
    if (!$signatureDetails || empty($signatureDetails['ClientSignature'])) {
        return '';
    }

    $clientSignature = (string) $signatureDetails['ClientSignature'];
    if ($consumeTemp) {
        _UpdateTableRecords($conn, 'temp_client_signature', "IsActive = 0 WHERE TicketID = $ticketID AND Type = 'PPM'");
    }
    return $clientSignature;
}

function dynamicPPMSyncGeneralServiceReport($conn, $ticketID, $generalDetails, $createdBy)
{
    $ticketID = (int) $ticketID;
    $response = array('error' => true, 'message' => 'Unable to sync general service report.', 'GeneralServiceReportID' => -1);
    if ($ticketID <= 0) {
        $response['message'] = 'Invalid ticket for general service report sync.';
        return $response;
    }

    if (!is_array($generalDetails)) {
        $generalDetails = array();
    }

    $mergedDetails = dynamicPPMGetDefaultGeneralDetails($conn, $ticketID);
    $existingReport = getPPMGeneralServiceReportByTicket($conn, $ticketID);
    if ($existingReport) {
        foreach (dynamicPPMGeneralReportFieldKeys() as $key) {
            if (isset($existingReport[$key]) && trim((string) $existingReport[$key]) !== '') {
                $mergedDetails[$key] = $existingReport[$key];
            }
        }
    }

    foreach ($generalDetails as $key => $value) {
        if ($value !== null) {
            $mergedDetails[$key] = $value;
        }
    }

    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');
    $createdByEsc = mysqli_real_escape_string($conn, dynamicPPMClean($createdBy));
    $clientSignature = dynamicPPMResolveClientSignature($conn, $ticketID, true);

    $fieldValues = array();
    foreach (dynamicPPMGeneralReportFieldKeys() as $key) {
        $fieldValues[$key] = mysqli_real_escape_string($conn, dynamicPPMClean(isset($mergedDetails[$key]) ? $mergedDetails[$key] : ''));
    }

    if ($existingReport) {
        $generalServiceReportID = (int) $existingReport['ID'];
        $setParts = array(
            "ProblemReportedByClient = '{$fieldValues['ProblemReportedByClient']}'",
            "Observation = '{$fieldValues['Observation']}'",
            "ActionTaken = '{$fieldValues['ActionTaken']}'",
            "Remarks = '{$fieldValues['Remarks']}'",
            "ClientRepresentative = '{$fieldValues['ClientRepresentative']}'",
            "ClientRepresentativeContact = '{$fieldValues['ClientRepresentativeContact']}'",
            "ClientRepresentativeEmails = '{$fieldValues['ClientRepresentativeEmails']}'",
            "ClientRepresentativeDesignation = '{$fieldValues['ClientRepresentativeDesignation']}'",
            "EquipmentDetails = '{$fieldValues['EquipmentDetails']}'",
            "SerialNo = '{$fieldValues['SerialNo']}'",
            "Capacity = '{$fieldValues['Capacity']}'",
            "RefrigerantType = '{$fieldValues['RefrigerantType']}'",
            "MakeModel = '{$fieldValues['MakeModel']}'",
            "Latitude = '{$fieldValues['Latitude']}'",
            "Longitude = '{$fieldValues['Longitude']}'",
        );
        if ($clientSignature !== '') {
            $clientSignatureEsc = mysqli_real_escape_string($conn, $clientSignature);
            $setParts[] = "ClientSignature = '$clientSignatureEsc'";
        }
        $updateSql = implode(', ', $setParts) . " WHERE ID = $generalServiceReportID";
        $updateResponse = _UpdateTableRecords($conn, 'ppm_ticket_general_service_report', $updateSql);
        if (isset($updateResponse['error']) && $updateResponse['error']) {
            return $updateResponse;
        }
    } else {
        if ($clientSignature === '') {
            $clientSignature = '';
        }
        $clientSignatureEsc = mysqli_real_escape_string($conn, $clientSignature);
        $insertSql = "INSERT INTO ppm_ticket_general_service_report (
                TicketID, ProblemReportedByClient, Observation, ActionTaken, Remarks,
                ClientRepresentative, ClientRepresentativeContact, ClientRepresentativeEmails,
                ClientRepresentativeDesignation, ClientSignature, EquipmentDetails, SerialNo, Capacity,
                RefrigerantType, MakeModel, Latitude, Longitude, CreatedDate, CreatedTime, CreatedBy
            ) VALUES (
                $ticketID,
                '{$fieldValues['ProblemReportedByClient']}',
                '{$fieldValues['Observation']}',
                '{$fieldValues['ActionTaken']}',
                '{$fieldValues['Remarks']}',
                '{$fieldValues['ClientRepresentative']}',
                '{$fieldValues['ClientRepresentativeContact']}',
                '{$fieldValues['ClientRepresentativeEmails']}',
                '{$fieldValues['ClientRepresentativeDesignation']}',
                '$clientSignatureEsc',
                '{$fieldValues['EquipmentDetails']}',
                '{$fieldValues['SerialNo']}',
                '{$fieldValues['Capacity']}',
                '{$fieldValues['RefrigerantType']}',
                '{$fieldValues['MakeModel']}',
                '{$fieldValues['Latitude']}',
                '{$fieldValues['Longitude']}',
                '$createdDate',
                '$createdTime',
                '$createdByEsc'
            )";
        $insertResponse = _InsertTableRecords($conn, $insertSql);
        if (isset($insertResponse['error']) && $insertResponse['error']) {
            return $insertResponse;
        }
        $generalServiceReportID = (int) $insertResponse['last_insert_id'];
    }

    return array(
        'error' => false,
        'message' => 'General service report synced successfully.',
        'GeneralServiceReportID' => $generalServiceReportID,
    );
}

function getDynamicPPMServiceReportByTicket($conn, $ticketID)
{
    $ticketID = (int) $ticketID;
    if ($ticketID <= 0) {
        return null;
    }
    return _getTableDetails($conn, 'ppm_dynamic_service_report', "WHERE TicketID = $ticketID AND IsActive = 1");
}

function getDynamicPPMServiceReportItems($conn, $serviceReportID)
{
    $serviceReportID = (int) $serviceReportID;
    if ($serviceReportID <= 0) {
        return array();
    }
    return _getTableRecords($conn, 'ppm_dynamic_service_report_items', "WHERE ServiceReportID = $serviceReportID AND IsActive = 1");
}

function getDynamicPPMChecklistResponsesForTicket($conn, $ticketID)
{
    $ticketID = (int) $ticketID;
    $result = array(
        'has_report' => false,
        'has_checklist' => false,
        'mapping_mode' => '',
        'checklist' => null,
        'asset_condition' => '',
        'items' => array(),
    );

    $dynamicReport = getDynamicPPMServiceReportByTicket($conn, $ticketID);
    $mappedChecklist = getDynamicPPMMappedChecklistForTicket($conn, $ticketID);
    $savedItems = array();
    $checklist = null;

    if ($dynamicReport) {
        $result['has_report'] = true;
        $result['asset_condition'] = isset($dynamicReport['AssetCondition']) ? (string) $dynamicReport['AssetCondition'] : '';
        $checklistID = (int) $dynamicReport['ChecklistID'];
        $checklist = getDynamicPPMChecklistMasterById($conn, $checklistID);
        if (!$checklist) {
            $checklist = getDynamicPPMChecklistById($conn, $checklistID);
        }
        $savedItems = getDynamicPPMServiceReportItems($conn, (int) $dynamicReport['ID']);
        if (!is_array($savedItems)) {
            $savedItems = array();
        }
    }

    if (!$checklist) {
        $checklist = $mappedChecklist;
    }

    if (!$checklist) {
        return $result;
    }

    $result['has_checklist'] = true;
    $result['checklist'] = $checklist;
    $result['mapping_mode'] = isset($mappedChecklist['_mapping_mode']) ? $mappedChecklist['_mapping_mode'] : '';

    $checklistID = (int) $checklist['ID'];
    $masterItems = $result['has_report']
        ? getDynamicPPMChecklistItemsForReport($conn, $checklistID)
        : getDynamicPPMChecklistItems($conn, $checklistID);
    if (!is_array($masterItems)) {
        $masterItems = array();
    }

    $savedMap = array();
    foreach ($savedItems as $row) {
        $savedMap[(int) $row['ChecklistItemID']] = $row;
    }

    if (!empty($masterItems)) {
        foreach ($masterItems as $item) {
            $itemID = (int) $item['ID'];
            $saved = isset($savedMap[$itemID]) ? $savedMap[$itemID] : null;
            $result['items'][] = array(
                'item_name' => $item['ItemName'],
                'item_code' => $item['ItemCode'],
                'input_type' => $item['InputType'],
                'unit_name' => $item['UnitName'],
                'response_value' => $saved ? $saved['ResponseValue'] : '',
                'response_status' => $saved ? $saved['ResponseStatus'] : '',
                'remarks' => $saved ? $saved['Remarks'] : '',
                'sort_order' => (int) $item['SortOrder'],
            );
        }
    } else {
        foreach ($savedItems as $saved) {
            $itemID = (int) $saved['ChecklistItemID'];
            $item = _getTableDetails($conn, 'ppm_dynamic_checklist_items', "WHERE ID = $itemID");
            $result['items'][] = array(
                'item_name' => $item && isset($item['ItemName']) ? $item['ItemName'] : ('Item #' . $itemID),
                'item_code' => $item && isset($item['ItemCode']) ? $item['ItemCode'] : '',
                'input_type' => $item && isset($item['InputType']) ? $item['InputType'] : '',
                'unit_name' => $item && isset($item['UnitName']) ? $item['UnitName'] : '',
                'response_value' => $saved['ResponseValue'],
                'response_status' => $saved['ResponseStatus'],
                'remarks' => $saved['Remarks'],
                'sort_order' => $item ? (int) $item['SortOrder'] : $itemID,
            );
        }
    }

    return $result;
}

function dynamicPPMBuildChecklistPdfHtmlFromAssetApi($conn, $ticketID)
{
    $payload = buildDynamicPPMChecklistFormPayload($conn, $ticketID, 'asset');
    if (!empty($payload['error']) || empty($payload['use_dynamic_ppm']) || empty($payload['items'])) {
        return '';
    }

    $bundle = array(
        'has_checklist' => true,
        'asset_condition' => '',
        'checklist' => array(
            'ChecklistName' => isset($payload['checklist']['checklist_name']) ? $payload['checklist']['checklist_name'] : 'Checklist',
            'ChecklistCode' => isset($payload['checklist']['checklist_code']) ? $payload['checklist']['checklist_code'] : '',
        ),
        'items' => array(),
    );
    if (!empty($payload['common_fields']['AssetCondition'])) {
        $bundle['asset_condition'] = (string) $payload['common_fields']['AssetCondition'];
    }

    foreach ($payload['items'] as $item) {
        $response = (isset($item['response']) && is_array($item['response'])) ? $item['response'] : array();
        $bundle['items'][] = array(
            'item_name' => isset($item['item_name']) ? $item['item_name'] : '',
            'item_code' => isset($item['item_code']) ? $item['item_code'] : '',
            'input_type' => isset($item['input_type']) ? $item['input_type'] : '',
            'unit_name' => isset($item['unit_name']) ? $item['unit_name'] : '',
            'response_value' => isset($response['value']) ? $response['value'] : '',
            'response_status' => isset($response['status']) ? $response['status'] : '',
            'remarks' => isset($response['remarks']) ? $response['remarks'] : '',
        );
    }

    return dynamicPPMRenderChecklistPdfHtml($bundle);
}

function dynamicPPMBuildChecklistPdfHtml($conn, $ticketID)
{
    $bundle = getDynamicPPMChecklistResponsesForTicket($conn, $ticketID);
    return dynamicPPMRenderChecklistPdfHtml($bundle);
}

function dynamicPPMRenderChecklistPdfHtml($bundle)
{
    if (empty($bundle['has_checklist']) || empty($bundle['items'])) {
        return '';
    }

    $heading = 'Checklist';
    if (!empty($bundle['checklist']['ChecklistName'])) {
        $heading = (string) $bundle['checklist']['ChecklistName'];
        if (!empty($bundle['checklist']['ChecklistCode'])) {
            $heading .= ' (' . $bundle['checklist']['ChecklistCode'] . ')';
        }
    }

    $assetCondition = htmlspecialchars((string) $bundle['asset_condition']);

    $rows = '';
    $index = 1;
    foreach ($bundle['items'] as $item) {
        $value = dynamicPPMFormatChecklistResponseValue($item);
        $value = $value !== '' ? htmlspecialchars($value) : 'N/A';

        $status = htmlspecialchars((string) $item['response_status']);
        if ($status === '') {
            $status = 'N/A';
        }
        $remarks = htmlspecialchars((string) $item['remarks']);
        if ($remarks === '') {
            $remarks = 'N/A';
        }

        $rows .= '<tr>
            <td style="padding:5px; width:5%;">' . $index . '</td>
            <td style="padding:5px; width:35%;">' . htmlspecialchars((string) $item['item_name']) . '</td>
            <td style="padding:5px; width:20%;">' . $value . '</td>
            <td style="padding:5px; width:15%;">' . $status . '</td>
            <td style="padding:5px; width:25%;">' . $remarks . '</td>
        </tr>';
        $index++;
    }

    $assetRow = '';
    if ($assetCondition !== '') {
        $assetRow = '<tr><td colspan="5" style="padding:8px;"><strong>Asset Condition:</strong> ' . $assetCondition . '</td></tr>';
    }

    return '<div class="attacment_div">
        <div class="attacment">' . htmlspecialchars($heading) . '</div>
        <div class="product_attachment_box">
            <table class="bottom_signature_table" style="border-top:none;">
                <tbody>
                    <tr style="background:#f4f4f4;">
                        <td class="bottom_table_bold" style="width:5%;">#</td>
                        <td class="bottom_table_bold" style="width:35%;">Checklist Item</td>
                        <td class="bottom_table_bold" style="width:20%;">Response</td>
                        <td class="bottom_table_bold" style="width:15%;">Status</td>
                        <td class="bottom_table_bold" style="width:25%;">Remarks</td>
                    </tr>
                    ' . $assetRow . $rows . '
                </tbody>
            </table>
        </div>
    </div>';
}

function saveDynamicPPMServiceReport($conn, $payload)
{
    $response = array('error' => true, 'message' => 'Invalid payload.');
    $ticketID = isset($payload['TicketID']) ? (int) $payload['TicketID'] : 0;
    $createdBy = isset($payload['CreatedBy']) ? dynamicPPMClean($payload['CreatedBy']) : '';
    $assetCondition = isset($payload['AssetCondition']) ? dynamicPPMClean($payload['AssetCondition']) : '';
    $generalDetails = isset($payload['GeneralDetails']) && is_array($payload['GeneralDetails']) ? $payload['GeneralDetails'] : array();
    $items = isset($payload['ChecklistItems']) && is_array($payload['ChecklistItems']) ? $payload['ChecklistItems'] : array();

    if ($ticketID <= 0 || $createdBy === '') {
        $response['message'] = 'TicketID and CreatedBy are required.';
        return $response;
    }

    $checklist = null;
    $mappingMode = isset($payload['MappingMode']) ? strtolower(trim((string) $payload['MappingMode'])) : 'company';
    if ($mappingMode === 'asset') {
        $checklist = getDynamicPPMChecklistForTicketByAsset($conn, $ticketID);
        if (!$checklist) {
            $response['message'] = 'No active dynamic checklist mapped for this ticket asset.';
            return $response;
        }
    } else {
        $checklist = getDynamicPPMChecklistForTicket($conn, $ticketID);
        if (!$checklist) {
            $response['message'] = 'No active dynamic checklist mapped for ticket company/category.';
            return $response;
        }
    }
    $checklistID = (int) $checklist['ID'];

    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');
    $generalJson = mysqli_real_escape_string($conn, json_encode($generalDetails));
    $assetConditionEsc = mysqli_real_escape_string($conn, $assetCondition);
    $createdByEsc = mysqli_real_escape_string($conn, $createdBy);

    $existingReport = getDynamicPPMServiceReportByTicket($conn, $ticketID);
    if ($existingReport) {
        $serviceReportID = (int) $existingReport['ID'];
        $updateSql = "ChecklistID = $checklistID,
                      AssetCondition = '$assetConditionEsc',
                      GeneralDetailsJson = '$generalJson',
                      UpdatedBy = '$createdByEsc',
                      UpdatedDate = '$createdDate',
                      UpdatedTime = '$createdTime'
                      WHERE ID = $serviceReportID";
        $updateRes = _UpdateTableRecords($conn, 'ppm_dynamic_service_report', $updateSql);
        if (isset($updateRes['error']) && $updateRes['error']) {
            return $updateRes;
        }
    } else {
        $insertSql = "INSERT INTO ppm_dynamic_service_report
            (TicketID, ChecklistID, AssetCondition, GeneralDetailsJson, CreatedBy, CreatedDate, CreatedTime)
            VALUES
            ($ticketID, $checklistID, '$assetConditionEsc', '$generalJson', '$createdByEsc', '$createdDate', '$createdTime')";
        $insertRes = _InsertTableRecords($conn, $insertSql);
        if (!isset($insertRes['error']) || $insertRes['error']) {
            return $insertRes;
        }
        $serviceReportID = (int) $insertRes['last_insert_id'];
    }

    $generalSync = dynamicPPMSyncGeneralServiceReport($conn, $ticketID, $generalDetails, $createdBy);
    if (isset($generalSync['error']) && $generalSync['error']) {
        return $generalSync;
    }
    $generalServiceReportID = isset($generalSync['GeneralServiceReportID']) ? (int) $generalSync['GeneralServiceReportID'] : -1;

    foreach ($items as $item) {
        $checklistItemID = isset($item['ChecklistItemID']) ? (int) $item['ChecklistItemID'] : 0;
        if ($checklistItemID <= 0) {
            continue;
        }
        $value = isset($item['ResponseValue']) ? dynamicPPMClean($item['ResponseValue']) : '';
        $status = isset($item['ResponseStatus']) ? dynamicPPMClean($item['ResponseStatus']) : '';
        $remarks = isset($item['Remarks']) ? dynamicPPMClean($item['Remarks']) : '';
        $responseJson = isset($item['ResponseJson']) && is_array($item['ResponseJson']) ? json_encode($item['ResponseJson']) : '';

        $valueEsc = mysqli_real_escape_string($conn, $value);
        $statusEsc = mysqli_real_escape_string($conn, $status);
        $remarksEsc = mysqli_real_escape_string($conn, $remarks);
        $responseJsonEsc = mysqli_real_escape_string($conn, $responseJson);

        $existingItem = _getTableDetails(
            $conn,
            'ppm_dynamic_service_report_items',
            "WHERE ServiceReportID = $serviceReportID AND ChecklistItemID = $checklistItemID AND IsActive = 1"
        );

        if ($existingItem) {
            $updateItemSql = "ResponseValue = '$valueEsc',
                              ResponseStatus = '$statusEsc',
                              Remarks = '$remarksEsc',
                              ResponseJson = '$responseJsonEsc',
                              UpdatedBy = '$createdByEsc',
                              UpdatedDate = '$createdDate',
                              UpdatedTime = '$createdTime'
                              WHERE ID = " . (int) $existingItem['ID'];
            _UpdateTableRecords($conn, 'ppm_dynamic_service_report_items', $updateItemSql);
        } else {
            $insertItemSql = "INSERT INTO ppm_dynamic_service_report_items
                (ServiceReportID, ChecklistItemID, ResponseValue, ResponseStatus, Remarks, ResponseJson, CreatedBy, CreatedDate, CreatedTime)
                VALUES
                ($serviceReportID, $checklistItemID, '$valueEsc', '$statusEsc', '$remarksEsc', '$responseJsonEsc', '$createdByEsc', '$createdDate', '$createdTime')";
            _InsertTableRecords($conn, $insertItemSql);
        }
    }

    return array(
        'error' => false,
        'message' => 'Dynamic PPM service report saved successfully.',
        'ServiceReportID' => $serviceReportID,
        'GeneralServiceReportID' => $generalServiceReportID,
        'ChecklistID' => $checklistID
    );
}

function getDynamicPPMCategories($conn)
{
    $rows = _getTableRecords($conn, 'manage_categories', "WHERE IsActive = 1 ORDER BY CategoriesName ASC");
    return is_array($rows) ? $rows : array();
}

function getDynamicPPMCategoryById($conn, $categoryID)
{
    $categoryID = (int) $categoryID;
    if ($categoryID <= 0) {
        return null;
    }
    return _getTableDetails($conn, 'manage_categories', "WHERE ID = $categoryID");
}

function dynamicPPMBuildChecklistCodePrefix($categoryName)
{
    $categoryName = strtoupper(trim((string) $categoryName));
    $categoryName = preg_replace('/[^A-Z0-9]+/', '', $categoryName);
    if ($categoryName === '') {
        $categoryName = 'GENERAL';
    }
    return 'CH' . $categoryName;
}

function getNextDynamicPPMChecklistCode($conn, $categoryID)
{
    $category = getDynamicPPMCategoryById($conn, $categoryID);
    if (!$category || !isset($category['CategoriesName'])) {
        return '';
    }
    $prefix = dynamicPPMBuildChecklistCodePrefix($category['CategoriesName']);
    $prefixEsc = mysqli_real_escape_string($conn, $prefix);

    $sql = "SELECT ChecklistCode
            FROM ppm_dynamic_checklist_master
            WHERE ChecklistCode LIKE '$prefixEsc%'
            ORDER BY ID DESC";
    $result = mysqli_query($conn, $sql);

    $maxSeq = 0;
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $code = isset($row['ChecklistCode']) ? strtoupper((string) $row['ChecklistCode']) : '';
            if (strpos($code, $prefix) !== 0) {
                continue;
            }
            $suffix = substr($code, strlen($prefix));
            if ($suffix !== '' && ctype_digit($suffix)) {
                $num = (int) $suffix;
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            }
        }
    }
    return $prefix . sprintf('%03d', $maxSeq + 1);
}

function getDynamicPPMCompanies($conn)
{
    $rows = _getTableRecords($conn, 'company', "WHERE IsActive = 1 ORDER BY CompanyName ASC");
    return is_array($rows) ? $rows : array();
}

function getDynamicPPMChecklistMasters($conn, $activeOnly = false)
{
    $where = $activeOnly ? "WHERE IsActive = 1" : "WHERE 1";
    $sql = "SELECT m.*, c.CategoriesName AS CategoryName
            FROM ppm_dynamic_checklist_master m
            LEFT JOIN manage_categories c ON c.ID = m.CategoryID
            $where
            ORDER BY m.ID DESC";
    $result = mysqli_query($conn, $sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function getDynamicPPMChecklistMasterDetail($conn, $checklistID)
{
    $checklistID = (int) $checklistID;
    if ($checklistID <= 0) {
        return null;
    }
    $sql = "SELECT m.*, c.CategoriesName AS CategoryName
            FROM ppm_dynamic_checklist_master m
            LEFT JOIN manage_categories c ON c.ID = m.CategoryID
            WHERE m.ID = $checklistID";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

function getDynamicPPMChecklistItemCount($conn, $checklistID)
{
    return (int) _getTotalRows($conn, 'ppm_dynamic_checklist_items', "WHERE ChecklistID = " . (int) $checklistID . " AND IsActive = 1");
}

function getDynamicPPMChecklistMastersWithStats($conn, $filters = array())
{
    $where = "WHERE 1";
    if (!empty($filters['CategoryID'])) {
        $where .= " AND m.CategoryID = " . (int) $filters['CategoryID'];
    }
    if (isset($filters['IsActive']) && $filters['IsActive'] !== '' && $filters['IsActive'] !== '-1') {
        $where .= " AND m.IsActive = " . (int) $filters['IsActive'];
    }
    if (!empty($filters['search'])) {
        $searchEsc = mysqli_real_escape_string($conn, trim((string) $filters['search']));
        $where .= " AND (m.ChecklistCode LIKE '%$searchEsc%' OR m.ChecklistName LIKE '%$searchEsc%' OR c.CategoriesName LIKE '%$searchEsc%')";
    }

    $sql = "SELECT m.*, c.CategoriesName AS CategoryName,
                   (SELECT COUNT(*) FROM ppm_dynamic_checklist_items i WHERE i.ChecklistID = m.ID AND i.IsActive = 1) AS ItemCount,
                   (SELECT COUNT(*) FROM ppm_dynamic_company_checklist_map cm WHERE cm.ChecklistID = m.ID AND cm.IsActive = 1) AS MappingCount
            FROM ppm_dynamic_checklist_master m
            LEFT JOIN manage_categories c ON c.ID = m.CategoryID
            $where
            ORDER BY m.ID DESC";
    $result = mysqli_query($conn, $sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function getDynamicPPMMappingsByChecklist($conn, $checklistID)
{
    $checklistID = (int) $checklistID;
    if ($checklistID <= 0) {
        return array();
    }
    $sql = "SELECT m.*, c.CompanyName, cat.CategoriesName AS CategoryName
            FROM ppm_dynamic_company_checklist_map m
            INNER JOIN company c ON c.ID = m.CorporateID
            INNER JOIN manage_categories cat ON cat.ID = m.CategoryID
            WHERE m.ChecklistID = $checklistID AND m.IsActive = 1
            ORDER BY m.ID DESC";
    $result = mysqli_query($conn, $sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function parseDynamicPPMChecklistNames($raw)
{
    $names = array();
    if (is_array($raw)) {
        foreach ($raw as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $names[] = $line;
            }
        }
        return $names;
    }
    $raw = str_replace(array("\r\n", "\r"), "\n", (string) $raw);
    foreach (explode("\n", $raw) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $names[] = $line;
        }
    }
    return $names;
}

function bulkCreateDynamicPPMChecklistMasters($conn, $data)
{
    // Prefer single checklist with user-provided code.
    if (!empty($data['ChecklistCode']) || (isset($data['ChecklistName']) && trim((string) $data['ChecklistName']) !== '')) {
        return createDynamicPPMChecklistMaster($conn, $data);
    }

    $categoryID = isset($data['CategoryID']) ? (int) $data['CategoryID'] : 0;
    $versionNo = isset($data['VersionNo']) ? (int) $data['VersionNo'] : 1;
    $description = isset($data['Description']) ? dynamicPPMClean($data['Description']) : '';
    $createdBy = isset($data['CreatedBy']) ? dynamicPPMClean($data['CreatedBy']) : '';

    $names = array();
    if (isset($data['ChecklistNames'])) {
        $names = parseDynamicPPMChecklistNames($data['ChecklistNames']);
    }

    if ($categoryID <= 0) {
        return array('error' => true, 'message' => 'Category is required.');
    }
    if (empty($names)) {
        return array('error' => true, 'message' => 'Checklist code and checklist name are required.');
    }

    return array('error' => true, 'message' => 'Please enter Checklist Code and Checklist Name.');
}

function createDynamicPPMChecklistMaster($conn, $data)
{
    $categoryID = isset($data['CategoryID']) ? (int) $data['CategoryID'] : 0;
    $checklistName = isset($data['ChecklistName']) ? dynamicPPMClean($data['ChecklistName']) : '';
    $versionNo = isset($data['VersionNo']) ? (int) $data['VersionNo'] : 1;
    $description = isset($data['Description']) ? dynamicPPMClean($data['Description']) : '';
    $createdBy = isset($data['CreatedBy']) ? dynamicPPMClean($data['CreatedBy']) : '';
    $checklistCode = isset($data['ChecklistCode']) ? strtoupper(trim((string) $data['ChecklistCode'])) : '';
    $checklistCode = preg_replace('/\s+/', '', $checklistCode);

    if ($categoryID <= 0 || $checklistName === '') {
        return array('error' => true, 'message' => 'Category and checklist name are required.');
    }
    if ($checklistCode === '') {
        return array('error' => true, 'message' => 'Checklist code is required.');
    }
    if (!preg_match('/^[A-Z0-9_\-]{2,50}$/', $checklistCode)) {
        return array('error' => true, 'message' => 'Checklist code must be 2-50 characters (A-Z, 0-9, _ or -).');
    }

    $checklistCodeEsc = mysqli_real_escape_string($conn, $checklistCode);
    $exists = _getTableDetails(
        $conn,
        'ppm_dynamic_checklist_master',
        "WHERE ChecklistCode = '$checklistCodeEsc' AND IFNULL(IsActive, 1) = 1"
    );
    if ($exists) {
        return array('error' => true, 'message' => 'Checklist code already exists: ' . $checklistCode);
    }

    $checklistNameEsc = mysqli_real_escape_string($conn, $checklistName);
    $descriptionEsc = mysqli_real_escape_string($conn, $description);
    $createdByEsc = mysqli_real_escape_string($conn, $createdBy);
    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');

    $sql = "INSERT INTO ppm_dynamic_checklist_master
        (CategoryID, ChecklistCode, ChecklistName, VersionNo, Description, Status, CreatedBy, CreatedDate, CreatedTime, IsActive)
        VALUES
        ($categoryID, '$checklistCodeEsc', '$checklistNameEsc', $versionNo, '$descriptionEsc', 'Active', '$createdByEsc', '$createdDate', '$createdTime', 1)";
    $res = _InsertTableRecords($conn, $sql);
    if (isset($res['error']) && $res['error'] === false) {
        $res['ChecklistCode'] = $checklistCode;
        $res['message'] = 'Checklist master created with code: ' . $checklistCode;
    }
    return $res;
}

function parseDynamicPPMOptionsJson($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return '';
    }
    $parts = array_filter(array_map('trim', explode(',', $raw)), 'strlen');
    return json_encode(array_values($parts));
}

function addDynamicPPMChecklistItem($conn, $data)
{
    $checklistID = isset($data['ChecklistID']) ? (int) $data['ChecklistID'] : 0;
    $itemCode = isset($data['ItemCode']) ? dynamicPPMClean($data['ItemCode']) : '';
    $itemName = isset($data['ItemName']) ? dynamicPPMClean($data['ItemName']) : '';
    $inputType = isset($data['InputType']) ? dynamicPPMClean($data['InputType']) : 'text';
    $unitName = isset($data['UnitName']) ? dynamicPPMClean($data['UnitName']) : '';
    $optionsJson = parseDynamicPPMOptionsJson(isset($data['OptionsJson']) ? $data['OptionsJson'] : '');
    $defaultValue = isset($data['DefaultValue']) ? dynamicPPMClean($data['DefaultValue']) : '';
    $isMandatory = isset($data['IsMandatory']) ? (int) $data['IsMandatory'] : 0;
    $sortOrder = isset($data['SortOrder']) ? (int) $data['SortOrder'] : 0;
    $helpText = isset($data['HelpText']) ? dynamicPPMClean($data['HelpText']) : '';
    $createdBy = isset($data['CreatedBy']) ? dynamicPPMClean($data['CreatedBy']) : '';

    if ($checklistID <= 0 || $itemName === '') {
        return array('error' => true, 'message' => 'Checklist and item name are required.');
    }

    if ($sortOrder <= 0) {
        $existing = getDynamicPPMChecklistItemsByChecklist($conn, $checklistID);
        $sortOrder = count($existing) + 1;
    }

    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');

    $itemCodeEsc = mysqli_real_escape_string($conn, $itemCode);
    $itemNameEsc = mysqli_real_escape_string($conn, $itemName);
    $inputTypeEsc = mysqli_real_escape_string($conn, $inputType);
    $unitNameEsc = mysqli_real_escape_string($conn, $unitName);
    $optionsJsonEsc = mysqli_real_escape_string($conn, $optionsJson);
    $defaultValueEsc = mysqli_real_escape_string($conn, $defaultValue);
    $helpTextEsc = mysqli_real_escape_string($conn, $helpText);
    $createdByEsc = mysqli_real_escape_string($conn, $createdBy);

    $sql = "INSERT INTO ppm_dynamic_checklist_items
        (ChecklistID, ItemCode, ItemName, InputType, UnitName, OptionsJson, DefaultValue, IsMandatory, SortOrder, HelpText, CreatedBy, CreatedDate, CreatedTime, IsActive)
        VALUES
        ($checklistID, '$itemCodeEsc', '$itemNameEsc', '$inputTypeEsc', '$unitNameEsc', '$optionsJsonEsc', '$defaultValueEsc', $isMandatory, $sortOrder, '$helpTextEsc', '$createdByEsc', '$createdDate', '$createdTime', 1)";
    return _InsertTableRecords($conn, $sql);
}

function dynamicPPMInputTypes()
{
    return array('text', 'number', 'dropdown', 'boolean', 'checkbox', 'textarea', 'date', 'time');
}

function getDynamicPPMChecklistItemById($conn, $itemID)
{
    $itemID = (int) $itemID;
    if ($itemID <= 0) {
        return null;
    }
    return _getTableDetails($conn, 'ppm_dynamic_checklist_items', "WHERE ID = $itemID AND IsActive = 1");
}

function updateDynamicPPMChecklistItem($conn, $data)
{
    $itemID = isset($data['ItemID']) ? (int) $data['ItemID'] : 0;
    $itemCode = isset($data['ItemCode']) ? dynamicPPMClean($data['ItemCode']) : '';
    $itemName = isset($data['ItemName']) ? dynamicPPMClean($data['ItemName']) : '';
    $inputType = isset($data['InputType']) ? dynamicPPMClean($data['InputType']) : 'text';
    $unitName = isset($data['UnitName']) ? dynamicPPMClean($data['UnitName']) : '';
    $optionsJson = parseDynamicPPMOptionsJson(isset($data['OptionsJson']) ? $data['OptionsJson'] : '');
    $defaultValue = isset($data['DefaultValue']) ? dynamicPPMClean($data['DefaultValue']) : '';
    $isMandatory = isset($data['IsMandatory']) ? (int) $data['IsMandatory'] : 0;
    $sortOrder = isset($data['SortOrder']) ? (int) $data['SortOrder'] : 0;
    $helpText = isset($data['HelpText']) ? dynamicPPMClean($data['HelpText']) : '';
    $updatedBy = isset($data['UpdatedBy']) ? dynamicPPMClean($data['UpdatedBy']) : '';
    if ($updatedBy === '') {
        $updatedBy = dynamicPPMGetSessionUser();
    }

    if ($itemID <= 0) {
        return array('error' => true, 'message' => 'Invalid checklist item.');
    }
    if ($itemName === '') {
        return array('error' => true, 'message' => 'Item name is required.');
    }

    $existing = getDynamicPPMChecklistItemById($conn, $itemID);
    if (!$existing) {
        return array('error' => true, 'message' => 'Checklist item not found.');
    }

    if ($sortOrder <= 0) {
        $sortOrder = (int) $existing['SortOrder'];
        if ($sortOrder <= 0) {
            $sortOrder = 1;
        }
    }

    $updatedDate = date('Y-m-d');
    $updatedTime = date('H:i:s');

    $itemCodeEsc = mysqli_real_escape_string($conn, $itemCode);
    $itemNameEsc = mysqli_real_escape_string($conn, $itemName);
    $inputTypeEsc = mysqli_real_escape_string($conn, $inputType);
    $unitNameEsc = mysqli_real_escape_string($conn, $unitName);
    $optionsJsonEsc = mysqli_real_escape_string($conn, $optionsJson);
    $defaultValueEsc = mysqli_real_escape_string($conn, $defaultValue);
    $helpTextEsc = mysqli_real_escape_string($conn, $helpText);
    $updatedByEsc = mysqli_real_escape_string($conn, $updatedBy);

    $updateSql = "ItemCode = '$itemCodeEsc',
                  ItemName = '$itemNameEsc',
                  InputType = '$inputTypeEsc',
                  UnitName = '$unitNameEsc',
                  OptionsJson = '$optionsJsonEsc',
                  DefaultValue = '$defaultValueEsc',
                  IsMandatory = $isMandatory,
                  SortOrder = $sortOrder,
                  HelpText = '$helpTextEsc',
                  UpdatedBy = '$updatedByEsc',
                  UpdatedDate = '$updatedDate',
                  UpdatedTime = '$updatedTime'
                  WHERE ID = $itemID";

    $res = _UpdateTableRecords($conn, 'ppm_dynamic_checklist_items', $updateSql);
    if (isset($res['error']) && $res['error'] === false) {
        $res['message'] = 'Checklist item updated successfully.';
    }
    return $res;
}

function bulkAddDynamicPPMChecklistItems($conn, $data)
{
    $checklistID = isset($data['ChecklistID']) ? (int) $data['ChecklistID'] : 0;
    $createdBy = isset($data['CreatedBy']) ? dynamicPPMClean($data['CreatedBy']) : '';

    if ($checklistID <= 0) {
        return array('error' => true, 'message' => 'Checklist is required.');
    }

    $items = array();
    if (isset($data['items']) && is_array($data['items'])) {
        $items = $data['items'];
    } elseif (isset($data['items_json']) && trim((string) $data['items_json']) !== '') {
        $decoded = json_decode($data['items_json'], true);
        if (is_array($decoded)) {
            $items = $decoded;
        }
    }

    if (isset($data['ItemNames'])) {
        $names = parseDynamicPPMChecklistNames($data['ItemNames']);
        $defaultInputType = isset($data['DefaultInputType']) ? dynamicPPMClean($data['DefaultInputType']) : 'text';
        $defaultMandatory = isset($data['DefaultIsMandatory']) ? (int) $data['DefaultIsMandatory'] : 0;
        $defaultUnit = isset($data['DefaultUnitName']) ? dynamicPPMClean($data['DefaultUnitName']) : '';
        $defaultOptions = isset($data['DefaultOptionsJson']) ? $data['DefaultOptionsJson'] : '';
        $defaultValue = isset($data['DefaultValue']) ? dynamicPPMClean($data['DefaultValue']) : '';
        $baseSort = count(getDynamicPPMChecklistItemsByChecklist($conn, $checklistID)) + count($items);
        foreach ($names as $idx => $name) {
            $items[] = array(
                'ItemName' => $name,
                'InputType' => $defaultInputType,
                'IsMandatory' => $defaultMandatory,
                'UnitName' => $defaultUnit,
                'DefaultValue' => $defaultValue,
                'OptionsJson' => $defaultOptions,
                'SortOrder' => $baseSort + $idx + 1,
            );
        }
    }

    if (empty($items) && isset($data['ItemName']) && trim((string) $data['ItemName']) !== '') {
        $items[] = $data;
    }

    if (empty($items)) {
        return array('error' => true, 'message' => 'Add at least one checklist item.');
    }

    $saved = 0;
    $errors = array();
    foreach ($items as $index => $item) {
        if (!is_array($item)) {
            continue;
        }
        $itemName = isset($item['ItemName']) ? trim((string) $item['ItemName']) : '';
        if ($itemName === '') {
            continue;
        }
        $row = array_merge($item, array(
            'ChecklistID' => $checklistID,
            'ItemName' => $itemName,
            'CreatedBy' => $createdBy,
        ));
        if (!isset($row['SortOrder']) || (int) $row['SortOrder'] <= 0) {
            $row['SortOrder'] = $index + 1;
        }
        $res = addDynamicPPMChecklistItem($conn, $row);
        if (isset($res['error']) && $res['error'] === false) {
            $saved++;
        } else {
            $errors[] = $itemName . ': ' . (isset($res['message']) ? $res['message'] : 'Failed');
        }
    }

    if ($saved === 0) {
        return array(
            'error' => true,
            'message' => !empty($errors) ? implode('; ', $errors) : 'No checklist items saved.',
            'saved_count' => 0,
        );
    }

    $message = $saved . ' checklist item(s) added successfully.';
    if (!empty($errors)) {
        $message .= ' Failed: ' . implode('; ', $errors);
    }
    return array(
        'error' => false,
        'message' => $message,
        'saved_count' => $saved,
    );
}

function getDynamicPPMChecklistItemsByChecklist($conn, $checklistID)
{
    return _getTableRecords($conn, 'ppm_dynamic_checklist_items', "WHERE ChecklistID = " . (int) $checklistID . " AND IsActive = 1 ORDER BY SortOrder ASC, ID ASC");
}

function dynamicPPMFormatMappingValidity($effectiveFrom, $effectiveTo)
{
    if ($effectiveTo === null || trim((string) $effectiveTo) === '') {
        if ($effectiveFrom === null || trim((string) $effectiveFrom) === '') {
            return 'Always';
        }
        return 'Always (from ' . $effectiveFrom . ')';
    }
    $from = ($effectiveFrom === null || trim((string) $effectiveFrom) === '') ? 'Open' : $effectiveFrom;
    return $from . ' to ' . $effectiveTo;
}

function mapDynamicPPMCompanyChecklist($conn, $data)
{
    $corporateID = isset($data['CorporateID']) ? (int) $data['CorporateID'] : 0;
    $categoryID = isset($data['CategoryID']) ? (int) $data['CategoryID'] : 0;
    $checklistID = isset($data['ChecklistID']) ? (int) $data['ChecklistID'] : 0;
    $mappingValidity = isset($data['MappingValidity']) ? dynamicPPMClean($data['MappingValidity']) : 'always';
    $effectiveFrom = isset($data['EffectiveFrom']) ? dynamicPPMClean($data['EffectiveFrom']) : '';
    $effectiveTo = isset($data['EffectiveTo']) ? dynamicPPMClean($data['EffectiveTo']) : '';
    $createdBy = isset($data['CreatedBy']) ? dynamicPPMClean($data['CreatedBy']) : '';

    if ($corporateID <= 0 || $categoryID <= 0 || $checklistID <= 0) {
        return array('error' => true, 'message' => 'Company, category and checklist are required.');
    }

    if ($mappingValidity === 'always') {
        $effectiveTo = '';
    } elseif ($effectiveFrom !== '' && $effectiveTo !== '' && $effectiveFrom > $effectiveTo) {
        return array('error' => true, 'message' => 'Effective From cannot be after Effective To.');
    }

    _UpdateTableRecords(
        $conn,
        'ppm_dynamic_company_checklist_map',
        "IsActive = 0 WHERE CorporateID = $corporateID AND CategoryID = $categoryID AND IsActive = 1"
    );

    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');
    $effectiveFromEsc = mysqli_real_escape_string($conn, $effectiveFrom);
    $effectiveToEsc = mysqli_real_escape_string($conn, $effectiveTo);
    $createdByEsc = mysqli_real_escape_string($conn, $createdBy);

    $sql = "INSERT INTO ppm_dynamic_company_checklist_map
        (CorporateID, CategoryID, ChecklistID, EffectiveFrom, EffectiveTo, CreatedBy, CreatedDate, CreatedTime, IsActive)
        VALUES
        ($corporateID, $categoryID, $checklistID, " .
        ($effectiveFromEsc === '' ? "NULL" : "'$effectiveFromEsc'") . ", " .
        ($effectiveToEsc === '' ? "NULL" : "'$effectiveToEsc'") . ", '$createdByEsc', '$createdDate', '$createdTime', 1)";
    $res = _InsertTableRecords($conn, $sql);
    if (isset($res['error']) && $res['error'] === false) {
        $res['message'] = $mappingValidity === 'always'
            ? 'Company mapping saved as Always (active until you change it).'
            : 'Company mapping saved with date range.';
    }
    return $res;
}

function getDynamicPPMCompanyChecklistMappings($conn)
{
    $sql = "SELECT m.*, c.CompanyName, cat.CategoriesName AS CategoryName, ch.ChecklistCode, ch.ChecklistName
            FROM ppm_dynamic_company_checklist_map m
            INNER JOIN company c ON c.ID = m.CorporateID
            INNER JOIN manage_categories cat ON cat.ID = m.CategoryID
            INNER JOIN ppm_dynamic_checklist_master ch ON ch.ID = m.ChecklistID
            WHERE m.IsActive = 1
            ORDER BY m.ID DESC";
    $result = mysqli_query($conn, $sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function dynamicPPMGetBaseUrl()
{
    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $projectRoot = rtrim(preg_replace('#/(api|admin)(/.*)?$#', '', $scriptName), '/');
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $projectRoot;
    }
    return 'https://techxpertindia.in';
}

function dynamicPPMDecodePdfGeneratorResponse($body)
{
    if (!is_string($body) || trim($body) === '') {
        return null;
    }
    $decoded = json_decode(trim($body), true);
    if (is_array($decoded)) {
        return $decoded;
    }
    // The generator may print PHP warnings before its JSON result.
    if (preg_match_all('/\{[^{}]*"pdfname"[^{}]*\}/', $body, $matches)) {
        $decoded = json_decode(end($matches[0]), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return null;
}

function dynamicPPMBuildReportPdfPublicUrl($pdfName)
{
    return dynamicPPMGetBaseUrl() . '/admin/corporate-tickets/reports/' . ltrim((string) $pdfName, '/');
}

function dynamicPPMBuildReportDownloadApiUrl($ticketID)
{
    return dynamicPPMGetBaseUrl() . '/api/dynamic-ppm/download_dynamic_ppm_service_report_pdf.php?TicketID=' . (int) $ticketID;
}

function dynamicPPMBuildAdminReportDownloadUrl($ticketID)
{
    return dynamicPPMGetBaseUrl() . '/admin/dynamic-ppm/action/download_dynamic_ppm_service_report_pdf.php?TicketID=' . (int) $ticketID;
}

function dynamicPPMFinalizePdfResult($ticketID, $serviceReportID, $pdfName)
{
    $reportsDir = dirname(dirname(__DIR__)) . '/corporate-tickets/reports/';
    $pdfPath = $reportsDir . ltrim((string) $pdfName, '/');
    if (!is_file($pdfPath)) {
        return array('error' => true, 'message' => 'PDF file was not created on server.');
    }

    return array(
        'error' => false,
        'message' => 'Dynamic PPM PDF generated successfully.',
        'pdfname' => $pdfName,
        'pdf_path' => $pdfPath,
        'pdf_url' => dynamicPPMBuildReportPdfPublicUrl($pdfName),
        'download_api' => dynamicPPMBuildReportDownloadApiUrl($ticketID),
        'download_action' => dynamicPPMBuildAdminReportDownloadUrl($ticketID),
        'general_service_report_id' => (int) $serviceReportID,
    );
}

function dynamicPPMRequestReportPdfGeneration($serviceReportID, $action = 'Download')
{
    $serviceReportID = (int) $serviceReportID;
    if ($serviceReportID <= 0) {
        return array('error' => true, 'message' => 'Invalid service report for PDF generation.');
    }

    $url = dynamicPPMGetBaseUrl() . '/admin/corporate-tickets/action/generate_ppm_service_report_pdf.php';
    $postFields = http_build_query(array(
        'ServiceReportID' => $serviceReportID,
        'Action' => $action,
    ));

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded'),
        ));
        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $decoded = dynamicPPMDecodePdfGeneratorResponse($body);
            if (is_array($decoded) && !empty($decoded['pdfname'])) {
                return $decoded;
            }
        }
    }

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postFields,
            'timeout' => 180,
            'ignore_errors' => true,
        ),
    ));
    $body = @file_get_contents($url, false, $context);
    $decoded = dynamicPPMDecodePdfGeneratorResponse($body);
    if (is_array($decoded) && !empty($decoded['pdfname'])) {
        return $decoded;
    }

    return array('error' => true, 'message' => 'Unable to generate PDF via HTTP request.');
}

function dynamicPPMGenerateReportPdf($conn, $ticketID, $action = 'Download')
{
    $ticketID = (int) $ticketID;
    if ($ticketID <= 0) {
        return array('error' => true, 'message' => 'Invalid ticket for PDF generation.');
    }

    $generalReport = getPPMGeneralServiceReportByTicket($conn, $ticketID);
    if (!$generalReport) {
        return array('error' => true, 'message' => 'General service report not found for PDF generation.');
    }

    $serviceReportID = (int) $generalReport['ID'];
    $httpResponse = dynamicPPMRequestReportPdfGeneration($serviceReportID, $action);
    if (!empty($httpResponse['error']) || empty($httpResponse['pdfname'])) {
        return array(
            'error' => true,
            'message' => isset($httpResponse['message']) ? $httpResponse['message'] : 'Unable to generate dynamic PPM PDF.',
        );
    }

    return dynamicPPMFinalizePdfResult($ticketID, $serviceReportID, $httpResponse['pdfname']);
}

function dynamicPPMStreamReportPdfDownload($conn, $ticketID)
{
    $pdfResult = dynamicPPMGenerateReportPdf($conn, $ticketID, 'Download');
    if (!empty($pdfResult['error'])) {
        return $pdfResult;
    }

    if (headers_sent()) {
        return $pdfResult;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($pdfResult['pdfname']) . '"');
    header('Content-Length: ' . filesize($pdfResult['pdf_path']));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    readfile($pdfResult['pdf_path']);
    exit;
}

?>
