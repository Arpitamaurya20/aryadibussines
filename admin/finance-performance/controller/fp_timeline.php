<?php
/**
 * Expense details: the chronological audit timeline of a request, built from branch_expense_audit_log
 * (EntityType 'expense_request'), with readable before/after values.
 * Loaded by fp_requests.php.
 *
 * Everyone who may open a request sees who did what, when, why, and the before/after values of the business fields.
 * The 'audit' permission (within its scope) adds the technical record: IP address, device and every stored value,
 * plus the CSV export of the timeline.
 */

function fp_timeline_action_labels()
{
    return array(
        'create'            => 'Draft created',
        'update'            => 'Draft changed',
        'submit'            => 'Submitted for approval',
        'cancel'            => 'Cancelled',
        'attachment_add'    => 'Document added',
        'attachment_remove' => 'Document removed',
        'approve_stage'     => 'Stage approved',
        'approve'           => 'Approved (final)',
        'reject'            => 'Rejected',
        'return'            => 'Returned for changes',
        'escalate'          => 'Escalated (overdue)',
        'reopen'            => 'Reopened for changes',
        'audit_export'      => 'Audit timeline exported',
        'payment_record'    => 'Payment recorded',
        'payment_failed'    => 'Payment marked failed',
        'payment_reversed'  => 'Payment reversed',
        'settlement_create'         => 'Settlement draft created',
        'settlement_update'         => 'Settlement draft changed',
        'settlement_doc_add'        => 'Settlement document added',
        'settlement_doc_remove'     => 'Settlement document removed',
        'settlement_submit'         => 'Settlement submitted',
        'settlement_cancel'         => 'Settlement cancelled',
        'settlement_branch_confirm' => 'Settlement confirmed by branch',
        'settlement_return'         => 'Settlement returned for changes',
        'settlement_verify'         => 'Settlement verified by Finance',
        'settlement_refund'         => 'Cash returned by employee',
        'settlement_additional'     => 'Extra paid to employee',
        'settlement_txn_failed'     => 'Settlement entry marked failed',
        'settlement_txn_reversed'   => 'Settlement entry reversed',
        'settlement_close'          => 'Advance settled and closed',
        'recurring_create'          => 'Draft created from recurring rule',
    );
}

/**
 * Actions whose Remarks hold an approver's comment rather than a reason.
 */
function fp_timeline_comment_actions()
{
    return array('approve_stage', 'approve', 'reject', 'return', 'settlement_branch_confirm', 'settlement_return', 'settlement_verify');
}

/**
 * Actions whose Remarks are free remarks rather than a reason.
 */
function fp_timeline_remark_actions()
{
    return array('payment_record', 'settlement_refund', 'settlement_additional', 'settlement_close');
}

