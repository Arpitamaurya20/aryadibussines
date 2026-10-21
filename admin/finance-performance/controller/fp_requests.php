<?php
/**
 * Expense booking: requests, items and attachments.
 * Requires fp_access.php and fp_audit.php.
 *
 * CompanyID, StateID and OfficeID are always derived on the server from an office the requester
 * is allowed to use; values posted by the browser for those fields are ignored.
 */

require_once __DIR__ . '/fp_documents.php';
require_once __DIR__ . '/fp_budgets.php';
require_once __DIR__ . '/fp_policies.php';
require_once __DIR__ . '/fp_approvals.php';
require_once __DIR__ . '/fp_timeline.php';
require_once __DIR__ . '/fp_payments.php';
require_once __DIR__ . '/fp_settlements.php';
require_once __DIR__ . '/fp_vendors.php';
require_once __DIR__ . '/fp_direct.php';
require_once __DIR__ . '/fp_recurring.php';

const FP_REQUEST_ENTITY = 'expense_request';
const FP_REQUEST_MAX_ITEMS = 50;
const FP_REQUEST_MAX_ATTACHMENTS = 20;
const FP_REQUEST_MAX_AMOUNT_PAISE = 9999999999; // 9,99,99,999.99

function fp_request_types()
{
    return array(
        'advance'       => 'Cash advance',
        'reimbursement' => 'Reimbursement',
        'direct'        => 'Direct expense',
    );
}

function fp_request_statuses()
{
    return array(
        'draft'     => 'Draft',
        'submitted' => 'Submitted',
        'returned'  => 'Returned',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
    );
}

function fp_request_editable_statuses()
{
    return array('draft', 'returned');
}

function fp_attachment_types()
{
    return array(
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    );
}

/**
 * Set the FP_ATTACHMENT_DIR environment variable to keep files outside the web root
 * (needed on servers that ignore storage/.htaccess, such as nginx).
 */
function fp_attachment_storage_dir()
{
    $custom = getenv('FP_ATTACHMENT_DIR');
    return is_string($custom) && $custom !== '' ? rtrim($custom, '/\\') : dirname(__DIR__) . '/storage/attachments';
}

function fp_ini_bytes($value)
{
    $value = trim((string) $value);
    $number = (int) $value;
    switch (strtolower(substr($value, -1))) {
        case 'g': return $number * 1073741824;
        case 'm': return $number * 1048576;
        case 'k': return $number * 1024;
    }
    return $number;
}

function fp_attachment_max_bytes()
{
    $limits = array(5 * 1048576);
    foreach (array('upload_max_filesize', 'post_max_size') as $key) {
        $bytes = fp_ini_bytes(ini_get($key));
        if ($bytes > 0) {
            $limits[] = $bytes;
        }
    }
    return min($limits);
}

/* ------------------------------------------------------------------
   Parsing helpers
------------------------------------------------------------------ */

function fp_parse_date($value)
{
    if ($value === '') {
        return null;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return ($date && $date->format('Y-m-d') === $value) ? $value : false;
}

/**
 * "1234.5" => 123450 paise; false when not a valid non-negative amount with up to 2 decimals.
 */
function fp_parse_paise($value)
{
    $value = str_replace(',', '', trim((string) $value));
    if (!preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/', $value, $m)) {
        return false;
    }
    return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
}

function fp_paise_to_decimal($paise)
{
    return sprintf('%d.%02d', intdiv($paise, 100), $paise % 100);
}

function fp_decimal_to_paise($decimal)
{
    $paise = fp_parse_paise($decimal);
    return $paise === false ? 0 : $paise;
}

function fp_financial_year_label($date)
{
    $year = (int) date('Y', strtotime($date));
    $start = (int) date('n', strtotime($date)) >= 4 ? $year : $year - 1;
    return $start . '-' . substr((string) ($start + 1), -2);
}

/* ------------------------------------------------------------------
   Lookups
------------------------------------------------------------------ */

/**
 * Offices the actor may raise requests against, with derived company/state names.
 */
function fp_request_offices_for_actor($conn, $actor)
{
    $allowed = fp_requester_offices($actor);
    if ($allowed !== true && !$allowed) {
        return array();
    }
    $where = $allowed === true ? '' : ' AND o.ID IN (' . implode(',', fp_unique_ints($allowed)) . ')';
    $rows = fp_db_rows(
        $conn,
        'SELECT o.ID, o.OfficeCode, o.OfficeName, o.CompanyID, o.StateID, q.CompanyName, s.StateName, c.CityName
         FROM branch_expense_offices o
         INNER JOIN quote_company_details q ON q.ID = o.CompanyID
         INNER JOIN state s ON s.ID = o.StateID
         LEFT JOIN citydata c ON c.CityId = o.CityID
         WHERE o.IsActive = 1' . $where . '
         ORDER BY o.OfficeName'
    );
    $offices = array();
    foreach ($rows as $row) {
        $offices[(int) $row['ID']] = array(
            'ID'          => (int) $row['ID'],
            'OfficeCode'  => $row['OfficeCode'],
            'OfficeName'  => $row['OfficeName'],
            'CompanyID'   => (int) $row['CompanyID'],
            'CompanyName' => $row['CompanyName'],
            'StateID'     => (int) $row['StateID'],
            'StateName'   => $row['StateName'],
            'CityName'    => (string) $row['CityName'],
        );
    }
    return $offices;
}

/**
 * Active leaf categories (a category with active sub-categories must be booked against a sub-category).
 */
function fp_request_categories($conn)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT c.ID, c.CategoryCode, c.CategoryName, c.CompanyID, c.ReceiptRequired, c.QuotationRequiredAbove, c.AdvanceAllowed,
                c.ReimbursementAllowed, c.RecurringAllowed, p.CategoryName AS ParentName
         FROM branch_expense_categories c
         LEFT JOIN branch_expense_categories p ON p.ID = c.ParentCategoryID
         WHERE c.IsActive = 1
           AND (c.ParentCategoryID IS NULL OR p.IsActive = 1)
           AND NOT EXISTS (SELECT 1 FROM branch_expense_categories ch WHERE ch.ParentCategoryID = c.ID AND ch.IsActive = 1)
         ORDER BY COALESCE(p.SortOrder, c.SortOrder), COALESCE(p.CategoryName, c.CategoryName), c.SortOrder, c.CategoryName'
    );
    $categories = array();
    foreach ($rows as $row) {
        $categories[(int) $row['ID']] = array(
            'ID'                   => (int) $row['ID'],
            'CategoryCode'         => $row['CategoryCode'],
            'Label'                => ($row['ParentName'] ? $row['ParentName'] . ' > ' : '') . $row['CategoryName'],
            'CompanyID'            => (int) $row['CompanyID'],
            'ReceiptRequired'      => (int) $row['ReceiptRequired'],
            'QuotationRequiredAbove' => $row['QuotationRequiredAbove'],
            'AdvanceAllowed'       => (int) $row['AdvanceAllowed'],
            'ReimbursementAllowed' => (int) $row['ReimbursementAllowed'],
            'RecurringAllowed'     => (int) $row['RecurringAllowed'],
        );
    }
    return $categories;
}

function fp_category_allows_type($category, $type)
{
    if ($type === 'advance') {
        return $category['AdvanceAllowed'] === 1;
    }
    if ($type === 'reimbursement') {
        return $category['ReimbursementAllowed'] === 1;
    }
    return true;
}

/* ------------------------------------------------------------------
   Loading
------------------------------------------------------------------ */

