<?php

define('CORPORATE_AUDIT_ICON_DIR', dirname(__DIR__) . '/../media/corporate-audit/');
define('CORPORATE_AUDIT_ICON_URL', '../media/corporate-audit/');

function corporateAuditIconUploadDir()
{
    if (!is_dir(CORPORATE_AUDIT_ICON_DIR) && !mkdir(CORPORATE_AUDIT_ICON_DIR, 0777, true)) {
        return false;
    }
    return CORPORATE_AUDIT_ICON_DIR;
}

function uploadCorporateAuditIcon($file, $prefix = 'audit-icon')
{
    $response = array('error' => true, 'message' => 'Invalid icon upload.');
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $response['message'] = 'No icon file uploaded.';
        return $response;
    }

    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
    $originalName = isset($file['name']) ? strtolower((string) $file['name']) : '';
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    if (!in_array($ext, $allowedExtensions, true)) {
        $response['message'] = 'Only image files are allowed (JPG, PNG, WEBP, GIF, SVG).';
        return $response;
    }

    $targetDir = corporateAuditIconUploadDir();
    if ($targetDir === false) {
        $response['message'] = 'Unable to create upload directory.';
        return $response;
    }

    $newFileName = $prefix . '-' . date('YmdHis') . '-' . mt_rand(1000, 9999) . '.' . $ext;
    $targetPath = $targetDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $response['message'] = 'Unable to save uploaded icon.';
        return $response;
    }

    return array('error' => false, 'file_name' => $newFileName);
}

function deleteCorporateAuditIconFile($fileName)
{
    $fileName = basename((string) $fileName);
    if ($fileName === '') {
        return;
    }
    $path = CORPORATE_AUDIT_ICON_DIR . $fileName;
    if (is_file($path)) {
        @unlink($path);
    }
}

function corporateAuditIconUrl($fileName, $baseUrl = '')
{
    $fileName = trim((string) $fileName);
    if ($fileName === '') {
        return '';
    }
    if ($baseUrl !== '') {
        return rtrim($baseUrl, '/') . '/admin/media/corporate-audit/' . $fileName;
    }
    return CORPORATE_AUDIT_ICON_URL . $fileName;
}

function corporateAuditFieldTypes()
{
    return array(
        'text' => 'Text',
        'number' => 'Number',
        'decimal' => 'Decimal',
        'select' => 'Dropdown Select',
        'checkbox' => 'Checkbox (Yes/No)',
        'textarea' => 'Long Text',
        'date' => 'Date',
    );
}

function corporateAuditFormatRecordIcons(&$row, $baseUrl = '')
{
    if (!is_array($row)) {
        return;
    }
    $row['icon_image_url'] = corporateAuditIconUrl(isset($row['IconImage']) ? $row['IconImage'] : '', $baseUrl);
    if (!empty($row['IconClass'])) {
        $row['icon_display'] = '<i class="' . htmlspecialchars($row['IconClass']) . ' fa-2x"></i>';
    } elseif (!empty($row['IconImage'])) {
        $row['icon_display'] = '<img src="' . htmlspecialchars($row['icon_image_url']) . '" alt="icon" style="max-height:40px;">';
    } else {
        $row['icon_display'] = '<i class="fa-solid fa-clipboard-check fa-2x text-muted"></i>';
    }
}

// ---------- Master Audit ----------

function getAllCorporateMasterAudits($conn, $activeOnly = true)
{
    $where = $activeOnly ? ' WHERE IsActive = 1' : ' WHERE 1=1';
    $where .= ' ORDER BY SortOrder ASC, AuditName ASC';
    return _getTableRecords($conn, 'corporate_master_audit', $where);
}

function getCorporateMasterAuditById($conn, $id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return array();
    }
    return _getTableDetails($conn, 'corporate_master_audit', " WHERE ID = $id");
}