function fp_timeline_field_labels()
{
    return array(
        'ExpenseNumber'    => 'Expense no.',
        'Status'           => 'Status',
        'RequestType'      => 'Type',
        'CompanyID'        => 'Legal entity',
        'StateID'          => 'State',
        'OfficeID'         => 'Branch office',
        'EmployeeID'       => 'Requester',
        'Purpose'          => 'Purpose',
        'PeriodFrom'       => 'Period from',
        'PeriodTo'         => 'Period to',
        'IsRecurring'      => 'Recurring',
        'Remarks'          => 'Remarks',
        'TotalAmount'      => 'Total amount',
        'ItemCount'        => 'Number of lines',
        'Items'            => 'Line items',
        'PaymentStatus'    => 'Payment status',
        'SettlementStatus' => 'Settlement status',
        'Stage'            => 'Stage',
        'NextStage'        => 'Next stage',
        'DecidedAs'        => 'Decided as',
        'Round'            => 'Submission no.',
        'ApprovalPolicy'   => 'Approval policy',
        'Stages'           => 'Approval route',
        'BudgetWarnings'   => 'Budget warning',
        'DocumentType'     => 'Document type',
        'OriginalName'     => 'File',
        'Entries'          => 'Entries exported',
        'PaymentNumber'    => 'Payment no.',
        'Amount'           => 'Amount',
        'PaymentDate'      => 'Payment date',
        'Mode'             => 'Mode',
        'Reference'        => 'Reference',
        'Payee'            => 'Paid to',
        'PaidTotal'        => 'Paid so far',
        'Balance'          => 'Unpaid balance',
        'SharedReference'  => 'Shared bank transfer',
        'StatusReference'  => 'Bank return reference',
        'SettlementNumber' => 'Settlement no.',
        'ClaimStatus'      => 'Settlement status',
        'Notes'            => 'Notes',
        'ClaimedAmount'    => 'Amount claimed',
        'AcceptedAmount'   => 'Amount accepted',
        'LineCount'        => 'Number of lines',
        'SettlementLines'  => 'Expense lines',
        'LineDecisions'    => 'Line decisions',
        'LineNo'           => 'Line',
        'AdvancePaid'      => 'Advance paid out',
        'ExpensesAccepted' => 'Expenses accepted so far',
        'RefundReceived'   => 'Cash returned so far',
        'AdditionalPaid'   => 'Extra paid so far',
        'SettlementBalance' => 'Advance balance',
        'TxnNumber'        => 'Entry no.',
        'TxnType'          => 'Entry',
        'TxnDate'          => 'Date',
        'VendorRegistrationID' => 'Registered vendor',
        'VendorName'       => 'Vendor / payee',
        'VendorGSTIN'      => 'Vendor GSTIN',
        'VendorAccountRef' => 'Consumer / agreement no.',
        'DueDate'          => 'Due date',
        'RecurringRule'    => 'Recurring rule',
    );
}

/**
 * Fields shown as plain information on an entry rather than as a before/after change.
 */
function fp_timeline_info_fields()
{
    return array('Stage', 'NextStage', 'DecidedAs', 'Round', 'ApprovalPolicy', 'Stages', 'BudgetWarnings', 'Entries',
        'PaymentNumber', 'Amount', 'PaymentDate', 'Mode', 'Reference', 'Payee', 'PaidTotal', 'Balance', 'SharedReference', 'StatusReference',
        'SettlementNumber', 'ClaimedAmount', 'AcceptedAmount', 'LineCount', 'LineDecisions', 'AdvancePaid', 'ExpensesAccepted', 'RefundReceived',
        'AdditionalPaid', 'SettlementBalance', 'TxnNumber', 'TxnType', 'TxnDate', 'RecurringRule');
}

/**
 * Raw audit rows of a request, oldest first.
 */
function fp_timeline_rows($conn, $requestId)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT a.ID, a.Action, a.OldValues, a.NewValues, a.ChangedFields, a.Remarks, a.ActorUserID, a.ActorEmployeeID, a.ActorUserName,
                a.IPAddress, a.UserAgent, a.CreatedAt, e.Name AS ActorName
         FROM branch_expense_audit_log a
         LEFT JOIN employees e ON a.ActorEmployeeID > 0 AND e.ID = a.ActorEmployeeID
         WHERE a.EntityType = ? AND a.EntityID = ?
         ORDER BY a.ID',
        'si',
        array(FP_REQUEST_ENTITY, (int) $requestId)
    );
    foreach ($rows as &$row) {
        $row['OldValues'] = $row['OldValues'] === null ? null : json_decode($row['OldValues'], true);
        $row['NewValues'] = $row['NewValues'] === null ? null : json_decode($row['NewValues'], true);
        $row['ChangedFields'] = (string) $row['ChangedFields'] === '' ? array() : explode(',', $row['ChangedFields']);
    }
    unset($row);
    return $rows;
}

/**
 * Names for the IDs that appear in the stored values.
 */
