<?php
/**
 * Payment register: Head Office payments against approved requests.
 * Loaded by fp_requests.php.
 *
 * Approval is not payment. A request's PaymentStatus is derived only from its completed payments
 * (unpaid / partially_paid / paid); the database guards from sql/m09_payments.sql enforce the same rule.
 * A payment is never edited or deleted: a wrong or bounced payment is marked failed or reversed with a reason,
 * after which a new payment can be recorded.
 */

const FP_PAYMENT_REASON_MIN = 5;
const FP_PAYMENT_REASON_MAX = 1000;
const FP_PAYMENT_EXPORT_LIMIT = 10000;

function fp_payment_modes()
{
    return array(
        'neft'          => 'NEFT',
        'rtgs'          => 'RTGS',
        'imps'          => 'IMPS',
        'upi'           => 'UPI',
        'bank_transfer' => 'Bank transfer',
        'cheque'        => 'Cheque',
        'cash'          => 'Cash',
    );
}

/**
 * Modes where a transaction reference is optional (a cash voucher number may still be entered).
 */
function fp_payment_reference_optional_modes()
{
    return array('cash');
}

function fp_payment_statuses()
{
    return array('completed' => 'Completed', 'failed' => 'Failed', 'reversed' => 'Reversed');
}

function fp_payment_request_statuses()
{
    return array('unpaid' => 'Unpaid', 'partially_paid' => 'Partly paid', 'paid' => 'Paid');
}

function fp_payment_payee_types()
{
    return array('employee' => 'Requester (employee)', 'vendor' => 'Vendor');
}

/**
 * Payee types allowed per request type: advances and reimbursements are always paid to the requester.
 */
function fp_payment_allowed_payee_types($requestType)
{
    return $requestType === 'direct' ? array('employee', 'vendor') : array('employee');
}

function fp_payment_reference_key($reference)
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $reference));
}

function fp_payment_mask_account($number)
{
    $digits = preg_replace('/\D/', '', (string) $number);
    return strlen($digits) >= 4 ? 'XXXX' . substr($digits, -4) : '';
}

function fp_payment_status_for($paidPaise, $totalPaise)
{
    if ($paidPaise <= 0) {
        return 'unpaid';
    }
    return $paidPaise >= $totalPaise ? 'paid' : 'partially_paid';
}

function fp_payment_money($paise)
{
    return 'INR ' . number_format($paise / 100, 2);
}

/* ------------------------------------------------------------------
   Loading
------------------------------------------------------------------ */

function fp_payment_paid_paise($conn, $requestId)
{
    $rows = fp_db_rows($conn, "SELECT COALESCE(SUM(Amount), 0) AS Paid FROM branch_expense_payments WHERE RequestID = ? AND Status = 'completed'", 'i', array((int) $requestId));
    return fp_decimal_to_paise($rows[0]['Paid']);
}

function fp_payment_rows($conn, $requestId)
{
    return fp_db_rows(
        $conn,
        'SELECT p.*, COALESCE(er.Name, p.RecordedByUserName) AS RecordedByName, COALESCE(es.Name, \'\') AS StatusChangedByName
         FROM branch_expense_payments p
         LEFT JOIN employees er ON p.RecordedByEmployeeID > 0 AND er.ID = p.RecordedByEmployeeID
         LEFT JOIN employees es ON p.StatusChangedByEmployeeID > 0 AND es.ID = p.StatusChangedByEmployeeID
         WHERE p.RequestID = ?
         ORDER BY p.ID',
        'i',
        array((int) $requestId)
    );
}

/**
 * Bank details of the requester for the payee: name on the account and the masked account number.
 */
function fp_payment_employee_account_hint($conn, $employeeId)
{
    $rows = fp_db_rows($conn, 'SELECT BankAccountName, BankAccountNumber FROM employees WHERE ID = ?', 'i', array((int) $employeeId));
    if (!$rows) {
        return '';
    }
    $masked = fp_payment_mask_account($rows[0]['BankAccountNumber']);
    $name = trim((string) $rows[0]['BankAccountName']);
    return trim($name . ($masked !== '' ? ($name !== '' ? ', ' : '') . 'A/c ' . $masked : ''));
}

/**
 * Bank details (masked) from the vendor master when the payee of a direct expense is a registered vendor.
 */
function fp_payment_request_vendor_hint($conn, $request)
{
    $vendorId = ($request['RequestType'] ?? '') === 'direct' ? (int) $request['VendorRegistrationID'] : 0;
    if ($vendorId <= 0) {
        return '';
    }
    $label = fp_vendor_labels($conn, array($vendorId))[$vendorId] ?? null;
    return $label ? $label['AccountHint'] : '';
}