function fp_request_get($conn, $id, $lock = '')
{
    $suffix = $lock === 'update' ? ' FOR UPDATE' : '';
    $rows = fp_db_rows(
        $conn,
        'SELECT r.* FROM branch_expense_requests r WHERE r.ID = ?' . $suffix,
        'i',
        array((int) $id)
    );
    return $rows ? $rows[0] : null;
}

function fp_request_get_detail($conn, $id)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT r.*, o.OfficeCode, o.OfficeName, q.CompanyName, s.StateName, e.Name AS EmployeeName, e.EmployeeNumber
         FROM branch_expense_requests r
         INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
         LEFT JOIN quote_company_details q ON q.ID = r.CompanyID
         LEFT JOIN state s ON s.ID = r.StateID
         LEFT JOIN employees e ON e.ID = r.EmployeeID
         WHERE r.ID = ?',
        'i',
        array((int) $id)
    );
    return $rows ? $rows[0] : null;
}

function fp_request_items($conn, $requestId)
{
    return fp_db_rows(
        $conn,
        'SELECT i.ID, i.LineNo, i.CategoryID, i.Description, i.ExpenseDate, i.VendorName, i.VendorGSTIN,
                i.InvoiceNumber, i.InvoiceDate, i.Amount, i.TaxAmount,
                c.CategoryCode, c.CategoryName, c.ReceiptRequired, c.QuotationRequiredAbove, p.CategoryName AS ParentCategoryName
         FROM branch_expense_items i
         INNER JOIN branch_expense_categories c ON c.ID = i.CategoryID
         LEFT JOIN branch_expense_categories p ON p.ID = c.ParentCategoryID
         WHERE i.RequestID = ?
         ORDER BY i.LineNo',
        'i',
        array((int) $requestId)
    );
}