function fp_timeline_lookups($conn, $rows)
{
    $ids = array('CompanyID' => array(), 'StateID' => array(), 'OfficeID' => array(), 'EmployeeID' => array(), 'Category' => array(), 'VendorRegistrationID' => array());
    foreach ($rows as $row) {
        foreach (array($row['OldValues'], $row['NewValues']) as $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach (array('CompanyID', 'StateID', 'OfficeID', 'EmployeeID', 'VendorRegistrationID') as $field) {
                if (isset($values[$field]) && is_scalar($values[$field])) {
                    $ids[$field][] = $values[$field];
                }
            }
            foreach (array('Items', 'SettlementLines') as $field) {
                if (isset($values[$field]) && is_string($values[$field])) {
                    foreach (explode("\n", $values[$field]) as $line) {
                        $ids['Category'][] = (int) strtok($line, ' ');
                    }
                }
            }
        }
    }
    $lookups = array();
    $queries = array(
        'CompanyID'  => 'SELECT ID, CompanyName AS Name FROM quote_company_details WHERE ID IN (%s)',
        'StateID'    => 'SELECT ID, StateName AS Name FROM state WHERE ID IN (%s)',
        'OfficeID'   => 'SELECT ID, CONCAT(OfficeName, \' (\', OfficeCode, \')\') AS Name FROM branch_expense_offices WHERE ID IN (%s)',
        'EmployeeID' => 'SELECT ID, Name FROM employees WHERE ID IN (%s)',
        'Category'   => 'SELECT c.ID, CONCAT(COALESCE(CONCAT(p.CategoryName, \' > \'), \'\'), c.CategoryName) AS Name
                         FROM branch_expense_categories c LEFT JOIN branch_expense_categories p ON p.ID = c.ParentCategoryID WHERE c.ID IN (%s)',
    );
    foreach ($queries as $key => $sql) {
        $lookups[$key] = array();
        $list = fp_unique_ints($ids[$key]);
        if ($list) {
            foreach (fp_db_rows($conn, sprintf($sql, implode(',', $list))) as $row) {
                $lookups[$key][(int) $row['ID']] = (string) $row['Name'];
            }
        }
    }
    $lookups['VendorRegistrationID'] = array();
    foreach (fp_vendor_labels($conn, $ids['VendorRegistrationID']) as $id => $label) {
        $lookups['VendorRegistrationID'][$id] = $label['Name'] . ' (' . $label['Code'] . ')';
    }
    return $lookups;
}

function fp_timeline_date($value)
{
    $time = strtotime((string) $value);
    return $time ? date('d M Y', $time) : (string) $value;
}

/**
 * One stored line ("category | description | date | vendor | invoice no. | invoice date | amount | tax") in words.
 */
function fp_timeline_line($line, $lookups)
{
    $parts = explode(' | ', $line);
    if (count($parts) < 8) {
        return $line;
    }
    $tail = array_slice($parts, -6);
    $description = implode(' | ', array_slice($parts, 1, count($parts) - 7));
    list($date, $vendor, $invoice, , $amount) = $tail;
    $category = $lookups['Category'][(int) $parts[0]] ?? 'Category #' . (int) $parts[0];
    $text = $category . ': ' . $description . ($date !== '' ? ', ' . fp_timeline_date($date) : '') . ', INR ' . number_format((float) $amount, 2);
    $extra = array_filter(array($vendor, $invoice !== '' ? 'invoice ' . $invoice : ''));
    return $text . ($extra ? ' (' . implode(', ', $extra) . ')' : '');
}

function fp_timeline_value($field, $value, $lookups)
{
    if ($value === null || $value === '' || $value === array()) {
        return '-';
    }
    switch ($field) {
        case 'Status':
            return fp_request_statuses()[$value] ?? (string) $value;
        case 'RequestType':
            return fp_request_types()[$value] ?? (string) $value;
        case 'CompanyID':
        case 'StateID':
        case 'OfficeID':
        case 'EmployeeID':
        case 'VendorRegistrationID':
            return $lookups[$field][(int) $value] ?? '#' . (int) $value;
        case 'IsRecurring':
            return (int) $value === 1 ? 'Yes' : 'No';
        case 'TotalAmount':
        case 'Amount':
        case 'PaidTotal':
        case 'Balance':
        case 'ClaimedAmount':
        case 'AcceptedAmount':
        case 'AdvancePaid':
        case 'ExpensesAccepted':
        case 'RefundReceived':
        case 'AdditionalPaid':
            return 'INR ' . number_format((float) $value, 2);
        case 'SettlementBalance':
            return fp_settlement_balance_text(fp_decimal_to_paise(ltrim((string) $value, '-')) * (strpos((string) $value, '-') === 0 ? -1 : 1));
        case 'PaymentStatus':
            return fp_payment_request_statuses()[$value] ?? ucwords(str_replace('_', ' ', (string) $value));
        case 'SettlementStatus':
            return fp_settlement_request_statuses()[$value] ?? ucwords(str_replace('_', ' ', (string) $value));
        case 'ClaimStatus':
            return fp_settlement_statuses()[$value] ?? (string) $value;
        case 'PeriodFrom':
        case 'PeriodTo':
        case 'PaymentDate':
        case 'TxnDate':
        case 'DueDate':
            return fp_timeline_date($value);
        case 'DocumentType':
            return fp_document_type_label($value);
        case 'Items':
        case 'SettlementLines':
            return implode("\n", array_map(function ($line) use ($lookups) {
                return fp_timeline_line($line, $lookups);
            }, explode("\n", (string) $value)));
    }
    if (is_array($value)) {
        return implode("\n", array_map(function ($item) {
            return is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE);
        }, $value));
    }
    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    }
    return (string) $value;
}

