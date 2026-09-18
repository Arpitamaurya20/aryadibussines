<?php
/**
 * Shared formatting helpers for audit APIs (no requires – included by each endpoint).
 */

function audit_api_base_url()
{
    if (defined('FRONT_SITE_PATH')) {
        return rtrim(FRONT_SITE_PATH, '/');
    }
    return 'https://techxpertindia.in';
}

function audit_api_icon_url($fileName, $baseUrl)
{
    $fileName = trim((string) $fileName);
    if ($fileName === '') {
        return '';
    }
    return rtrim($baseUrl, '/') . '/admin/media/corporate-audit/' . basename($fileName);
}

function audit_api_format_audit($row, $baseUrl, $extra = array())
{
    $iconUrl = audit_api_icon_url(isset($row['IconImage']) ? $row['IconImage'] : '', $baseUrl);
    $item = array(
        'id' => (int) $row['ID'],
        'audit_name' => $row['AuditName'],
        'description' => $row['AuditDescription'],
        'icon_class' => $row['IconClass'],
        'icon_image' => $row['IconImage'],
        'icon_image_url' => $iconUrl,
        'sort_order' => (int) $row['SortOrder'],
        'is_active' => (int) $row['IsActive'],
    );
    return array_merge($item, $extra);
}

function audit_api_format_sub_audit($row, $baseUrl, $masterAuditName = '', $extra = array())
{
    $iconUrl = audit_api_icon_url(isset($row['IconImage']) ? $row['IconImage'] : '', $baseUrl);
    $item = array(
        'id' => (int) $row['ID'],
        'master_audit_id' => (int) $row['MasterAuditID'],
        'master_audit_name' => $masterAuditName,
        'sub_audit_name' => $row['SubAuditName'],
        'description' => $row['SubAuditDescription'],
        'icon_class' => $row['IconClass'],
        'icon_image' => $row['IconImage'],
        'icon_image_url' => $iconUrl,
        'sort_order' => (int) $row['SortOrder'],
        'is_active' => (int) $row['IsActive'],
    );
    return array_merge($item, $extra);
}

function audit_api_field_meta($fieldType, $row, $options = array())
{
    $meta = array(
        'input_type' => 'text',
        'keyboard' => 'default',
        'multiline' => false,
        'options' => $options,
        'placeholder' => '',
    );
    switch ($fieldType) {
        case 'number':
            $meta['input_type'] = 'number';
            $meta['keyboard'] = 'numeric';
            break;
        case 'decimal':
            $meta['input_type'] = 'decimal';
            $meta['keyboard'] = 'decimal';
            break;
        case 'textarea':
            $meta['input_type'] = 'textarea';
            $meta['multiline'] = true;
            break;
        case 'select':
            $meta['input_type'] = 'select';
            $meta['options'] = $options;
            break;
        case 'checkbox':
            $meta['input_type'] = 'checkbox';
            $meta['options'] = array('Yes', 'No');
            break;
        case 'date':
            $meta['input_type'] = 'date';
            break;
    }
    $ideal = isset($row['IdealValue']) ? (string) $row['IdealValue'] : '';
    if ($ideal !== '') {
        $meta['placeholder'] = $ideal;
        if (!empty($row['Unit'])) {
            $meta['placeholder'] .= ' ' . $row['Unit'];
        }
    }
    $meta['validation'] = array(
        'required' => (int) (isset($row['IsMandatory']) ? $row['IsMandatory'] : 0) === 1,
        'min_value' => isset($row['MinValue']) ? $row['MinValue'] : '',
        'max_value' => isset($row['MaxValue']) ? $row['MaxValue'] : '',
        'ideal_value' => $ideal,
        'unit' => isset($row['Unit']) ? $row['Unit'] : '',
    );
    return $meta;
}

function audit_api_format_checklist($row, $extra = array())
{
    $options = array();
    if (!empty($row['OptionsJSON'])) {
        $decoded = json_decode($row['OptionsJSON'], true);
        if (is_array($decoded)) {
            $options = array_values($decoded);
        }
    }
    $fieldType = isset($row['FieldType']) ? $row['FieldType'] : 'text';
    $item = array(
        'id' => (int) $row['ID'],
        'sub_audit_id' => (int) $row['SubAuditID'],
        'checkpoint_name' => $row['CheckpointName'],
        'description' => $row['CheckpointDescription'],
        'field_type' => $fieldType,
        'ideal_value' => $row['IdealValue'],
        'min_value' => $row['MinValue'],
        'max_value' => $row['MaxValue'],
        'unit' => $row['Unit'],
        'options' => $options,
        'help_text' => $row['HelpText'],
        'sort_order' => (int) $row['SortOrder'],
        'is_mandatory' => (int) $row['IsMandatory'],
        'is_active' => (int) $row['IsActive'],
        'field_meta' => audit_api_field_meta($fieldType, $row, $options),
    );
    return array_merge($item, $extra);
}

function audit_api_parse_input()
{
    $data_raw = file_get_contents('php://input');
    $data = json_decode($data_raw, true);
    if (!is_array($data)) {
        $data = array();
    }
    if (!empty($_GET)) {
        $data = array_merge($_GET, $data);
    }
    return $data;
}

function audit_api_is_active_only($data)
{
    if (isset($data['include_inactive']) && (int) $data['include_inactive'] === 1) {
        return false;
    }
    if (isset($data['all']) && (int) $data['all'] === 1) {
        return false;
    }
    return true;
}
