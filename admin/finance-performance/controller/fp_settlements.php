<?php
/**
 * Advance settlement: expense claims against a paid cash advance, branch review, Finance line-by-line verification,
 * cash returned by the holder, extra paid to the holder, outstanding advance aging and closing.
 * Loaded by fp_requests.php. Tables and database guards: sql/m10_settlements.sql.
 *
 * Advance balance = paid out (completed payments) + extra paid - accepted expenses (verified claims) - cash returned.
 *   > 0  the holder still holds company cash (refund due)
 *   < 0  the company owes the holder (additional reimbursement due)
 * An advance is closed (SettlementStatus 'settled') only when every claim is decided and the balance is zero.
 * The advance payment itself is never an expense: budgets recognise the accepted claim lines (fp_budget_usage()).
 */

const FP_SETTLEMENT_MAX_LINES = 50;
const FP_SETTLEMENT_REASON_MIN = 5;
const FP_SETTLEMENT_LIST_LIMIT = 2000;

function fp_settlement_statuses()
{
    return array(
        'draft'           => 'Draft',
        'submitted'       => 'Waiting for branch review',
        'branch_reviewed' => 'Waiting for Finance verification',
        'returned'        => 'Returned for changes',
        'verified'        => 'Verified',
        'cancelled'       => 'Cancelled',
    );
}

function fp_settlement_open_statuses()
{
    return array('draft', 'submitted', 'branch_reviewed', 'returned');
}

function fp_settlement_request_statuses()
{
    return array('not_applicable' => 'Not applicable', 'not_started' => 'Not started', 'in_progress' => 'In progress', 'settled' => 'Settled (closed)');
}

function fp_settlement_line_decisions()
{
    return array('pending' => 'Not decided', 'accepted' => 'Accepted', 'partial' => 'Partly accepted', 'rejected' => 'Rejected');
}

function fp_settlement_txn_types()
{
    return array('refund' => 'Cash returned by employee', 'additional' => 'Extra paid to employee');
}

/**
 * Days after the first advance payment within which the holder is expected to settle (access matrix setting).
 */
function fp_settlement_due_days()
{
    $days = (int) (fp_access_matrix()['advance_settlement_days'] ?? 30);
    return $days > 0 ? $days : 30;
}

function fp_settlement_aging_buckets()
{
    return array('0_30' => '0-30 days', '31_60' => '31-60 days', '61_90' => '61-90 days', '90_plus' => 'Over 90 days');
}

function fp_settlement_bucket($days)
{
    if ($days <= 30) {
        return '0_30';
    }
    if ($days <= 60) {
        return '31_60';
    }
    return $days <= 90 ? '61_90' : '90_plus';
}

function fp_settlement_balance_text($paise)
{
    if ($paise > 0) {
        return 'Refund due from employee: ' . fp_payment_money($paise);
    }
    if ($paise < 0) {
        return 'Reimbursement due to employee: ' . fp_payment_money(-$paise);
    }
    return 'Reconciled (balance zero)';
}

/* ------------------------------------------------------------------
   Loading
------------------------------------------------------------------ */

/**
 * Money position of one advance, in paise.
 */
function fp_settlement_summary($conn, $requestId)
{
    $id = (int) $requestId;
    $pay = fp_db_rows($conn, "SELECT COALESCE(SUM(Amount), 0) AS Paid, MIN(PaymentDate) AS FirstPaid FROM branch_expense_payments WHERE RequestID = ? AND Status = 'completed'", 'i', array($id))[0];
    $claims = fp_db_rows(
        $conn,
        "SELECT COALESCE(SUM(CASE WHEN Status = 'verified' THEN AcceptedAmount END), 0) AS Accepted,
                COALESCE(SUM(CASE WHEN Status = 'verified' THEN ClaimedAmount END), 0) AS Claimed,
                COALESCE(SUM(Status IN ('draft', 'submitted', 'branch_reviewed', 'returned')), 0) AS OpenCount
         FROM branch_expense_settlements WHERE RequestID = ?",
        'i',
        array($id)
    )[0];
    $txns = fp_db_rows(
        $conn,
        "SELECT COALESCE(SUM(CASE WHEN TxnType = 'refund' THEN Amount END), 0) AS Refund, COALESCE(SUM(CASE WHEN TxnType = 'additional' THEN Amount END), 0) AS Additional
         FROM branch_expense_settlement_transactions WHERE RequestID = ? AND Status = 'completed'",
        'i',
        array($id)
    )[0];
    $paid = fp_decimal_to_paise($pay['Paid']);
    $accepted = fp_decimal_to_paise($claims['Accepted']);
    $refund = fp_decimal_to_paise($txns['Refund']);
    $additional = fp_decimal_to_paise($txns['Additional']);
    $firstPaid = $pay['FirstPaid'] === null ? '' : (string) $pay['FirstPaid'];
    return array(
        'paid'        => $paid,
        'claimed'     => fp_decimal_to_paise($claims['Claimed']),
        'accepted'    => $accepted,
        'refund'      => $refund,
        'additional'  => $additional,
        'balance'     => $paid + $additional - $accepted - $refund,
        'first_paid'  => $firstPaid,
        'days'        => $firstPaid === '' ? 0 : max(0, (int) floor((strtotime(date('Y-m-d')) - strtotime($firstPaid)) / 86400)),
        'open_claims' => (int) $claims['OpenCount'],
    );
}

function fp_settlement_summary_values($summary)
{
    return array(
        'AdvancePaid'       => fp_paise_to_decimal($summary['paid']),
        'ExpensesAccepted'  => fp_paise_to_decimal($summary['accepted']),
        'RefundReceived'    => fp_paise_to_decimal($summary['refund']),
        'AdditionalPaid'    => fp_paise_to_decimal($summary['additional']),
        'SettlementBalance' => ($summary['balance'] < 0 ? '-' : '') . fp_paise_to_decimal(abs($summary['balance'])),
    );
}

function fp_settlement_get($conn, $id, $lock = '')
{
    $rows = fp_db_rows($conn, 'SELECT * FROM branch_expense_settlements WHERE ID = ?' . ($lock === 'update' ? ' FOR UPDATE' : ''), 'i', array((int) $id));
    return $rows ? $rows[0] : null;
}

function fp_settlement_claims($conn, $requestId)
{
    return fp_db_rows(
        $conn,
        'SELECT s.*, COALESCE(rv.Name, \'\') AS ReviewedByName, COALESCE(vf.Name, \'\') AS VerifiedByName, COALESCE(rt.Name, \'\') AS ReturnedByName
         FROM branch_expense_settlements s
         LEFT JOIN employees rv ON s.ReviewedByEmployeeID > 0 AND rv.ID = s.ReviewedByEmployeeID
         LEFT JOIN employees vf ON s.VerifiedByEmployeeID > 0 AND vf.ID = s.VerifiedByEmployeeID
         LEFT JOIN employees rt ON s.ReturnedByEmployeeID > 0 AND rt.ID = s.ReturnedByEmployeeID
         WHERE s.RequestID = ?
         ORDER BY s.ID',
        'i',
        array((int) $requestId)
    );
}

function fp_settlement_lines($conn, $settlementId)
{
    return fp_db_rows(
        $conn,
        'SELECT l.*, c.CategoryName, c.ReceiptRequired, c.QuotationRequiredAbove, p.CategoryName AS ParentCategoryName
         FROM branch_expense_settlement_lines l
         INNER JOIN branch_expense_categories c ON c.ID = l.CategoryID
         LEFT JOIN branch_expense_categories p ON p.ID = c.ParentCategoryID
         WHERE l.SettlementID = ?
         ORDER BY l.LineNo',
        'i',
        array((int) $settlementId)
    );
}

function fp_settlement_line_category($line)
{
    return ($line['ParentCategoryName'] ? $line['ParentCategoryName'] . ' > ' : '') . $line['CategoryName'];
}