function fp_request_attachments($conn, $requestId)
{
    return fp_db_rows(
        $conn,
        'SELECT a.ID, a.ItemID, a.DocumentType, a.Notes, a.OriginalName, a.MimeType, a.SizeBytes, a.UploadedAt, a.UploadedByEmployeeID,
                i.LineNo, COALESCE(e.Name, u.UserName, \'\') AS UploadedByName
         FROM branch_expense_attachments a
         LEFT JOIN branch_expense_items i ON i.ID = a.ItemID
         LEFT JOIN employees e ON a.UploadedByEmployeeID > 0 AND e.ID = a.UploadedByEmployeeID
         LEFT JOIN users u ON a.UploadedByUserID > 0 AND u.UserID = a.UploadedByUserID
         WHERE a.RequestID = ? AND a.IsDeleted = 0
         ORDER BY a.ID',
        'i',
        array((int) $requestId)
    );
}

function fp_request_scope_record($request, $stateName = '')
{
    return array(
        'employee_id'            => (int) $request['EmployeeID'],
        'created_by_employee_id' => (int) $request['CreatedByEmployeeID'],
        'state_name'             => $stateName !== '' ? $stateName : (string) ($request['StateName'] ?? ''),
        'office_id'              => (int) $request['OfficeID'],
    );
}

/**
 * Drafts stay private to their requester; other records follow the view scope.
 */
function fp_request_can_view($actor, $request)
{
    $isOwner = (int) $actor['employee_id'] > 0 && (int) $request['EmployeeID'] === (int) $actor['employee_id'];
    if ($request['Status'] === 'draft' && !$isOwner) {
        return false;
    }
    return fp_record_in_scope($actor, 'view', fp_request_scope_record($request));
}

function fp_request_can_edit($actor, $request)
{
    return fp_can($actor, 'edit')
        && (int) $actor['employee_id'] > 0
        && (int) $request['EmployeeID'] === (int) $actor['employee_id']
        && in_array($request['Status'], fp_request_editable_statuses(), true);
}

/* ------------------------------------------------------------------
   Validation
------------------------------------------------------------------ */

function fp_request_input($post)
{
    $items = array();
    $postedItems = isset($post['items']) && is_array($post['items']) ? $post['items'] : array();
    foreach (array_values($postedItems) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $items[] = array(
            'ItemID'        => fp_input_int($item, 'ItemID'),
            'CategoryID'    => fp_input_int($item, 'CategoryID'),
            'Description'   => preg_replace('/\s+/u', ' ', fp_input_string($item, 'Description')),
            'ExpenseDate'   => fp_input_string($item, 'ExpenseDate'),
            'VendorName'    => preg_replace('/\s+/u', ' ', fp_input_string($item, 'VendorName')),
            'VendorGSTIN'   => strtoupper(fp_input_string($item, 'VendorGSTIN')),
            'InvoiceNumber' => fp_input_string($item, 'InvoiceNumber'),
            'InvoiceDate'   => fp_input_string($item, 'InvoiceDate'),
            'Amount'        => fp_input_string($item, 'Amount'),
            'TaxAmount'     => fp_input_string($item, 'TaxAmount'),
        );
    }
    return array(
        'ID'          => fp_input_int($post, 'ID'),
        'RowVersion'  => fp_input_int($post, 'RowVersion'),
        'RequestType' => fp_input_string($post, 'RequestType'),
        'OfficeID'    => fp_input_int($post, 'OfficeID'),
        'Purpose'     => fp_input_string($post, 'Purpose'),
        'PeriodFrom'  => fp_input_string($post, 'PeriodFrom'),
        'PeriodTo'    => fp_input_string($post, 'PeriodTo'),
        'IsRecurring' => fp_input_string($post, 'IsRecurring') === '1' ? 1 : 0,
        'Remarks'     => fp_input_string($post, 'Remarks'),
        'DueDate'     => fp_input_string($post, 'DueDate'),
        'items'       => $items,
    ) + fp_vendor_input($post);
}

/**
 * Validates a request against the actor's offices and the live category rules.
 * Returns [errors, clean] where clean holds normalised values (dates, paise, derived office).
 */
function fp_request_validate($conn, $actor, $in, $offices, $categories)
{
    $errors = array();
    $clean = array('items' => array());
    $types = fp_request_types();
    $today = date('Y-m-d');

    if (!isset($types[$in['RequestType']])) {
        $errors['RequestType'] = 'Choose the request type.';
    }
    $type = $in['RequestType'];

    $officeId = $in['OfficeID'];
    if ($officeId <= 0 && count($offices) === 1) {
        $officeId = (int) key($offices);
    }
    if (!isset($offices[$officeId])) {
        $errors['OfficeID'] = 'Choose one of your branch offices.';
        $office = null;
    } else {
        $office = $offices[$officeId];
        $clean['OfficeID'] = $office['ID'];
        $clean['CompanyID'] = $office['CompanyID'];
        $clean['StateID'] = $office['StateID'];
    }

    $purposeLength = mb_strlen($in['Purpose']);
    if ($purposeLength < 5 || $purposeLength > 1000) {
        $errors['Purpose'] = 'Describe the purpose in 5-1000 characters.';
    }
    if (mb_strlen($in['Remarks']) > 1000) {
        $errors['Remarks'] = 'Remarks can be at most 1000 characters.';
    }

    $from = fp_parse_date($in['PeriodFrom']);
    $to = fp_parse_date($in['PeriodTo']);
    if ($from === false) {
        $errors['PeriodFrom'] = 'Enter a valid date.';
    }
    if ($to === false) {
        $errors['PeriodTo'] = 'Enter a valid date.';
    }
    if ($from !== false && $to !== false) {
        if (($from === null) !== ($to === null)) {
            $errors[$from === null ? 'PeriodFrom' : 'PeriodTo'] = 'Enter both period dates or neither.';
        } elseif ($from !== null && $from > $to) {
            $errors['PeriodTo'] = 'The period cannot end before it starts.';
        } elseif ($in['IsRecurring'] && $from === null) {
            $errors['PeriodFrom'] = 'A recurring expense needs a period.';
        }
    }
    $clean['PeriodFrom'] = $from ?: null;
    $clean['PeriodTo'] = $to ?: null;

    $payee = array('VendorRegistrationID' => null, 'VendorName' => '', 'VendorGSTIN' => '', 'VendorAccountRef' => '');
    $clean['DueDate'] = null;
    if ($type === 'direct') {
        list($payeeErrors, $payee) = fp_vendor_resolve($conn, $in, false);
        $errors = array_merge($errors, $payeeErrors);
        $due = fp_parse_date($in['DueDate']);
        if ($due === false) {
            $errors['DueDate'] = 'Enter a valid date.';
        } elseif ($due !== null && ($due < date('Y-m-d', strtotime('-1 year')) || $due > date('Y-m-d', strtotime('+1 year')))) {
            $errors['DueDate'] = 'The due date must be within one year of today.';
        }
        $clean['DueDate'] = $due ?: null;
    }
    $clean += $payee;

    $count = count($in['items']);
    if ($count === 0) {
        $errors['items'] = 'Add at least one line item.';
    } elseif ($count > FP_REQUEST_MAX_ITEMS) {
        $errors['items'] = 'A request can have at most ' . FP_REQUEST_MAX_ITEMS . ' line items.';
    }

    $total = 0;
    foreach ($in['items'] as $index => $item) {
        $prefix = 'items.' . $index . '.';
        $category = $categories[$item['CategoryID']] ?? null;
        if (!$category) {
            $errors[$prefix . 'CategoryID'] = 'Choose an active category.';
        } else {
            if ($office && $category['CompanyID'] !== 0 && $category['CompanyID'] !== $office['CompanyID']) {
                $errors[$prefix . 'CategoryID'] = 'This category is not available for the office\'s legal entity.';
            } elseif (isset($types[$type]) && !fp_category_allows_type($category, $type)) {
                $errors[$prefix . 'CategoryID'] = 'This category does not allow ' . strtolower($types[$type]) . ' requests.';
            } elseif ($in['IsRecurring'] && $category['RecurringAllowed'] !== 1) {
                $errors[$prefix . 'CategoryID'] = 'This category does not allow recurring expenses.';
            }
        }

        $descriptionLength = mb_strlen($item['Description']);
        if ($descriptionLength < 2 || $descriptionLength > 500) {
            $errors[$prefix . 'Description'] = 'Enter 2-500 characters.';
        }

        $expenseDate = fp_parse_date($item['ExpenseDate']);
        if ($expenseDate === false) {
            $errors[$prefix . 'ExpenseDate'] = 'Enter a valid date.';
        } elseif ($type !== 'advance' && $expenseDate === null) {
            $errors[$prefix . 'ExpenseDate'] = 'Enter the expense date.';
        } elseif ($type !== 'advance' && $expenseDate > $today) {
            $errors[$prefix . 'ExpenseDate'] = 'The expense date cannot be in the future.';
        }

        $invoiceDate = fp_parse_date($item['InvoiceDate']);
        if ($invoiceDate === false) {
            $errors[$prefix . 'InvoiceDate'] = 'Enter a valid date.';
        } elseif ($invoiceDate !== null && $invoiceDate > $today) {
            $errors[$prefix . 'InvoiceDate'] = 'The invoice date cannot be in the future.';
        }

        if ($type === 'direct' && $item['VendorName'] === '' && $payee['VendorName'] !== '') {
            $item['VendorName'] = $payee['VendorName'];
            $item['VendorGSTIN'] = $item['VendorGSTIN'] !== '' ? $item['VendorGSTIN'] : $payee['VendorGSTIN'];
        }
        if (mb_strlen($item['VendorName']) > 150) {
            $errors[$prefix . 'VendorName'] = 'At most 150 characters.';
        }
        if ($item['VendorGSTIN'] !== '' && !preg_match('/^\d{2}[A-Z0-9]{13}$/', $item['VendorGSTIN'])) {
            $errors[$prefix . 'VendorGSTIN'] = 'Enter a 15-character GSTIN.';
        }
        if (mb_strlen($item['InvoiceNumber']) > 50) {
            $errors[$prefix . 'InvoiceNumber'] = 'At most 50 characters.';
        }

        $amount = fp_parse_paise($item['Amount']);
        if ($amount === false || $amount <= 0 || $amount > FP_REQUEST_MAX_AMOUNT_PAISE) {
            $errors[$prefix . 'Amount'] = 'Enter an amount greater than zero, with up to 2 decimals.';
            $amount = 0;
        }
        $tax = $item['TaxAmount'] === '' ? 0 : fp_parse_paise($item['TaxAmount']);
        if ($tax === false || $tax > $amount) {
            $errors[$prefix . 'TaxAmount'] = 'Tax must be zero or more and not exceed the amount.';
            $tax = 0;
        }
        $total += $amount;

        $clean['items'][] = array(
            'ItemID'        => $item['ItemID'],
            'CategoryID'    => $item['CategoryID'],
            'Description'   => $item['Description'],
            'ExpenseDate'   => $expenseDate ?: null,
            'VendorName'    => $item['VendorName'],
            'VendorGSTIN'   => $item['VendorGSTIN'],
            'InvoiceNumber' => $item['InvoiceNumber'],
            'InvoiceDate'   => $invoiceDate ?: null,
            'Amount'        => fp_paise_to_decimal($amount),
            'TaxAmount'     => fp_paise_to_decimal($tax),
        );
    }
    if ($total > FP_REQUEST_MAX_AMOUNT_PAISE * 10) {
        $errors['items'] = 'The request total is too large.';
    }

    $clean['RequestType'] = $type;
    $clean['Purpose'] = $in['Purpose'];
    $clean['Remarks'] = $in['Remarks'];
    $clean['IsRecurring'] = $in['IsRecurring'];
    $clean['TotalAmount'] = fp_paise_to_decimal($total);
    $clean['ItemCount'] = count($clean['items']);
    return array($errors, $clean);
}

/**
 * Extra rules for submission, checked against what is stored (items with IDs, attachments).
 */
function fp_request_validate_submission($request, $items, $attachments)
{
    $errors = array();
    $type = $request['RequestType'];
    if ($type === 'direct') {
        if ((string) ($request['VendorName'] ?? '') === '') {
            $errors['VendorName'] = 'Choose or enter the vendor to be paid.';
        }
        if (empty($request['DueDate'])) {
            $errors['DueDate'] = 'Enter the date the vendor must be paid by.';
        }
    }
    foreach ($items as $index => $item) {
        $line = 'Line ' . ((int) $item['LineNo']) . ': ';
        $prefix = 'items.' . $index . '.';
        if ($type === 'direct') {
            if ($item['VendorName'] === '') {
                $errors[$prefix . 'VendorName'] = $line . 'vendor name is required for a direct expense.';
            }
            if ($item['InvoiceNumber'] === '') {
                $errors[$prefix . 'InvoiceNumber'] = $line . 'invoice number is required for a direct expense.';
            }
            if ($item['InvoiceDate'] === null || $item['InvoiceDate'] === '') {
                $errors[$prefix . 'InvoiceDate'] = $line . 'invoice date is required for a direct expense.';
            }
        }
    }
    return array_merge($errors, fp_document_missing_errors($type, $items, $attachments));
}

/* ------------------------------------------------------------------
   Snapshots for the audit log
------------------------------------------------------------------ */

function fp_request_snapshot($request, $items)
{
    $snapshot = array();
    foreach (array('ExpenseNumber', 'RequestType', 'CompanyID', 'StateID', 'OfficeID', 'EmployeeID', 'Purpose', 'PeriodFrom', 'PeriodTo', 'IsRecurring', 'Remarks', 'TotalAmount', 'ItemCount', 'Status') as $field) {
        $snapshot[$field] = $request[$field] ?? null;
    }
    if (($request['RequestType'] ?? '') === 'direct') {
        foreach (array('VendorRegistrationID', 'VendorName', 'VendorGSTIN', 'VendorAccountRef', 'DueDate') as $field) {
            $snapshot[$field] = $request[$field] ?? null;
        }
    }
    $lines = array();
    foreach ($items as $item) {
        $lines[] = implode(' | ', array(
            (int) $item['CategoryID'],
            $item['Description'],
            (string) $item['ExpenseDate'],
            $item['VendorName'],
            $item['InvoiceNumber'],
            (string) $item['InvoiceDate'],
            $item['Amount'],
            $item['TaxAmount'],
        ));
    }
    $snapshot['Items'] = implode("\n", $lines);
    return $snapshot;
}

/* ------------------------------------------------------------------
   Writes
------------------------------------------------------------------ */

function fp_request_next_number($conn, $date)
{
    return fp_sequence_next($conn, 'EXP', $date);
}

/**
 * Next number of a per-financial-year series, e.g. EXP/2026-27/000001. Call inside a transaction.
 */
function fp_sequence_next($conn, $prefix, $date)
{
    $key = $prefix . '/' . fp_financial_year_label($date);
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT IGNORE INTO branch_expense_sequences (SequenceKey, LastValue, UpdatedAt) VALUES (?, 0, ?)');
    $stmt->bind_param('ss', $key, $now);
    $stmt->execute();
    $stmt->close();
    $rows = fp_db_rows($conn, 'SELECT LastValue FROM branch_expense_sequences WHERE SequenceKey = ? FOR UPDATE', 's', array($key));
    $next = (int) $rows[0]['LastValue'] + 1;
    $stmt = $conn->prepare('UPDATE branch_expense_sequences SET LastValue = ?, UpdatedAt = ? WHERE SequenceKey = ?');
    $stmt->bind_param('iss', $next, $now, $key);
    $stmt->execute();
    $stmt->close();
    return sprintf('%s/%06d', $key, $next);
}

function fp_request_write_items($conn, $requestId, $cleanItems, $existingItems)
{
    $existingIds = array();
    foreach ($existingItems as $item) {
        $existingIds[(int) $item['ID']] = true;
    }
    $keep = array();
    $now = date('Y-m-d H:i:s');
    foreach ($cleanItems as $index => $item) {
        $lineNo = $index + 1;
        if ($item['ItemID'] > 0 && isset($existingIds[$item['ItemID']])) {
            $keep[$item['ItemID']] = true;
            $stmt = $conn->prepare(
                'UPDATE branch_expense_items
                 SET LineNo = ?, CategoryID = ?, Description = ?, ExpenseDate = ?, VendorName = ?, VendorGSTIN = ?,
                     InvoiceNumber = ?, InvoiceDate = ?, Amount = ?, TaxAmount = ?, UpdatedAt = ?
                 WHERE ID = ? AND RequestID = ?'
            );
            $stmt->bind_param(
                'iisssssssssii',
                $lineNo, $item['CategoryID'], $item['Description'], $item['ExpenseDate'], $item['VendorName'], $item['VendorGSTIN'],
                $item['InvoiceNumber'], $item['InvoiceDate'], $item['Amount'], $item['TaxAmount'], $now, $item['ItemID'], $requestId
            );
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO branch_expense_items
                    (RequestID, LineNo, CategoryID, Description, ExpenseDate, VendorName, VendorGSTIN, InvoiceNumber, InvoiceDate, Amount, TaxAmount, CreatedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'iiisssssssss',
                $requestId, $lineNo, $item['CategoryID'], $item['Description'], $item['ExpenseDate'], $item['VendorName'], $item['VendorGSTIN'],
                $item['InvoiceNumber'], $item['InvoiceDate'], $item['Amount'], $item['TaxAmount'], $now
            );
        }
        $stmt->execute();
        $stmt->close();
    }
    foreach (array_keys($existingIds) as $itemId) {
        if (!isset($keep[$itemId])) {
            $stmt = $conn->prepare('DELETE FROM branch_expense_items WHERE ID = ? AND RequestID = ?');
            $stmt->bind_param('ii', $itemId, $requestId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/**
 * Creates or updates a draft. Only the requester can edit, and only while the request is draft or returned.
 */
function fp_request_save($conn, $actor, $post)
{
    $in = fp_request_input($post);
    if ($in['ID'] > 0 && !fp_can($actor, 'edit')) {
        return fp_result(false, 403, 'You do not have permission to edit requests.');
    }

    return fp_transaction($conn, function () use ($conn, $actor, $in) {
        $existing = null;
        $existingItems = array();
        if ($in['ID'] > 0) {
            $existing = fp_request_get($conn, $in['ID'], 'update');
            if (!$existing || !fp_request_can_edit($actor, $existing)) {
                return fp_result(false, 403, 'This request cannot be edited.');
            }
            if ((int) $existing['RowVersion'] !== $in['RowVersion']) {
                return fp_result(false, 409, 'This request was changed in another window. Reload the page and try again.');
            }
            $existingItems = fp_request_items($conn, $in['ID']);
            if ((int) $existing['RecurringRuleID'] > 0) {
                $in = fp_recurring_lock_draft_input($existing, $in);
            }
        }

        $offices = fp_request_offices_for_actor($conn, $actor);
        if (!$offices) {
            return fp_result(false, 403, 'You are not assigned to any active branch office. Ask an administrator to assign you.');
        }
        list($errors, $clean) = fp_request_validate($conn, $actor, $in, $offices, fp_request_categories($conn));
        foreach ($in['items'] as $index => $item) {
            if ($item['ItemID'] > 0 && !in_array($item['ItemID'], array_map('intval', array_column($existingItems, 'ID')), true)) {
                $errors['items.' . $index . '.CategoryID'] = 'This line no longer exists. Reload the page.';
            }
        }
        if ($errors) {
            return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
        }
        fp_db_rows($conn, 'SELECT ID FROM branch_expense_offices WHERE ID = ? LOCK IN SHARE MODE', 'i', array($clean['OfficeID']));

        $now = date('Y-m-d H:i:s');
        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];

        if (!$existing) {
            $number = fp_request_next_number($conn, $now);
            $settlement = $clean['RequestType'] === 'advance' ? 'not_started' : 'not_applicable';
            $stmt = $conn->prepare(
                'INSERT INTO branch_expense_requests
                    (ExpenseNumber, RequestType, CompanyID, StateID, OfficeID, EmployeeID, Purpose, PeriodFrom, PeriodTo, IsRecurring, Remarks,
                     VendorRegistrationID, VendorName, VendorGSTIN, VendorAccountRef, DueDate,
                     TotalAmount, ItemCount, Status, PaymentStatus, SettlementStatus, RowVersion, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'draft\', \'unpaid\', ?, 1, ?, ?, ?)'
            );
            $stmt->bind_param(
                'ssiiiisssisisssssisiis',
                $number, $clean['RequestType'], $clean['CompanyID'], $clean['StateID'], $clean['OfficeID'], $employeeId,
                $clean['Purpose'], $clean['PeriodFrom'], $clean['PeriodTo'], $clean['IsRecurring'], $clean['Remarks'],
                $clean['VendorRegistrationID'], $clean['VendorName'], $clean['VendorGSTIN'], $clean['VendorAccountRef'], $clean['DueDate'],
                $clean['TotalAmount'], $clean['ItemCount'], $settlement, $userId, $employeeId, $now
            );
            $stmt->execute();
            $id = (int) $conn->insert_id;
            $stmt->close();
            fp_request_write_items($conn, $id, $clean['items'], array());
            $saved = fp_request_get($conn, $id);
            fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $id, 'create', null, fp_request_snapshot($saved, fp_request_items($conn, $id)));
            return fp_result(true, 200, 'Draft saved as ' . $number . '.', array('data' => array('ID' => $id, 'ExpenseNumber' => $number, 'RowVersion' => 1)));
        }

        $old = fp_request_snapshot($existing, $existingItems);
        $settlement = $clean['RequestType'] === 'advance' ? 'not_started' : 'not_applicable';
        $stmt = $conn->prepare(
            'UPDATE branch_expense_requests
             SET RequestType = ?, CompanyID = ?, StateID = ?, OfficeID = ?, Purpose = ?, PeriodFrom = ?, PeriodTo = ?, IsRecurring = ?,
                 Remarks = ?, VendorRegistrationID = ?, VendorName = ?, VendorGSTIN = ?, VendorAccountRef = ?, DueDate = ?,
                 TotalAmount = ?, ItemCount = ?, SettlementStatus = ?, RowVersion = RowVersion + 1,
                 UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND RowVersion = ? AND Status IN (\'draft\', \'returned\')'
        );
        $stmt->bind_param(
            'siiisssisisssssisiisii',
            $clean['RequestType'], $clean['CompanyID'], $clean['StateID'], $clean['OfficeID'], $clean['Purpose'], $clean['PeriodFrom'], $clean['PeriodTo'],
            $clean['IsRecurring'], $clean['Remarks'],
            $clean['VendorRegistrationID'], $clean['VendorName'], $clean['VendorGSTIN'], $clean['VendorAccountRef'], $clean['DueDate'],
            $clean['TotalAmount'], $clean['ItemCount'], $settlement,
            $userId, $employeeId, $now, $in['ID'], $in['RowVersion']
        );
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected !== 1) {
            return fp_result(false, 409, 'This request was changed in another window. Reload the page and try again.');
        }
        fp_request_write_items($conn, $in['ID'], $clean['items'], $existingItems);
        $saved = fp_request_get($conn, $in['ID']);
        $new = fp_request_snapshot($saved, fp_request_items($conn, $in['ID']));
        if (fp_audit_changed_fields($old, $new)) {
            fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $in['ID'], 'update', $old, $new);
        }
        return fp_result(true, 200, 'Draft saved.', array('data' => array('ID' => $in['ID'], 'ExpenseNumber' => $saved['ExpenseNumber'], 'RowVersion' => (int) $saved['RowVersion'])));
    });
}

function fp_request_submit($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    if ($id <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }

    $result = fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion) {
        $request = fp_request_get($conn, $id, 'update');
        if (!$request || !fp_request_can_edit($actor, $request)) {
            return fp_result(false, 403, 'This request cannot be submitted.');
        }
        if ((int) $request['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This request was changed in another window. Reload the page and try again.');
        }

        $items = fp_request_items($conn, $id);
        $attachments = fp_request_attachments($conn, $id);
        $categories = fp_request_categories($conn);
        $offices = fp_request_offices_for_actor($conn, $actor);

        $in = array(
            'RequestType' => $request['RequestType'],
            'OfficeID'    => (int) $request['OfficeID'],
            'Purpose'     => $request['Purpose'],
            'PeriodFrom'  => (string) $request['PeriodFrom'],
            'PeriodTo'    => (string) $request['PeriodTo'],
            'IsRecurring' => (int) $request['IsRecurring'],
            'Remarks'     => $request['Remarks'],
            'DueDate'     => (string) $request['DueDate'],
            'VendorRegistrationID' => (int) $request['VendorRegistrationID'],
            'VendorName'           => (string) $request['VendorName'],
            'VendorGSTIN'          => (string) $request['VendorGSTIN'],
            'VendorAccountRef'     => (string) $request['VendorAccountRef'],
            'items'       => array(),
        );
        foreach ($items as $item) {
            $in['items'][] = array(
                'ItemID' => (int) $item['ID'], 'CategoryID' => (int) $item['CategoryID'], 'Description' => $item['Description'],
                'ExpenseDate' => (string) $item['ExpenseDate'], 'VendorName' => $item['VendorName'], 'VendorGSTIN' => $item['VendorGSTIN'],
                'InvoiceNumber' => $item['InvoiceNumber'], 'InvoiceDate' => (string) $item['InvoiceDate'],
                'Amount' => $item['Amount'], 'TaxAmount' => $item['TaxAmount'],
            );
        }
        list($errors, $clean) = fp_request_validate($conn, $actor, $in, $offices, $categories);
        $errors = array_merge($errors, fp_request_validate_submission($request, $items, $attachments));
        if ($errors) {
            return fp_result(false, 422, 'The request is not ready to submit: ' . implode(' ', array_slice(array_values($errors), 0, 5)), array('errors' => $errors));
        }
        if ($clean['CompanyID'] !== (int) $request['CompanyID'] || $clean['StateID'] !== (int) $request['StateID'] || $clean['TotalAmount'] !== $request['TotalAmount']) {
            return fp_result(false, 409, 'The office or totals changed since the draft was saved. Save the draft again, then submit.');
        }

        $now = date('Y-m-d H:i:s');
        $blocked = array();
        $warnings = array();
        foreach (fp_budget_impact($conn, $request, $items, true, substr($now, 0, 10)) as $budget) {
            if ($budget['Exceeded']) {
                $text = $budget['Label'] . ' would be exceeded by ' . fp_budget_money($budget['OverBy']) . '.';
                if ($budget['Enforcement'] === 'block') {
                    $blocked[] = $text;
                } else {
                    $warnings[] = $text;
                }
            }
        }
        if ($blocked) {
            return fp_result(false, 422, 'This request cannot be submitted because it is over budget: ' . implode(' ', $blocked) . ' Please contact Finance.', array('errors' => array('budget' => implode(' ', $blocked))));
        }

        $route = fp_policy_route($conn, $request, $items, substr($now, 0, 10));
        if ($route['error'] !== '') {
            return fp_result(false, 422, $route['error'], array('errors' => array('approval' => $route['error'])));
        }
        $policy = $route['policy'];
        $round = (int) $request['SubmissionRound'] + 1;
        $currentStage = 0;
        foreach ($route['stages'] as $stage) {
            if ($stage['Status'] === 'pending') {
                $currentStage = $stage['StageNo'];
                break;
            }
        }

        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $stmt = $conn->prepare(
            'UPDATE branch_expense_requests
             SET Status = \'submitted\', SubmittedAt = ?, SubmittedByUserID = ?, ApprovalPolicyID = ?, ApprovalPolicyCode = ?, ApprovalPolicyVersion = ?,
                 SubmissionRound = ?, CurrentStageNo = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND RowVersion = ? AND Status IN (\'draft\', \'returned\')'
        );
        $stmt->bind_param('siisiiiiisii', $now, $userId, $policy['ID'], $policy['PolicyCode'], $policy['Version'], $round, $currentStage, $userId, $employeeId, $now, $id, $rowVersion);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected !== 1) {
            return fp_result(false, 409, 'This request was changed in another window. Reload the page and try again.');
        }
        fp_policy_snapshot_route($conn, $id, $round, $route, $now);
        $newValues = array(
            'Status'         => 'submitted',
            'ApprovalPolicy' => $policy['PolicyCode'] . ' v' . $policy['Version'],
            'Round'          => $round,
            'Stages'         => array_map(function ($stage) {
                return $stage['StageNo'] . ' ' . $stage['StageName'] . ': ' . $stage['Status'] . ($stage['Note'] !== '' ? ' (' . $stage['Note'] . ')' : '');
            }, $route['stages']),
        );
        if ($warnings) {
            $newValues['BudgetWarnings'] = $warnings;
        }
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $id, 'submit', array('Status' => $request['Status']), $newValues);
        fp_notice_add('stage', $id);
        $message = $request['ExpenseNumber'] . ' submitted for approval.';
        if ($warnings) {
            $message .= ' Budget warning: ' . implode(' ', $warnings) . ' Approvers will see this.';
        }
        return fp_result(true, 200, $message, array('data' => array('ID' => $id)));
    });
    fp_notice_finish($conn, $result);
    return $result;
}

