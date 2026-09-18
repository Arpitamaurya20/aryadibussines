<?php
/**
 * Shared helpers for audit ticket APIs.
 */

function audit_ticket_api_parse_input()
{
    $dataRaw = file_get_contents('php://input');
    $data = json_decode($dataRaw, true);
    if (!is_array($data)) {
        $data = array();
    }
    if (!empty($_GET)) {
        $data = array_merge($_GET, $data);
    }
    return $data;
}

function audit_ticket_api_response($error, $message, $extra = array())
{
    $response = array_merge(array(
        'error' => (bool) $error,
        'message' => $message,
    ), $extra);
    echo json_encode($response);
    exit;
}

function audit_ticket_normalize_ok_status($value)
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return 'Pending';
    }
    $lower = strtolower(str_replace(array('_', '-'), ' ', $raw));
    if (in_array($lower, array('ok', 'yes', '1', 'true', 'pass', 'passed'), true)) {
        return 'OK';
    }
    if (in_array($lower, array('not ok', 'notok', 'no', '0', 'false', 'fail', 'failed', 'not okay'), true)) {
        return 'Not OK';
    }
    if ($raw === 'OK' || $raw === 'Not OK') {
        return $raw;
    }
    return 'Pending';
}

function audit_ticket_ok_status_options()
{
    return array('OK', 'Not OK');
}

define('AUDIT_TICKET_CHECKLIST_MEDIA_DIR', dirname(__DIR__, 2) . '/admin/media/audit-ticket/checklist/');
define('AUDIT_TICKET_SIGNATURE_MEDIA_DIR', dirname(__DIR__, 2) . '/admin/media/audit-ticket/signatures/');

function audit_ticket_is_draft_save($data)
{
    if (isset($data['save_draft']) && (int) $data['save_draft'] === 1) {
        return true;
    }
    if (isset($data['mark_completed']) && (int) $data['mark_completed'] === 0) {
        return true;
    }
    if (isset($data['is_draft']) && (int) $data['is_draft'] === 1) {
        return true;
    }
    return false;
}

function audit_ticket_merge_payload_sources($data)
{
    if (!is_array($data)) {
        return array();
    }
    foreach (array('userdata', 'userdetails') as $nestedKey) {
        if (!isset($data[$nestedKey]) || !is_array($data[$nestedKey])) {
            continue;
        }
        foreach ($data[$nestedKey] as $key => $value) {
            if (!array_key_exists($key, $data) || audit_ticket_payload_value_is_empty($data[$key])) {
                $data[$key] = $value;
            }
        }
    }
    return $data;
}

function audit_ticket_payload_value_is_empty($value)
{
    return $value === null || trim((string) $value) === '';
}

function audit_ticket_pick_payload_value($data, $keys)
{
    if (!is_array($data)) {
        return '';
    }
    foreach ($keys as $key) {
        if (isset($data[$key]) && !audit_ticket_payload_value_is_empty($data[$key])) {
            return trim((string) $data[$key]);
        }
    }
    return '';
}

function audit_ticket_resolve_ticket_id($conn, $data)
{
    if (!function_exists('getCorporateAuditTicketById')) {
        require_once dirname(__DIR__, 2) . '/admin/audit-ticket/controller/audit_ticket_controller.php';
    }

    $idKeys = array('AuditTicketID', 'audit_ticket_id', 'ServiceReportID', 'id');
    foreach ($idKeys as $key) {
        if (!isset($data[$key]) || (int) $data[$key] <= 0) {
            continue;
        }
        $ticket = getCorporateAuditTicketById($conn, (int) $data[$key]);
        if ($ticket) {
            return (int) $ticket['ID'];
        }
    }

    $lookupKeys = array('ServiceReportTicketID', 'ticket_id', 'TicketID');
    foreach ($lookupKeys as $key) {
        if (!isset($data[$key])) {
            continue;
        }
        $val = trim((string) $data[$key]);
        if ($val === '') {
            continue;
        }
        $ticket = getCorporateAuditTicketById($conn, $val, true);
        if ($ticket) {
            return (int) $ticket['ID'];
        }
        if (ctype_digit($val)) {
            $ticket = getCorporateAuditTicketById($conn, (int) $val);
            if ($ticket) {
                return (int) $ticket['ID'];
            }
        }
    }

    return 0;
}