function fp_settlement_documents($conn, $settlementId, $deleted = false)
{
    return fp_db_rows(
        $conn,
        'SELECT d.ID, d.LineID, d.DocumentType, d.Notes, d.OriginalName, d.MimeType, d.SizeBytes, d.Sha256, d.UploadedAt, d.DeletedAt,
                l.LineNo, COALESCE(e.Name, \'\') AS UploadedByName
         FROM branch_expense_settlement_documents d
         LEFT JOIN branch_expense_settlement_lines l ON l.ID = d.LineID
         LEFT JOIN employees e ON d.UploadedByEmployeeID > 0 AND e.ID = d.UploadedByEmployeeID
         WHERE d.SettlementID = ? AND d.IsDeleted = ?
         ORDER BY d.ID',
        'ii',
        array((int) $settlementId, $deleted ? 1 : 0)
    );
}

function fp_settlement_transactions($conn, $requestId)
{
    return fp_db_rows(
        $conn,
        'SELECT t.*, COALESCE(er.Name, t.RecordedByUserName) AS RecordedByName, COALESCE(es.Name, \'\') AS StatusChangedByName
         FROM branch_expense_settlement_transactions t
         LEFT JOIN employees er ON t.RecordedByEmployeeID > 0 AND er.ID = t.RecordedByEmployeeID
         LEFT JOIN employees es ON t.StatusChangedByEmployeeID > 0 AND es.ID = t.StatusChangedByEmployeeID
         WHERE t.RequestID = ?
         ORDER BY t.ID',
        'i',
        array((int) $requestId)
    );
}

/* ------------------------------------------------------------------
   Who may do what
------------------------------------------------------------------ */

function fp_settlement_is_holder($actor, $request)
{
    return (int) $actor['employee_id'] > 0 && (int) $request['EmployeeID'] === (int) $actor['employee_id'];
}

function fp_settlement_can_view($conn, $actor, $request)
{
    return fp_settlement_is_holder($actor, $request) || fp_request_can_view($actor, $request) || fp_approval_is_involved($conn, $actor, $request);
}

/**
 * Bills of a draft claim stay private to the holder; afterwards the document rules of the advance apply.
 */
function fp_settlement_can_see_documents($actor, $request, $claim)
{
    if (fp_settlement_is_holder($actor, $request)) {
        return true;
    }
    return $claim['Status'] !== 'draft' && (fp_documents_can_view($actor, $request) || fp_record_in_scope($actor, 'approve', fp_request_scope_record($request)));
}

/**
 * Array(status, message) when the actor may not start a new claim on the advance; null when allowed.
 */
function fp_settlement_start_block($conn, $actor, $request)
{
    if (!fp_can($actor, 'submit') || !fp_settlement_is_holder($actor, $request)) {
        return array(403, 'Only the advance holder can settle this advance.');
    }
    $block = fp_settlement_advance_block($request);
    if ($block) {
        return $block;
    }
    $summary = fp_settlement_summary($conn, $request['ID']);
    if ($summary['paid'] <= 0) {
        return array(422, 'Nothing has been paid on this advance yet, so there is nothing to settle.');
    }
    $open = fp_db_rows($conn, "SELECT SettlementNumber FROM branch_expense_settlements WHERE RequestID = ? AND Status IN ('draft', 'submitted', 'branch_reviewed', 'returned') LIMIT 1", 'i', array((int) $request['ID']));
    if ($open) {
        return array(422, 'Settlement ' . $open[0]['SettlementNumber'] . ' is still open. Finish or cancel it before starting another.');
    }
    return null;
}

/**
 * The advance itself must be an approved, open cash advance.
 */
function fp_settlement_advance_block($request)
{
    if ($request['RequestType'] !== 'advance') {
        return array(422, 'Only cash advances are settled.');
    }
    if ($request['Status'] !== 'approved') {
        return array(422, 'The advance is not approved.');
    }
    if ($request['SettlementStatus'] === 'settled') {
        return array(422, 'This advance is already settled and closed.');
    }
    return null;
}

function fp_settlement_edit_block($actor, $request, $claim)
{
    if (!fp_can($actor, 'submit') || !fp_settlement_is_holder($actor, $request)) {
        return array(403, 'Only the advance holder can change this settlement.');
    }
    if (!in_array($claim['Status'], array('draft', 'returned'), true)) {
        return array(422, 'This settlement is ' . strtolower(fp_settlement_statuses()[$claim['Status']]) . ' and cannot be changed.');
    }
    return fp_settlement_advance_block($request);
}

function fp_settlement_review_block($actor, $request, $claim)
{
    if ((int) $actor['employee_id'] <= 0) {
        return array(403, 'Reviewing settlements needs a login linked to an active employee record.');
    }
    $check = fp_can_act_on_record($actor, 'approve', fp_request_scope_record($request));
    if (!$check['allowed']) {
        return array(403, $check['reason'] === 'You cannot approve your own request.' ? 'You cannot review your own settlement.' : $check['reason']);
    }
    if ($claim['Status'] !== 'submitted') {
        return array(409, 'This settlement is not waiting for branch review.');
    }
    return fp_settlement_advance_block($request);
}

function fp_settlement_verify_block($actor, $request, $claim)
{
    if ((int) $actor['employee_id'] <= 0) {
        return array(403, 'Verifying settlements needs a login linked to an active employee record.');
    }
    $check = fp_can_act_on_record($actor, 'pay', fp_request_scope_record($request));
    if (!$check['allowed']) {
        return array(403, $check['reason'] === 'You cannot pay your own request.' ? 'You cannot verify your own settlement.' : $check['reason']);
    }
    if ($claim['Status'] !== 'branch_reviewed') {
        return array(409, 'This settlement is not waiting for Finance verification.');
    }
    if ((int) $claim['ReviewedByEmployeeID'] === (int) $actor['employee_id']) {
        return array(403, 'You reviewed this settlement for the branch, so another Finance person must verify it.');
    }
    return fp_settlement_advance_block($request);
}

/**
 * Recording cash returned / extra paid, marking them failed or reversed, and closing: Finance ('pay'), never the holder.
 */
function fp_settlement_finance_block($actor, $request)
{
    if ((int) $actor['employee_id'] <= 0) {
        return array(403, 'This needs a login linked to an active employee record.');
    }
    $check = fp_can_act_on_record($actor, 'pay', fp_request_scope_record($request));
    if (!$check['allowed']) {
        return array(403, $check['reason'] === 'You cannot pay your own request.' ? 'You cannot settle your own advance.' : $check['reason']);
    }
    return fp_settlement_advance_block($request);
}

/* ------------------------------------------------------------------
   Claim input and validation
------------------------------------------------------------------ */

function fp_settlement_input($post)
{
    $lines = array();
    foreach (array_values(isset($post['lines']) && is_array($post['lines']) ? $post['lines'] : array()) as $line) {
        if (!is_array($line)) {
            continue;
        }
        $lines[] = array(
            'LineID'        => fp_input_int($line, 'LineID'),
            'CategoryID'    => fp_input_int($line, 'CategoryID'),
            'Description'   => preg_replace('/\s+/u', ' ', fp_input_string($line, 'Description')),
            'ExpenseDate'   => fp_input_string($line, 'ExpenseDate'),
            'VendorName'    => preg_replace('/\s+/u', ' ', fp_input_string($line, 'VendorName')),
            'VendorGSTIN'   => strtoupper(fp_input_string($line, 'VendorGSTIN')),
            'InvoiceNumber' => fp_input_string($line, 'InvoiceNumber'),
            'InvoiceDate'   => fp_input_string($line, 'InvoiceDate'),
            'Amount'        => fp_input_string($line, 'Amount'),
            'TaxAmount'     => fp_input_string($line, 'TaxAmount'),
        );
    }
    return array(
        'ID'         => fp_input_int($post, 'ID'),
        'RowVersion' => fp_input_int($post, 'RowVersion'),
        'RequestID'  => fp_input_int($post, 'RequestID'),
        'Notes'      => fp_input_string($post, 'Notes'),
        'lines'      => $lines,
    );
}

/**
 * Active leaf categories usable for the advance's legal entity.
 */
function fp_settlement_categories($conn, $companyId)
{
    return array_filter(fp_request_categories($conn), function ($category) use ($companyId) {
        return $category['CompanyID'] === 0 || $category['CompanyID'] === (int) $companyId;
    });
}

/**
 * Returns [errors, clean]. Lines are actual expenses: date required and not in the future.
 */
function fp_settlement_validate($in, $categories)
{
    $errors = array();
    $clean = array('lines' => array(), 'Notes' => $in['Notes']);
    $today = date('Y-m-d');
    if (mb_strlen($in['Notes']) > 1000) {
        $errors['Notes'] = 'Notes can be at most 1000 characters.';
    }
    $count = count($in['lines']);
    if ($count === 0) {
        $errors['lines'] = 'Add at least one expense line.';
    } elseif ($count > FP_SETTLEMENT_MAX_LINES) {
        $errors['lines'] = 'A settlement can have at most ' . FP_SETTLEMENT_MAX_LINES . ' lines.';
    }
    $total = 0;
    foreach ($in['lines'] as $index => $line) {
        $prefix = 'lines.' . $index . '.';
        if (!isset($categories[$line['CategoryID']])) {
            $errors[$prefix . 'CategoryID'] = 'Choose an active category.';
        }
        $length = mb_strlen($line['Description']);
        if ($length < 2 || $length > 500) {
            $errors[$prefix . 'Description'] = 'Enter 2-500 characters.';
        }
        $expenseDate = fp_parse_date($line['ExpenseDate']);
        if (!$expenseDate) {
            $errors[$prefix . 'ExpenseDate'] = 'Enter the date the money was spent.';
        } elseif ($expenseDate > $today) {
            $errors[$prefix . 'ExpenseDate'] = 'The expense date cannot be in the future.';
        }
        $invoiceDate = fp_parse_date($line['InvoiceDate']);
        if ($invoiceDate === false) {
            $errors[$prefix . 'InvoiceDate'] = 'Enter a valid date.';
        } elseif ($invoiceDate !== null && $invoiceDate > $today) {
            $errors[$prefix . 'InvoiceDate'] = 'The invoice date cannot be in the future.';
        }
        if (mb_strlen($line['VendorName']) > 150) {
            $errors[$prefix . 'VendorName'] = 'At most 150 characters.';
        }
        if ($line['VendorGSTIN'] !== '' && !preg_match('/^\d{2}[A-Z0-9]{13}$/', $line['VendorGSTIN'])) {
            $errors[$prefix . 'VendorGSTIN'] = 'Enter a 15-character GSTIN.';
        }
        if (mb_strlen($line['InvoiceNumber']) > 50) {
            $errors[$prefix . 'InvoiceNumber'] = 'At most 50 characters.';
        }
        $amount = fp_parse_paise($line['Amount']);
        if ($amount === false || $amount <= 0 || $amount > FP_REQUEST_MAX_AMOUNT_PAISE) {
            $errors[$prefix . 'Amount'] = 'Enter an amount greater than zero, with up to 2 decimals.';
            $amount = 0;
        }
        $tax = $line['TaxAmount'] === '' ? 0 : fp_parse_paise($line['TaxAmount']);
        if ($tax === false || $tax > $amount) {
            $errors[$prefix . 'TaxAmount'] = 'Tax must be zero or more and not exceed the amount.';
            $tax = 0;
        }
        $total += $amount;
        $clean['lines'][] = array(
            'LineID'        => $line['LineID'],
            'CategoryID'    => $line['CategoryID'],
            'Description'   => $line['Description'],
            'ExpenseDate'   => $expenseDate ?: null,
            'VendorName'    => $line['VendorName'],
            'VendorGSTIN'   => $line['VendorGSTIN'],
            'InvoiceNumber' => $line['InvoiceNumber'],
            'InvoiceDate'   => $invoiceDate ?: null,
            'Amount'        => fp_paise_to_decimal($amount),
            'TaxAmount'     => fp_paise_to_decimal($tax),
        );
    }
    $clean['ClaimedAmount'] = fp_paise_to_decimal($total);
    $clean['LineCount'] = count($clean['lines']);
    return array($errors, $clean);
}

/**
 * Lines missing a mandatory document, keyed lines.N.Documents (same category rules as a reimbursement).
 */
function fp_settlement_missing_documents($lines, $documents)
{
    $byLine = array();
    foreach ($documents as $document) {
        if ((int) $document['LineID'] > 0) {
            $byLine[(int) $document['LineID']][] = $document['DocumentType'];
        }
    }
    $errors = array();
    foreach ($lines as $index => $line) {
        $missing = array();
        foreach (fp_document_requirements('reimbursement', $line) as $rule) {
            if (!array_intersect($rule['types'], $byLine[(int) $line['ID']] ?? array())) {
                $missing[] = $rule['label'];
            }
        }
        if ($missing) {
            $errors['lines.' . $index . '.Documents'] = 'Line ' . (int) $line['LineNo'] . ': attach ' . implode(' and ', $missing) . '.';
        }
    }
    return $errors;
}

function fp_settlement_snapshot($claim, $lines)
{
    $text = array();
    foreach ($lines as $line) {
        $text[] = implode(' | ', array((int) $line['CategoryID'], $line['Description'], (string) $line['ExpenseDate'], $line['VendorName'],
            $line['InvoiceNumber'], (string) $line['InvoiceDate'], $line['Amount'], $line['TaxAmount']));
    }
    return array(
        'SettlementNumber' => $claim['SettlementNumber'],
        'ClaimStatus'      => $claim['Status'],
        'Notes'            => $claim['Notes'],
        'ClaimedAmount'    => $claim['ClaimedAmount'],
        'LineCount'        => (int) $claim['LineCount'],
        'SettlementLines'  => implode("\n", $text),
    );
}

function fp_settlement_write_lines($conn, $settlementId, $cleanLines, $existingLines)
{
    $existing = array();
    foreach ($existingLines as $line) {
        $existing[(int) $line['ID']] = true;
    }
    $keep = array();
    $now = date('Y-m-d H:i:s');
    foreach ($cleanLines as $index => $line) {
        $lineNo = $index + 1;
        if ($line['LineID'] > 0 && isset($existing[$line['LineID']])) {
            $keep[$line['LineID']] = true;
            fp_policy_exec(
                $conn,
                'UPDATE branch_expense_settlement_lines
                 SET LineNo = ?, CategoryID = ?, Description = ?, ExpenseDate = ?, VendorName = ?, VendorGSTIN = ?, InvoiceNumber = ?, InvoiceDate = ?,
                     Amount = ?, TaxAmount = ?, UpdatedAt = ?
                 WHERE ID = ? AND SettlementID = ?',
                'iisssssssssii',
                array($lineNo, $line['CategoryID'], $line['Description'], $line['ExpenseDate'], $line['VendorName'], $line['VendorGSTIN'], $line['InvoiceNumber'],
                      $line['InvoiceDate'], $line['Amount'], $line['TaxAmount'], $now, $line['LineID'], $settlementId)
            );
        } else {
            fp_policy_exec(
                $conn,
                'INSERT INTO branch_expense_settlement_lines
                    (SettlementID, LineNo, CategoryID, Description, ExpenseDate, VendorName, VendorGSTIN, InvoiceNumber, InvoiceDate, Amount, TaxAmount, CreatedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                'iiisssssssss',
                array($settlementId, $lineNo, $line['CategoryID'], $line['Description'], $line['ExpenseDate'], $line['VendorName'], $line['VendorGSTIN'],
                      $line['InvoiceNumber'], $line['InvoiceDate'], $line['Amount'], $line['TaxAmount'], $now)
            );
        }
    }
    foreach (array_keys($existing) as $lineId) {
        if (!isset($keep[$lineId])) {
            fp_policy_exec($conn, 'DELETE FROM branch_expense_settlement_lines WHERE ID = ? AND SettlementID = ?', 'ii', array($lineId, $settlementId));
        }
    }
}

/**
 * Moves the advance to 'in_progress' the first time anything is settled against it.
 */
function fp_settlement_mark_in_progress($conn, $actor, $request, $now)
{
    if ($request['SettlementStatus'] !== 'not_started') {
        return;
    }
    fp_policy_exec(
        $conn,
        "UPDATE branch_expense_requests SET SettlementStatus = 'in_progress', RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?",
        'iisi',
        array((int) $actor['user_id'], (int) $actor['employee_id'], $now, (int) $request['ID'])
    );
}

/**
 * Locks the claim and its advance (advance first, the same order as every other writer) and loads both.
 * Returns array(claim, request) or an fp_result on failure.
 */
function fp_settlement_lock($conn, $settlementId)
{
    $rows = fp_db_rows($conn, 'SELECT RequestID FROM branch_expense_settlements WHERE ID = ?', 'i', array((int) $settlementId));
    if (!$rows || !fp_request_get($conn, $rows[0]['RequestID'], 'update')) {
        return fp_result(false, 404, 'Settlement not found.');
    }
    $claim = fp_settlement_get($conn, $settlementId, 'update');
    return array($claim, fp_request_get_detail($conn, $claim['RequestID']));
}

/* ------------------------------------------------------------------
   Holder: save, submit, cancel, documents
------------------------------------------------------------------ */

function fp_settlement_save($conn, $actor, $post)
{
    $in = fp_settlement_input($post);
    return fp_transaction($conn, function () use ($conn, $actor, $in) {
        $existingLines = array();
        if ($in['ID'] > 0) {
            $locked = fp_settlement_lock($conn, $in['ID']);
            if (isset($locked['ok'])) {
                return $locked;
            }
            list($claim, $request) = $locked;
            $block = fp_settlement_edit_block($actor, $request, $claim);
            if ($block) {
                return fp_result(false, $block[0], $block[1]);
            }
            if ((int) $claim['RowVersion'] !== $in['RowVersion']) {
                return fp_result(false, 409, 'This settlement was changed in another window. Reload the page and try again.');
            }
            $existingLines = fp_settlement_lines($conn, $claim['ID']);
        } else {
            $request = $in['RequestID'] > 0 && fp_request_get($conn, $in['RequestID'], 'update') ? fp_request_get_detail($conn, $in['RequestID']) : null;
            if (!$request) {
                return fp_result(false, 404, 'Advance not found.');
            }
            $block = fp_settlement_start_block($conn, $actor, $request);
            if ($block) {
                return fp_result(false, $block[0], $block[1]);
            }
            $claim = null;
        }

        list($errors, $clean) = fp_settlement_validate($in, fp_settlement_categories($conn, $request['CompanyID']));
        $existingIds = array_map('intval', array_column($existingLines, 'ID'));
        foreach ($in['lines'] as $index => $line) {
            if ($line['LineID'] > 0 && !in_array($line['LineID'], $existingIds, true)) {
                $errors['lines.' . $index . '.CategoryID'] = 'This line no longer exists. Reload the page.';
            }
        }
        if ($errors) {
            return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
        }

        $now = date('Y-m-d H:i:s');
        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        if (!$claim) {
            $number = fp_sequence_next($conn, 'STL', $now);
            fp_policy_exec(
                $conn,
                'INSERT INTO branch_expense_settlements
                    (SettlementNumber, RequestID, EmployeeID, OfficeID, Status, Notes, ClaimedAmount, LineCount, RowVersion, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
                 VALUES (?, ?, ?, ?, \'draft\', ?, ?, ?, 1, ?, ?, ?)',
                'siiissiiis',
                array($number, (int) $request['ID'], (int) $request['EmployeeID'], (int) $request['OfficeID'], $clean['Notes'], $clean['ClaimedAmount'], $clean['LineCount'], $userId, $employeeId, $now)
            );
            $id = (int) $conn->insert_id;
            fp_settlement_write_lines($conn, $id, $clean['lines'], array());
            $saved = fp_settlement_get($conn, $id);
            fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'settlement_create', null, fp_settlement_snapshot($saved, fp_settlement_lines($conn, $id)));
            return fp_result(true, 200, 'Settlement draft saved as ' . $number . '.', array('data' => array('ID' => $id, 'SettlementNumber' => $number, 'RowVersion' => 1)));
        }

        $old = fp_settlement_snapshot($claim, $existingLines);
        $affected = fp_policy_exec(
            $conn,
            'UPDATE branch_expense_settlements SET Notes = ?, ClaimedAmount = ?, LineCount = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND RowVersion = ?',
            'ssiiisii',
            array($clean['Notes'], $clean['ClaimedAmount'], $clean['LineCount'], $userId, $employeeId, $now, (int) $claim['ID'], $in['RowVersion'])
        );
        if ($affected !== 1) {
            return fp_result(false, 409, 'This settlement was changed in another window. Reload the page and try again.');
        }
        fp_settlement_write_lines($conn, (int) $claim['ID'], $clean['lines'], $existingLines);
        $saved = fp_settlement_get($conn, $claim['ID']);
        $new = fp_settlement_snapshot($saved, fp_settlement_lines($conn, $claim['ID']));
        if (fp_audit_changed_fields($old, $new)) {
            fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'settlement_update', $old, $new);
        }
        return fp_result(true, 200, 'Draft saved.', array('data' => array('ID' => (int) $claim['ID'], 'SettlementNumber' => $claim['SettlementNumber'], 'RowVersion' => (int) $saved['RowVersion'])));
    });
}

function fp_settlement_submit($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    if ($id <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    $result = fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion) {
        $locked = fp_settlement_lock($conn, $id);
        if (isset($locked['ok'])) {
            return $locked;
        }
        list($claim, $request) = $locked;
        $block = fp_settlement_edit_block($actor, $request, $claim);
        if ($block) {
            return fp_result(false, $block[0], $block[1]);
        }
        if ((int) $claim['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This settlement was changed in another window. Reload the page and try again.');
        }
        $lines = fp_settlement_lines($conn, $id);
        if (!$lines) {
            return fp_result(false, 422, 'Add at least one expense line.', array('errors' => array('lines' => 'Add at least one expense line.')));
        }
        $categories = fp_settlement_categories($conn, $request['CompanyID']);
        $errors = array();
        foreach ($lines as $index => $line) {
            if (!isset($categories[(int) $line['CategoryID']])) {
                $errors['lines.' . $index . '.CategoryID'] = 'Line ' . (int) $line['LineNo'] . ': the category is no longer active. Choose another.';
            }
        }
        $errors = array_merge($errors, fp_settlement_missing_documents($lines, fp_settlement_documents($conn, $id)));
        if ($errors) {
            return fp_result(false, 422, 'The settlement is not ready to submit.', array('errors' => $errors));
        }

        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            "UPDATE branch_expense_settlements
             SET Status = 'submitted', SubmissionRound = SubmissionRound + 1, SubmittedAt = ?, ReviewedAt = NULL, ReviewedByUserID = 0, ReviewedByEmployeeID = 0,
                 ReviewComments = '', RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ?",
            'siisi',
            array($now, (int) $actor['user_id'], (int) $actor['employee_id'], $now, $id)
        );
        fp_settlement_mark_in_progress($conn, $actor, $request, $now);
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'settlement_submit', array('ClaimStatus' => $claim['Status']), array(
            'ClaimStatus' => 'submitted', 'SettlementNumber' => $claim['SettlementNumber'], 'ClaimedAmount' => $claim['ClaimedAmount'], 'LineCount' => (int) $claim['LineCount'],
        ));
        fp_notice_add('settlement', $request['ID'], array('event' => 'submitted', 'settlement_id' => $id));
        return fp_result(true, 200, $claim['SettlementNumber'] . ' was submitted for branch review.', array('data' => array('ID' => $id, 'RequestID' => (int) $request['ID'])));
    });
    fp_notice_finish($conn, $result);
    return $result;
}

