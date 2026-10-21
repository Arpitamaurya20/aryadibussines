<?php
/**
 * Expense documents: document types, mandatory-document rules and who may see attachments.
 * Loaded by fp_requests.php.
 */

/**
 * 'proof' types satisfy a category's "receipt required" rule.
 */
function fp_document_types()
{
    return array(
        'receipt'   => array('label' => 'Receipt',                   'proof' => true),
        'bill'      => array('label' => 'Bill',                      'proof' => true),
        'invoice'   => array('label' => 'Tax invoice',               'proof' => true),
        'quotation' => array('label' => 'Quotation',                 'proof' => false),
        'other'     => array('label' => 'Other supporting document', 'proof' => false),
    );
}

function fp_document_type_label($type)
{
    $types = fp_document_types();
    return isset($types[$type]) ? $types[$type]['label'] : (string) $type;
}

function fp_document_proof_types()
{
    return array_keys(array_filter(fp_document_types(), function ($type) {
        return $type['proof'];
    }));
}

/**
 * Documents a line needs before its request can be submitted.
 * $item: Amount plus the line category's ReceiptRequired and QuotationRequiredAbove.
 * Returns a list of array('key', 'label', 'types' => accepted document types).
 */
function fp_document_requirements($requestType, $item)
{
    $rules = array();
    if ($requestType === 'direct') {
        $rules[] = array('key' => 'invoice', 'label' => 'Vendor tax invoice', 'types' => array('invoice'));
    } elseif ($requestType !== 'advance' && (int) $item['ReceiptRequired'] === 1) {
        $rules[] = array('key' => 'proof', 'label' => 'Receipt, bill or invoice', 'types' => fp_document_proof_types());
    }
    $threshold = $item['QuotationRequiredAbove'] ?? null;
    if ($threshold !== null && $threshold !== '' && fp_decimal_to_paise($item['Amount']) > fp_decimal_to_paise($threshold)) {
        $rules[] = array(
            'key'   => 'quotation',
            'label' => 'Quotation (line above INR ' . number_format((float) $threshold, 2) . ')',
            'types' => array('quotation'),
        );
    }
    return $rules;
}

/**
 * Requirement status for each line, keyed like $items. Only documents linked to the line count.
 */
function fp_document_checklist($requestType, $items, $attachments)
{
    $typesByItem = array();
    foreach ($attachments as $attachment) {
        if ((int) $attachment['ItemID'] > 0) {
            $typesByItem[(int) $attachment['ItemID']][] = $attachment['DocumentType'];
        }
    }
    $checklist = array();
    foreach ($items as $index => $item) {
        $attached = $typesByItem[(int) $item['ID']] ?? array();
        $rules = array();
        foreach (fp_document_requirements($requestType, $item) as $rule) {
            $rule['met'] = (bool) array_intersect($rule['types'], $attached);
            $rules[] = $rule;
        }
        $checklist[$index] = $rules;
    }
    return $checklist;
}

/**
 * Submission errors keyed items.N.Documents for lines missing a mandatory document.
 */
function fp_document_missing_errors($requestType, $items, $attachments)
{
    $errors = array();
    foreach (fp_document_checklist($requestType, $items, $attachments) as $index => $rules) {
        $missing = array();
        foreach ($rules as $rule) {
            if (!$rule['met']) {
                $missing[] = $rule['label'];
            }
        }
        if ($missing) {
            $errors['items.' . $index . '.Documents'] = 'Line ' . (int) $items[$index]['LineNo'] . ': attach ' . implode(' and ', $missing) . '.';
        }
    }
    return $errors;
}

/**
 * Attachments are limited to the requester, approvers and Finance (the 'documents' permission),
 * each within their own scope. Drafts stay private to the requester.
 */
function fp_documents_can_view($actor, $request)
{
    $isOwner = (int) $actor['employee_id'] > 0 && (int) $request['EmployeeID'] === (int) $actor['employee_id'];
    if ($request['Status'] === 'draft' && !$isOwner) {
        return false;
    }
    return fp_record_in_scope($actor, 'documents', fp_request_scope_record($request));
}

/**
 * The detected MIME type must match the file's real structure (PDF header, decodable image header).
 */
function fp_document_content_ok($path, $mime)
{
    if ($mime === 'application/pdf') {
        $handle = fopen($path, 'rb');
        $head = $handle ? (string) fread($handle, 1024) : '';
        if ($handle) {
            fclose($handle);
        }
        return strpos($head, '%PDF-') !== false;
    }
    $imageTypes = array('image/jpeg' => IMAGETYPE_JPEG, 'image/png' => IMAGETYPE_PNG, 'image/webp' => IMAGETYPE_WEBP);
    $info = @getimagesize($path);
    return isset($imageTypes[$mime]) && $info !== false && $info[2] === $imageTypes[$mime] && $info[0] > 0 && $info[1] > 0;
}