function fp_request_cancel($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $reason = fp_input_string($post, 'Reason');
    if ($id <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'Please give a reason for cancelling (5-500 characters).', array('errors' => array('Reason' => 'Required.')));
    }

    return fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $reason) {
        $request = fp_request_get($conn, $id, 'update');
        if (!$request || !fp_request_can_edit($actor, $request)) {
            return fp_result(false, 403, 'Only your own draft or returned requests can be cancelled.');
        }
        if ((int) $request['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This request was changed in another window. Reload the page and try again.');
        }
        $now = date('Y-m-d H:i:s');
        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $stmt = $conn->prepare(
            'UPDATE branch_expense_requests
             SET Status = \'cancelled\', CancelledAt = ?, CancelledByUserID = ?, RowVersion = RowVersion + 1,
                 UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND RowVersion = ? AND Status IN (\'draft\', \'returned\')'
        );
        $stmt->bind_param('siiisii', $now, $userId, $userId, $employeeId, $now, $id, $rowVersion);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected !== 1) {
            return fp_result(false, 409, 'This request was changed in another window. Reload the page and try again.');
        }
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $id, 'cancel', array('Status' => $request['Status']), array('Status' => 'cancelled'), $reason);
        return fp_result(true, 200, $request['ExpenseNumber'] . ' cancelled.');
    });
}