function fp_settlement_cancel($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $reason = preg_replace('/[ \t]+/', ' ', fp_input_string($post, 'Reason'));
    if ($id <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'The reason can be at most 500 characters.', array('errors' => array('Reason' => 'Too long.')));
    }
    return fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $reason) {
        $locked = fp_settlement_lock($conn, $id);
        if (isset($locked['ok'])) {
            return $locked;
        }
        list($claim, $request) = $locked;
        $block = fp_settlement_edit_block($actor, $request, $claim);
        if ($block) {
            return fp_result(false, $block[0], $block[1]);
        }
        if ((int) $claim['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This settlement was changed in another window. Reload the page and try again.');
        }
        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            "UPDATE branch_expense_settlements SET Status = 'cancelled', CancelledAt = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?",
            'siisi',
            array($now, (int) $actor['user_id'], (int) $actor['employee_id'], $now, $id)
        );
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'settlement_cancel', array('ClaimStatus' => $claim['Status']), array(
            'ClaimStatus' => 'cancelled', 'SettlementNumber' => $claim['SettlementNumber'], 'ClaimedAmount' => $claim['ClaimedAmount'],
        ), $reason);
        return fp_result(true, 200, $claim['SettlementNumber'] . ' was cancelled.', array('data' => array('RequestID' => (int) $request['ID'])));
    });
}

/**
 * Error message for an uploaded file, or null when it is an acceptable PDF/image. Returns array(message, mime).
 */