/**
 * Lines removed and added between two stored Items values (each line compared as a whole).
 */
function fp_timeline_line_diff($before, $after, $lookups)
{
    $old = $before === null || $before === '' ? array() : explode("\n", (string) $before);
    $new = $after === null || $after === '' ? array() : explode("\n", (string) $after);
    $removed = $old;
    $added = array();
    foreach ($new as $line) {
        $index = array_search($line, $removed, true);
        if ($index === false) {
            $added[] = $line;
        } else {
            unset($removed[$index]);
        }
    }
    $words = function ($lines) use ($lookups) {
        return $lines ? implode("\n", array_map(function ($line) use ($lookups) {
            return fp_timeline_line($line, $lookups);
        }, $lines)) : '-';
    };
    return array($words(array_values($removed)), $words($added));
}

/**
 * Readable before/after changes and information of one audit row.
 * Returns array(changes => [[label, before, after]], details => [[label, value]]).
 */
function fp_timeline_describe($row, $lookups, $canSeeDocs)
{
    $labels = fp_timeline_field_labels();
    $old = is_array($row['OldValues']) ? $row['OldValues'] : array();
    $new = is_array($row['NewValues']) ? $row['NewValues'] : array();
    $changes = array();
    $details = array();
    $label = function ($field) use ($labels) {
        return $labels[$field] ?? ucfirst(trim(preg_replace('/([A-Z])/', ' $1', $field)));
    };

    switch ($row['Action']) {
        case 'create':
        case 'recurring_create':
            if (array_key_exists('RecurringRule', $new)) {
                $details[] = array($label('RecurringRule'), (string) $new['RecurringRule']);
            }
            foreach (array('RequestType', 'OfficeID', 'Purpose', 'VendorName', 'DueDate', 'TotalAmount', 'Items') as $field) {
                if (array_key_exists($field, $new) && $new[$field] !== '' && $new[$field] !== null) {
                    $changes[] = array($label($field), '-', fp_timeline_value($field, $new[$field], $lookups));
                }
            }
            break;

        case 'settlement_create':
            foreach (array('SettlementNumber', 'ClaimedAmount', 'Notes', 'SettlementLines') as $field) {
                if (array_key_exists($field, $new) && $new[$field] !== '') {
                    $changes[] = array($label($field), '-', fp_timeline_value($field, $new[$field], $lookups));
                }
            }
            break;

        case 'update':
        case 'settlement_update':
            $fields = $row['ChangedFields'] ?: array_keys(array_diff_assoc(array_map('json_encode', $new), array_map('json_encode', $old)));
            if ($row['Action'] === 'settlement_update') {
                $details[] = array($label('SettlementNumber'), (string) ($new['SettlementNumber'] ?? ''));
            }
            foreach ($fields as $field) {
                if ($field === 'Items' || $field === 'SettlementLines') {
                    list($removed, $added) = fp_timeline_line_diff($old[$field] ?? '', $new[$field] ?? '', $lookups);
                    $changes[] = array($label($field), $removed === '-' ? '-' : 'Removed or changed:' . "\n" . $removed, $added === '-' ? '-' : 'Added or changed:' . "\n" . $added);
                } else {
                    $changes[] = array($label($field), fp_timeline_value($field, $old[$field] ?? null, $lookups), fp_timeline_value($field, $new[$field] ?? null, $lookups));
                }
            }
            break;

        case 'attachment_add':
        case 'attachment_remove':
        case 'settlement_doc_add':
        case 'settlement_doc_remove':
            $values = $new ?: $old;
            $document = fp_document_type_label($values['DocumentType'] ?? '');
            if ($canSeeDocs && !empty($values['OriginalName'])) {
                $document .= ': ' . $values['OriginalName'];
            }
            if (!empty($values['SettlementNumber'])) {
                $details[] = array($label('SettlementNumber'), (string) $values['SettlementNumber']);
            }
            if (!empty($values['LineNo'])) {
                $details[] = array($label('LineNo'), (string) $values['LineNo']);
            }
            $changes[] = $new ? array('Document', '-', $document) : array('Document', $document, 'Removed (file kept for audit)');
            break;

        default:
            if (array_key_exists('Status', $new) && (string) ($old['Status'] ?? '') !== (string) $new['Status']) {
                $changes[] = array('Status', fp_timeline_value('Status', $old['Status'] ?? null, $lookups), fp_timeline_value('Status', $new['Status'], $lookups));
            }
            foreach ($new as $field => $value) {
                if ($field === 'Status') {
                    continue;
                }
                if (in_array($field, fp_timeline_info_fields(), true)) {
                    $details[] = array($label($field), fp_timeline_value($field, $value, $lookups));
                } elseif (!array_key_exists($field, $old) || json_encode($old[$field]) !== json_encode($value)) {
                    $changes[] = array($label($field), fp_timeline_value($field, $old[$field] ?? null, $lookups), fp_timeline_value($field, $value, $lookups));
                }
            }
    }
    return array('changes' => $changes, 'details' => $details);
}