/* ------------------------------------------------------------------
   Attachments
------------------------------------------------------------------ */

function fp_clean_filename($name)
{
    $name = basename(str_replace('\\', '/', (string) $name));
    $name = preg_replace('/[\x00-\x1F\x7F"<>:|?*]+/u', '', $name);
    $name = trim($name);
    return $name === '' ? 'attachment' : mb_substr($name, 0, 200);
}

/**
 * $file is one entry of $_FILES. $mover moves the temporary file into storage (move_uploaded_file outside tests).
 */
function fp_attachment_add($conn, $actor, $post, $file, $mover = 'move_uploaded_file')
{
    $requestId = fp_input_int($post, 'RequestID');
    $itemId = fp_input_int($post, 'ItemID');
    $documentType = fp_input_string($post, 'DocumentType');
    $notes = preg_replace('/\s+/u', ' ', fp_input_string($post, 'Notes'));
    if ($requestId <= 0 && !$post && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        return fp_result(false, 422, 'The file is larger than ' . round(fp_attachment_max_bytes() / 1048576, 1) . ' MB.');
    }
    if ($requestId <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (!isset(fp_document_types()[$documentType])) {
        return fp_result(false, 422, 'Choose what kind of document this is.', array('errors' => array('DocumentType' => 'Required.')));
    }
    if (mb_strlen($notes) > 255) {
        return fp_result(false, 422, 'The note can be at most 255 characters.', array('errors' => array('Notes' => 'Too long.')));
    }
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        return fp_result(false, 422, 'Choose a file to upload.');
    }
    $maxBytes = fp_attachment_max_bytes();
    if (in_array($file['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true) || (int) $file['size'] > $maxBytes) {
        return fp_result(false, 422, 'The file is larger than ' . round($maxBytes / 1048576, 1) . ' MB.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK || (int) $file['size'] <= 0) {
        return fp_result(false, 422, 'The upload failed. Please try again.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types = fp_attachment_types();
    if (!isset($types[$mime])) {
        return fp_result(false, 422, 'Only PDF, JPG, PNG or WEBP files are allowed.');
    }
    if (!fp_document_content_ok($file['tmp_name'], $mime)) {
        return fp_result(false, 422, 'The file appears to be damaged or is not a real PDF or image.');
    }

    $relative = date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
    $target = fp_attachment_storage_dir() . '/' . $relative;
    $stored = false;

    try {
        $result = fp_transaction($conn, function () use ($conn, $actor, $requestId, $itemId, $documentType, $notes, $file, $mime, $relative, $target, $mover, &$stored) {
            $request = fp_request_get($conn, $requestId, 'update');
            if (!$request || !fp_request_can_edit($actor, $request)) {
                return fp_result(false, 403, 'Attachments can only be added to your own draft or returned requests.');
            }
            if ($itemId > 0 && !fp_db_rows($conn, 'SELECT ID FROM branch_expense_items WHERE ID = ? AND RequestID = ?', 'ii', array($itemId, $requestId))) {
                return fp_result(false, 422, 'That line item no longer exists. Save the draft and try again.');
            }
            $count = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt FROM branch_expense_attachments WHERE RequestID = ? AND IsDeleted = 0', 'i', array($requestId));
            if ((int) $count[0]['Cnt'] >= FP_REQUEST_MAX_ATTACHMENTS) {
                return fp_result(false, 422, 'A request can have at most ' . FP_REQUEST_MAX_ATTACHMENTS . ' attachments.');
            }

            if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0750, true) && !is_dir(dirname($target))) {
                throw new RuntimeException('Cannot create attachment folder.');
            }
            if (!$mover($file['tmp_name'], $target)) {
                throw new RuntimeException('Cannot store uploaded file.');
            }
            $stored = true;

            $name = fp_clean_filename($file['name'] ?? '');
            $size = (int) filesize($target);
            $hash = hash_file('sha256', $target);
            $itemValue = $itemId > 0 ? $itemId : null;
            $userId = (int) $actor['user_id'];
            $employeeId = (int) $actor['employee_id'];
            $now = date('Y-m-d H:i:s');
            $stmt = $conn->prepare(
                'INSERT INTO branch_expense_attachments
                    (RequestID, ItemID, DocumentType, Notes, OriginalName, StoredPath, MimeType, SizeBytes, Sha256, UploadedByUserID, UploadedByEmployeeID, UploadedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('iisssssisiis', $requestId, $itemValue, $documentType, $notes, $name, $relative, $mime, $size, $hash, $userId, $employeeId, $now);
            $stmt->execute();
            $attachmentId = (int) $conn->insert_id;
            $stmt->close();
            fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $requestId, 'attachment_add', null, array(
                'AttachmentID' => $attachmentId, 'ItemID' => $itemValue, 'DocumentType' => $documentType, 'Notes' => $notes,
                'OriginalName' => $name, 'SizeBytes' => $size, 'Sha256' => $hash,
            ));
            return fp_result(true, 200, fp_document_type_label($documentType) . ' attached.', array('data' => array('ID' => $attachmentId)));
        });
    } catch (Throwable $e) {
        if ($stored && is_file($target)) {
            @unlink($target);
        }
        throw $e;
    }
    if (!$result['ok'] && $stored && is_file($target)) {
        @unlink($target);
    }
    return $result;
}

function fp_attachment_delete($conn, $actor, $post)
{
    $attachmentId = fp_input_int($post, 'ID');
    if ($attachmentId <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    return fp_transaction($conn, function () use ($conn, $actor, $attachmentId) {
        $rows = fp_db_rows($conn, 'SELECT * FROM branch_expense_attachments WHERE ID = ? AND IsDeleted = 0', 'i', array($attachmentId));
        if (!$rows) {
            return fp_result(false, 404, 'This attachment no longer exists.');
        }
        $attachment = $rows[0];
        $request = fp_request_get($conn, (int) $attachment['RequestID'], 'update');
        if (!$request || !fp_request_can_edit($actor, $request)) {
            return fp_result(false, 403, 'Attachments can only be removed from your own draft or returned requests.');
        }
        $userId = (int) $actor['user_id'];
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare('UPDATE branch_expense_attachments SET IsDeleted = 1, DeletedByUserID = ?, DeletedAt = ? WHERE ID = ? AND IsDeleted = 0');
        $stmt->bind_param('isi', $userId, $now, $attachmentId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected !== 1) {
            return fp_result(false, 409, 'This attachment was already removed.');
        }
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, (int) $attachment['RequestID'], 'attachment_remove', array('AttachmentID' => $attachmentId, 'DocumentType' => $attachment['DocumentType'], 'OriginalName' => $attachment['OriginalName']), null);
        return fp_result(true, 200, 'Attachment removed.');
    });
}

/**
 * Returns the attachment row and absolute path when the actor may view it, otherwise null.
 */
function fp_attachment_for_download($conn, $actor, $attachmentId)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT a.*, r.EmployeeID, r.CreatedByEmployeeID, r.OfficeID, r.Status, s.StateName
         FROM branch_expense_attachments a
         INNER JOIN branch_expense_requests r ON r.ID = a.RequestID
         LEFT JOIN state s ON s.ID = r.StateID
         WHERE a.ID = ?',
        'i',
        array((int) $attachmentId)
    );
    if (!$rows) {
        return null;
    }
    if ((int) $rows[0]['IsDeleted'] === 1) {
        if (!fp_documents_can_view($actor, $rows[0]) || !fp_record_in_scope($actor, 'audit', fp_request_scope_record($rows[0]))) {
            return null;
        }
    } elseif (!fp_documents_can_view($actor, $rows[0])) {
        $request = fp_request_get_detail($conn, $rows[0]['RequestID']);
        if (!$request || !fp_approval_is_involved($conn, $actor, $request)) {
            return null;
        }
    }
    $base = realpath(fp_attachment_storage_dir());
    $path = realpath(fp_attachment_storage_dir() . '/' . $rows[0]['StoredPath']);
    if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
        return null;
    }
    return array('row' => $rows[0], 'path' => $path);
}

/* ------------------------------------------------------------------
   Listing
------------------------------------------------------------------ */

/**
 * Builds WHERE clause and params for list/export. 'view' = 'mine' or 'all' (everything in the actor's view scope).
 */
function fp_request_list_filter($conn, $actor, $params)
{
    $me = (int) $actor['employee_id'];
    $view = fp_input_string($params, 'view') === 'all' ? 'all' : 'mine';
    $where = array();
    $types = '';
    $values = array();

    if ($view === 'mine') {
        $where[] = $me > 0 ? 'r.EmployeeID = ' . $me : '1=0';
    } else {
        $where[] = fp_scope_where($conn, $actor, 'view', array('employee_id' => 'r.EmployeeID', 'state_name' => 's.StateName', 'office_id' => 'r.OfficeID'));
        $where[] = "(r.Status <> 'draft' OR r.EmployeeID = " . $me . ')';
    }
    $base = implode(' AND ', $where);

    $status = fp_input_string($params, 'status');
    if (isset(fp_request_statuses()[$status])) {
        $where[] = 'r.Status = ?';
        $types .= 's';
        $values[] = $status;
    }
    $type = fp_input_string($params, 'type');
    if (isset(fp_request_types()[$type])) {
        $where[] = 'r.RequestType = ?';
        $types .= 's';
        $values[] = $type;
    }
    $officeId = fp_input_int($params, 'office');
    if ($officeId > 0) {
        $where[] = 'r.OfficeID = ?';
        $types .= 'i';
        $values[] = $officeId;
    }
    $from = fp_parse_date(fp_input_string($params, 'from'));
    if ($from) {
        $where[] = 'r.CreatedAt >= ?';
        $types .= 's';
        $values[] = $from . ' 00:00:00';
    }
    $to = fp_parse_date(fp_input_string($params, 'to'));
    if ($to) {
        $where[] = 'r.CreatedAt <= ?';
        $types .= 's';
        $values[] = $to . ' 23:59:59';
    }
    $search = mb_substr(fp_input_string($params, 'q'), 0, 100);
    if ($search === '' && isset($params['search']) && is_array($params['search'])) {
        $search = mb_substr(fp_input_string($params['search'], 'value'), 0, 100);
    }
    if ($search !== '') {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $where[] = '(r.ExpenseNumber LIKE ? OR r.Purpose LIKE ? OR e.Name LIKE ? OR o.OfficeName LIKE ?)';
        $types .= 'ssss';
        array_push($values, $like, $like, $like, $like);
    }

    return array('base' => $base, 'where' => implode(' AND ', $where), 'types' => $types, 'values' => $values, 'view' => $view);
}

function fp_request_list_from()
{
    return ' FROM branch_expense_requests r
             INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
             LEFT JOIN state s ON s.ID = r.StateID
             LEFT JOIN quote_company_details q ON q.ID = r.CompanyID
             LEFT JOIN employees e ON e.ID = r.EmployeeID ';
}

function fp_request_list_columns()
{
    return 'r.ID, r.ExpenseNumber, r.RequestType, r.Status, r.PaymentStatus, r.SettlementStatus, r.Purpose,
            r.PeriodFrom, r.PeriodTo, r.TotalAmount, r.ItemCount, r.CreatedAt, r.SubmittedAt, r.EmployeeID, r.RowVersion,
            o.OfficeCode, o.OfficeName, s.StateName, q.CompanyName, e.Name AS EmployeeName, e.EmployeeNumber';
}

function fp_request_list($conn, $actor, $params)
{
    $filter = fp_request_list_filter($conn, $actor, $params);
    $start = max(0, fp_input_int($params, 'start'));
    $length = fp_input_int($params, 'length');
    $length = $length <= 0 ? 25 : min($length, 100);

    $total = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt' . fp_request_list_from() . 'WHERE ' . $filter['base']);
    $filtered = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt' . fp_request_list_from() . 'WHERE ' . $filter['where'], $filter['types'], $filter['values']);
    $rows = fp_db_rows(
        $conn,
        'SELECT ' . fp_request_list_columns() . fp_request_list_from() . 'WHERE ' . $filter['where'] . ' ORDER BY r.ID DESC LIMIT ? OFFSET ?',
        $filter['types'] . 'ii',
        array_merge($filter['values'], array($length, $start))
    );

    $types = fp_request_types();
    $statuses = fp_request_statuses();
    $me = (int) $actor['employee_id'];
    $activity = fp_input_string($params, 'activity') === '1' ? fp_timeline_last_activity($conn, array_column($rows, 'ID')) : array();
    $data = array();
    foreach ($rows as $row) {
        $data[] = array(
            'ID'            => (int) $row['ID'],
            'ExpenseNumber' => $row['ExpenseNumber'],
            'TypeLabel'     => $types[$row['RequestType']] ?? $row['RequestType'],
            'Status'        => $row['Status'],
            'StatusLabel'   => $statuses[$row['Status']] ?? $row['Status'],
            'PaymentStatus' => $row['PaymentStatus'],
            'SettlementStatus' => $row['SettlementStatus'],
            'LastActivity'  => $activity[(int) $row['ID']] ?? null,
            'Purpose'       => $row['Purpose'],
            'Office'        => $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')',
            'StateName'     => (string) $row['StateName'],
            'CompanyName'   => (string) $row['CompanyName'],
            'EmployeeName'  => (string) $row['EmployeeName'],
            'TotalAmount'   => $row['TotalAmount'],
            'ItemCount'     => (int) $row['ItemCount'],
            'CreatedAt'     => $row['CreatedAt'],
            'SubmittedAt'   => (string) $row['SubmittedAt'],
            'RowVersion'    => (int) $row['RowVersion'],
            'IsMine'        => (int) $row['EmployeeID'] === $me,
            'CanEdit'       => (int) $row['EmployeeID'] === $me && in_array($row['Status'], fp_request_editable_statuses(), true) && fp_can($actor, 'edit'),
        );
    }

    return array(
        'draw'            => fp_input_int($params, 'draw'),
        'recordsTotal'    => (int) $total[0]['Cnt'],
        'recordsFiltered' => (int) $filtered[0]['Cnt'],
        'data'            => $data,
    );
}

/**
 * Streams the filtered list as CSV (max 10,000 rows). Cells that start with a formula character are prefixed.
 */
function fp_request_export_csv($conn, $actor, $params)
{
    $filter = fp_request_list_filter($conn, $actor, $params);
    $rows = fp_db_rows(
        $conn,
        'SELECT ' . fp_request_list_columns() . fp_request_list_from() . 'WHERE ' . $filter['where'] . ' ORDER BY r.ID DESC LIMIT 10000',
        $filter['types'],
        $filter['values']
    );
    $types = fp_request_types();
    $statuses = fp_request_statuses();
    $cell = function ($value) {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    };

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="expense-requests-' . date('Ymd-His') . '.csv"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('Expense No.', 'Type', 'Status', 'Payment status', 'Requester', 'Employee No.', 'Office', 'State', 'Legal entity', 'Purpose', 'Period from', 'Period to', 'Items', 'Total (INR)', 'Created', 'Submitted'));
    foreach ($rows as $row) {
        fputcsv($out, array_map($cell, array(
            $row['ExpenseNumber'], $types[$row['RequestType']] ?? $row['RequestType'], $statuses[$row['Status']] ?? $row['Status'],
            $row['PaymentStatus'], $row['EmployeeName'], $row['EmployeeNumber'], $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')',
            $row['StateName'], $row['CompanyName'], $row['Purpose'], $row['PeriodFrom'], $row['PeriodTo'], $row['ItemCount'],
            $row['TotalAmount'], $row['CreatedAt'], $row['SubmittedAt'],
        )));
    }
    fclose($out);
    exit;
}