function fp_settlement_upload_check($file)
{
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        return array('Choose a file to upload.', '');
    }
    $maxBytes = fp_attachment_max_bytes();
    if (in_array($file['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true) || (int) $file['size'] > $maxBytes) {
        return array('The file is larger than ' . round($maxBytes / 1048576, 1) . ' MB.', '');
    }
    if ($file['error'] !== UPLOAD_ERR_OK || (int) $file['size'] <= 0) {
        return array('The upload failed. Please try again.', '');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(fp_attachment_types()[$mime])) {
        return array('Only PDF, JPG, PNG or WEBP files are allowed.', '');
    }
    if (!fp_document_content_ok($file['tmp_name'], $mime)) {
        return array('The file appears to be damaged or is not a real PDF or image.', '');
    }
    return array(null, $mime);
}

function fp_settlement_document_add($conn, $actor, $post, $file, $mover = 'move_uploaded_file')
{
    $settlementId = fp_input_int($post, 'SettlementID');
    $lineId = fp_input_int($post, 'LineID');
    $documentType = fp_input_string($post, 'DocumentType');
    $notes = preg_replace('/\s+/u', ' ', fp_input_string($post, 'Notes'));
    if ($settlementId <= 0 && !$post && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        return fp_result(false, 422, 'The file is larger than ' . round(fp_attachment_max_bytes() / 1048576, 1) . ' MB.');
    }
    if ($settlementId <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (!isset(fp_document_types()[$documentType])) {
        return fp_result(false, 422, 'Choose what kind of document this is.', array('errors' => array('DocumentType' => 'Required.')));
    }
    if (mb_strlen($notes) > 255) {
        return fp_result(false, 422, 'The note can be at most 255 characters.', array('errors' => array('Notes' => 'Too long.')));
    }
    list($error, $mime) = fp_settlement_upload_check($file);
    if ($error) {
        return fp_result(false, 422, $error);
    }

    $relative = 'settlements/' . date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . fp_attachment_types()[$mime];
    $target = fp_attachment_storage_dir() . '/' . $relative;
    $stored = false;
    try {
        $result = fp_transaction($conn, function () use ($conn, $actor, $settlementId, $lineId, $documentType, $notes, $file, $mime, $relative, $target, $mover, &$stored) {
            $locked = fp_settlement_lock($conn, $settlementId);
            if (isset($locked['ok'])) {
                return $locked;
            }
            list($claim, $request) = $locked;
            $block = fp_settlement_edit_block($actor, $request, $claim);
            if ($block) {
                return fp_result(false, $block[0], 'Documents can only be added to your own draft or returned settlement.');
            }
            $line = $lineId > 0 ? fp_db_rows($conn, 'SELECT LineNo FROM branch_expense_settlement_lines WHERE ID = ? AND SettlementID = ?', 'ii', array($lineId, $settlementId)) : array();
            if ($lineId > 0 && !$line) {
                return fp_result(false, 422, 'That line no longer exists. Save the draft and try again.');
            }
            $count = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt FROM branch_expense_settlement_documents WHERE SettlementID = ? AND IsDeleted = 0', 'i', array($settlementId));
            if ((int) $count[0]['Cnt'] >= FP_REQUEST_MAX_ATTACHMENTS) {
                return fp_result(false, 422, 'A settlement can have at most ' . FP_REQUEST_MAX_ATTACHMENTS . ' documents.');
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
            fp_policy_exec(
                $conn,
                'INSERT INTO branch_expense_settlement_documents
                    (SettlementID, LineID, DocumentType, Notes, OriginalName, StoredPath, MimeType, SizeBytes, Sha256, UploadedByUserID, UploadedByEmployeeID, UploadedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                'iisssssisiis',
                array($settlementId, $lineId > 0 ? $lineId : null, $documentType, $notes, $name, $relative, $mime, $size, $hash, (int) $actor['user_id'], (int) $actor['employee_id'], date('Y-m-d H:i:s'))
            );
            $documentId = (int) $conn->insert_id;
            fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'settlement_doc_add', null, array(
                'SettlementNumber' => $claim['SettlementNumber'], 'DocumentID' => $documentId, 'LineNo' => $line ? (int) $line[0]['LineNo'] : null,
                'DocumentType' => $documentType, 'OriginalName' => $name, 'SizeBytes' => $size, 'Sha256' => $hash,
            ));
            return fp_result(true, 200, fp_document_type_label($documentType) . ' attached.', array('data' => array('ID' => $documentId)));
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

function fp_settlement_document_delete($conn, $actor, $post)
{
    $documentId = fp_input_int($post, 'ID');
    if ($documentId <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    return fp_transaction($conn, function () use ($conn, $actor, $documentId) {
        $rows = fp_db_rows($conn, 'SELECT * FROM branch_expense_settlement_documents WHERE ID = ? AND IsDeleted = 0', 'i', array($documentId));
        if (!$rows) {
            return fp_result(false, 404, 'This document no longer exists.');
        }
        $locked = fp_settlement_lock($conn, $rows[0]['SettlementID']);
        if (isset($locked['ok'])) {
            return $locked;
        }
        list($claim, $request) = $locked;
        if (fp_settlement_edit_block($actor, $request, $claim)) {
            return fp_result(false, 403, 'Documents can only be removed from your own draft or returned settlement.');
        }
        $affected = fp_policy_exec(
            $conn,
            'UPDATE branch_expense_settlement_documents SET IsDeleted = 1, DeletedByUserID = ?, DeletedAt = ? WHERE ID = ? AND IsDeleted = 0',
            'isi',
            array((int) $actor['user_id'], date('Y-m-d H:i:s'), $documentId)
        );
        if ($affected !== 1) {
            return fp_result(false, 409, 'This document was already removed.');
        }
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'settlement_doc_remove', array(
            'SettlementNumber' => $claim['SettlementNumber'], 'DocumentID' => $documentId, 'DocumentType' => $rows[0]['DocumentType'], 'OriginalName' => $rows[0]['OriginalName'],
        ), null);
        return fp_result(true, 200, 'Document removed.');
    });
}

/**
 * Returns the document row and absolute path when the actor may open it, otherwise null.
 * Removed documents stay available to people with audit access.
 */
function fp_settlement_document_for_download($conn, $actor, $documentId)
{
    $rows = fp_db_rows($conn, 'SELECT d.*, s.Status AS ClaimStatus, s.RequestID FROM branch_expense_settlement_documents d INNER JOIN branch_expense_settlements s ON s.ID = d.SettlementID WHERE d.ID = ?', 'i', array((int) $documentId));
    if (!$rows) {
        return null;
    }
    $request = fp_request_get_detail($conn, $rows[0]['RequestID']);
    if (!$request || !fp_settlement_can_see_documents($actor, $request, array('Status' => $rows[0]['ClaimStatus']))) {
        return null;
    }
    if ((int) $rows[0]['IsDeleted'] === 1 && !fp_record_in_scope($actor, 'audit', fp_request_scope_record($request))) {
        return null;
    }
    $base = realpath(fp_attachment_storage_dir());
    $path = realpath(fp_attachment_storage_dir() . '/' . $rows[0]['StoredPath']);
    if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
        return null;
    }
    return array('row' => $rows[0], 'path' => $path);
}

/* ------------------------------------------------------------------
   Branch review and Finance verification
------------------------------------------------------------------ */

/**
 * Points a reviewer should look at, per line ID: list of plain-language warnings.
 */
function fp_settlement_checks($conn, $request, $claim, $lines, $documents)
{
    $checks = array();
    $approvedOn = substr((string) $request['ApprovedAt'], 0, 10);
    $advanceCategories = array_map('intval', array_column(fp_request_items($conn, $request['ID']), 'CategoryID'));
    $hashesByLine = array();
    foreach ($documents as $document) {
        $hashesByLine[(int) $document['LineID']][] = $document['Sha256'];
    }
    foreach ($lines as $line) {
        $warnings = array();
        if ($approvedOn !== '' && $line['ExpenseDate'] < $approvedOn) {
            $warnings[] = 'Spent before the advance was approved (' . fp_timeline_date($approvedOn) . ').';
        }
        if ($request['PeriodFrom'] && ($line['ExpenseDate'] < $request['PeriodFrom'] || $line['ExpenseDate'] > $request['PeriodTo'])) {
            $warnings[] = 'Outside the advance period (' . fp_timeline_date($request['PeriodFrom']) . ' to ' . fp_timeline_date($request['PeriodTo']) . ').';
        }
        if (!in_array((int) $line['CategoryID'], $advanceCategories, true)) {
            $warnings[] = 'Category is not one the advance was approved for.';
        }
        if ($line['InvoiceNumber'] !== '') {
            $byGstin = $line['VendorGSTIN'] !== '';
            $vendorMatch = function ($alias) use ($byGstin) {
                return $byGstin ? $alias . '.VendorGSTIN = ?' : 'LOWER(' . $alias . '.VendorName) = LOWER(?)';
            };
            $vendor = $byGstin ? $line['VendorGSTIN'] : $line['VendorName'];
            $found = array_merge(
                array_column(fp_db_rows(
                    $conn,
                    "SELECT DISTINCT s.SettlementNumber AS Ref FROM branch_expense_settlement_lines l INNER JOIN branch_expense_settlements s ON s.ID = l.SettlementID
                     WHERE LOWER(l.InvoiceNumber) = LOWER(?) AND " . $vendorMatch('l') . " AND s.ID <> ? AND s.Status <> 'cancelled' AND l.Decision <> 'rejected'",
                    'ssi',
                    array($line['InvoiceNumber'], $vendor, (int) $claim['ID'])
                ), 'Ref'),
                array_column(fp_db_rows(
                    $conn,
                    "SELECT DISTINCT r.ExpenseNumber AS Ref FROM branch_expense_items i INNER JOIN branch_expense_requests r ON r.ID = i.RequestID
                     WHERE LOWER(i.InvoiceNumber) = LOWER(?) AND " . $vendorMatch('i') . " AND r.Status IN ('submitted', 'approved') AND r.RequestType <> 'advance'",
                    'ss',
                    array($line['InvoiceNumber'], $vendor)
                ), 'Ref')
            );
            if ($found) {
                $warnings[] = 'Invoice ' . $line['InvoiceNumber'] . ' is also claimed in ' . implode(', ', $found) . '.';
            }
        }
        $hashes = array_values(array_unique($hashesByLine[(int) $line['ID']] ?? array()));
        if ($hashes) {
            $marks = implode(',', array_fill(0, count($hashes), '?'));
            $found = array_merge(
                array_column(fp_db_rows(
                    $conn,
                    "SELECT DISTINCT s.SettlementNumber AS Ref FROM branch_expense_settlement_documents d INNER JOIN branch_expense_settlements s ON s.ID = d.SettlementID
                     WHERE d.Sha256 IN ($marks) AND d.IsDeleted = 0 AND s.ID <> ? AND s.Status <> 'cancelled'",
                    str_repeat('s', count($hashes)) . 'i',
                    array_merge($hashes, array((int) $claim['ID']))
                ), 'Ref'),
                array_column(fp_db_rows(
                    $conn,
                    "SELECT DISTINCT r.ExpenseNumber AS Ref FROM branch_expense_attachments a INNER JOIN branch_expense_requests r ON r.ID = a.RequestID
                     WHERE a.Sha256 IN ($marks) AND a.IsDeleted = 0 AND r.Status NOT IN ('draft', 'cancelled')",
                    str_repeat('s', count($hashes)),
                    $hashes
                ), 'Ref')
            );
            if ($found) {
                $warnings[] = 'The same receipt file is also used in ' . implode(', ', array_unique($found)) . '.';
            }
        }
        $checks[(int) $line['ID']] = $warnings;
    }
    return $checks;
}

/**
 * Branch review. Post: ID, RowVersion, Decision (confirm | return), Comments (required to return).
 */
function fp_settlement_review($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $decision = fp_input_string($post, 'Decision');
    $comments = preg_replace('/[ \t]+/', ' ', fp_input_string($post, 'Comments'));
    if ($id <= 0 || !in_array($decision, array('confirm', 'return'), true)) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (mb_strlen($comments) > 1000 || ($decision === 'return' && mb_strlen($comments) < FP_SETTLEMENT_REASON_MIN)) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => array('Comments' => $decision === 'return' ? 'Say what must be corrected (5-1000 characters).' : 'At most 1000 characters.')));
    }
    $result = fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $decision, $comments) {
        $locked = fp_settlement_lock($conn, $id);
        if (isset($locked['ok'])) {
            return $locked;
        }
        list($claim, $request) = $locked;
        if (!fp_settlement_can_view($conn, $actor, $request)) {
            return fp_result(false, 404, 'Settlement not found.');
        }
        $block = fp_settlement_review_block($actor, $request, $claim);
        if ($block) {
            return fp_result(false, $block[0], $block[1]);
        }
        if ((int) $claim['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This settlement was changed by someone else. Reload the page.');
        }
        $now = date('Y-m-d H:i:s');
        if ($decision === 'confirm') {
            fp_policy_exec(
                $conn,
                "UPDATE branch_expense_settlements SET Status = 'branch_reviewed', ReviewedAt = ?, ReviewedByUserID = ?, ReviewedByEmployeeID = ?, ReviewComments = ?,
                     RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?",
                'siisiisi',
                array($now, (int) $actor['user_id'], (int) $actor['employee_id'], $comments, (int) $actor['user_id'], (int) $actor['employee_id'], $now, $id)
            );
        } else {
            fp_policy_exec(
                $conn,
                "UPDATE branch_expense_settlements SET Status = 'returned', ReturnedAt = ?, ReturnedByEmployeeID = ?, ReturnReason = ?,
                     RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?",
                'sisiisi',
                array($now, (int) $actor['employee_id'], $comments, (int) $actor['user_id'], (int) $actor['employee_id'], $now, $id)
            );
        }
        $newStatus = $decision === 'confirm' ? 'branch_reviewed' : 'returned';
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], $decision === 'confirm' ? 'settlement_branch_confirm' : 'settlement_return', array('ClaimStatus' => $claim['Status']), array(
            'ClaimStatus' => $newStatus, 'SettlementNumber' => $claim['SettlementNumber'], 'ClaimedAmount' => $claim['ClaimedAmount'], 'Stage' => 'Branch review',
        ), $comments);
        if ($decision === 'return') {
            fp_notice_add('settlement', $request['ID'], array('event' => 'returned', 'settlement_id' => $id, 'comments' => $comments));
        }
        return fp_result(true, 200, $decision === 'confirm'
            ? $claim['SettlementNumber'] . ' was confirmed and sent to Head Office Finance for verification.'
            : $claim['SettlementNumber'] . ' was returned to the employee.', array('data' => array('RequestID' => (int) $request['ID'])));
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/**
 * Finance verification. Post: ID, RowVersion, Decision (verify | return), Comments,
 * lines[] = {LineID, Decision (accepted | partial | rejected), AcceptedAmount (partial only), Reason (partial and rejected)}.
 */
function fp_settlement_verify($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $decision = fp_input_string($post, 'Decision');
    $comments = preg_replace('/[ \t]+/', ' ', fp_input_string($post, 'Comments'));
    if ($id <= 0 || !in_array($decision, array('verify', 'return'), true)) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (mb_strlen($comments) > 1000 || ($decision === 'return' && mb_strlen($comments) < FP_SETTLEMENT_REASON_MIN)) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => array('Comments' => $decision === 'return' ? 'Say what must be corrected (5-1000 characters).' : 'At most 1000 characters.')));
    }
    $posted = array();
    foreach (isset($post['lines']) && is_array($post['lines']) ? $post['lines'] : array() as $line) {
        if (is_array($line) && fp_input_int($line, 'LineID') > 0) {
            $posted[fp_input_int($line, 'LineID')] = array(
                'Decision' => fp_input_string($line, 'Decision'),
                'Amount'   => fp_input_string($line, 'AcceptedAmount'),
                'Reason'   => preg_replace('/\s+/u', ' ', fp_input_string($line, 'Reason')),
            );
        }
    }

    $result = fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $decision, $comments, $posted) {
        $locked = fp_settlement_lock($conn, $id);
        if (isset($locked['ok'])) {
            return $locked;
        }
        list($claim, $request) = $locked;
        if (!fp_settlement_can_view($conn, $actor, $request)) {
            return fp_result(false, 404, 'Settlement not found.');
        }
        $block = fp_settlement_verify_block($actor, $request, $claim);
        if ($block) {
            return fp_result(false, $block[0], $block[1]);
        }
        if ((int) $claim['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This settlement was changed by someone else. Reload the page.');
        }
        $now = date('Y-m-d H:i:s');

        if ($decision === 'return') {
            fp_policy_exec(
                $conn,
                "UPDATE branch_expense_settlements SET Status = 'returned', ReturnedAt = ?, ReturnedByEmployeeID = ?, ReturnReason = ?,
                     RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?",
                'sisiisi',
                array($now, (int) $actor['employee_id'], $comments, (int) $actor['user_id'], (int) $actor['employee_id'], $now, $id)
            );
            fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'settlement_return', array('ClaimStatus' => $claim['Status']), array(
                'ClaimStatus' => 'returned', 'SettlementNumber' => $claim['SettlementNumber'], 'ClaimedAmount' => $claim['ClaimedAmount'], 'Stage' => 'Finance verification',
            ), $comments);
            fp_notice_add('settlement', $request['ID'], array('event' => 'returned', 'settlement_id' => $id, 'comments' => $comments));
            return fp_result(true, 200, $claim['SettlementNumber'] . ' was returned to the employee.', array('data' => array('RequestID' => (int) $request['ID'])));
        }

        $lines = fp_settlement_lines($conn, $id);
        $errors = array();
        $decided = array();
        $acceptedTotal = 0;
        $labels = fp_settlement_line_decisions();
        foreach ($lines as $index => $line) {
            $prefix = 'lines.' . $index . '.';
            $in = $posted[(int) $line['ID']] ?? array('Decision' => '', 'Amount' => '', 'Reason' => '');
            $amount = fp_decimal_to_paise($line['Amount']);
            if (!in_array($in['Decision'], array('accepted', 'partial', 'rejected'), true)) {
                $errors[$prefix . 'Decision'] = 'Line ' . (int) $line['LineNo'] . ': accept, partly accept or reject it.';
                continue;
            }
            if ($in['Decision'] !== 'accepted' && (mb_strlen($in['Reason']) < FP_SETTLEMENT_REASON_MIN || mb_strlen($in['Reason']) > 500)) {
                $errors[$prefix . 'Reason'] = 'Line ' . (int) $line['LineNo'] . ': give the reason (5-500 characters).';
            }
            $accepted = $amount;
            if ($in['Decision'] === 'rejected') {
                $accepted = 0;
            } elseif ($in['Decision'] === 'partial') {
                $accepted = fp_parse_paise($in['Amount']);
                if ($accepted === false || $accepted <= 0 || $accepted >= $amount) {
                    $errors[$prefix . 'AcceptedAmount'] = 'Line ' . (int) $line['LineNo'] . ': the accepted amount must be more than zero and less than ' . fp_payment_money($amount) . '.';
                    continue;
                }
            }
            $acceptedTotal += $accepted;
            $decided[] = array('line' => $line, 'decision' => $in['Decision'], 'accepted' => $accepted, 'reason' => $in['Decision'] === 'accepted' ? '' : $in['Reason']);
        }
        if ($errors) {
            return fp_result(false, 422, 'Please decide every line.', array('errors' => $errors));
        }

        $summaryText = array();
        foreach ($decided as $item) {
            fp_policy_exec(
                $conn,
                'UPDATE branch_expense_settlement_lines SET Decision = ?, AcceptedAmount = ?, DecisionReason = ?, UpdatedAt = ? WHERE ID = ?',
                'ssssi',
                array($item['decision'], fp_paise_to_decimal($item['accepted']), $item['reason'], $now, (int) $item['line']['ID'])
            );
            $summaryText[] = 'Line ' . (int) $item['line']['LineNo'] . ': ' . strtolower($labels[$item['decision']])
                . ($item['decision'] === 'partial' ? ' ' . fp_payment_money($item['accepted']) . ' of ' . fp_payment_money(fp_decimal_to_paise($item['line']['Amount'])) : ' ' . fp_payment_money(fp_decimal_to_paise($item['line']['Amount'])))
                . ($item['reason'] !== '' ? ' (' . $item['reason'] . ')' : '');
        }
        fp_policy_exec(
            $conn,
            "UPDATE branch_expense_settlements SET Status = 'verified', AcceptedAmount = ?, VerifiedAt = ?, VerifiedByUserID = ?, VerifiedByEmployeeID = ?, VerifyComments = ?,
                 RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?",
            'ssiisiisi',
            array(fp_paise_to_decimal($acceptedTotal), $now, (int) $actor['user_id'], (int) $actor['employee_id'], $comments, (int) $actor['user_id'], (int) $actor['employee_id'], $now, $id)
        );
        fp_settlement_mark_in_progress($conn, $actor, $request, $now);
        $summary = fp_settlement_summary($conn, $request['ID']);
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'settlement_verify', array('ClaimStatus' => $claim['Status']), array(
            'ClaimStatus' => 'verified', 'SettlementNumber' => $claim['SettlementNumber'], 'ClaimedAmount' => $claim['ClaimedAmount'],
            'AcceptedAmount' => fp_paise_to_decimal($acceptedTotal), 'LineDecisions' => implode("\n", $summaryText),
        ) + fp_settlement_summary_values($summary), $comments);
        fp_notice_add('settlement', $request['ID'], array('event' => 'verified', 'settlement_id' => $id, 'accepted' => $acceptedTotal, 'claimed' => fp_decimal_to_paise($claim['ClaimedAmount']), 'balance' => $summary['balance']));
        return fp_result(true, 200, $claim['SettlementNumber'] . ' verified: ' . fp_payment_money($acceptedTotal) . ' of ' . fp_payment_money(fp_decimal_to_paise($claim['ClaimedAmount'])) . ' accepted. '
            . fp_settlement_balance_text($summary['balance']) . '.', array('data' => array('RequestID' => (int) $request['ID'])));
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/* ------------------------------------------------------------------
   Cash returned, extra paid, closing
------------------------------------------------------------------ */