function audit_ticket_decode_base64_image($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return false;
    }
    if (strpos($raw, 'base64,') !== false) {
        $parts = explode('base64,', $raw, 2);
        $raw = isset($parts[1]) ? $parts[1] : '';
    }
    $decoded = base64_decode($raw, true);
    return $decoded !== false ? $decoded : false;
}

function audit_ticket_ensure_media_dir($dir)
{
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function audit_ticket_is_saved_media_filename($value, $prefix)
{
    $value = trim((string) $value);
    if ($value === '' || strlen($value) > 255) {
        return false;
    }
    if (strpos($value, 'data:') === 0 || strpos($value, 'base64,') !== false) {
        return false;
    }
    return strpos($value, $prefix) === 0;
}

function audit_ticket_save_checklist_image($auditTicketId, $checklistId, $imageRaw, $existingSaved = '')
{
    $imageRaw = trim((string) $imageRaw);
    if ($imageRaw === '') {
        return $existingSaved;
    }
    if (audit_ticket_is_saved_media_filename($imageRaw, 'audit_chk_')) {
        return $imageRaw;
    }
    $bin = audit_ticket_decode_base64_image($imageRaw);
    if ($bin === false || $bin === '') {
        return $existingSaved;
    }
    audit_ticket_ensure_media_dir(AUDIT_TICKET_CHECKLIST_MEDIA_DIR);
    $filename = 'audit_chk_' . (int) $auditTicketId . '_' . (int) $checklistId . '_' . uniqid() . '.jpg';
    file_put_contents(AUDIT_TICKET_CHECKLIST_MEDIA_DIR . $filename, $bin);
    return $filename;
}

function audit_ticket_save_client_signature_image($auditTicketId, $imageRaw, $existingSaved = '')
{
    $imageRaw = trim((string) $imageRaw);
    if ($imageRaw === '') {
        return $existingSaved;
    }
    if (audit_ticket_is_saved_media_filename($imageRaw, 'audit_sig_')) {
        return $imageRaw;
    }
    $bin = audit_ticket_decode_base64_image($imageRaw);
    if ($bin === false || $bin === '') {
        return $existingSaved;
    }
    audit_ticket_ensure_media_dir(AUDIT_TICKET_SIGNATURE_MEDIA_DIR);
    $filename = 'audit_sig_' . (int) $auditTicketId . '_' . uniqid() . '.jpg';
    file_put_contents(AUDIT_TICKET_SIGNATURE_MEDIA_DIR . $filename, $bin);
    return $filename;
}

function audit_ticket_checklist_image_url($filename, $baseUrl = '')
{
    $filename = trim((string) $filename);
    if ($filename === '') {
        return '';
    }
    $path = '/admin/media/audit-ticket/checklist/' . ltrim($filename, '/');
    return $baseUrl !== '' ? rtrim($baseUrl, '/') . $path : $path;
}

function audit_ticket_signature_image_url($filename, $baseUrl = '')
{
    $filename = trim((string) $filename);
    if ($filename === '') {
        return '';
    }
    $path = '/admin/media/audit-ticket/signatures/' . ltrim($filename, '/');
    return $baseUrl !== '' ? rtrim($baseUrl, '/') . $path : $path;
}

function audit_ticket_parse_legacy_checklist_payload($conn, $ticket, $data, $subAuditIdFilter = 0)
{
    require_once dirname(__DIR__, 2) . '/admin/corporate-audit/controller/corporate_audit_controller.php';

    $merged = audit_ticket_merge_payload_sources($data);
    $subAuditIdFilter = (int) $subAuditIdFilter;
    $allTicketSubAuditIds = getTicketSubAuditIdList($conn, $ticket);
    $subAuditIds = $allTicketSubAuditIds;
    if ($subAuditIdFilter > 0) {
        $subAuditIds = in_array($subAuditIdFilter, $subAuditIds, true) ? array($subAuditIdFilter) : array();
    }

    // Build global sequence start index per sub-audit to support both:
    // - local keys for selected audit (1..N)
    // - global keys from full multi-audit form (e.g. 7..15)
    $subAuditGlobalStartIndex = array();
    $runningIndex = 1;
    foreach ($allTicketSubAuditIds as $ticketSubAuditId) {
        $subAuditGlobalStartIndex[(int) $ticketSubAuditId] = $runningIndex;
        $ticketChecklists = getCorporateAuditChecklists($conn, (int) $ticketSubAuditId, true);
        $runningIndex += count($ticketChecklists);
    }

    $responses = array();
    $idx = 1;

    foreach ($subAuditIds as $subAuditId) {
        $checklists = getCorporateAuditChecklists($conn, $subAuditId, true);
        $localIdx = 1;
        $globalBase = isset($subAuditGlobalStartIndex[(int) $subAuditId]) ? (int) $subAuditGlobalStartIndex[(int) $subAuditId] : 1;
        $detectedLegacyStart = 0;
        if ($subAuditIdFilter > 0) {
            $allSources = array($merged);
            if (isset($data['userdata']) && is_array($data['userdata'])) {
                $allSources[] = $data['userdata'];
            }
            if (isset($data['userdetails']) && is_array($data['userdetails'])) {
                $allSources[] = $data['userdetails'];
            }

            $numericKeys = array();
            foreach ($allSources as $source) {
                foreach ($source as $k => $v) {
                    if (!preg_match('/^\d+$/', (string) $k)) {
                        continue;
                    }
                    if (trim((string) $v) !== '') {
                        $numericKeys[] = (int) $k;
                    }
                }
            }
            if (!empty($numericKeys)) {
                sort($numericKeys);
                $detectedLegacyStart = (int) $numericKeys[0];
            }
        }

        foreach ($checklists as $cp) {
            $nLocal = (string) $localIdx;
            $nGlobal = (string) ($globalBase + $localIdx - 1);
            $nDetected = $detectedLegacyStart > 0 ? (string) ($detectedLegacyStart + $localIdx - 1) : '';
            $sources = array($merged);
            if (isset($data['userdata']) && is_array($data['userdata'])) {
                $sources[] = $data['userdata'];
            }
            if (isset($data['userdetails']) && is_array($data['userdetails'])) {
                $sources[] = $data['userdetails'];
            }

            $okStatus = '';
            $value = '';
            $remark = '';
            $image = '';
            foreach ($sources as $source) {
                if ($okStatus === '') {
                    $okStatus = audit_ticket_pick_payload_value($source, array(
                        $nLocal, $nLocal . '_status', 'status_' . $nLocal,
                        $nGlobal, $nGlobal . '_status', 'status_' . $nGlobal,
                        $nDetected, ($nDetected !== '' ? $nDetected . '_status' : ''), ($nDetected !== '' ? 'status_' . $nDetected : '')
                    ));
                }
                if ($value === '') {
                    $value = audit_ticket_pick_payload_value($source, array(
                        'value_' . $nLocal, $nLocal . '_value', 'val_' . $nLocal,
                        'value_' . $nGlobal, $nGlobal . '_value', 'val_' . $nGlobal,
                        ($nDetected !== '' ? 'value_' . $nDetected : ''), ($nDetected !== '' ? $nDetected . '_value' : ''), ($nDetected !== '' ? 'val_' . $nDetected : '')
                    ));
                }
                if ($remark === '') {
                    $remark = audit_ticket_pick_payload_value($source, array(
                        'remark_' . $nLocal, $nLocal . '_remark',
                        'remark_' . $nGlobal, $nGlobal . '_remark',
                        ($nDetected !== '' ? 'remark_' . $nDetected : ''), ($nDetected !== '' ? $nDetected . '_remark' : '')
                    ));
                }
                if ($image === '') {
                    $image = audit_ticket_pick_payload_value($source, array(
                        'image_' . $nLocal, $nLocal . '_image',
                        'image_' . $nGlobal, $nGlobal . '_image',
                        ($nDetected !== '' ? 'image_' . $nDetected : ''), ($nDetected !== '' ? $nDetected . '_image' : '')
                    ));
                }
            }

            $hasAny = ($okStatus !== '' || $value !== '' || $remark !== '' || $image !== '');
            if ($hasAny) {
                if ($value === '' && $okStatus !== '') {
                    $value = $okStatus;
                }
                $responses[] = array(
                    'ChecklistID' => (int) $cp['ID'],
                    'SubAuditID' => (int) $subAuditId,
                    'sequence_index' => $globalBase + $localIdx - 1,
                    'ResponseValue' => $value,
                    'OkStatus' => $okStatus !== '' ? $okStatus : 'Pending',
                    'Remarks' => $remark,
                    'ResponseImage' => $image,
                );
            }
            $idx++;
            $localIdx++;
        }
    }

    return array(
        'responses' => $responses,
        'checklist_count' => max(0, $idx - 1),
    );
}

function audit_ticket_extract_report_meta_from_payload($data)
{
    $merged = audit_ticket_merge_payload_sources($data);
    $meta = array();

    $map = array(
        'ReportFloor' => array('ReportFloor', 'report_floor', 'FloorName'),
        'ReportSiteAddress' => array('ReportSiteAddress', 'report_site_address'),
        'ClientRepresentative' => array('ClientRepresentative', 'client_representative'),
        'ClientRepresentativeContact' => array('ClientRepresentativeContact', 'client_representative_contact'),
        'ClientRepresentativeDesignation' => array('ClientRepresentativeDesignation', 'client_representative_designation'),
        'ClientRepresentativeEmail' => array('ClientRepresentativeEmail', 'ClientRepresentativeEmails', 'client_representative_email'),
        'AuditObservation' => array('AuditObservation', 'audit_observation', 'Observation'),
        'AuditConclusion' => array('AuditConclusion', 'audit_conclusion'),
        'TechnicianNotes' => array('TechnicianNotes', 'technician_notes'),
        'ProblemReportedByClient' => array('ProblemReportedByClient', 'problem_reported_by_client'),
        'ActionTaken' => array('ActionTaken', 'action_taken'),
        'GeneralRemarks' => array('GeneralRemarks', 'general_remarks', 'Remarks'),
        'ClientSignature' => array('ClientSignature', 'client_signature'),
        'ReportLatitude' => array('ReportLatitude', 'Latitude', 'latitude'),
        'ReportLongitude' => array('ReportLongitude', 'Longitude', 'longitude'),
    );

    foreach ($map as $target => $keys) {
        $value = audit_ticket_pick_payload_value($merged, $keys);
        if ($value !== '') {
            $meta[$target] = $value;
        }
    }

    return $meta;
}

function audit_ticket_build_legacy_mobile_payload($conn, $ticket, $baseUrl = '')
{
    if (!function_exists('getCorporateAuditTicketChecklistWithResponses')) {
        require_once dirname(__DIR__, 2) . '/admin/audit-ticket/controller/audit_ticket_controller.php';
    }

    if (!is_array($ticket)) {
        return array();
    }

    $auditTicketId = (int) $ticket['ID'];
    $checklistItems = getCorporateAuditTicketChecklistWithResponses($conn, $auditTicketId);
    $audits = getCorporateAuditTicketAudits($conn, $auditTicketId);
    $payload = array(
        'sub_audit_id' => (string) (isset($ticket['SubAuditID']) ? $ticket['SubAuditID'] : ''),
        'audit_count' => (string) count($audits),
        'ticket_id' => (string) $auditTicketId,
        'AuditTicketID' => (string) $auditTicketId,
        'ServiceReportID' => (string) $auditTicketId,
        'ServiceReportTicketID' => (string) $auditTicketId,
    );

    $idx = 1;
    foreach ($checklistItems as $item) {
        $n = (string) $idx;
        $okRaw = $item['ok_status'];
        if ($okRaw === 'OK') {
            $legacyOk = 'Yes';
        } elseif ($okRaw === 'Not OK') {
            $legacyOk = 'No';
        } else {
            $legacyOk = '';
        }

        $imageFile = isset($item['response_image']) ? $item['response_image'] : '';
        $imageValue = $imageFile;
        if ($imageFile !== '' && $baseUrl !== '') {
            $imageValue = audit_ticket_checklist_image_url($imageFile, $baseUrl);
        }

        $payload[$n] = $legacyOk;
        $payload['remark_' . $n] = isset($item['remarks']) ? $item['remarks'] : '';
        $payload[$n . '_remark'] = $payload['remark_' . $n];
        $payload['value_' . $n] = isset($item['response_value']) ? $item['response_value'] : '';
        $payload[$n . '_value'] = $payload['value_' . $n];
        $payload['val_' . $n] = $payload['value_' . $n];
        $payload['image_' . $n] = $imageValue;
        $payload[$n . '_image'] = $imageValue;
        $idx++;
    }

    $reportFields = array(
        'ProblemReportedByClient' => isset($ticket['ProblemReportedByClient']) ? $ticket['ProblemReportedByClient'] : '',
        'Observation' => isset($ticket['AuditObservation']) ? $ticket['AuditObservation'] : '',
        'ActionTaken' => isset($ticket['ActionTaken']) ? $ticket['ActionTaken'] : '',
        'Remarks' => isset($ticket['GeneralRemarks']) ? $ticket['GeneralRemarks'] : '',
        'ClientRepresentative' => isset($ticket['ClientRepresentative']) ? $ticket['ClientRepresentative'] : '',
        'ClientRepresentativeContact' => isset($ticket['ClientRepresentativeContact']) ? $ticket['ClientRepresentativeContact'] : '',
        'ClientRepresentativeEmails' => isset($ticket['ClientRepresentativeEmail']) ? $ticket['ClientRepresentativeEmail'] : '',
        'ClientRepresentativeDesignation' => isset($ticket['ClientRepresentativeDesignation']) ? $ticket['ClientRepresentativeDesignation'] : '',
        'FloorName' => isset($ticket['ReportFloor']) ? $ticket['ReportFloor'] : '',
        'CreatedBy' => isset($ticket['CreatedBy']) ? $ticket['CreatedBy'] : '',
        'Latitude' => isset($ticket['ReportLatitude']) ? $ticket['ReportLatitude'] : null,
        'Longitude' => isset($ticket['ReportLongitude']) ? $ticket['ReportLongitude'] : null,
    );

    $signatureFile = isset($ticket['ClientSignature']) ? $ticket['ClientSignature'] : '';
    if ($signatureFile !== '') {
        $reportFields['ClientSignature'] = $baseUrl !== ''
            ? audit_ticket_signature_image_url($signatureFile, $baseUrl)
            : $signatureFile;
    } else {
        $reportFields['ClientSignature'] = '';
    }

    foreach ($reportFields as $key => $value) {
        $payload[$key] = $value;
    }

    $payload['userdata'] = array_merge($payload, $reportFields);
    $payload['userdetails'] = $payload['userdata'];

    return $payload;
}

function audit_ticket_format_checklist_field_for_mobile($checklistRow, $responseItem = array(), $baseUrl = '')
{
    require_once dirname(__DIR__) . '/audit/audit_helpers.php';

    $responseValue = isset($responseItem['response_value']) ? $responseItem['response_value'] : '';
    $okStatus = isset($responseItem['ok_status']) ? $responseItem['ok_status'] : 'Pending';
    $remarks = isset($responseItem['remarks']) ? $responseItem['remarks'] : '';
    $responseImage = isset($responseItem['response_image']) ? $responseItem['response_image'] : '';
    $responseImageUrl = audit_ticket_checklist_image_url($responseImage, $baseUrl);

    $formatted = audit_api_format_checklist($checklistRow, array(
        'response_value' => $responseValue,
        'compliance_status' => isset($responseItem['compliance_status']) ? $responseItem['compliance_status'] : 'Pending',
        'ok_status' => $okStatus,
        'filled_by' => isset($responseItem['filled_by']) ? $responseItem['filled_by'] : '',
        'filled_date' => isset($responseItem['filled_date']) ? $responseItem['filled_date'] : '',
        'filled_time' => isset($responseItem['filled_time']) ? $responseItem['filled_time'] : '',
        'response_remarks' => $remarks,
    ));

    $formatted['handover'] = array(
        'value' => $responseValue,
        'ok_status' => $okStatus,
        'remarks' => $remarks,
        'image' => $responseImage,
        'image_url' => $responseImageUrl,
        'ok_status_options' => audit_ticket_ok_status_options(),
        'requires_value' => true,
        'requires_ok_status' => true,
        'remarks_required_when_not_ok' => true,
    );

    $formatted['mobile_form'] = array(
        'checklist_id' => (int) $checklistRow['ID'],
        'sequence_index' => isset($responseItem['sequence_index']) ? (int) $responseItem['sequence_index'] : 0,
        'value_field' => array(
            'key' => 'ResponseValue',
            'label' => 'Measured / Observed Value',
            'field_meta' => $formatted['field_meta'],
            'current_value' => $responseValue,
        ),
        'ok_status_field' => array(
            'key' => 'OkStatus',
            'label' => 'Status',
            'input_type' => 'select',
            'options' => array(
                array('value' => 'OK', 'label' => 'OK'),
                array('value' => 'Not OK', 'label' => 'Not OK'),
            ),
            'required' => true,
            'current_value' => $okStatus === 'Pending' ? '' : $okStatus,
        ),
        'remarks_field' => array(
            'key' => 'Remarks',
            'label' => 'Remarks',
            'input_type' => 'textarea',
            'required' => ($okStatus === 'Not OK'),
            'placeholder' => 'Add remarks (required when status is Not OK)',
            'current_value' => $remarks,
        ),
        'image_field' => array(
            'key' => 'ResponseImage',
            'label' => 'Photo / Attachment',
            'input_type' => 'image',
            'required' => false,
            'current_value' => $responseImage,
            'current_url' => $responseImageUrl,
        ),
    );

    return $formatted;
}

function audit_ticket_validate_checklist_response_item($item, $checklistRow, $isDraft = false)
{
    $errors = array();
    $value = trim((string) (isset($item['ResponseValue']) ? $item['ResponseValue'] : (isset($item['value']) ? $item['value'] : '')));
    $rawOk = trim((string) (isset($item['OkStatus']) ? $item['OkStatus'] : (isset($item['ok_status']) ? $item['ok_status'] : '')));
    $okStatus = audit_ticket_normalize_ok_status($rawOk);
    $remarks = trim((string) (isset($item['Remarks']) ? $item['Remarks'] : ''));
    $image = trim((string) (isset($item['ResponseImage']) ? $item['ResponseImage'] : ''));

    if ($value === '' && $rawOk !== '') {
        $value = $rawOk;
    }

    $hasAny = ($value !== '' || $okStatus !== 'Pending' || $remarks !== '' || $image !== '');
    if ($isDraft && !$hasAny) {
        return array(
            'errors' => array(),
            'skip' => true,
            'response_value' => '',
            'ok_status' => 'Pending',
            'remarks' => '',
        );
    }

    if ($isDraft) {
        return array(
            'errors' => array(),
            'skip' => false,
            'response_value' => cleantext($value),
            'ok_status' => $okStatus,
            'remarks' => cleantext($remarks),
        );
    }

    if ($value === '') {
        $errors[] = 'Value is required for checkpoint: ' . $checklistRow['CheckpointName'];
    }
    if ($okStatus === 'Pending') {
        $errors[] = 'OK / Not OK status is required for checkpoint: ' . $checklistRow['CheckpointName'];
    }
    if ($okStatus === 'Not OK' && $remarks === '') {
        $errors[] = 'Remarks are required when status is Not OK for checkpoint: ' . $checklistRow['CheckpointName'];
    }

    return array(
        'errors' => $errors,
        'skip' => false,
        'response_value' => cleantext($value),
        'ok_status' => $okStatus,
        'remarks' => cleantext($remarks),
    );
}