/**
 * Array(status, message) when the actor may not record a payment on the request; null when allowed.
 */
function fp_payment_record_block($actor, $request)
{
    if (!fp_can($actor, 'pay')) {
        return array(403, 'You do not have permission to record payments.');
    }
    if ((int) $actor['employee_id'] <= 0) {
        return array(403, 'Recording payments needs a login linked to an active employee record.');
    }
    $check = fp_can_act_on_record($actor, 'pay', fp_request_scope_record($request));
    if (!$check['allowed']) {
        return array(403, $check['reason']);
    }
    if ($request['Status'] !== 'approved') {
        return array(422, 'Only approved requests can be paid.');
    }
    if ($request['PaymentStatus'] === 'paid') {
        return array(422, 'This request is already fully paid.');
    }
    if ($request['SettlementStatus'] === 'settled') {
        return array(422, 'This advance is settled and closed.');
    }
    return null;
}

/**
 * Whether the actor may mark payments of the request failed or reversed (never after an advance is settled and closed).
 */
function fp_payment_can_change_status($actor, $request)
{
    if (!fp_can($actor, 'pay') || (int) $actor['employee_id'] <= 0 || ($request['SettlementStatus'] ?? '') === 'settled') {
        return false;
    }
    return fp_can_act_on_record($actor, 'pay', fp_request_scope_record($request))['allowed'];
}

/**
 * Data for the request page's payment panel.
 */
function fp_payment_panel($conn, $actor, $request)
{
    $payments = fp_payment_rows($conn, $request['ID']);
    $totalPaise = fp_decimal_to_paise($request['TotalAmount']);
    $paidPaise = 0;
    foreach ($payments as $payment) {
        if ($payment['Status'] === 'completed') {
            $paidPaise += fp_decimal_to_paise($payment['Amount']);
        }
    }
    $isPayer = fp_can($actor, 'pay') && fp_record_in_scope($actor, 'pay', fp_request_scope_record($request));
    return array(
        'payments'          => $payments,
        'total_paise'       => $totalPaise,
        'paid_paise'        => $paidPaise,
        'balance_paise'     => $request['Status'] === 'approved' ? max(0, $totalPaise - $paidPaise) : 0,
        'can_record'        => fp_payment_record_block($actor, $request) === null,
        'can_change_status' => fp_payment_can_change_status($actor, $request),
        'show_details'      => $isPayer,
    );
}

/**
 * Everything the "Record payment" form needs for one request.
 */
function fp_payment_form($conn, $actor, $requestId)
{
    $request = $requestId > 0 ? fp_request_get_detail($conn, $requestId) : null;
    if (!$request || !fp_record_in_scope($actor, 'pay', fp_request_scope_record($request))) {
        return fp_result(false, 404, 'Request not found.');
    }
    $block = fp_payment_record_block($actor, $request);
    if ($block) {
        return fp_result(false, $block[0], $block[1]);
    }
    $totalPaise = fp_decimal_to_paise($request['TotalAmount']);
    $paidPaise = fp_payment_paid_paise($conn, $request['ID']);
    $vendors = array();
    $requestVendor = $request['RequestType'] === 'direct' ? trim((string) $request['VendorName']) : '';
    if ($requestVendor !== '') {
        $vendors[] = $requestVendor;
    }
    if ($request['RequestType'] === 'direct') {
        foreach (fp_request_items($conn, $request['ID']) as $item) {
            $name = trim((string) $item['VendorName']);
            if ($name !== '' && !in_array($name, $vendors, true)) {
                $vendors[] = $name;
            }
        }
    }
    $payeeTypes = array();
    foreach (fp_payment_allowed_payee_types($request['RequestType']) as $type) {
        $payeeTypes[$type] = fp_payment_payee_types()[$type];
    }
    return fp_result(true, 200, '', array('data' => array(
        'request' => array(
            'ID'            => (int) $request['ID'],
            'RowVersion'    => (int) $request['RowVersion'],
            'ExpenseNumber' => $request['ExpenseNumber'],
            'Type'          => fp_request_types()[$request['RequestType']] ?? $request['RequestType'],
            'Employee'      => (string) $request['EmployeeName'],
            'Office'        => $request['OfficeName'] . ' (' . $request['OfficeCode'] . ')',
            'Purpose'       => $request['Purpose'],
            'TotalAmount'   => fp_paise_to_decimal($totalPaise),
            'PaidAmount'    => fp_paise_to_decimal($paidPaise),
            'Balance'       => fp_paise_to_decimal(max(0, $totalPaise - $paidPaise)),
            'ApprovedOn'    => substr((string) $request['ApprovedAt'], 0, 10),
            'DueDate'       => $request['RequestType'] === 'direct' ? (string) $request['DueDate'] : '',
        ),
        'payee' => array(
            'types'          => $payeeTypes,
            'default_type'   => $requestVendor !== '' ? 'vendor' : 'employee',
            'employee_name'  => (string) $request['EmployeeName'],
            'employee_hint'  => fp_payment_employee_account_hint($conn, $request['EmployeeID']),
            'vendors'        => $vendors,
            'vendor_name'    => $requestVendor,
            'vendor_hint'    => fp_payment_request_vendor_hint($conn, $request),
            'vendor_ref'     => $requestVendor !== '' ? (string) $request['VendorAccountRef'] : '',
        ),
        'modes'            => fp_payment_modes(),
        'reference_optional' => fp_payment_reference_optional_modes(),
        'today'            => date('Y-m-d'),
    )));
}