/**
 * Records cash returned by the holder (refund) or extra paid to the holder (additional).
 * Post: RequestID, RowVersion, TxnType, Amount, TxnDate, TxnMode, Reference, Remarks, ConfirmSharedReference.
 */
function fp_settlement_txn_record($conn, $actor, $post)
{
    $requestId = fp_input_int($post, 'RequestID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $type = fp_input_string($post, 'TxnType');
    $amountPaise = fp_parse_paise(fp_input_string($post, 'Amount'));
    $date = fp_parse_date(fp_input_string($post, 'TxnDate'));
    $mode = fp_input_string($post, 'TxnMode');
    $reference = preg_replace('/\s+/', ' ', fp_input_string($post, 'Reference'));
    $remarks = fp_input_string($post, 'Remarks');
    $confirmShared = fp_input_string($post, 'ConfirmSharedReference') === '1';
    if ($requestId <= 0 || !isset(fp_settlement_txn_types()[$type])) {
        return fp_result(false, 422, 'Invalid request.');
    }
    $errors = array();
    if ($amountPaise === false || $amountPaise <= 0) {
        $errors['Amount'] = 'Enter an amount greater than zero (up to 2 decimals).';
    }
    if (!$date) {
        $errors['TxnDate'] = 'Enter the date of the transfer.';
    } elseif ($date > date('Y-m-d')) {
        $errors['TxnDate'] = 'The date cannot be in the future.';
    }
    if (!isset(fp_payment_modes()[$mode])) {
        $errors['TxnMode'] = 'Choose how the money was transferred.';
    }
    $referenceKey = fp_payment_reference_key($reference);
    if ($reference === '' && !in_array($mode, fp_payment_reference_optional_modes(), true)) {
        $errors['Reference'] = 'Enter the transaction reference (UTR, UPI or cheque number).';
    } elseif ($reference !== '' && (mb_strlen($reference) > 100 || !preg_match('/^[A-Za-z0-9 \/._-]+$/', $reference) || strlen($referenceKey) < 4)) {
        $errors['Reference'] = 'Use 4-100 characters: letters, numbers, space, / . _ or -.';
    }
    if (mb_strlen($remarks) > 500) {
        $errors['Remarks'] = 'Remarks can be at most 500 characters.';
    }
    if ($errors) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
    }

    $result = fp_transaction($conn, function () use ($conn, $actor, $requestId, $rowVersion, $type, $amountPaise, $date, $mode, $reference, $referenceKey, $remarks, $confirmShared) {
        $request = fp_request_get($conn, $requestId, 'update') ? fp_request_get_detail($conn, $requestId) : null;
        if (!$request || !fp_record_in_scope($actor, 'pay', fp_request_scope_record($request))) {
            return fp_result(false, 404, 'Advance not found.');
        }
        if ((int) $request['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This advance was changed by someone else. Reload the page and check the balance again.');
        }
        $block = fp_settlement_finance_block($actor, $request);
        if ($block) {
            return fp_result(false, $block[0], $block[1]);
        }
        $summary = fp_settlement_summary($conn, $requestId);
        if ($summary['paid'] <= 0) {
            return fp_result(false, 422, 'Nothing has been paid on this advance yet.');
        }
        if ($summary['first_paid'] !== '' && $date < $summary['first_paid']) {
            return fp_result(false, 422, 'The date cannot be before the advance was paid (' . fp_timeline_date($summary['first_paid']) . ').', array('errors' => array('TxnDate' => 'Before the advance payment.')));
        }
        $due = $type === 'refund' ? $summary['balance'] : -$summary['balance'];
        if ($due <= 0) {
            return fp_result(false, 422, $type === 'refund' ? 'No cash is due back from the employee. ' . fp_settlement_balance_text($summary['balance']) . '.' : 'Nothing is due to the employee. ' . fp_settlement_balance_text($summary['balance']) . '.');
        }
        if ($amountPaise > $due) {
            return fp_result(false, 422, 'The amount is more than the ' . fp_payment_money($due) . ' due.', array('errors' => array('Amount' => 'At most ' . fp_payment_money($due) . '.')));
        }

        $shared = 0;
        if ($referenceKey !== '') {
            $others = array();
            foreach (fp_db_rows(
                $conn,
                "SELECT t.TxnNumber, t.RequestID, r.ExpenseNumber FROM branch_expense_settlement_transactions t INNER JOIN branch_expense_requests r ON r.ID = t.RequestID
                 WHERE t.ReferenceKey = ? AND t.TxnType = ? AND t.Status <> 'failed' ORDER BY t.ID",
                'ss',
                array($referenceKey, $type)
            ) as $match) {
                if ((int) $match['RequestID'] === $requestId) {
                    return fp_result(false, 422, 'This reference is already recorded for this advance (' . $match['TxnNumber'] . ').', array('errors' => array('Reference' => 'Already used for ' . $match['TxnNumber'] . '.')));
                }
                $others[] = array('TxnNumber' => $match['TxnNumber'], 'ExpenseNumber' => $match['ExpenseNumber']);
            }
            if ($others && !$confirmShared) {
                return fp_result(false, 422, 'This reference is already used on another advance. If one transfer covered several advances, tick the confirmation and save again.', array(
                    'errors' => array('Reference' => 'Already used on ' . implode(', ', array_column($others, 'ExpenseNumber')) . '.'),
                    'data'   => array('shared_reference' => $others),
                ));
            }
            $shared = $others ? 1 : 0;
        }

        $now = date('Y-m-d H:i:s');
        $number = fp_sequence_next($conn, $type === 'refund' ? 'RFD' : 'ADP', $date);
        $amount = fp_paise_to_decimal($amountPaise);
        $hint = $type === 'additional' ? fp_payment_employee_account_hint($conn, $request['EmployeeID']) : '';
        fp_policy_exec(
            $conn,
            'INSERT INTO branch_expense_settlement_transactions
                (TxnNumber, RequestID, TxnType, Amount, TxnDate, TxnMode, Reference, ReferenceKey, SharedReference, EmployeeID, AccountHint, Remarks, Status,
                 RecordedByUserID, RecordedByEmployeeID, RecordedByUserName, RecordedAt, IPAddress)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'completed\', ?, ?, ?, ?, ?)',
            'sissssssiissiisss',
            array($number, $requestId, $type, $amount, $date, $mode, $reference, $referenceKey, $shared, (int) $request['EmployeeID'], $hint, $remarks,
                  (int) $actor['user_id'], (int) $actor['employee_id'], (string) $actor['username'], $now, substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45))
        );
        if ($request['SettlementStatus'] === 'not_started') {
            fp_settlement_mark_in_progress($conn, $actor, $request, $now);
        } else {
            fp_policy_exec($conn, 'UPDATE branch_expense_requests SET RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?', 'iisi', array((int) $actor['user_id'], (int) $actor['employee_id'], $now, $requestId));
        }
        $after = fp_settlement_summary($conn, $requestId);
        $new = array('TxnNumber' => $number, 'TxnType' => fp_settlement_txn_types()[$type], 'Amount' => $amount, 'TxnDate' => $date,
            'Mode' => fp_payment_modes()[$mode], 'Reference' => $reference) + fp_settlement_summary_values($after);
        if ($shared) {
            $new['SharedReference'] = 'Yes';
        }
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $requestId, $type === 'refund' ? 'settlement_refund' : 'settlement_additional', null, $new, $remarks);
        fp_notice_add('settlement', $requestId, array('event' => $type, 'number' => $number, 'amount' => $amountPaise, 'mode' => fp_payment_modes()[$mode], 'reference' => $reference, 'balance' => $after['balance']));
        return fp_result(true, 200, ($type === 'refund' ? 'Cash returned' : 'Extra payment') . ' ' . $number . ' of ' . fp_payment_money($amountPaise) . ' recorded. '
            . fp_settlement_balance_text($after['balance']) . '.', array('data' => array('TxnNumber' => $number)));
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/**
 * Marks a completed refund / extra payment failed (it never arrived) or reversed. Post: TxnID, Outcome, Reason, StatusReference.
 */
function fp_settlement_txn_change_status($conn, $actor, $post)
{
    $txnId = fp_input_int($post, 'TxnID');
    $outcome = fp_input_string($post, 'Outcome');
    $reason = preg_replace('/[ \t]+/', ' ', fp_input_string($post, 'Reason'));
    $statusReference = preg_replace('/\s+/', ' ', fp_input_string($post, 'StatusReference'));
    if ($txnId <= 0 || !in_array($outcome, array('failed', 'reversed'), true)) {
        return fp_result(false, 422, 'Invalid request.');
    }
    $errors = array();
    if (mb_strlen($reason) < FP_PAYMENT_REASON_MIN || mb_strlen($reason) > FP_PAYMENT_REASON_MAX) {
        $errors['Reason'] = 'Please write why (' . FP_PAYMENT_REASON_MIN . '-' . FP_PAYMENT_REASON_MAX . ' characters).';
    }
    if (mb_strlen($statusReference) > 100) {
        $errors['StatusReference'] = 'At most 100 characters.';
    }
    if ($errors) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
    }
    $result = fp_transaction($conn, function () use ($conn, $actor, $txnId, $outcome, $reason, $statusReference) {
        $rows = fp_db_rows($conn, 'SELECT RequestID FROM branch_expense_settlement_transactions WHERE ID = ?', 'i', array($txnId));
        if (!$rows || !fp_request_get($conn, $rows[0]['RequestID'], 'update')) {
            return fp_result(false, 404, 'Entry not found.');
        }
        $txn = fp_db_rows($conn, 'SELECT * FROM branch_expense_settlement_transactions WHERE ID = ? FOR UPDATE', 'i', array($txnId))[0];
        $request = fp_request_get_detail($conn, $txn['RequestID']);
        if (!fp_record_in_scope($actor, 'pay', fp_request_scope_record($request))) {
            return fp_result(false, 404, 'Entry not found.');
        }
        $block = fp_settlement_finance_block($actor, $request);
        if ($block) {
            return fp_result(false, $block[0], $block[1]);
        }
        if ($txn['Status'] !== 'completed') {
            return fp_result(false, 409, 'This entry is already marked ' . strtolower(fp_payment_statuses()[$txn['Status']]) . '. Reload the page.');
        }
        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            "UPDATE branch_expense_settlement_transactions SET Status = ?, StatusReason = ?, StatusReference = ?, StatusChangedAt = ?, StatusChangedByUserID = ?, StatusChangedByEmployeeID = ?
             WHERE ID = ? AND Status = 'completed'",
            'ssssiii',
            array($outcome, $reason, $statusReference, $now, (int) $actor['user_id'], (int) $actor['employee_id'], $txnId)
        );
        fp_policy_exec($conn, 'UPDATE branch_expense_requests SET RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?', 'iisi', array((int) $actor['user_id'], (int) $actor['employee_id'], $now, (int) $request['ID']));
        $after = fp_settlement_summary($conn, $request['ID']);
        $new = array('TxnNumber' => $txn['TxnNumber'], 'TxnType' => fp_settlement_txn_types()[$txn['TxnType']], 'Amount' => $txn['Amount'], 'TxnDate' => $txn['TxnDate'],
            'Mode' => fp_payment_modes()[$txn['TxnMode']] ?? $txn['TxnMode'], 'Reference' => $txn['Reference']) + fp_settlement_summary_values($after);
        if ($statusReference !== '') {
            $new['StatusReference'] = $statusReference;
        }
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'settlement_txn_' . $outcome, null, $new, $reason);
        fp_notice_add('settlement', $request['ID'], array('event' => 'txn_' . $outcome, 'number' => $txn['TxnNumber'], 'amount' => fp_decimal_to_paise($txn['Amount']), 'reason' => $reason, 'balance' => $after['balance']));
        return fp_result(true, 200, $txn['TxnNumber'] . ' marked ' . $outcome . '. ' . fp_settlement_balance_text($after['balance']) . '.');
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/**
 * Array(status, message) when the advance cannot be closed yet; null when it can.
 */
function fp_settlement_close_block($summary)
{
    if ($summary['paid'] <= 0) {
        return array(422, 'Nothing has been paid on this advance, so there is nothing to close.');
    }
    if ($summary['open_claims'] > 0) {
        return array(422, 'A settlement is still open. It must be verified or cancelled first.');
    }
    if ($summary['balance'] !== 0) {
        return array(422, 'The advance is not reconciled yet. ' . fp_settlement_balance_text($summary['balance']) . '.');
    }
    return null;
}

function fp_settlement_close($conn, $actor, $post)
{
    $requestId = fp_input_int($post, 'RequestID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $remarks = preg_replace('/[ \t]+/', ' ', fp_input_string($post, 'Remarks'));
    if ($requestId <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (mb_strlen($remarks) > 500) {
        return fp_result(false, 422, 'Remarks can be at most 500 characters.', array('errors' => array('Remarks' => 'Too long.')));
    }
    $result = fp_transaction($conn, function () use ($conn, $actor, $requestId, $rowVersion, $remarks) {
        $request = fp_request_get($conn, $requestId, 'update') ? fp_request_get_detail($conn, $requestId) : null;
        if (!$request || !fp_record_in_scope($actor, 'pay', fp_request_scope_record($request))) {
            return fp_result(false, 404, 'Advance not found.');
        }
        if ((int) $request['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This advance was changed by someone else. Reload the page and check the balance again.');
        }
        $block = fp_settlement_finance_block($actor, $request);
        $summary = fp_settlement_summary($conn, $requestId);
        $block = $block ?: fp_settlement_close_block($summary);
        if ($block) {
            return fp_result(false, $block[0], $block[1]);
        }
        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            "UPDATE branch_expense_requests SET SettlementStatus = 'settled', RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?",
            'iisi',
            array((int) $actor['user_id'], (int) $actor['employee_id'], $now, $requestId)
        );
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $requestId, 'settlement_close', array('SettlementStatus' => $request['SettlementStatus']),
            array('SettlementStatus' => 'settled') + fp_settlement_summary_values($summary), $remarks);
        fp_notice_add('settlement', $requestId, array('event' => 'closed', 'accepted' => $summary['accepted']));
        return fp_result(true, 200, $request['ExpenseNumber'] . ' is settled and closed. Accepted expenses: ' . fp_payment_money($summary['accepted']) . '.');
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/* ------------------------------------------------------------------
   Pages
------------------------------------------------------------------ */

/**
 * Everything the advance settlement page shows. Returns null when the actor may not see the advance.
 */
function fp_settlement_page($conn, $actor, $requestId)
{
    $request = $requestId > 0 ? fp_request_get_detail($conn, $requestId) : null;
    if (!$request || $request['RequestType'] !== 'advance' || $request['Status'] !== 'approved' || !fp_settlement_can_view($conn, $actor, $request)) {
        return null;
    }
    $isHolder = fp_settlement_is_holder($actor, $request);
    $isFinance = fp_record_in_scope($actor, 'pay', fp_request_scope_record($request));
    $claims = array();
    foreach (fp_settlement_claims($conn, $requestId) as $claim) {
        if ($claim['Status'] === 'draft' && !$isHolder) {
            continue;
        }
        $lines = fp_settlement_lines($conn, $claim['ID']);
        $canSeeDocs = fp_settlement_can_see_documents($actor, $request, $claim);
        $documents = $canSeeDocs ? fp_settlement_documents($conn, $claim['ID']) : array();
        $reviewable = in_array($claim['Status'], array('submitted', 'branch_reviewed'), true);
        $claims[] = array(
            'claim'      => $claim,
            'lines'      => $lines,
            'documents'  => $documents,
            'removed'    => $canSeeDocs && fp_record_in_scope($actor, 'audit', fp_request_scope_record($request)) ? fp_settlement_documents($conn, $claim['ID'], true) : array(),
            'checks'     => $reviewable && !$isHolder ? fp_settlement_checks($conn, $request, $claim, $lines, $documents) : array(),
            'can_edit'   => fp_settlement_edit_block($actor, $request, $claim) === null,
            'can_review' => fp_settlement_review_block($actor, $request, $claim) === null,
            'can_verify' => fp_settlement_verify_block($actor, $request, $claim) === null,
            'see_docs'   => $canSeeDocs,
        );
    }
    $summary = fp_settlement_summary($conn, $requestId);
    $financeBlock = fp_settlement_finance_block($actor, $request);
    return array(
        'request'      => $request,
        'summary'      => $summary,
        'claims'       => $claims,
        'transactions' => fp_settlement_transactions($conn, $requestId),
        'is_holder'    => $isHolder,
        'is_finance'   => $isFinance,
        'can_start'    => fp_settlement_start_block($conn, $actor, $request) === null,
        'can_finance'  => $financeBlock === null && $summary['paid'] > 0,
        'can_close'    => $financeBlock === null && fp_settlement_close_block($summary) === null,
        'close_block'  => fp_settlement_close_block($summary),
        'overdue'      => $request['SettlementStatus'] !== 'settled' && $summary['balance'] > 0 && $summary['days'] > fp_settlement_due_days(),
    );
}

/**
 * Settlement block for the expense request page (advances only).
 */
function fp_settlement_request_panel($conn, $actor, $request)
{
    if ($request['RequestType'] !== 'advance' || $request['Status'] !== 'approved') {
        return null;
    }
    $summary = fp_settlement_summary($conn, $request['ID']);
    return array(
        'summary'   => $summary,
        'claims'    => count(array_filter(fp_settlement_claims($conn, $request['ID']), function ($claim) use ($actor, $request) {
            return $claim['Status'] !== 'cancelled' && ($claim['Status'] !== 'draft' || fp_settlement_is_holder($actor, $request));
        })),
        'can_start' => fp_settlement_start_block($conn, $actor, $request) === null,
    );
}

/**
 * Data for the claim editor. $requestId starts a new claim, $settlementId edits one.
 */
function fp_settlement_form_data($conn, $actor, $requestId, $settlementId)
{
    $claim = null;
    if ($settlementId > 0) {
        $claim = fp_settlement_get($conn, $settlementId);
        $request = $claim ? fp_request_get_detail($conn, $claim['RequestID']) : null;
        if (!$request || fp_settlement_edit_block($actor, $request, $claim)) {
            return fp_result(false, 403, 'This settlement cannot be edited.');
        }
    } else {
        $request = $requestId > 0 ? fp_request_get_detail($conn, $requestId) : null;
        if (!$request || !fp_settlement_is_holder($actor, $request)) {
            return fp_result(false, 404, 'Advance not found.');
        }
        $block = fp_settlement_start_block($conn, $actor, $request);
        if ($block) {
            return fp_result(false, $block[0], $block[1]);
        }
    }
    $summary = fp_settlement_summary($conn, $request['ID']);
    $data = array(
        'advance' => array(
            'ID' => (int) $request['ID'], 'ExpenseNumber' => $request['ExpenseNumber'], 'Purpose' => $request['Purpose'],
            'Office' => $request['OfficeName'] . ' (' . $request['OfficeCode'] . ')', 'TotalAmount' => $request['TotalAmount'],
            'Paid' => fp_paise_to_decimal($summary['paid']), 'Accepted' => fp_paise_to_decimal($summary['accepted']),
            'Refund' => fp_paise_to_decimal($summary['refund']), 'Additional' => fp_paise_to_decimal($summary['additional']),
            'Balance' => ($summary['balance'] < 0 ? '-' : '') . fp_paise_to_decimal(abs($summary['balance'])), 'BalanceText' => fp_settlement_balance_text($summary['balance']),
            'PeriodFrom' => (string) $request['PeriodFrom'], 'PeriodTo' => (string) $request['PeriodTo'],
        ),
        'categories'     => array_values(fp_settlement_categories($conn, $request['CompanyID'])),
        'documentTypes'  => fp_document_types(),
        'maxUploadBytes' => fp_attachment_max_bytes(),
        'today'          => date('Y-m-d'),
        'claim'          => null,
        'lines'          => array(),
        'documents'      => array(),
    );
    if ($claim) {
        $data['claim'] = array(
            'ID' => (int) $claim['ID'], 'SettlementNumber' => $claim['SettlementNumber'], 'Status' => $claim['Status'],
            'StatusLabel' => fp_settlement_statuses()[$claim['Status']], 'Notes' => $claim['Notes'], 'RowVersion' => (int) $claim['RowVersion'],
            'ReturnReason' => $claim['Status'] === 'returned' ? $claim['ReturnReason'] : '',
        );
        foreach (fp_settlement_lines($conn, $claim['ID']) as $line) {
            $data['lines'][] = array(
                'LineID' => (int) $line['ID'], 'LineNo' => (int) $line['LineNo'], 'CategoryID' => (int) $line['CategoryID'], 'CategoryLabel' => fp_settlement_line_category($line),
                'Description' => $line['Description'], 'ExpenseDate' => (string) $line['ExpenseDate'], 'VendorName' => $line['VendorName'],
                'VendorGSTIN' => $line['VendorGSTIN'], 'InvoiceNumber' => $line['InvoiceNumber'], 'InvoiceDate' => (string) $line['InvoiceDate'],
                'Amount' => $line['Amount'], 'TaxAmount' => $line['TaxAmount'],
            );
        }
        foreach (fp_settlement_documents($conn, $claim['ID']) as $document) {
            $data['documents'][] = array(
                'ID' => (int) $document['ID'], 'LineID' => (int) $document['LineID'], 'LineNo' => (int) $document['LineNo'], 'OriginalName' => $document['OriginalName'],
                'DocumentType' => $document['DocumentType'], 'DocumentLabel' => fp_document_type_label($document['DocumentType']), 'Notes' => $document['Notes'],
                'SizeBytes' => (int) $document['SizeBytes'], 'UploadedAt' => $document['UploadedAt'],
            );
        }
    }
    return fp_result(true, 200, 'OK', array('data' => $data));
}

/* ------------------------------------------------------------------
   Lists
------------------------------------------------------------------ */

/**
 * Claims to act on. 'view': review (waiting for branch review), verify (waiting for Finance), mine (the actor's own claims).
 */
function fp_settlement_claim_list($conn, $actor, $params)
{
    $view = fp_input_string($params, 'view');
    $me = (int) $actor['employee_id'];
    $columns = array('employee_id' => 'r.EmployeeID', 'state_name' => 'st.StateName', 'office_id' => 'r.OfficeID');
    if ($view === 'review') {
        $where = "s.Status = 'submitted' AND " . fp_scope_where($conn, $actor, 'approve', $columns);
    } elseif ($view === 'verify') {
        $where = "s.Status = 'branch_reviewed' AND " . fp_scope_where($conn, $actor, 'pay', $columns);
    } elseif ($view === 'mine') {
        $where = $me > 0 ? 's.EmployeeID = ' . $me : '1=0';
    } else {
        return fp_result(false, 422, 'Invalid view.');
    }
    $rows = fp_db_rows(
        $conn,
        'SELECT s.ID, s.SettlementNumber, s.Status, s.ClaimedAmount, s.AcceptedAmount, s.LineCount, s.SubmittedAt, s.ReviewedAt, s.ReviewedByEmployeeID, s.UpdatedAt, s.CreatedAt,
                r.ID AS RequestID, r.ExpenseNumber, r.EmployeeID, r.CreatedByEmployeeID, r.OfficeID, r.RequestType, r.Status AS RequestStatus, r.SettlementStatus,
                o.OfficeName, o.OfficeCode, st.StateName, e.Name AS EmployeeName
         FROM branch_expense_settlements s
         INNER JOIN branch_expense_requests r ON r.ID = s.RequestID
         INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
         LEFT JOIN state st ON st.ID = r.StateID
         LEFT JOIN employees e ON e.ID = r.EmployeeID
         WHERE ' . $where . '
         ORDER BY COALESCE(s.SubmittedAt, s.CreatedAt), s.ID
         LIMIT ' . FP_SETTLEMENT_LIST_LIMIT
    );
    $data = array();
    foreach ($rows as $row) {
        $request = array('Status' => $row['RequestStatus']) + $row;
        $block = null;
        if ($view === 'review') {
            $block = fp_settlement_review_block($actor, $request, $row);
        } elseif ($view === 'verify') {
            $block = fp_settlement_verify_block($actor, $request, $row);
        }
        $data[] = array(
            'ID'               => (int) $row['ID'],
            'SettlementNumber' => $row['SettlementNumber'],
            'RequestID'        => (int) $row['RequestID'],
            'ExpenseNumber'    => $row['ExpenseNumber'],
            'Employee'         => (string) $row['EmployeeName'],
            'Office'           => $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')',
            'Status'           => $row['Status'],
            'StatusLabel'      => fp_settlement_statuses()[$row['Status']] ?? $row['Status'],
            'ClaimedAmount'    => $row['ClaimedAmount'],
            'AcceptedAmount'   => $row['Status'] === 'verified' ? $row['AcceptedAmount'] : null,
            'LineCount'        => (int) $row['LineCount'],
            'SubmittedAt'      => (string) $row['SubmittedAt'],
            'CanAct'           => $view !== 'mine' && $block === null,
            'CannotActReason'  => $block ? $block[1] : '',
        );
    }
    return fp_result(true, 200, 'OK', array('data' => $data));
}

/**
 * Paid advances with their settlement position and age, oldest first.
 * Params: mine (1 = own advances only), status (open | settled | all), bucket, office, q.
 */
function fp_settlement_advances($conn, $actor, $params)
{
    $mine = fp_input_string($params, 'mine') === '1';
    $where = array("r.RequestType = 'advance'", "r.Status = 'approved'");
    $types = '';
    $values = array();
    if ($mine) {
        $where[] = 'r.EmployeeID = ' . max(0, (int) $actor['employee_id']);
    } else {
        $where[] = fp_scope_where($conn, $actor, 'view', array('employee_id' => 'r.EmployeeID', 'state_name' => 'st.StateName', 'office_id' => 'r.OfficeID'));
    }
    $status = fp_input_string($params, 'status');
    if ($status === 'settled') {
        $where[] = "r.SettlementStatus = 'settled'";
    } elseif ($status !== 'all') {
        $where[] = "r.SettlementStatus <> 'settled'";
    }
    $officeId = fp_input_int($params, 'office');
    if ($officeId > 0) {
        $where[] = 'r.OfficeID = ?';
        $types .= 'i';
        $values[] = $officeId;
    }
    $search = mb_substr(fp_input_string($params, 'q'), 0, 100);
    if ($search !== '') {
        $where[] = '(r.ExpenseNumber LIKE ? OR r.Purpose LIKE ? OR e.Name LIKE ? OR o.OfficeName LIKE ?)';
        $types .= 'ssss';
        array_push($values, ...array_fill(0, 4, fp_payment_like($search)));
    }
    $rows = fp_db_rows(
        $conn,
        "SELECT r.ID, r.ExpenseNumber, r.Purpose, r.TotalAmount, r.SettlementStatus, r.EmployeeID, r.CreatedByEmployeeID, r.OfficeID,
                o.OfficeName, o.OfficeCode, st.StateName, e.Name AS EmployeeName, p.Paid, p.FirstPaid,
                COALESCE((SELECT SUM(s.AcceptedAmount) FROM branch_expense_settlements s WHERE s.RequestID = r.ID AND s.Status = 'verified'), 0) AS Accepted,
                COALESCE((SELECT SUM(t.Amount) FROM branch_expense_settlement_transactions t WHERE t.RequestID = r.ID AND t.Status = 'completed' AND t.TxnType = 'refund'), 0) AS Refund,
                COALESCE((SELECT SUM(t.Amount) FROM branch_expense_settlement_transactions t WHERE t.RequestID = r.ID AND t.Status = 'completed' AND t.TxnType = 'additional'), 0) AS Additional,
                (SELECT s.Status FROM branch_expense_settlements s WHERE s.RequestID = r.ID AND s.Status IN ('draft', 'submitted', 'branch_reviewed', 'returned') ORDER BY s.ID DESC LIMIT 1) AS OpenClaimStatus
         FROM branch_expense_requests r
         INNER JOIN (SELECT RequestID, SUM(Amount) AS Paid, MIN(PaymentDate) AS FirstPaid FROM branch_expense_payments WHERE Status = 'completed' GROUP BY RequestID) p ON p.RequestID = r.ID
         INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
         LEFT JOIN state st ON st.ID = r.StateID
         LEFT JOIN employees e ON e.ID = r.EmployeeID
         WHERE " . implode(' AND ', $where) . '
         ORDER BY p.FirstPaid, r.ID
         LIMIT ' . FP_SETTLEMENT_LIST_LIMIT,
        $types,
        $values
    );
    $bucketFilter = fp_input_string($params, 'bucket');
    $buckets = fp_settlement_aging_buckets();
    $totals = array_fill_keys(array_keys($buckets), 0);
    $dueDays = fp_settlement_due_days();
    $today = strtotime(date('Y-m-d'));
    $data = array();
    $holding = 0;
    $owed = 0;
    foreach ($rows as $row) {
        $paid = fp_decimal_to_paise($row['Paid']);
        $accepted = fp_decimal_to_paise($row['Accepted']);
        $refund = fp_decimal_to_paise($row['Refund']);
        $additional = fp_decimal_to_paise($row['Additional']);
        $balance = $paid + $additional - $accepted - $refund;
        $days = max(0, (int) floor(($today - strtotime($row['FirstPaid'])) / 86400));
        $bucket = fp_settlement_bucket($days);
        if ($bucketFilter !== '' && isset($buckets[$bucketFilter]) && $bucket !== $bucketFilter) {
            continue;
        }
        if ($row['SettlementStatus'] !== 'settled') {
            if ($balance > 0) {
                $totals[$bucket] += $balance;
                $holding += $balance;
            } elseif ($balance < 0) {
                $owed -= $balance;
            }
        }
        $data[] = array(
            'ID'               => (int) $row['ID'],
            'ExpenseNumber'    => $row['ExpenseNumber'],
            'Purpose'          => $row['Purpose'],
            'Employee'         => (string) $row['EmployeeName'],
            'Office'           => $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')',
            'State'            => (string) $row['StateName'],
            'Approved'         => $row['TotalAmount'],
            'Paid'             => fp_paise_to_decimal($paid),
            'Accepted'         => fp_paise_to_decimal($accepted),
            'Refund'           => fp_paise_to_decimal($refund),
            'Additional'       => fp_paise_to_decimal($additional),
            'Balance'          => ($balance < 0 ? '-' : '') . fp_paise_to_decimal(abs($balance)),
            'BalanceText'      => fp_settlement_balance_text($balance),
            'FirstPaid'        => $row['FirstPaid'],
            'Days'             => $days,
            'Bucket'           => $bucket,
            'BucketLabel'      => $buckets[$bucket],
            'Overdue'          => $row['SettlementStatus'] !== 'settled' && $balance > 0 && $days > $dueDays,
            'SettlementStatus' => $row['SettlementStatus'],
            'SettlementLabel'  => fp_settlement_request_statuses()[$row['SettlementStatus']] ?? $row['SettlementStatus'],
            'OpenClaim'        => $row['OpenClaimStatus'] === null ? '' : fp_settlement_statuses()[$row['OpenClaimStatus']],
        );
    }
    return fp_result(true, 200, 'OK', array('data' => array('rows' => $data, 'summary' => array(
        'Holding'  => fp_paise_to_decimal($holding),
        'Owed'     => fp_paise_to_decimal($owed),
        'Buckets'  => array_map('fp_paise_to_decimal', $totals),
        'DueDays'  => $dueDays,
    ))));
}

function fp_settlement_advances_csv($conn, $actor, $params)
{
    $result = fp_settlement_advances($conn, $actor, $params);
    $cell = function ($value) {
        $value = str_replace(array("\r", "\n"), ' ', (string) $value);
        return preg_match('/^[=+\-@\t]/', $value) ? "'" . $value : $value;
    };
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="advance-settlements-' . date('Ymd-His') . '.csv"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('Expense No.', 'Employee', 'Office', 'State', 'Purpose', 'Approved', 'Paid out', 'Expenses accepted', 'Cash returned', 'Extra paid',
        'Balance', 'Position', 'First paid on', 'Days outstanding', 'Age', 'Overdue', 'Settlement status', 'Open claim'));
    foreach ($result['data']['rows'] as $row) {
        fputcsv($out, array_map($cell, array(
            $row['ExpenseNumber'], $row['Employee'], $row['Office'], $row['State'], $row['Purpose'], $row['Approved'], $row['Paid'], $row['Accepted'], $row['Refund'],
            $row['Additional'], ltrim($row['Balance'], '-'), $row['BalanceText'], $row['FirstPaid'], $row['Days'], $row['BucketLabel'], $row['Overdue'] ? 'Yes' : '',
            $row['SettlementLabel'], $row['OpenClaim'],
        )));
    }
    fclose($out);
    exit;
}

/* ------------------------------------------------------------------
   Notifications
------------------------------------------------------------------ */

/**
 * Messages for a 'settlement' notice (see fp_notice_messages()).
 */
function fp_settlement_notice_messages($conn, $notice, $request, $summary)
{
    $url = 'finance-performance/view-advance-settlement.php?id=' . (int) $request['ID'];
    $holder = (int) $request['EmployeeID'];
    $claim = !empty($notice['settlement_id']) ? fp_settlement_get($conn, $notice['settlement_id']) : null;
    $number = $claim ? $claim['SettlementNumber'] : '';
    switch ($notice['event']) {
        case 'submitted':
            $managers = fp_db_rows($conn, 'SELECT DISTINCT EmployeeID FROM branch_expense_office_employees WHERE OfficeID = ? AND IsManager = 1 AND IsActive = 1 AND EmployeeID <> ?', 'ii', array((int) $request['OfficeID'], $holder));
            $messages = array();
            foreach (array_slice($managers, 0, FP_APPROVAL_NOTIFY_LIMIT) as $manager) {
                $messages[] = array('employee_id' => (int) $manager['EmployeeID'], 'url' => $url, 'title' => 'Advance settlement to review',
                    'body' => $summary . ': ' . $request['EmployeeName'] . ' submitted ' . $number . ' (' . fp_payment_money(fp_decimal_to_paise($claim['ClaimedAmount'])) . ') for branch review.');
            }
            return $messages;
        case 'returned':
            return array(array('employee_id' => $holder, 'url' => $url, 'title' => 'Advance settlement returned for changes',
                'body' => $summary . ': ' . $number . ' was returned. Comment: ' . mb_strimwidth($notice['comments'] ?? '', 0, 300, '...')));
        case 'verified':
            return array(array('employee_id' => $holder, 'url' => $url, 'title' => 'Advance settlement verified',
                'body' => $summary . ': ' . $number . ' verified, ' . fp_payment_money($notice['accepted']) . ' of ' . fp_payment_money($notice['claimed']) . ' accepted. ' . fp_settlement_balance_text($notice['balance']) . '.'));
        case 'refund':
        case 'additional':
            return array(array('employee_id' => $holder, 'url' => $url, 'title' => $notice['event'] === 'refund' ? 'Advance cash return recorded' : 'Advance extra payment made',
                'body' => $summary . ': ' . fp_payment_money($notice['amount']) . ($notice['event'] === 'refund' ? ' received from you' : ' paid to you') . ' by ' . $notice['mode']
                    . ($notice['reference'] !== '' ? ' (ref. ' . $notice['reference'] . ')' : '') . ' as ' . $notice['number'] . '. ' . fp_settlement_balance_text($notice['balance']) . '.'));
        case 'txn_failed':
        case 'txn_reversed':
            return array(array('employee_id' => $holder, 'url' => $url, 'title' => 'Advance settlement entry ' . ($notice['event'] === 'txn_failed' ? 'failed' : 'reversed'),
                'body' => $summary . ': ' . $notice['number'] . ' of ' . fp_payment_money($notice['amount']) . ' was marked ' . substr($notice['event'], 4) . '. Reason: '
                    . mb_strimwidth($notice['reason'], 0, 300, '...') . ' ' . fp_settlement_balance_text($notice['balance']) . '.'));
        case 'closed':
            return array(array('employee_id' => $holder, 'url' => $url, 'title' => 'Advance settled and closed',
                'body' => $summary . ': the advance is fully settled. Accepted expenses: ' . fp_payment_money($notice['accepted']) . '.'));
    }
    return array();
}