/**
 * Data for the request form: permitted offices, usable categories and (when editing) the request itself.
 */
function fp_request_form_data($conn, $actor, $requestId)
{
    $offices = fp_request_offices_for_actor($conn, $actor);
    $data = array(
        'offices'         => array_values($offices),
        'categories'      => array_values(fp_request_categories($conn)),
        'types'           => fp_request_types(),
        'documentTypes'   => fp_document_types(),
        'maxUploadBytes'  => fp_attachment_max_bytes(),
        'vendorMode'      => fp_vendor_master_mode(),
        'request'         => null,
        'items'           => array(),
        'attachments'     => array(),
    );
    if ($requestId > 0) {
        $request = fp_request_get($conn, $requestId);
        if (!$request || !fp_request_can_edit($actor, $request)) {
            return fp_result(false, 403, 'This request cannot be edited.');
        }
        $data['request'] = array(
            'ID' => (int) $request['ID'], 'ExpenseNumber' => $request['ExpenseNumber'], 'RequestType' => $request['RequestType'],
            'OfficeID' => (int) $request['OfficeID'], 'Purpose' => $request['Purpose'], 'PeriodFrom' => (string) $request['PeriodFrom'],
            'PeriodTo' => (string) $request['PeriodTo'], 'IsRecurring' => (int) $request['IsRecurring'], 'Remarks' => $request['Remarks'],
            'Status' => $request['Status'], 'RowVersion' => (int) $request['RowVersion'], 'TotalAmount' => $request['TotalAmount'],
            'VendorRegistrationID' => (int) $request['VendorRegistrationID'], 'VendorName' => $request['VendorName'],
            'VendorGSTIN' => $request['VendorGSTIN'], 'VendorAccountRef' => $request['VendorAccountRef'], 'DueDate' => (string) $request['DueDate'],
            'VendorLabel' => '', 'Recurring' => null,
        );
        if ((int) $request['VendorRegistrationID'] > 0) {
            $label = fp_vendor_labels($conn, array($request['VendorRegistrationID']))[(int) $request['VendorRegistrationID']] ?? null;
            $data['request']['VendorLabel'] = $label ? $label['Code'] . ($label['Approved'] ? '' : ' (no longer approved)') : '';
        }
        if ((int) $request['RecurringRuleID'] > 0) {
            $rule = fp_db_rows($conn, 'SELECT RuleNumber, Title FROM branch_expense_recurring_rules WHERE ID = ?', 'i', array((int) $request['RecurringRuleID']));
            $data['request']['Recurring'] = array(
                'RuleNumber' => $rule ? $rule[0]['RuleNumber'] : '',
                'Title'      => $rule ? $rule[0]['Title'] : '',
                'Period'     => fp_recurring_period_label($request['PeriodFrom'], $request['PeriodTo']),
            );
        }
        foreach (fp_request_items($conn, $requestId) as $item) {
            $data['items'][] = array(
                'ItemID' => (int) $item['ID'], 'LineNo' => (int) $item['LineNo'], 'CategoryID' => (int) $item['CategoryID'],
                'CategoryLabel' => ($item['ParentCategoryName'] ? $item['ParentCategoryName'] . ' > ' : '') . $item['CategoryName'],
                'Description' => $item['Description'], 'ExpenseDate' => (string) $item['ExpenseDate'], 'VendorName' => $item['VendorName'],
                'VendorGSTIN' => $item['VendorGSTIN'], 'InvoiceNumber' => $item['InvoiceNumber'], 'InvoiceDate' => (string) $item['InvoiceDate'],
                'Amount' => $item['Amount'], 'TaxAmount' => $item['TaxAmount'],
            );
        }
        foreach (fp_request_attachments($conn, $requestId) as $attachment) {
            $data['attachments'][] = array(
                'ID' => (int) $attachment['ID'], 'ItemID' => $attachment['ItemID'] === null ? 0 : (int) $attachment['ItemID'],
                'LineNo' => $attachment['LineNo'] === null ? 0 : (int) $attachment['LineNo'], 'OriginalName' => $attachment['OriginalName'],
                'DocumentType' => $attachment['DocumentType'], 'DocumentLabel' => fp_document_type_label($attachment['DocumentType']),
                'Notes' => $attachment['Notes'], 'SizeBytes' => (int) $attachment['SizeBytes'], 'UploadedAt' => $attachment['UploadedAt'],
                'UploadedByName' => $attachment['UploadedByName'],
            );
        }
    }
    return fp_result(true, 200, 'OK', array('data' => $data));
}