/**
 * The request's timeline, oldest first. $technical adds IP address, device and the stored values.
 */
function fp_timeline_for_request($conn, $requestId, $technical, $canSeeDocs)
{
    $rows = fp_timeline_rows($conn, $requestId);
    $lookups = fp_timeline_lookups($conn, $rows);
    $labels = fp_timeline_action_labels();
    $entries = array();
    foreach ($rows as $row) {
        if ($row['Action'] === 'audit_export' && !$technical) {
            continue;
        }
        $described = fp_timeline_describe($row, $lookups, $canSeeDocs);
        $entry = array(
            'ID'        => (int) $row['ID'],
            'When'      => $row['CreatedAt'],
            'Actor'     => $row['ActorName'] !== null && $row['ActorName'] !== '' ? $row['ActorName'] : $row['ActorUserName'],
            'UserName'  => $row['ActorUserName'],
            'Action'    => $row['Action'],
            'Label'     => $labels[$row['Action']] ?? ucfirst(str_replace('_', ' ', $row['Action'])),
            'NoteLabel' => in_array($row['Action'], fp_timeline_comment_actions(), true) ? 'Comment' : (in_array($row['Action'], fp_timeline_remark_actions(), true) ? 'Remarks' : 'Reason'),
            'Note'      => (string) $row['Remarks'],
            'Changes'   => $described['changes'],
            'Details'   => $described['details'],
        );
        if ($technical) {
            $entry['IPAddress'] = $row['IPAddress'];
            $entry['Device'] = $row['UserAgent'];
            $entry['StoredBefore'] = $row['OldValues'];
            $entry['StoredAfter'] = $row['NewValues'];
            if (!$canSeeDocs) {
                unset($entry['StoredBefore']['OriginalName'], $entry['StoredAfter']['OriginalName']);
            }
        }
        $entries[] = $entry;
    }
    return $entries;
}

/**
 * Documents removed from the request (kept for audit), oldest first.
 */