/* ------------------------------------------------------------------
   Writes
------------------------------------------------------------------ */

function fp_payment_update_request_status($conn, $actor, $requestId, $status, $now)
{
    return fp_policy_exec(
        $conn,
        'UPDATE branch_expense_requests SET PaymentStatus = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?',
        'siisi',
        array($status, (int) $actor['user_id'], (int) $actor['employee_id'], $now, (int) $requestId)
    );
}

/**
 * Records one payment. Post: RequestID, RowVersion, Amount, PaymentDate, PaymentMode, Reference, PayeeType,
 * PayeeName (vendor only), Remarks, ConfirmSharedReference ('1' after the user confirmed a bank reference
 * already used on another request, e.g. one bulk transfer covering several requests).
 */
function fp_payment_record($conn, $actor, $post)
{
    $requestId = fp_input_int($post, 'RequestID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $amountPaise = fp_parse_paise(fp_input_string($post, 'Amount'));
    $date = fp_parse_date(fp_input_string($post, 'PaymentDate'));
    $mode = fp_input_string($post, 'PaymentMode');
    $reference = preg_replace('/\s+/', ' ', fp_input_string($post, 'Reference'));
    $payeeType = fp_input_string($post, 'PayeeType');
    $payeeName = preg_replace('/\s+/u', ' ', fp_input_string($post, 'PayeeName'));
    $remarks = fp_input_string($post, 'Remarks');
    $confirmShared = fp_input_string($post, 'ConfirmSharedReference') === '1';

    if ($requestId <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    $errors = array();
    if ($amountPaise === false || $amountPaise <= 0) {
        $errors['Amount'] = 'Enter an amount greater than zero (up to 2 decimals).';
    }
    if (!$date) {
        $errors['PaymentDate'] = 'Enter the date the payment was made.';
    } elseif ($date > date('Y-m-d')) {
        $errors['PaymentDate'] = 'The payment date cannot be in the future.';
    }
    if (!isset(fp_payment_modes()[$mode])) {
        $errors['PaymentMode'] = 'Choose how the payment was made.';
    }
    $referenceKey = fp_payment_reference_key($reference);
    if ($reference === '' && !in_array($mode, fp_payment_reference_optional_modes(), true)) {
        $errors['Reference'] = 'Enter the transaction reference (UTR, UPI or cheque number).';
    } elseif ($reference !== '' && (mb_strlen($reference) > 100 || !preg_match('/^[A-Za-z0-9 \/._-]+$/', $reference) || strlen($referenceKey) < 4)) {
        $errors['Reference'] = 'Use 4-100 characters: letters, numbers, space, / . _ or -.';
    }
    if (!isset(fp_payment_payee_types()[$payeeType])) {
        $errors['PayeeType'] = 'Choose who was paid.';
    } elseif ($payeeType === 'vendor' && (mb_strlen($payeeName) < 2 || mb_strlen($payeeName) > 150)) {
        $errors['PayeeName'] = 'Enter the vendor name (2-150 characters).';
    }
    if (mb_strlen($remarks) > 500) {
        $errors['Remarks'] = 'Remarks can be at most 500 characters.';
    }
    if ($errors) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
    }

    $result = fp_transaction($conn, function () use ($conn, $actor, $requestId, $rowVersion, $amountPaise, $date, $mode, $reference, $referenceKey, $payeeType, $payeeName, $remarks, $confirmShared) {
        if (!fp_request_get($conn, $requestId, 'update')) {
            return fp_result(false, 404, 'Request not found.');
        }
        $request = fp_request_get_detail($conn, $requestId);
        if (!fp_record_in_scope($actor, 'pay', fp_request_scope_record($request))) {
            return fp_result(false, 404, 'Request not found.');
        }
        if ((int) $request['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'This request was changed by someone else. Reload and check the payments before trying again.');
        }
        $block = fp_payment_record_block($actor, $request);
        if ($block) {
            return fp_result(false, $block[0], $block[1]);
        }
        if (!in_array($payeeType, fp_payment_allowed_payee_types($request['RequestType']), true)) {
            return fp_result(false, 422, 'Advances and reimbursements are paid to the requester.', array('errors' => array('PayeeType' => 'Only the requester can be paid for this request.')));
        }
        $approvedOn = substr((string) $request['ApprovedAt'], 0, 10);
        if ($approvedOn !== '' && $date < $approvedOn) {
            return fp_result(false, 422, 'The payment date cannot be before the approval date (' . fp_timeline_date($approvedOn) . ').', array('errors' => array('PaymentDate' => 'Before the approval date.')));
        }

        $totalPaise = fp_decimal_to_paise($request['TotalAmount']);
        $paidPaise = fp_payment_paid_paise($conn, $requestId);
        $balancePaise = $totalPaise - $paidPaise;
        if ($amountPaise > $balancePaise) {
            return fp_result(false, 422, 'The amount is more than the unpaid balance of ' . fp_payment_money($balancePaise) . '.', array('errors' => array('Amount' => 'At most ' . fp_payment_money($balancePaise) . '.')));
        }

        $shared = 0;
        if ($referenceKey !== '') {
            $matches = fp_db_rows(
                $conn,
                "SELECT p.PaymentNumber, p.RequestID, p.Amount, p.PaymentDate, r.ExpenseNumber
                 FROM branch_expense_payments p INNER JOIN branch_expense_requests r ON r.ID = p.RequestID
                 WHERE p.ReferenceKey = ? AND p.Status <> 'failed' ORDER BY p.ID",
                's',
                array($referenceKey)
            );
            $others = array();
            foreach ($matches as $match) {
                if ((int) $match['RequestID'] === $requestId) {
                    return fp_result(false, 422, 'This reference is already recorded for this request (' . $match['PaymentNumber'] . ').', array('errors' => array('Reference' => 'Already used for ' . $match['PaymentNumber'] . '.')));
                }
                $others[] = array('PaymentNumber' => $match['PaymentNumber'], 'ExpenseNumber' => $match['ExpenseNumber'], 'Amount' => $match['Amount'], 'PaymentDate' => $match['PaymentDate']);
            }
            if ($others && !$confirmShared) {
                return fp_result(false, 422, 'This reference is already used on another request. If one bank transfer paid several requests, tick the confirmation and save again.', array(
                    'errors' => array('Reference' => 'Already used on ' . implode(', ', array_column($others, 'ExpenseNumber')) . '.'),
                    'data'   => array('shared_reference' => $others),
                ));
            }
            $shared = $others ? 1 : 0;
        }

        if ($payeeType === 'employee') {
            $payeeEmployeeId = (int) $request['EmployeeID'];
            $payeeName = (string) $request['EmployeeName'];
            $hint = fp_payment_employee_account_hint($conn, $payeeEmployeeId);
        } else {
            $payeeEmployeeId = 0;
            $hint = '';
            if (mb_strtolower($payeeName) === mb_strtolower(trim((string) $request['VendorName']))) {
                $hint = mb_substr(fp_payment_request_vendor_hint($conn, $request), -80);
                if ($hint === '' && (string) $request['VendorAccountRef'] !== '') {
                    $hint = mb_substr('Ref. ' . $request['VendorAccountRef'], 0, 80);
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $number = fp_sequence_next($conn, 'PAY', $date);
        $amount = fp_paise_to_decimal($amountPaise);
        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        fp_policy_exec(
            $conn,
            'INSERT INTO branch_expense_payments
                (PaymentNumber, RequestID, Amount, PaymentDate, PaymentMode, Reference, ReferenceKey, SharedReference, PayeeType, PayeeEmployeeID, PayeeName,
                 PayeeAccountHint, Remarks, Status, RecordedByUserID, RecordedByEmployeeID, RecordedByUserName, RecordedAt, IPAddress)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'completed\', ?, ?, ?, ?, ?)',
            'sisssssisisssiisss',
            array($number, $requestId, $amount, $date, $mode, $reference, $referenceKey, $shared, $payeeType, $payeeEmployeeId, $payeeName,
                  $hint, $remarks, (int) $actor['user_id'], (int) $actor['employee_id'], (string) $actor['username'], $now, $ip)
        );

        $newPaid = $paidPaise + $amountPaise;
        $newStatus = fp_payment_status_for($newPaid, $totalPaise);
        if ($newStatus !== $request['PaymentStatus']) {
            fp_payment_update_request_status($conn, $actor, $requestId, $newStatus, $now);
        } else {
            fp_policy_exec($conn, 'UPDATE branch_expense_requests SET RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ? WHERE ID = ?', 'iisi', array((int) $actor['user_id'], (int) $actor['employee_id'], $now, $requestId));
        }

        $new = array(
            'PaymentStatus' => $newStatus,
            'PaymentNumber' => $number,
            'Amount'        => $amount,
            'PaymentDate'   => $date,
            'Mode'          => fp_payment_modes()[$mode],
            'Reference'     => $reference,
            'Payee'         => $payeeName,
            'PaidTotal'     => fp_paise_to_decimal($newPaid),
            'Balance'       => fp_paise_to_decimal($totalPaise - $newPaid),
        );
        if ($shared) {
            $new['SharedReference'] = 'Yes';
        }
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $requestId, 'payment_record', array('PaymentStatus' => $request['PaymentStatus']), $new, $remarks);
        fp_notice_add('payment', $requestId, array('event' => 'recorded', 'number' => $number, 'amount' => $amountPaise, 'mode' => fp_payment_modes()[$mode], 'reference' => $reference, 'status' => $newStatus));

        $message = 'Payment ' . $number . ' of ' . fp_payment_money($amountPaise) . ' recorded. ' . $request['ExpenseNumber'] . ' is now '
            . strtolower(fp_payment_request_statuses()[$newStatus])
            . ($newStatus === 'partially_paid' ? ' (balance ' . fp_payment_money($totalPaise - $newPaid) . ')' : '') . '.';
        return fp_result(true, 200, $message, array('data' => array('PaymentNumber' => $number, 'PaymentStatus' => $newStatus)));
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/**
 * Marks a completed payment failed (it never reached the payee) or reversed (the money came back).
 * Post: PaymentID, Outcome (failed | reversed), Reason, StatusReference (optional bank return reference).
 */
function fp_payment_change_status($conn, $actor, $post)
{
    $paymentId = fp_input_int($post, 'PaymentID');
    $outcome = fp_input_string($post, 'Outcome');
    $reason = preg_replace('/[ \t]+/', ' ', fp_input_string($post, 'Reason'));
    $statusReference = preg_replace('/\s+/', ' ', fp_input_string($post, 'StatusReference'));
    if ($paymentId <= 0 || !in_array($outcome, array('failed', 'reversed'), true)) {
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

    $result = fp_transaction($conn, function () use ($conn, $actor, $paymentId, $outcome, $reason, $statusReference) {
        $rows = fp_db_rows($conn, 'SELECT RequestID FROM branch_expense_payments WHERE ID = ?', 'i', array($paymentId));
        if (!$rows || !fp_request_get($conn, $rows[0]['RequestID'], 'update')) {
            return fp_result(false, 404, 'Payment not found.');
        }
        $payment = fp_db_rows($conn, 'SELECT * FROM branch_expense_payments WHERE ID = ? FOR UPDATE', 'i', array($paymentId))[0];
        $request = fp_request_get_detail($conn, $payment['RequestID']);
        if (!fp_record_in_scope($actor, 'pay', fp_request_scope_record($request))) {
            return fp_result(false, 404, 'Payment not found.');
        }
        if ($request['SettlementStatus'] === 'settled') {
            return fp_result(false, 422, 'This advance is settled and closed, so its payments cannot be changed.');
        }
        if (!fp_payment_can_change_status($actor, $request)) {
            return fp_result(false, 403, fp_can_act_on_record($actor, 'pay', fp_request_scope_record($request))['reason'] ?: 'You do not have permission to change payments.');
        }
        if ($payment['Status'] !== 'completed') {
            return fp_result(false, 409, 'This payment is already marked ' . strtolower(fp_payment_statuses()[$payment['Status']]) . '. Reload the page.');
        }

        $now = date('Y-m-d H:i:s');
        fp_policy_exec(
            $conn,
            'UPDATE branch_expense_payments SET Status = ?, StatusReason = ?, StatusReference = ?, StatusChangedAt = ?, StatusChangedByUserID = ?, StatusChangedByEmployeeID = ?
             WHERE ID = ? AND Status = \'completed\'',
            'ssssiii',
            array($outcome, $reason, $statusReference, $now, (int) $actor['user_id'], (int) $actor['employee_id'], $paymentId)
        );
        $totalPaise = fp_decimal_to_paise($request['TotalAmount']);
        $paidPaise = fp_payment_paid_paise($conn, $request['ID']);
        $newStatus = fp_payment_status_for($paidPaise, $totalPaise);
        if ($newStatus !== $request['PaymentStatus']) {
            fp_payment_update_request_status($conn, $actor, $request['ID'], $newStatus, $now);
        }

        $amountPaise = fp_decimal_to_paise($payment['Amount']);
        $new = array(
            'PaymentStatus' => $newStatus,
            'PaymentNumber' => $payment['PaymentNumber'],
            'Amount'        => $payment['Amount'],
            'PaymentDate'   => $payment['PaymentDate'],
            'Mode'          => fp_payment_modes()[$payment['PaymentMode']] ?? $payment['PaymentMode'],
            'Reference'     => $payment['Reference'],
            'PaidTotal'     => fp_paise_to_decimal($paidPaise),
            'Balance'       => fp_paise_to_decimal($totalPaise - $paidPaise),
        );
        if ($statusReference !== '') {
            $new['StatusReference'] = $statusReference;
        }
        fp_audit_log($conn, $actor, FP_REQUEST_ENTITY, $request['ID'], 'payment_' . $outcome, array('PaymentStatus' => $request['PaymentStatus']), $new, $reason);
        fp_notice_add('payment', $request['ID'], array('event' => $outcome, 'number' => $payment['PaymentNumber'], 'amount' => $amountPaise, 'reason' => $reason, 'status' => $newStatus));

        return fp_result(true, 200, 'Payment ' . $payment['PaymentNumber'] . ' marked ' . $outcome . '. ' . $request['ExpenseNumber'] . ' is now '
            . strtolower(fp_payment_request_statuses()[$newStatus]) . '; the unpaid balance is ' . fp_payment_money($totalPaise - $paidPaise) . '.');
    });
    fp_notice_finish($conn, $result);
    return $result;
}

/* ------------------------------------------------------------------
   Lists
------------------------------------------------------------------ */

function fp_payment_scope_where($conn, $actor)
{
    return fp_scope_where($conn, $actor, 'pay', array('employee_id' => 'r.EmployeeID', 'state_name' => 's.StateName', 'office_id' => 'r.OfficeID'));
}

function fp_payment_page_params($params)
{
    $start = max(0, fp_input_int($params, 'start'));
    $length = fp_input_int($params, 'length');
    return array($start, $length <= 0 ? 25 : min($length, 100));
}

function fp_payment_like($search)
{
    return '%' . addcslashes($search, '%_\\') . '%';
}

/**
 * Approved requests in the actor's pay scope that still have an unpaid balance, oldest approval first.
 */
function fp_payment_payable($conn, $actor, $params)
{
    $base = "r.Status = 'approved' AND r.PaymentStatus <> 'paid' AND r.SettlementStatus <> 'settled' AND " . fp_payment_scope_where($conn, $actor);
    $where = array($base);
    $types = '';
    $values = array();
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
    $search = mb_substr(fp_input_string($params, 'q'), 0, 100);
    if ($search !== '') {
        $where[] = '(r.ExpenseNumber LIKE ? OR r.Purpose LIKE ? OR e.Name LIKE ? OR o.OfficeName LIKE ?)';
        $types .= 'ssss';
        array_push($values, ...array_fill(0, 4, fp_payment_like($search)));
    }
    $from = ' FROM branch_expense_requests r
              INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
              LEFT JOIN state s ON s.ID = r.StateID
              LEFT JOIN employees e ON e.ID = r.EmployeeID ';
    list($start, $length) = fp_payment_page_params($params);
    $total = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt' . $from . 'WHERE ' . $base);
    $filtered = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt, COALESCE(SUM(r.TotalAmount), 0) AS Approved,
            COALESCE(SUM((SELECT SUM(p.Amount) FROM branch_expense_payments p WHERE p.RequestID = r.ID AND p.Status = \'completed\')), 0) AS Paid'
        . $from . 'WHERE ' . implode(' AND ', $where), $types, $values);
    $rows = fp_db_rows(
        $conn,
        'SELECT r.ID, r.ExpenseNumber, r.RequestType, r.Purpose, r.TotalAmount, r.PaymentStatus, r.ApprovedAt, r.EmployeeID, r.CreatedByEmployeeID, r.OfficeID,
                r.DueDate, r.VendorName, o.OfficeName, o.OfficeCode, s.StateName, e.Name AS EmployeeName,
                COALESCE((SELECT SUM(p.Amount) FROM branch_expense_payments p WHERE p.RequestID = r.ID AND p.Status = \'completed\'), 0) AS PaidAmount'
        . $from . 'WHERE ' . implode(' AND ', $where) . ' ORDER BY COALESCE(r.DueDate, DATE(r.ApprovedAt)), r.ApprovedAt, r.ID LIMIT ? OFFSET ?',
        $types . 'ii',
        array_merge($values, array($length, $start))
    );
    $data = array();
    foreach ($rows as $row) {
        $totalPaise = fp_decimal_to_paise($row['TotalAmount']);
        $paidPaise = fp_decimal_to_paise($row['PaidAmount']);
        $check = fp_can_act_on_record($actor, 'pay', fp_request_scope_record($row));
        $data[] = array(
            'ID'            => (int) $row['ID'],
            'ExpenseNumber' => $row['ExpenseNumber'],
            'TypeLabel'     => fp_request_types()[$row['RequestType']] ?? $row['RequestType'],
            'Purpose'       => $row['Purpose'],
            'Employee'      => (string) $row['EmployeeName'],
            'Office'        => $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')',
            'State'         => (string) $row['StateName'],
            'ApprovedAt'    => (string) $row['ApprovedAt'],
            'DueDate'       => (string) $row['DueDate'],
            'Overdue'       => $row['DueDate'] !== null && $row['DueDate'] < date('Y-m-d'),
            'VendorName'    => $row['RequestType'] === 'direct' ? (string) $row['VendorName'] : '',
            'TotalAmount'   => fp_paise_to_decimal($totalPaise),
            'PaidAmount'    => fp_paise_to_decimal($paidPaise),
            'Balance'       => fp_paise_to_decimal($totalPaise - $paidPaise),
            'PaymentStatus' => $row['PaymentStatus'],
            'PaymentStatusLabel' => fp_payment_request_statuses()[$row['PaymentStatus']] ?? $row['PaymentStatus'],
            'CanPay'        => $check['allowed'] && (int) $actor['employee_id'] > 0,
            'CannotPayReason' => $check['allowed'] ? '' : $check['reason'],
        );
    }
    $approvedPaise = fp_decimal_to_paise($filtered[0]['Approved']);
    $paidPaise = fp_decimal_to_paise($filtered[0]['Paid']);
    return array(
        'draw'            => fp_input_int($params, 'draw'),
        'recordsTotal'    => (int) $total[0]['Cnt'],
        'recordsFiltered' => (int) $filtered[0]['Cnt'],
        'data'            => $data,
        'summary'         => array('Balance' => fp_paise_to_decimal($approvedPaise - $paidPaise)),
    );
}

function fp_payment_register_filter($conn, $actor, $params)
{
    $base = fp_payment_scope_where($conn, $actor);
    $where = array($base);
    $types = '';
    $values = array();
    $status = fp_input_string($params, 'status');
    if (isset(fp_payment_statuses()[$status])) {
        $where[] = 'p.Status = ?';
        $types .= 's';
        $values[] = $status;
    }
    $mode = fp_input_string($params, 'mode');
    if (isset(fp_payment_modes()[$mode])) {
        $where[] = 'p.PaymentMode = ?';
        $types .= 's';
        $values[] = $mode;
    }
    $officeId = fp_input_int($params, 'office');
    if ($officeId > 0) {
        $where[] = 'r.OfficeID = ?';
        $types .= 'i';
        $values[] = $officeId;
    }
    foreach (array('from' => '>=', 'to' => '<=') as $key => $operator) {
        $date = fp_parse_date(fp_input_string($params, $key));
        if ($date) {
            $where[] = 'p.PaymentDate ' . $operator . ' ?';
            $types .= 's';
            $values[] = $date;
        }
    }
    $search = mb_substr(fp_input_string($params, 'q'), 0, 100);
    if ($search !== '') {
        $like = fp_payment_like($search);
        $key = fp_payment_reference_key($search);
        $where[] = '(p.PaymentNumber LIKE ? OR r.ExpenseNumber LIKE ? OR p.Reference LIKE ? OR p.PayeeName LIKE ? OR e.Name LIKE ?' . ($key !== '' ? ' OR p.ReferenceKey LIKE ?' : '') . ')';
        $types .= 'sssss';
        array_push($values, $like, $like, $like, $like, $like);
        if ($key !== '') {
            $types .= 's';
            $values[] = fp_payment_like($key);
        }
    }
    return array('base' => $base, 'where' => implode(' AND ', $where), 'types' => $types, 'values' => $values);
}

function fp_payment_register_from()
{
    return ' FROM branch_expense_payments p
             INNER JOIN branch_expense_requests r ON r.ID = p.RequestID
             INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
             LEFT JOIN state s ON s.ID = r.StateID
             LEFT JOIN employees e ON e.ID = r.EmployeeID
             LEFT JOIN employees er ON p.RecordedByEmployeeID > 0 AND er.ID = p.RecordedByEmployeeID ';
}

function fp_payment_register_columns()
{
    return 'p.*, r.ExpenseNumber, r.RequestType, r.EmployeeID, r.CreatedByEmployeeID, r.OfficeID, r.SettlementStatus, o.OfficeName, o.OfficeCode, s.StateName,
            e.Name AS EmployeeName, COALESCE(er.Name, p.RecordedByUserName) AS RecordedByName';
}

function fp_payment_register_row($actor, $row)
{
    return array(
        'ID'             => (int) $row['ID'],
        'PaymentNumber'  => $row['PaymentNumber'],
        'RequestID'      => (int) $row['RequestID'],
        'ExpenseNumber'  => $row['ExpenseNumber'],
        'TypeLabel'      => fp_request_types()[$row['RequestType']] ?? $row['RequestType'],
        'Employee'       => (string) $row['EmployeeName'],
        'Office'         => $row['OfficeName'] . ' (' . $row['OfficeCode'] . ')',
        'State'          => (string) $row['StateName'],
        'Amount'         => $row['Amount'],
        'PaymentDate'    => $row['PaymentDate'],
        'Mode'           => $row['PaymentMode'],
        'ModeLabel'      => fp_payment_modes()[$row['PaymentMode']] ?? $row['PaymentMode'],
        'Reference'      => $row['Reference'],
        'SharedReference' => (int) $row['SharedReference'] === 1,
        'Payee'          => $row['PayeeName'],
        'PayeeType'      => $row['PayeeType'],
        'PayeeAccount'   => $row['PayeeAccountHint'],
        'Status'         => $row['Status'],
        'StatusLabel'    => fp_payment_statuses()[$row['Status']] ?? $row['Status'],
        'StatusReason'   => $row['StatusReason'],
        'StatusReference' => $row['StatusReference'],
        'RecordedBy'     => (string) $row['RecordedByName'],
        'RecordedAt'     => $row['RecordedAt'],
        'CanChangeStatus' => $row['Status'] === 'completed' && fp_payment_can_change_status($actor, $row),
    );
}

function fp_payment_register($conn, $actor, $params)
{
    $filter = fp_payment_register_filter($conn, $actor, $params);
    list($start, $length) = fp_payment_page_params($params);
    $total = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt' . fp_payment_register_from() . 'WHERE ' . $filter['base']);
    $filtered = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt, COALESCE(SUM(CASE WHEN p.Status = \'completed\' THEN p.Amount ELSE 0 END), 0) AS Completed'
        . fp_payment_register_from() . 'WHERE ' . $filter['where'], $filter['types'], $filter['values']);
    $rows = fp_db_rows(
        $conn,
        'SELECT ' . fp_payment_register_columns() . fp_payment_register_from() . 'WHERE ' . $filter['where'] . ' ORDER BY p.PaymentDate DESC, p.ID DESC LIMIT ? OFFSET ?',
        $filter['types'] . 'ii',
        array_merge($filter['values'], array($length, $start))
    );
    return array(
        'draw'            => fp_input_int($params, 'draw'),
        'recordsTotal'    => (int) $total[0]['Cnt'],
        'recordsFiltered' => (int) $filtered[0]['Cnt'],
        'data'            => array_map(function ($row) use ($actor) { return fp_payment_register_row($actor, $row); }, $rows),
        'summary'         => array('Completed' => fp_paise_to_decimal(fp_decimal_to_paise($filtered[0]['Completed']))),
    );
}

/**
 * Streams the filtered register as CSV. Cells that start with a formula character are prefixed.
 */
function fp_payment_export_csv($conn, $actor, $params)
{
    $filter = fp_payment_register_filter($conn, $actor, $params);
    $rows = fp_db_rows(
        $conn,
        'SELECT ' . fp_payment_register_columns() . fp_payment_register_from() . 'WHERE ' . $filter['where'] . ' ORDER BY p.PaymentDate DESC, p.ID DESC LIMIT ' . FP_PAYMENT_EXPORT_LIMIT,
        $filter['types'],
        $filter['values']
    );
    $cell = function ($value) {
        $value = str_replace(array("\r", "\n"), ' ', (string) $value);
        return preg_match('/^[=+\-@\t]/', $value) ? "'" . $value : $value;
    };
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="payment-register-' . date('Ymd-His') . '.csv"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('Payment No.', 'Payment date', 'Amount', 'Status', 'Mode', 'Reference', 'Shared reference', 'Payee', 'Payee account',
        'Expense No.', 'Type', 'Requester', 'Office', 'State', 'Recorded by', 'Recorded at', 'Failed / reversed reason', 'Bank return reference'));
    foreach ($rows as $row) {
        $r = fp_payment_register_row($actor, $row);
        fputcsv($out, array_map($cell, array(
            $r['PaymentNumber'], $r['PaymentDate'], $r['Amount'], $r['StatusLabel'], $r['ModeLabel'], $r['Reference'], $r['SharedReference'] ? 'Yes' : '',
            $r['Payee'], $r['PayeeAccount'], $r['ExpenseNumber'], $r['TypeLabel'], $r['Employee'], $r['Office'], $r['State'],
            $r['RecordedBy'], $r['RecordedAt'], $r['StatusReason'], $r['StatusReference'],
        )));
    }
    fclose($out);
    exit;
}