function insertCorporateMasterAudit($conn, $data, $files = array())
{
    $response = array('error' => true, 'message' => 'Unable to add audit.');
    $auditName = cleantext($data['audit_name']);
    if ($auditName === '') {
        $response['message'] = 'Audit name is required.';
        return $response;
    }

    $description = cleantext(isset($data['audit_description']) ? $data['audit_description'] : '');
    $iconClass = cleantext(isset($data['icon_class']) ? $data['icon_class'] : '');
    $sortOrder = (int) (isset($data['sort_order']) ? $data['sort_order'] : 0);
    $isActive = (int) (isset($data['is_active']) ? $data['is_active'] : 1);
    $createdBy = cleantext(isset($data['CreatedBy']) ? $data['CreatedBy'] : '');
    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');
    $iconImage = '';

    if (isset($files['icon_image']) && isset($files['icon_image']['error']) && (int) $files['icon_image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadCorporateAuditIcon($files['icon_image'], 'master-audit');
        if ($upload['error']) {
            $response['message'] = $upload['message'];
            return $response;
        }
        $iconImage = cleantext($upload['file_name']);
    }

    $sql = "INSERT INTO corporate_master_audit (AuditName, AuditDescription, IconClass, IconImage, SortOrder, IsActive, CreatedBy, CreatedDate, CreatedTime)
            VALUES ('$auditName', '$description', '$iconClass', '$iconImage', $sortOrder, $isActive, '$createdBy', '$createdDate', '$createdTime')";
    $response = _InsertTableRecords($conn, $sql);
    if ($response['error'] === false) {
        $response['message'] = 'Master audit added successfully.';
        $response['id'] = mysqli_insert_id($conn);
    }
    return $response;
}

function updateCorporateMasterAudit($conn, $data, $files = array())
{
    $response = array('error' => true, 'message' => 'Unable to update audit.');
    $id = (int) (isset($data['form_id']) ? $data['form_id'] : 0);
    if ($id <= 0) {
        $response['message'] = 'Invalid audit ID.';
        return $response;
    }

    $existing = getCorporateMasterAuditById($conn, $id);
    if (empty($existing)) {
        $response['message'] = 'Audit not found.';
        return $response;
    }

    $auditName = cleantext($data['audit_name']);
    if ($auditName === '') {
        $response['message'] = 'Audit name is required.';
        return $response;
    }

    $description = cleantext(isset($data['audit_description']) ? $data['audit_description'] : '');
    $iconClass = cleantext(isset($data['icon_class']) ? $data['icon_class'] : '');
    $sortOrder = (int) (isset($data['sort_order']) ? $data['sort_order'] : 0);
    $isActive = (int) (isset($data['is_active']) ? $data['is_active'] : 1);
    $iconImage = cleantext(isset($existing['IconImage']) ? $existing['IconImage'] : '');

    if (isset($files['icon_image']) && isset($files['icon_image']['error']) && (int) $files['icon_image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadCorporateAuditIcon($files['icon_image'], 'master-audit');
        if ($upload['error']) {
            $response['message'] = $upload['message'];
            return $response;
        }
        if ($iconImage !== '') {
            deleteCorporateAuditIconFile($iconImage);
        }
        $iconImage = cleantext($upload['file_name']);
    }

    $updateParam = " AuditName = '$auditName', AuditDescription = '$description', IconClass = '$iconClass', IconImage = '$iconImage',
        SortOrder = $sortOrder, IsActive = $isActive WHERE ID = $id";
    $response = _UpdateTableRecords($conn, 'corporate_master_audit', $updateParam);
    if ($response['error'] === false) {
        $response['message'] = 'Master audit updated successfully.';
    }
    return $response;
}

function deleteCorporateMasterAudit($conn, $id)
{
    $id = (int) $id;
    $response = _UpdateTableRecords($conn, 'corporate_master_audit', " IsActive = 0 WHERE ID = $id");
    if ($response['error'] === false) {
        $response['message'] = 'Master audit deactivated successfully.';
    }
    return $response;
}

// ---------- Master Sub Audit ----------

function getAllCorporateMasterSubAudits($conn, $masterAuditId = 0, $activeOnly = true)
{
    $where = $activeOnly ? ' WHERE IsActive = 1' : ' WHERE 1=1';
    if ((int) $masterAuditId > 0) {
        $where .= ' AND MasterAuditID = ' . (int) $masterAuditId;
    }
    $where .= ' ORDER BY SortOrder ASC, SubAuditName ASC';
    return _getTableRecords($conn, 'corporate_master_sub_audit', $where);
}

function getCorporateMasterSubAuditById($conn, $id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return array();
    }
    return _getTableDetails($conn, 'corporate_master_sub_audit', " WHERE ID = $id");
}

function insertCorporateMasterSubAudit($conn, $data, $files = array())
{
    $response = array('error' => true, 'message' => 'Unable to add sub audit.');
    $masterAuditId = (int) (isset($data['master_audit_id']) ? $data['master_audit_id'] : 0);
    $subAuditName = cleantext($data['sub_audit_name']);
    if ($masterAuditId <= 0) {
        $response['message'] = 'Please select a master audit.';
        return $response;
    }
    if ($subAuditName === '') {
        $response['message'] = 'Sub audit name is required.';
        return $response;
    }

    $description = cleantext(isset($data['sub_audit_description']) ? $data['sub_audit_description'] : '');
    $iconClass = cleantext(isset($data['icon_class']) ? $data['icon_class'] : '');
    $sortOrder = (int) (isset($data['sort_order']) ? $data['sort_order'] : 0);
    $isActive = (int) (isset($data['is_active']) ? $data['is_active'] : 1);
    $createdBy = cleantext(isset($data['CreatedBy']) ? $data['CreatedBy'] : '');
    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');
    $iconImage = '';

    if (isset($files['icon_image']) && isset($files['icon_image']['error']) && (int) $files['icon_image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadCorporateAuditIcon($files['icon_image'], 'sub-audit');
        if ($upload['error']) {
            $response['message'] = $upload['message'];
            return $response;
        }
        $iconImage = cleantext($upload['file_name']);
    }

    $sql = "INSERT INTO corporate_master_sub_audit (MasterAuditID, SubAuditName, SubAuditDescription, IconClass, IconImage, SortOrder, IsActive, CreatedBy, CreatedDate, CreatedTime)
            VALUES ($masterAuditId, '$subAuditName', '$description', '$iconClass', '$iconImage', $sortOrder, $isActive, '$createdBy', '$createdDate', '$createdTime')";
    $response = _InsertTableRecords($conn, $sql);
    if ($response['error'] === false) {
        $response['message'] = 'Sub audit added successfully.';
        $response['id'] = mysqli_insert_id($conn);
    }
    return $response;
}

function updateCorporateMasterSubAudit($conn, $data, $files = array())
{
    $response = array('error' => true, 'message' => 'Unable to update sub audit.');
    $id = (int) (isset($data['form_id']) ? $data['form_id'] : 0);
    if ($id <= 0) {
        $response['message'] = 'Invalid sub audit ID.';
        return $response;
    }

    $existing = getCorporateMasterSubAuditById($conn, $id);
    if (empty($existing)) {
        $response['message'] = 'Sub audit not found.';
        return $response;
    }

    $masterAuditId = (int) (isset($data['master_audit_id']) ? $data['master_audit_id'] : 0);
    $subAuditName = cleantext($data['sub_audit_name']);
    if ($masterAuditId <= 0 || $subAuditName === '') {
        $response['message'] = 'Master audit and sub audit name are required.';
        return $response;
    }

    $description = cleantext(isset($data['sub_audit_description']) ? $data['sub_audit_description'] : '');
    $iconClass = cleantext(isset($data['icon_class']) ? $data['icon_class'] : '');
    $sortOrder = (int) (isset($data['sort_order']) ? $data['sort_order'] : 0);
    $isActive = (int) (isset($data['is_active']) ? $data['is_active'] : 1);
    $iconImage = cleantext(isset($existing['IconImage']) ? $existing['IconImage'] : '');

    if (isset($files['icon_image']) && isset($files['icon_image']['error']) && (int) $files['icon_image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadCorporateAuditIcon($files['icon_image'], 'sub-audit');
        if ($upload['error']) {
            $response['message'] = $upload['message'];
            return $response;
        }
        if ($iconImage !== '') {
            deleteCorporateAuditIconFile($iconImage);
        }
        $iconImage = cleantext($upload['file_name']);
    }

    $updateParam = " MasterAuditID = $masterAuditId, SubAuditName = '$subAuditName', SubAuditDescription = '$description',
        IconClass = '$iconClass', IconImage = '$iconImage', SortOrder = $sortOrder, IsActive = $isActive WHERE ID = $id";
    $response = _UpdateTableRecords($conn, 'corporate_master_sub_audit', $updateParam);
    if ($response['error'] === false) {
        $response['message'] = 'Sub audit updated successfully.';
    }
    return $response;
}

function deleteCorporateMasterSubAudit($conn, $id)
{
    $id = (int) $id;
    $response = _UpdateTableRecords($conn, 'corporate_master_sub_audit', " IsActive = 0 WHERE ID = $id");
    if ($response['error'] === false) {
        $response['message'] = 'Sub audit deactivated successfully.';
    }
    return $response;
}

// ---------- Checklist ----------

function getCorporateAuditChecklists($conn, $subAuditId = 0, $activeOnly = true)
{
    $where = $activeOnly ? ' WHERE IsActive = 1' : ' WHERE 1=1';
    if ((int) $subAuditId > 0) {
        $where .= ' AND SubAuditID = ' . (int) $subAuditId;
    }
    $where .= ' ORDER BY SortOrder ASC, CheckpointName ASC';
    return _getTableRecords($conn, 'corporate_audit_checklist', $where);
}

function getCorporateAuditChecklistById($conn, $id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return array();
    }
    return _getTableDetails($conn, 'corporate_audit_checklist', " WHERE ID = $id");
}

function corporateAuditNormalizeOptionsJson($optionsRaw)
{
    $optionsRaw = trim((string) $optionsRaw);
    if ($optionsRaw === '') {
        return '';
    }
    if ($optionsRaw[0] === '[') {
        $decoded = json_decode($optionsRaw, true);
        if (is_array($decoded)) {
            return cleantext(json_encode(array_values($decoded)));
        }
    }
    $parts = array_filter(array_map('trim', explode(',', $optionsRaw)), 'strlen');
    return cleantext(json_encode(array_values($parts)));
}

function insertCorporateAuditChecklist($conn, $data)
{
    $response = array('error' => true, 'message' => 'Unable to add checklist item.');
    $subAuditId = (int) (isset($data['sub_audit_id']) ? $data['sub_audit_id'] : 0);
    $checkpointName = cleantext($data['checkpoint_name']);
    if ($subAuditId <= 0) {
        $response['message'] = 'Please select a sub audit module.';
        return $response;
    }
    if ($checkpointName === '') {
        $response['message'] = 'Checkpoint name is required.';
        return $response;
    }

    $fieldTypes = array_keys(corporateAuditFieldTypes());
    $fieldType = cleantext(isset($data['field_type']) ? $data['field_type'] : 'text');
    if (!in_array($fieldType, $fieldTypes, true)) {
        $fieldType = 'text';
    }

    $description = cleantext(isset($data['checkpoint_description']) ? $data['checkpoint_description'] : '');
    $idealValue = cleantext(isset($data['ideal_value']) ? $data['ideal_value'] : '');
    $minValue = cleantext(isset($data['min_value']) ? $data['min_value'] : '');
    $maxValue = cleantext(isset($data['max_value']) ? $data['max_value'] : '');
    $unit = cleantext(isset($data['unit']) ? $data['unit'] : '');
    $helpText = cleantext(isset($data['help_text']) ? $data['help_text'] : '');
    $optionsJson = corporateAuditNormalizeOptionsJson(isset($data['options_json']) ? $data['options_json'] : '');
    $sortOrder = (int) (isset($data['sort_order']) ? $data['sort_order'] : 0);
    $isMandatory = (int) (isset($data['is_mandatory']) ? $data['is_mandatory'] : 0);
    $isActive = (int) (isset($data['is_active']) ? $data['is_active'] : 1);
    $createdBy = cleantext(isset($data['CreatedBy']) ? $data['CreatedBy'] : '');
    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');

    $sql = "INSERT INTO corporate_audit_checklist (`SubAuditID`, `CheckpointName`, `CheckpointDescription`, `FieldType`, `IdealValue`, `MinValue`, `MaxValue`, `Unit`, `OptionsJSON`, `HelpText`, `SortOrder`, `IsMandatory`, `IsActive`, `CreatedBy`, `CreatedDate`, `CreatedTime`)
            VALUES ($subAuditId, '$checkpointName', '$description', '$fieldType', '$idealValue', '$minValue', '$maxValue', '$unit', '$optionsJson', '$helpText', $sortOrder, $isMandatory, $isActive, '$createdBy', '$createdDate', '$createdTime')";
    $response = _InsertTableRecords($conn, $sql);
    if ($response['error'] === false) {
        $response['message'] = 'Checklist item added successfully.';
        $response['id'] = mysqli_insert_id($conn);
    }
    return $response;
}

function bulkInsertCorporateAuditChecklist($conn, $data)
{
    $response = array('error' => true, 'message' => 'Unable to add checklist items.');
    $subAuditId = (int) (isset($data['sub_audit_id']) ? $data['sub_audit_id'] : 0);
    if ($subAuditId <= 0) {
        $response['message'] = 'Please select a sub audit module.';
        return $response;
    }

    $items = isset($data['items']) ? $data['items'] : array();
    if (is_string($items)) {
        $decoded = json_decode($items, true);
        $items = is_array($decoded) ? $decoded : array();
    }
    if (empty($items)) {
        $response['message'] = 'Add at least one checkpoint row with a name.';
        return $response;
    }

    $createdBy = cleantext(isset($data['CreatedBy']) ? $data['CreatedBy'] : '');
    $defaultFieldType = cleantext(isset($data['default_field_type']) ? $data['default_field_type'] : 'text');
    $defaultMandatory = (int) (isset($data['default_is_mandatory']) ? $data['default_is_mandatory'] : 0);
    $defaultActive = (int) (isset($data['default_is_active']) ? $data['default_is_active'] : 1);

    $inserted = 0;
    $skipped = 0;
    $errors = array();

    foreach ($items as $index => $item) {
        if (!is_array($item)) {
            $skipped++;
            continue;
        }
        $checkpointName = isset($item['checkpoint_name']) ? trim((string) $item['checkpoint_name']) : '';
        if ($checkpointName === '') {
            $skipped++;
            continue;
        }

        $rowData = array(
            'sub_audit_id' => $subAuditId,
            'checkpoint_name' => $checkpointName,
            'checkpoint_description' => isset($item['checkpoint_description']) ? $item['checkpoint_description'] : '',
            'field_type' => isset($item['field_type']) && $item['field_type'] !== '' ? $item['field_type'] : $defaultFieldType,
            'ideal_value' => isset($item['ideal_value']) ? $item['ideal_value'] : '',
            'min_value' => isset($item['min_value']) ? $item['min_value'] : '',
            'max_value' => isset($item['max_value']) ? $item['max_value'] : '',
            'unit' => isset($item['unit']) ? $item['unit'] : '',
            'options_json' => isset($item['options_json']) ? $item['options_json'] : '',
            'help_text' => isset($item['help_text']) ? $item['help_text'] : '',
            'sort_order' => isset($item['sort_order']) ? $item['sort_order'] : $index + 1,
            'is_mandatory' => isset($item['is_mandatory']) && $item['is_mandatory'] !== '' ? $item['is_mandatory'] : $defaultMandatory,
            'is_active' => isset($item['is_active']) && $item['is_active'] !== '' ? $item['is_active'] : $defaultActive,
            'CreatedBy' => $createdBy,
        );

        $insertResult = insertCorporateAuditChecklist($conn, $rowData);
        if ($insertResult['error'] === false) {
            $inserted++;
        } else {
            $errors[] = 'Row ' . ($index + 1) . ' (' . $checkpointName . '): ' . ($insertResult['message'] ?? 'Failed');
        }
    }

    if ($inserted === 0) {
        $response['message'] = !empty($errors) ? implode('; ', $errors) : 'No valid checkpoints to save.';
        $response['inserted'] = 0;
        $response['skipped'] = $skipped;
        return $response;
    }

    $response['error'] = false;
    $response['inserted'] = $inserted;
    $response['skipped'] = $skipped;
    $response['message'] = $inserted . ' checkpoint(s) added successfully.';
    if ($skipped > 0) {
        $response['message'] .= ' ' . $skipped . ' empty row(s) skipped.';
    }
    if (!empty($errors)) {
        $response['message'] .= ' Some rows failed: ' . implode('; ', $errors);
    }
    return $response;
}

function updateCorporateAuditChecklist($conn, $data)
{
    $response = array('error' => true, 'message' => 'Unable to update checklist item.');
    $id = (int) (isset($data['form_id']) ? $data['form_id'] : 0);
    if ($id <= 0) {
        $response['message'] = 'Invalid checklist ID.';
        return $response;
    }

    $subAuditId = (int) (isset($data['sub_audit_id']) ? $data['sub_audit_id'] : 0);
    $checkpointName = cleantext($data['checkpoint_name']);
    if ($subAuditId <= 0 || $checkpointName === '') {
        $response['message'] = 'Sub audit and checkpoint name are required.';
        return $response;
    }

    $fieldTypes = array_keys(corporateAuditFieldTypes());
    $fieldType = cleantext(isset($data['field_type']) ? $data['field_type'] : 'text');
    if (!in_array($fieldType, $fieldTypes, true)) {
        $fieldType = 'text';
    }

    $description = cleantext(isset($data['checkpoint_description']) ? $data['checkpoint_description'] : '');
    $idealValue = cleantext(isset($data['ideal_value']) ? $data['ideal_value'] : '');
    $minValue = cleantext(isset($data['min_value']) ? $data['min_value'] : '');
    $maxValue = cleantext(isset($data['max_value']) ? $data['max_value'] : '');
    $unit = cleantext(isset($data['unit']) ? $data['unit'] : '');
    $helpText = cleantext(isset($data['help_text']) ? $data['help_text'] : '');
    $optionsJson = corporateAuditNormalizeOptionsJson(isset($data['options_json']) ? $data['options_json'] : '');
    $sortOrder = (int) (isset($data['sort_order']) ? $data['sort_order'] : 0);
    $isMandatory = (int) (isset($data['is_mandatory']) ? $data['is_mandatory'] : 0);
    $isActive = (int) (isset($data['is_active']) ? $data['is_active'] : 1);

    $updateParam = " `SubAuditID` = $subAuditId, `CheckpointName` = '$checkpointName', `CheckpointDescription` = '$description',
        `FieldType` = '$fieldType', `IdealValue` = '$idealValue', `MinValue` = '$minValue', `MaxValue` = '$maxValue', `Unit` = '$unit',
        `OptionsJSON` = '$optionsJson', `HelpText` = '$helpText', `SortOrder` = $sortOrder, `IsMandatory` = $isMandatory, `IsActive` = $isActive WHERE `ID` = $id";
    $response = _UpdateTableRecords($conn, 'corporate_audit_checklist', $updateParam);
    if ($response['error'] === false) {
        $response['message'] = 'Checklist item updated successfully.';
    }
    return $response;
}

function deleteCorporateAuditChecklist($conn, $id)
{
    $id = (int) $id;
    $response = _UpdateTableRecords($conn, 'corporate_audit_checklist', " IsActive = 0 WHERE ID = $id");
    if ($response['error'] === false) {
        $response['message'] = 'Checklist item deactivated successfully.';
    }
    return $response;
}

function getCorporateAuditHierarchy($conn, $activeOnly = true, $baseUrl = '')
{
    $audits = getAllCorporateMasterAudits($conn, $activeOnly);
    $hierarchy = array();

    foreach ($audits as $audit) {
        corporateAuditFormatRecordIcons($audit, $baseUrl);
        $auditId = (int) $audit['ID'];
        $subAudits = getAllCorporateMasterSubAudits($conn, $auditId, $activeOnly);
        $subList = array();

        foreach ($subAudits as $sub) {
            corporateAuditFormatRecordIcons($sub, $baseUrl);
            $subId = (int) $sub['ID'];
            $checklists = getCorporateAuditChecklists($conn, $subId, $activeOnly);
            foreach ($checklists as &$checkpoint) {
                if (!empty($checkpoint['OptionsJSON'])) {
                    $decoded = json_decode($checkpoint['OptionsJSON'], true);
                    $checkpoint['options'] = is_array($decoded) ? $decoded : array();
                } else {
                    $checkpoint['options'] = array();
                }
            }
            unset($checkpoint);
            $sub['checklists'] = $checklists;
            $subList[] = $sub;
        }

        $audit['sub_audits'] = $subList;
        $hierarchy[] = $audit;
    }

    return $hierarchy;
}

function corporateAuditMasterAuditNameMap($conn)
{
    $rows = getAllCorporateMasterAudits($conn, false);
    $map = array();
    foreach ($rows as $row) {
        $map[(int) $row['ID']] = $row['AuditName'];
    }
    return $map;
}

function corporateAuditSubAuditNameMap($conn, $masterAuditId = 0)
{
    $rows = getAllCorporateMasterSubAudits($conn, $masterAuditId, false);
    $map = array();
    foreach ($rows as $row) {
        $map[(int) $row['ID']] = $row['SubAuditName'];
    }
    return $map;
}