function fp_timeline_removed_documents($conn, $requestId)
{
    return fp_db_rows(
        $conn,
        'SELECT a.ID, a.DocumentType, a.OriginalName, a.SizeBytes, a.UploadedAt, a.DeletedAt, i.LineNo, COALESCE(e.Name, u.UserName, \'\') AS DeletedByName
         FROM branch_expense_attachments a
         LEFT JOIN branch_expense_items i ON i.ID = a.ItemID
         LEFT JOIN users u ON a.DeletedByUserID > 0 AND u.UserID = a.DeletedByUserID
         LEFT JOIN employees e ON u.EmployeeID > 0 AND e.ID = u.EmployeeID
         WHERE a.RequestID = ? AND a.IsDeleted = 1
         ORDER BY a.DeletedAt, a.ID',
        'i',
        array((int) $requestId)
    );
}

/**
 * Whether the actor may see the technical audit record of the request.
 */
function fp_timeline_can_audit($actor, $request)
{
    return fp_record_in_scope($actor, 'audit', fp_request_scope_record($request));
}

/**
 * Latest audit entry per request: [RequestID => array(Action, Label, ActorUserName, CreatedAt)].
 */
function fp_timeline_last_activity($conn, $requestIds)
{
    $ids = fp_unique_ints($requestIds);
    if (!$ids) {
        return array();
    }
    $labels = fp_timeline_action_labels();
    $result = array();
    foreach (fp_db_rows(
        $conn,
        'SELECT a.EntityID, a.Action, a.ActorUserName, a.CreatedAt, e.Name AS ActorName
         FROM branch_expense_audit_log a
         INNER JOIN (SELECT EntityID, MAX(ID) AS LastID FROM branch_expense_audit_log
                     WHERE EntityType = ? AND Action <> \'audit_export\' AND EntityID IN (' . implode(',', $ids) . ') GROUP BY EntityID) m ON m.LastID = a.ID
         LEFT JOIN employees e ON a.ActorEmployeeID > 0 AND e.ID = a.ActorEmployeeID',
        's',
        array(FP_REQUEST_ENTITY)
    ) as $row) {
        $result[(int) $row['EntityID']] = array(
            'Label' => $labels[$row['Action']] ?? ucfirst(str_replace('_', ' ', $row['Action'])),
            'By'    => $row['ActorName'] !== null && $row['ActorName'] !== '' ? $row['ActorName'] : $row['ActorUserName'],
            'At'    => $row['CreatedAt'],
        );
    }
    return $result;
}

/**
 * Streams the timeline as CSV: one row per change (or one row for an entry without changes).
 */
function fp_timeline_export_csv($conn, $actor, $requestId)
{
    $request = $requestId > 0 ? fp_request_get_detail($conn, $requestId) : null;
    $visible = $request && (fp_request_can_view($actor, $request) || fp_approval_is_involved($conn, $actor, $request));
    if (!$visible || !fp_timeline_can_audit($actor, $request)) {
        fp_json_response(array('error' => true, 'message' => 'You do not have audit access to this request.'), 403);
    }
    $entries = fp_timeline_for_request($conn, $requestId, true, fp_documents_can_view($actor, $request));
    $cell = function ($value) {
        $value = str_replace("\n", ' / ', (string) $value);
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    };
    fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $requestId, 'audit_export', null, array('Entries' => count($entries)));

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit-' . preg_replace('/[^A-Za-z0-9-]/', '-', $request['ExpenseNumber']) . '-' . date('Ymd-His') . '.csv"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('Expense No.', 'Entry', 'When', 'By', 'Login', 'Action', 'Reason / comment', 'Field', 'Before', 'After', 'Details', 'IP address', 'Device'));
    foreach ($entries as $entry) {
        $details = implode('; ', array_map(function ($detail) {
            return $detail[0] . ': ' . $detail[1];
        }, $entry['Details']));
        $changes = $entry['Changes'] ?: array(array('', '', ''));
        foreach ($changes as $change) {
            fputcsv($out, array_map($cell, array(
                $request['ExpenseNumber'], $entry['ID'], $entry['When'], $entry['Actor'], $entry['UserName'], $entry['Label'], $entry['Note'],
                $change[0], $change[1], $change[2], $details, $entry['IPAddress'], $entry['Device'],
            )));
        }
    }
    fclose($out);
    exit;
}
