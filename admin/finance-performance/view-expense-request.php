<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
require_once('controller/fp_audit.php');
require_once('controller/fp_requests.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_page($conn, 'view');
$UserType = $fpActor['user_type'];

$fpDb = fp_db_connect();
$fpId = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$fpRequest = $fpId > 0 ? fp_request_get_detail($fpDb, $fpId) : null;
$fpInvolved = false;
if ($fpRequest && !fp_request_can_view($fpActor, $fpRequest)) {
    $fpInvolved = fp_approval_is_involved($fpDb, $fpActor, $fpRequest);
    if (!$fpInvolved) {
        $fpRequest = null;
    }
}
$fpApproval = $fpRequest ? fp_approval_panel($fpDb, $fpActor, $fpRequest) : array();
if ($fpRequest && !$fpInvolved) {
    $fpInvolved = $fpApproval['can_decide'] || in_array((int) $fpActor['employee_id'], array_map('intval', array_column($fpApproval['decisions'], 'ActorEmployeeID')), true);
}
$fpItems = $fpRequest ? fp_request_items($fpDb, $fpId) : array();
$fpAttachments = $fpRequest ? fp_request_attachments($fpDb, $fpId) : array();
$fpCanEdit = $fpRequest && fp_request_can_edit($fpActor, $fpRequest);
$fpCanSeeDocs = $fpRequest && (fp_documents_can_view($fpActor, $fpRequest) || ($fpInvolved && (int) $fpActor['employee_id'] > 0));
$fpCanAudit = $fpRequest && fp_timeline_can_audit($fpActor, $fpRequest);
$fpTimeline = $fpRequest ? fp_timeline_for_request($fpDb, $fpId, $fpCanAudit, $fpCanSeeDocs) : array();
$fpRemovedDocs = $fpCanAudit && $fpCanSeeDocs ? fp_timeline_removed_documents($fpDb, $fpId) : array();
$fpPayment = $fpRequest ? fp_payment_panel($fpDb, $fpActor, $fpRequest) : array();
$fpPaymentActions = $fpRequest && ($fpPayment['can_record'] || $fpPayment['can_change_status']);
$fpSettlement = $fpRequest ? fp_settlement_request_panel($fpDb, $fpActor, $fpRequest) : null;
$fpIsOwn = $fpRequest && (int) $fpRequest['EmployeeID'] === (int) $fpActor['employee_id'];
if ($fpIsOwn) {
    $fpBack = array('file' => 'view-expense-requests.php', 'label' => 'My Requests', 'group' => 'booking', 'key' => 'my_requests');
} elseif ($fpInvolved) {
    $fpBack = array('file' => 'view-approvals.php', 'label' => 'Approvals', 'group' => '', 'key' => 'approvals');
} elseif ($fpRequest && $fpRequest['Status'] === 'approved' && $fpPayment['show_details']) {
    $fpBack = array('file' => 'view-payments.php', 'label' => 'Payments', 'group' => '', 'key' => 'payments');
} else {
    $fpBack = array('file' => 'view-expense-details.php', 'label' => 'Expense Details', 'group' => '', 'key' => 'expense_details');
}
$fpChecklist = $fpRequest ? fp_document_checklist($fpRequest['RequestType'], $fpItems, $fpAttachments) : array();
$fpCanSeeBudget = $fpRequest && in_array($fpRequest['Status'], array('submitted', 'returned', 'approved'), true)
    && (fp_can_globally($fpActor, 'budgets') || fp_record_in_scope($fpActor, 'approve', fp_request_scope_record($fpRequest)) || $fpInvolved);
$fpBudgetImpact = $fpCanSeeBudget ? fp_budget_impact($fpDb, $fpRequest, $fpItems) : array();
$fpRoute = $fpRequest ? fp_request_route($fpDb, $fpRequest) : array();
if (!$fpRequest) {
    http_response_code(404);
}

$fpDecisionLabels = fp_approval_decision_labels();
$fpDecisionClass = array('approved' => 'success', 'rejected' => 'danger', 'returned' => 'warning', 'reopened' => 'warning');
$fpStageStatusLabels = fp_approval_stage_status_labels();
$fpRoundDecisions = array();
foreach ($fpApproval['decisions'] ?? array() as $decision) {
    if ((int) $decision['Round'] === (int) ($fpRequest['SubmissionRound'] ?? 0) && (int) $decision['StageNo'] > 0) {
        $fpRoundDecisions[(int) $decision['StageNo']] = $decision;
    }
}

$fpTypes = fp_request_types();
$fpStatuses = fp_request_statuses();
$fpStatusClass = array('draft' => 'secondary', 'submitted' => 'info', 'returned' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'dark');
$fpTimelineClass = array(
    'create' => 'secondary', 'update' => 'secondary', 'submit' => 'info', 'cancel' => 'dark', 'attachment_add' => 'light', 'attachment_remove' => 'light',
    'approve_stage' => 'success', 'approve' => 'success', 'reject' => 'danger', 'return' => 'warning', 'escalate' => 'danger', 'reopen' => 'warning',
    'payment_record' => 'success', 'payment_failed' => 'danger', 'payment_reversed' => 'warning',
    'settlement_create' => 'secondary', 'settlement_update' => 'secondary', 'settlement_doc_add' => 'light', 'settlement_doc_remove' => 'light',
    'settlement_submit' => 'info', 'settlement_cancel' => 'dark', 'settlement_branch_confirm' => 'success', 'settlement_return' => 'warning',
    'settlement_verify' => 'success', 'settlement_refund' => 'success', 'settlement_additional' => 'success', 'settlement_txn_failed' => 'danger',
    'settlement_txn_reversed' => 'warning', 'settlement_close' => 'success', 'recurring_create' => 'secondary',
);
$fpDirect = null;
if ($fpRequest && $fpRequest['RequestType'] === 'direct') {
    $fpVendorId = (int) $fpRequest['VendorRegistrationID'];
    $fpVendorLabel = $fpVendorId > 0 ? (fp_vendor_labels($fpDb, array($fpVendorId))[$fpVendorId] ?? null) : null;
    $fpRule = (int) $fpRequest['RecurringRuleID'] > 0 ? fp_recurring_get($fpDb, $fpRequest['RecurringRuleID']) : null;
    $fpDueState = fp_direct_due_state($fpRequest, date('Y-m-d'));
    $fpDirect = array(
        'vendor'     => (string) $fpRequest['VendorName'],
        'code'       => $fpVendorLabel ? $fpVendorLabel['Code'] . ($fpVendorLabel['Approved'] ? '' : ' (no longer approved)') : '',
        'gstin'      => (string) $fpRequest['VendorGSTIN'],
        'ref'        => (string) $fpRequest['VendorAccountRef'],
        'bank'       => $fpPayment['show_details'] && $fpVendorLabel ? $fpVendorLabel['AccountHint'] : '',
        'due'        => (string) $fpRequest['DueDate'],
        'due_state'  => $fpDueState,
        'due_label'  => fp_direct_due_states()[$fpDueState],
        'rule'       => $fpRule ? $fpRule['RuleNumber'] . ' ' . $fpRule['Title'] : '',
        'rule_id'    => $fpRule && fp_recurring_can_view($fpActor, $fpRule) ? (int) $fpRule['ID'] : 0,
    );
}
$fpPaymentStatusLabels = fp_payment_request_statuses();
$fpPaymentRowClass = array('completed' => 'success', 'failed' => 'danger', 'reversed' => 'warning');
$fpTax = 0.0;
foreach ($fpItems as $item) {
    $fpTax += (float) $item['TaxAmount'];
}

setNavigation($_SESSION['Roles'] ?? array());

function fp_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function fp_money($value)
{
    return 'INR ' . number_format((float) $value, 2);
}

function fp_date($value)
{
    return $value ? date('d M Y', strtotime($value)) : '-';
}

function fp_datetime($value)
{
    return $value ? date('d M Y, h:i A', strtotime($value)) : '-';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $fpRequest ? fp_h($fpRequest['ExpenseNumber']) : 'Request not found'; ?> - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        .fp-dl dt { font-weight: 600; color: #666; font-size: 12px; }
        .fp-dl dd { margin-bottom: 10px; }
        #fp-detail-items th, #fp-detail-items td { font-size: 12.5px; }
        .fp-print-only { display: none; }
        .fp-timeline { list-style: none; margin: 0 0 10px; padding: 0 0 0 18px; border-left: 2px solid #e3e3e3; }
        .fp-tl-item { position: relative; padding: 0 0 16px 12px; }
        .fp-tl-item:before { content: ""; position: absolute; left: -25px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: #adb5bd; border: 2px solid #fff; }
        .fp-tl-success:before { background: #1dc9b7; } .fp-tl-danger:before { background: #fd3995; } .fp-tl-warning:before { background: #ffc241; } .fp-tl-info:before { background: #2196f3; }
        .fp-tl-note { white-space: pre-line; margin-top: 3px; }
        .fp-tl-changes td { white-space: pre-line; font-size: 12px; vertical-align: top; }
        .fp-tl-changes th { font-size: 11.5px; }
        .fp-tl-before { color: #8a8a8a; }
        .fp-tl-raw { max-height: 260px; overflow: auto; font-size: 11px; background: #f7f7f7; padding: 6px; white-space: pre-wrap; }
        @media print {
            .page-sidebar, .page-header, .page-footer, .breadcrumb, .fp-no-print, .page-content-overlay { display: none !important; }
            .page-content-wrapper, .page-content { margin: 0 !important; padding: 0 !important; }
            .panel { border: 0 !important; box-shadow: none !important; }
            .fp-print-only { display: block; }
            a[href]:after { content: none !important; }
        }
    </style>
</head>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item">Finance &amp; Performance</li>
                        <li class="breadcrumb-item"><a href="<?php echo fp_h($fpBack['file']); ?>"><?php echo fp_h($fpBack['label']); ?></a></li>
                        <li class="breadcrumb-item active"><?php echo $fpRequest ? fp_h($fpRequest['ExpenseNumber']) : 'Not found'; ?></li>
                    </ol>

                    <?php if (!$fpRequest) { ?>
                        <div class="alert alert-warning">This request does not exist or you do not have access to it.</div>
                        <a href="view-expense-details.php" class="btn btn-secondary">Back to Expense Details</a>
                    <?php } else { ?>
                        <div class="d-flex flex-wrap mb-3 fp-no-print">
                            <a href="<?php echo fp_h($fpBack['file']); ?>" class="btn btn-secondary btn-sm mr-auto mb-2">Back to <?php echo fp_h($fpBack['label']); ?></a>
                            <?php if ($fpApproval['can_reopen']) { ?>
                                <button type="button" class="btn btn-outline-warning btn-sm mr-2 mb-2" data-toggle="modal" data-target="#fp-reopen-modal"><i class="fa-solid fa-rotate-left mr-1"></i>Reopen for changes</button>
                            <?php } ?>
                            <?php if ($fpCanEdit) { ?>
                                <a href="view-expense-request-form.php?id=<?php echo (int) $fpRequest['ID']; ?>" class="btn btn-primary btn-sm mr-2 mb-2"><?php echo $fpRequest['Status'] === 'returned' ? 'Revise request' : 'Edit draft'; ?></a>
                            <?php } ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm mb-2" onclick="window.print()"><i class="fa-solid fa-print mr-1"></i>Print</button>
                        </div>

                        <div class="fp-print-only mb-3">
                            <h3 class="mb-0">Expense request <?php echo fp_h($fpRequest['ExpenseNumber']); ?></h3>
                            <small>Printed <?php echo fp_h(date('d M Y, h:i A')); ?> by <?php echo fp_h($fpActor['username']); ?></small>
                        </div>

                        <div class="panel">
                            <div class="panel-hdr">
                                <h2><?php echo fp_h($fpRequest['ExpenseNumber']); ?>&nbsp;<span class="fw-300"><?php echo fp_h($fpTypes[$fpRequest['RequestType']] ?? $fpRequest['RequestType']); ?></span></h2>
                                <div class="panel-toolbar">
                                    <span class="badge badge-<?php echo fp_h($fpStatusClass[$fpRequest['Status']] ?? 'secondary'); ?> mr-3" style="font-size:12px"><?php echo fp_h($fpStatuses[$fpRequest['Status']] ?? $fpRequest['Status']); ?></span>
                                </div>
                            </div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <dl class="row fp-dl mb-0">
                                        <div class="col-md-3"><dt>Requester</dt><dd><?php echo fp_h($fpRequest['EmployeeName']); ?><?php echo $fpRequest['EmployeeNumber'] !== null && $fpRequest['EmployeeNumber'] !== '' ? ' (' . fp_h($fpRequest['EmployeeNumber']) . ')' : ''; ?></dd></div>
                                        <div class="col-md-3"><dt>Branch office</dt><dd><?php echo fp_h($fpRequest['OfficeName'] . ' (' . $fpRequest['OfficeCode'] . ')'); ?></dd></div>
                                        <div class="col-md-3"><dt>Legal entity</dt><dd><?php echo fp_h($fpRequest['CompanyName']); ?></dd></div>
                                        <div class="col-md-3"><dt>State</dt><dd><?php echo fp_h($fpRequest['StateName']); ?></dd></div>
                                        <div class="col-md-12"><dt>Purpose</dt><dd style="white-space:pre-line"><?php echo fp_h($fpRequest['Purpose']); ?></dd></div>
                                        <?php if ($fpDirect) { ?>
                                            <div class="col-md-3"><dt>Pay to (vendor / payee)</dt><dd><?php echo $fpDirect['vendor'] !== '' ? fp_h($fpDirect['vendor']) : '<span class="text-muted">Not entered</span>'; ?>
                                                <?php echo $fpDirect['code'] !== '' ? ' <span class="badge badge-success">' . fp_h($fpDirect['code']) . '</span>' : ($fpDirect['vendor'] !== '' ? ' <span class="badge badge-light">not a registered vendor</span>' : ''); ?>
                                                <?php echo $fpDirect['gstin'] !== '' ? '<div class="small text-muted">GSTIN ' . fp_h($fpDirect['gstin']) . '</div>' : ''; ?>
                                                <?php echo $fpDirect['bank'] !== '' ? '<div class="small text-muted">Bank: ' . fp_h($fpDirect['bank']) . '</div>' : ''; ?></dd></div>
                                            <div class="col-md-3"><dt>Consumer / agreement no.</dt><dd><?php echo $fpDirect['ref'] !== '' ? fp_h($fpDirect['ref']) : '-'; ?></dd></div>
                                            <div class="col-md-3"><dt>Due date</dt><dd><?php echo fp_h(fp_date($fpDirect['due'])); ?>
                                                <?php if (in_array($fpDirect['due_state'], array('overdue', 'due_soon'), true)) { ?>
                                                    <span class="badge badge-<?php echo $fpDirect['due_state'] === 'overdue' ? 'danger' : 'warning'; ?>"><?php echo fp_h($fpDirect['due_label']); ?></span>
                                                <?php } ?></dd></div>
                                            <div class="col-md-3"><dt>Recurring expense</dt><dd><?php
                                                if ($fpDirect['rule'] === '') {
                                                    echo 'One-off';
                                                } elseif ($fpDirect['rule_id'] > 0) {
                                                    echo '<a href="view-recurring-expenses.php#rule-' . (int) $fpDirect['rule_id'] . '">' . fp_h($fpDirect['rule']) . '</a>';
                                                } else {
                                                    echo fp_h($fpDirect['rule']);
                                                } ?></dd></div>
                                        <?php } ?>
                                        <div class="col-md-3"><dt>Period</dt><dd><?php echo $fpRequest['PeriodFrom'] ? fp_h(fp_date($fpRequest['PeriodFrom']) . ' to ' . fp_date($fpRequest['PeriodTo'])) : '-'; ?><?php echo (int) $fpRequest['IsRecurring'] === 1 ? ' <span class="badge badge-light">recurring</span>' : ''; ?></dd></div>
                                        <div class="col-md-3"><dt>Total amount</dt><dd><strong><?php echo fp_h(fp_money($fpRequest['TotalAmount'])); ?></strong><?php echo $fpTax > 0 ? ' <small class="text-muted">incl. tax ' . fp_h(fp_money($fpTax)) . '</small>' : ''; ?></dd></div>
                                        <div class="col-md-3"><dt>Payment status</dt><dd><?php echo fp_h($fpPaymentStatusLabels[$fpRequest['PaymentStatus']] ?? $fpRequest['PaymentStatus']); ?></dd></div>
                                        <div class="col-md-3"><dt>Settlement status</dt><dd><?php echo fp_h(fp_settlement_request_statuses()[$fpRequest['SettlementStatus']] ?? $fpRequest['SettlementStatus']); ?></dd></div>
                                        <div class="col-md-3"><dt>Created</dt><dd><?php echo fp_h(fp_datetime($fpRequest['CreatedAt'])); ?></dd></div>
                                        <div class="col-md-3"><dt>Submitted</dt><dd><?php echo fp_h(fp_datetime($fpRequest['SubmittedAt'])); ?></dd></div>
                                        <?php if ($fpRequest['Status'] === 'approved' && $fpRequest['ApprovedAt']) { ?>
                                            <div class="col-md-3"><dt>Approved</dt><dd><?php echo fp_h(fp_datetime($fpRequest['ApprovedAt'])); ?></dd></div>
                                        <?php } elseif ($fpRequest['Status'] === 'rejected' && $fpRequest['RejectedAt']) { ?>
                                            <div class="col-md-3"><dt>Rejected</dt><dd><?php echo fp_h(fp_datetime($fpRequest['RejectedAt'])); ?></dd></div>
                                        <?php } elseif ($fpRequest['Status'] === 'returned' && $fpRequest['ReturnedAt']) { ?>
                                            <div class="col-md-3"><dt>Returned</dt><dd><?php echo fp_h(fp_datetime($fpRequest['ReturnedAt'])); ?></dd></div>
                                        <?php } ?>
                                        <?php if ($fpRequest['CancelledAt']) { ?>
                                            <div class="col-md-3"><dt>Cancelled</dt><dd><?php echo fp_h(fp_datetime($fpRequest['CancelledAt'])); ?></dd></div>
                                        <?php } ?>
                                        <?php if ($fpRequest['Remarks'] !== '') { ?>
                                            <div class="col-md-12"><dt>Remarks</dt><dd style="white-space:pre-line"><?php echo fp_h($fpRequest['Remarks']); ?></dd></div>
                                        <?php } ?>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <?php if ($fpRequest['Status'] === 'returned' && $fpApproval['decisions']) {
                            $fpLast = end($fpApproval['decisions']);
                            if (in_array($fpLast['Decision'], array('returned', 'reopened'), true)) { ?>
                                <div class="alert alert-warning">
                                    <strong>Returned for changes</strong> by <?php echo fp_h($fpLast['ActorName']); ?> on <?php echo fp_h(fp_datetime($fpLast['DecidedAt'])); ?>:
                                    <span style="white-space:pre-line"><?php echo fp_h($fpLast['Comments']); ?></span>
                                    <div class="small mt-1">After changing and submitting again, every approval stage must approve again.</div>
                                </div>
                        <?php }
                        } ?>

                        <?php if ($fpRequest['Status'] === 'submitted' && $fpApproval['stage']) {
                            $fpStage = $fpApproval['stage']; ?>
                            <div class="panel fp-no-print" id="fp-decision-panel">
                                <div class="panel-hdr">
                                    <h2>Approval&nbsp;<span class="fw-300">Stage <?php echo (int) $fpStage['StageNo'] . ': ' . fp_h($fpStage['StageName']); ?></span></h2>
                                    <div class="panel-toolbar">
                                        <?php if ($fpApproval['overdue']) { ?><span class="badge badge-danger mr-3">Overdue - escalated</span><?php } ?>
                                    </div>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <p class="mb-2">
                                            Waiting since <?php echo fp_h(fp_datetime($fpStage['ActivatedAt'] ?: $fpRequest['SubmittedAt'])); ?>.
                                            <?php if ($fpApproval['waiting_for']) { ?>
                                                Can be decided by:
                                                <?php echo fp_h(implode(', ', array_map(function ($person) { return $person['name'] . ' (' . $person['label'] . ')'; }, $fpApproval['waiting_for']))); ?>
                                            <?php } else { ?>
                                                <span class="text-danger">Nobody can decide this stage at the moment. Please ask Finance to check the approval policy, roles or delegations.</span>
                                            <?php } ?>
                                        </p>
                                        <?php if ($fpApproval['can_decide']) { ?>
                                            <form id="fp-decision-form" onsubmit="return false;" data-id="<?php echo (int) $fpRequest['ID']; ?>" data-row-version="<?php echo (int) $fpRequest['RowVersion']; ?>" data-stage="<?php echo (int) $fpStage['StageNo']; ?>">
                                                <div class="alert alert-info py-2 mb-2">You can decide this stage as: <strong><?php echo fp_h($fpApproval['decide_as']); ?></strong>. Please check the line items and documents below first.</div>
                                                <div class="form-group mb-2">
                                                    <label for="fp-decision-comments">Comments <small class="text-muted">(required to reject or return; the requester sees them)</small></label>
                                                    <textarea class="form-control" id="fp-decision-comments" name="Comments" rows="3" maxlength="<?php echo FP_APPROVAL_COMMENT_MAX; ?>"></textarea>
                                                    <div class="invalid-feedback" data-for="Comments"></div>
                                                </div>
                                                <button type="button" class="btn btn-success mr-2 fp-decide" data-decision="approve"><i class="fa-solid fa-check mr-1"></i>Approve</button>
                                                <button type="button" class="btn btn-warning mr-2 fp-decide" data-decision="return"><i class="fa-solid fa-rotate-left mr-1"></i>Return for changes</button>
                                                <button type="button" class="btn btn-danger fp-decide" data-decision="reject"><i class="fa-solid fa-xmark mr-1"></i>Reject</button>
                                            </form>
                                        <?php } elseif ((int) $fpRequest['EmployeeID'] !== (int) $fpActor['employee_id'] && $fpInvolved) { ?>
                                            <div class="text-muted"><?php echo fp_h($fpApproval['decide_reason']); ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <div class="panel">
                            <div class="panel-hdr"><h2>Line items</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content table-responsive">
                                    <table class="table table-bordered table-sm mb-0" id="fp-detail-items">
                                        <thead class="thead-light">
                                            <tr><th>#</th><th>Category</th><th>Description</th><th><?php echo $fpRequest['RequestType'] === 'advance' ? 'Expected date' : 'Expense date'; ?></th><th>Vendor</th><th>Invoice</th><th>Documents</th><th class="text-right">Tax</th><th class="text-right">Amount</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fpItems as $index => $item) { ?>
                                                <tr>
                                                    <td><?php echo (int) $item['LineNo']; ?></td>
                                                    <td><?php echo fp_h(($item['ParentCategoryName'] ? $item['ParentCategoryName'] . ' > ' : '') . $item['CategoryName']); ?></td>
                                                    <td><?php echo fp_h($item['Description']); ?></td>
                                                    <td class="text-nowrap"><?php echo fp_h(fp_date($item['ExpenseDate'])); ?></td>
                                                    <td><?php echo fp_h($item['VendorName'] ?: '-'); ?><?php echo $item['VendorGSTIN'] ? '<div class="small text-muted">GSTIN ' . fp_h($item['VendorGSTIN']) . '</div>' : ''; ?></td>
                                                    <td><?php echo fp_h($item['InvoiceNumber'] ?: '-'); ?><?php echo $item['InvoiceDate'] ? '<div class="small text-muted">' . fp_h(fp_date($item['InvoiceDate'])) . '</div>' : ''; ?></td>
                                                    <td class="small">
                                                        <?php if (empty($fpChecklist[$index])) { ?>
                                                            <span class="text-muted">None required</span>
                                                        <?php } ?>
                                                        <?php foreach ($fpChecklist[$index] ?? array() as $rule) { ?>
                                                            <div class="<?php echo $rule['met'] ? 'text-success' : 'text-danger'; ?>"><i class="fa-solid <?php echo $rule['met'] ? 'fa-check' : 'fa-xmark'; ?>"></i> <?php echo fp_h($rule['label']); ?></div>
                                                        <?php } ?>
                                                    </td>
                                                    <td class="text-right text-nowrap"><?php echo (float) $item['TaxAmount'] > 0 ? fp_h(number_format((float) $item['TaxAmount'], 2)) : '-'; ?></td>
                                                    <td class="text-right text-nowrap"><?php echo fp_h(number_format((float) $item['Amount'], 2)); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                        <tfoot><tr><th colspan="8" class="text-right">Total</th><th class="text-right text-nowrap"><?php echo fp_h(fp_money($fpRequest['TotalAmount'])); ?></th></tr></tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <?php if ($fpRoute) { ?>
                            <div class="panel">
                                <div class="panel-hdr"><h2>Approval route&nbsp;<span class="fw-300">Policy <?php echo fp_h($fpRequest['ApprovalPolicyCode'] . ' v' . $fpRequest['ApprovalPolicyVersion']); ?></span></h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="thead-light"><tr><th>Stage</th><th>Approver</th><th>Mandatory</th><th>Escalation</th><th>Status</th><th>Decision</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($fpRoute as $stage) {
                                                    $fpIsCurrent = (int) $stage['StageNo'] === (int) $fpRequest['CurrentStageNo'] && $fpRequest['Status'] === 'submitted';
                                                    $fpStageDecision = $fpRoundDecisions[(int) $stage['StageNo']] ?? null;
                                                    $fpBadge = array('skipped' => 'secondary', 'approved' => 'success', 'rejected' => 'danger', 'returned' => 'warning', 'cancelled' => 'light')[$stage['Status']] ?? 'light'; ?>
                                                    <tr<?php echo $fpIsCurrent ? ' class="table-info"' : ''; ?>>
                                                        <td><?php echo (int) $stage['StageNo'] . '. ' . fp_h($stage['StageName']); ?></td>
                                                        <td><?php echo fp_h($stage['ApproverLabel']); ?></td>
                                                        <td><?php echo (int) $stage['IsMandatory'] === 1 ? 'Yes' : 'No'; ?></td>
                                                        <td>
                                                            <?php echo fp_h($stage['EscalationLabel'] ?: '-'); ?>
                                                            <?php echo $stage['EscalatedAt'] ? '<div class="small text-danger">Escalated ' . fp_h(fp_datetime($stage['EscalatedAt'])) . '</div>' : ''; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $fpIsCurrent ? '<span class="badge badge-info">Waiting</span>' : '<span class="badge badge-' . $fpBadge . '">' . fp_h($fpStageStatusLabels[$stage['Status']] ?? ucfirst($stage['Status'])) . '</span>'; ?>
                                                            <?php echo $stage['Note'] !== '' ? '<div class="small text-muted">' . fp_h($stage['Note']) . '</div>' : ''; ?>
                                                        </td>
                                                        <td class="small">
                                                            <?php if ($fpStageDecision) { ?>
                                                                <strong><?php echo fp_h($fpStageDecision['ActorName']); ?></strong>, <?php echo fp_h(fp_datetime($fpStageDecision['DecidedAt'])); ?>
                                                                <div class="text-muted"><?php echo fp_h($fpStageDecision['BasisLabel']); ?></div>
                                                                <?php echo $fpStageDecision['Comments'] !== '' ? '<div style="white-space:pre-line">' . fp_h($fpStageDecision['Comments']) . '</div>' : ''; ?>
                                                            <?php } else { ?>
                                                                -
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                        <p class="small text-muted mb-0 mt-2">The route was fixed when the request was submitted and does not change if the policy is later edited. Stages are decided one after another.</p>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if ($fpApproval['decisions']) { ?>
                            <div class="panel">
                                <div class="panel-hdr"><h2>Approval decisions</h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="thead-light"><tr><th>When</th><th>Submission</th><th>Stage</th><th>Decision</th><th>By</th><th>Comments</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($fpApproval['decisions'] as $decision) { ?>
                                                    <tr>
                                                        <td class="text-nowrap"><?php echo fp_h(fp_datetime($decision['DecidedAt'])); ?></td>
                                                        <td><?php echo (int) $decision['Round']; ?></td>
                                                        <td><?php echo (int) $decision['StageNo'] > 0 ? (int) $decision['StageNo'] . '. ' . fp_h($decision['StageName']) : 'After final approval'; ?></td>
                                                        <td><span class="badge badge-<?php echo fp_h($fpDecisionClass[$decision['Decision']] ?? 'light'); ?>"><?php echo fp_h($fpDecisionLabels[$decision['Decision']] ?? $decision['Decision']); ?></span></td>
                                                        <td><?php echo fp_h($decision['ActorName']); ?><div class="small text-muted"><?php echo fp_h($decision['BasisLabel']); ?></div></td>
                                                        <td style="white-space:pre-line"><?php echo fp_h($decision['Comments'] ?: '-'); ?></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if ($fpCanSeeBudget) { ?>
                            <div class="panel">
                                <div class="panel-hdr"><h2>Budget impact</h2></div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <?php if (!$fpBudgetImpact) { ?>
                                            <span class="text-muted">No active budget covers this request.</span>
                                        <?php } else { ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="thead-light"><tr><th>Budget</th><th>If exceeded</th><th class="text-right">Budget</th><th class="text-right">Committed by other requests</th><th class="text-right">Reserved for category budgets</th><th class="text-right">This request</th><th class="text-right">Left after this request</th></tr></thead>
                                                    <tbody>
                                                        <?php foreach ($fpBudgetImpact as $budget) { ?>
                                                            <tr>
                                                                <td><?php echo fp_h($budget['Label']); ?></td>
                                                                <td><?php echo $budget['Enforcement'] === 'block' ? 'Block' : 'Warn'; ?></td>
                                                                <td class="text-right text-nowrap"><?php echo fp_h(fp_money($budget['Amount'] / 100)); ?></td>
                                                                <td class="text-right text-nowrap"><?php echo fp_h(fp_money($budget['Committed'] / 100)); ?></td>
                                                                <td class="text-right text-nowrap"><?php echo $budget['Reserved'] > 0 ? fp_h(fp_money($budget['Reserved'] / 100)) : '-'; ?></td>
                                                                <td class="text-right text-nowrap"><?php echo fp_h(fp_money($budget['ThisRequest'] / 100)); ?></td>
                                                                <td class="text-right text-nowrap">
                                                                    <?php if ($budget['Exceeded']) { ?>
                                                                        <span class="text-danger font-weight-bold">Over by <?php echo fp_h(fp_money($budget['OverBy'] / 100)); ?></span>
                                                                    <?php } else { ?>
                                                                        <span class="text-success"><?php echo fp_h(fp_money($budget['LeftAfter'] / 100)); ?></span>
                                                                    <?php } ?>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <p class="small text-muted mb-0 mt-2">Committed = pending approval + approved requests in the same period. Figures are live and change as other requests move through approval.</p>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <div class="panel">
                            <div class="panel-hdr"><h2>Documents</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <?php if (!$fpAttachments) { ?>
                                        <span class="text-muted">No documents attached.</span>
                                    <?php } elseif (!$fpCanSeeDocs) { ?>
                                        <span class="text-muted"><?php echo count($fpAttachments); ?> document(s) attached. Only the requester, approvers and Finance can open them.</span>
                                    <?php } else { ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="thead-light"><tr><th>Line</th><th>Type</th><th>File</th><th>Note</th><th>Size</th><th>Uploaded</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($fpAttachments as $attachment) {
                                                        $fpUrl = 'action/download_attachment.php?ID=' . (int) $attachment['ID']; ?>
                                                        <tr>
                                                            <td><?php echo $attachment['LineNo'] ? 'Line ' . (int) $attachment['LineNo'] : 'Whole request'; ?></td>
                                                            <td><?php echo fp_h(fp_document_type_label($attachment['DocumentType'])); ?></td>
                                                            <td>
                                                                <a href="<?php echo $fpUrl; ?>" target="_blank" rel="noopener"><?php echo fp_h($attachment['OriginalName']); ?></a>
                                                                <a href="<?php echo $fpUrl; ?>&amp;download=1" class="ml-1 fp-no-print" title="Download"><i class="fa-solid fa-download"></i></a>
                                                            </td>
                                                            <td><?php echo fp_h($attachment['Notes']); ?></td>
                                                            <td class="text-nowrap"><?php echo fp_h(number_format($attachment['SizeBytes'] / 1024, 0)); ?> KB</td>
                                                            <td class="text-nowrap"><?php echo fp_h(fp_datetime($attachment['UploadedAt'])); ?><?php echo $attachment['UploadedByName'] !== '' ? '<div class="small text-muted">' . fp_h($attachment['UploadedByName']) . '</div>' : ''; ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                    <?php if ($fpRemovedDocs) { ?>
                                        <h6 class="mt-3 mb-2">Removed documents <small class="text-muted">(kept for audit; only audit users see this list)</small></h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="thead-light"><tr><th>Line</th><th>Type</th><th>File</th><th>Uploaded</th><th>Removed</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($fpRemovedDocs as $attachment) {
                                                        $fpUrl = 'action/download_attachment.php?ID=' . (int) $attachment['ID']; ?>
                                                        <tr class="text-muted">
                                                            <td><?php echo $attachment['LineNo'] ? 'Line ' . (int) $attachment['LineNo'] : 'Whole request'; ?></td>
                                                            <td><?php echo fp_h(fp_document_type_label($attachment['DocumentType'])); ?></td>
                                                            <td><a href="<?php echo $fpUrl; ?>" target="_blank" rel="noopener"><?php echo fp_h($attachment['OriginalName']); ?></a></td>
                                                            <td class="text-nowrap"><?php echo fp_h(fp_datetime($attachment['UploadedAt'])); ?></td>
                                                            <td class="text-nowrap"><?php echo fp_h(fp_datetime($attachment['DeletedAt'])); ?><?php echo $attachment['DeletedByName'] !== '' ? '<div class="small">' . fp_h($attachment['DeletedByName']) . '</div>' : ''; ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="panel" id="fp-payments-panel">
                            <div class="panel-hdr">
                                <h2>Payments &amp; settlement</h2>
                                <div class="panel-toolbar fp-no-print">
                                    <?php if ($fpPayment['can_record']) { ?>
                                        <button type="button" class="btn btn-primary btn-sm mr-3 fp-record-payment" data-id="<?php echo (int) $fpRequest['ID']; ?>"><i class="fa-solid fa-money-check-dollar mr-1"></i>Record payment</button>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <dl class="row fp-dl mb-0">
                                        <div class="col-md-3"><dt>Payment status</dt><dd><strong><?php echo fp_h($fpPaymentStatusLabels[$fpRequest['PaymentStatus']] ?? $fpRequest['PaymentStatus']); ?></strong></dd></div>
                                        <div class="col-md-3"><dt>Paid</dt><dd><?php echo fp_h(fp_payment_money($fpPayment['paid_paise'])); ?> of <?php echo fp_h(fp_money($fpRequest['TotalAmount'])); ?></dd></div>
                                        <div class="col-md-3"><dt>Unpaid balance</dt><dd><?php echo $fpRequest['Status'] === 'approved' ? fp_h(fp_payment_money($fpPayment['balance_paise'])) : '<span class="text-muted">Not approved</span>'; ?></dd></div>
                                        <div class="col-md-3"><dt>Settlement status</dt><dd><?php echo fp_h(fp_settlement_request_statuses()[$fpRequest['SettlementStatus']] ?? $fpRequest['SettlementStatus']); ?></dd></div>
                                    </dl>
                                    <?php if (!$fpPayment['payments']) { ?>
                                        <p class="text-muted mb-0"><?php echo $fpRequest['Status'] === 'approved' ? 'Approved but not paid yet. Approval is not payment: Head Office Finance records the payment when the money is sent.' : 'Payments can be made after final approval.'; ?></p>
                                    <?php } else { ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="thead-light"><tr><th>Payment no.</th><th>Date</th><th class="text-right">Amount</th><th>Mode / reference</th><th>Paid to</th><th>Status</th><th>Recorded</th><?php echo $fpPayment['can_change_status'] ? '<th class="fp-no-print"></th>' : ''; ?></tr></thead>
                                                <tbody>
                                                    <?php foreach ($fpPayment['payments'] as $payment) { ?>
                                                        <tr>
                                                            <td class="text-nowrap"><?php echo fp_h($payment['PaymentNumber']); ?></td>
                                                            <td class="text-nowrap"><?php echo fp_h(fp_date($payment['PaymentDate'])); ?></td>
                                                            <td class="text-right text-nowrap"><?php echo $payment['Status'] === 'completed' ? fp_h(fp_money($payment['Amount'])) : '<s>' . fp_h(fp_money($payment['Amount'])) . '</s>'; ?></td>
                                                            <td><?php echo fp_h(fp_payment_modes()[$payment['PaymentMode']] ?? $payment['PaymentMode']); ?><div class="small text-muted"><?php echo fp_h($payment['Reference'] !== '' ? $payment['Reference'] : '-'); ?><?php echo (int) $payment['SharedReference'] === 1 ? ' <span class="badge badge-light">shared transfer</span>' : ''; ?></div></td>
                                                            <td><?php echo fp_h($payment['PayeeName']); ?><?php echo $fpPayment['show_details'] && $payment['PayeeAccountHint'] !== '' ? '<div class="small text-muted">' . fp_h($payment['PayeeAccountHint']) . '</div>' : ''; ?></td>
                                                            <td>
                                                                <span class="badge badge-<?php echo fp_h($fpPaymentRowClass[$payment['Status']] ?? 'light'); ?>"><?php echo fp_h(fp_payment_statuses()[$payment['Status']] ?? $payment['Status']); ?></span>
                                                                <?php if ($payment['Status'] !== 'completed') { ?>
                                                                    <div class="small text-muted"><?php echo fp_h($payment['StatusReason']); ?><?php echo $payment['StatusReference'] !== '' ? ' (ref. ' . fp_h($payment['StatusReference']) . ')' : ''; ?></div>
                                                                    <div class="small text-muted"><?php echo fp_h(fp_datetime($payment['StatusChangedAt'])); ?><?php echo $payment['StatusChangedByName'] !== '' ? ', ' . fp_h($payment['StatusChangedByName']) : ''; ?></div>
                                                                <?php } ?>
                                                            </td>
                                                            <td class="text-nowrap small"><?php echo fp_h(fp_datetime($payment['RecordedAt'])); ?><div class="text-muted"><?php echo fp_h($payment['RecordedByName']); ?></div></td>
                                                            <?php if ($fpPayment['can_change_status']) { ?>
                                                                <td class="text-nowrap fp-no-print">
                                                                    <?php if ($payment['Status'] === 'completed') { ?>
                                                                        <button type="button" class="btn btn-xs btn-outline-danger fp-payment-status" data-outcome="failed" data-id="<?php echo (int) $payment['ID']; ?>" data-number="<?php echo fp_h($payment['PaymentNumber']); ?>">Failed</button>
                                                                        <button type="button" class="btn btn-xs btn-outline-warning fp-payment-status" data-outcome="reversed" data-id="<?php echo (int) $payment['ID']; ?>" data-number="<?php echo fp_h($payment['PaymentNumber']); ?>">Reverse</button>
                                                                    <?php } ?>
                                                                </td>
                                                            <?php } ?>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                    <?php if ($fpSettlement && $fpSettlement['summary']['paid'] > 0) { $fpStl = $fpSettlement['summary']; ?>
                                        <div class="border rounded p-2 mt-3">
                                            <div class="d-flex flex-wrap align-items-center">
                                                <strong class="mr-3">Advance settlement</strong>
                                                <span class="mr-3">Expenses accepted: <?php echo fp_h(fp_payment_money($fpStl['accepted'])); ?></span>
                                                <span class="mr-3">Cash returned: <?php echo fp_h(fp_payment_money($fpStl['refund'])); ?></span>
                                                <span class="mr-3">Extra paid: <?php echo fp_h(fp_payment_money($fpStl['additional'])); ?></span>
                                                <strong class="mr-auto"><?php echo fp_h(fp_settlement_balance_text($fpStl['balance'])); ?></strong>
                                                <a href="view-advance-settlement.php?id=<?php echo (int) $fpRequest['ID']; ?>" class="btn btn-sm <?php echo $fpSettlement['can_start'] ? 'btn-primary' : 'btn-outline-primary'; ?> fp-no-print">
                                                    <i class="fa-solid fa-hand-holding-dollar mr-1"></i><?php echo $fpSettlement['can_start'] ? 'Settle this advance' : 'Settlement details'; ?>
                                                </a>
                                            </div>
                                            <?php if ($fpStl['open_claims'] > 0 || $fpSettlement['claims'] > 0) { ?>
                                                <div class="small text-muted mt-1"><?php echo (int) $fpSettlement['claims']; ?> settlement claim(s)<?php echo $fpStl['open_claims'] > 0 ? ', one still open' : ''; ?>.</div>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                    <?php if ($fpRequest['Status'] === 'approved' || $fpRequest['PaymentStatus'] !== 'unpaid') { ?>
                                        <div class="alert alert-secondary small mt-3 mb-0">
                                            Approved and paid records cannot be edited or deleted. Corrections are made openly:
                                            an approved, unpaid request can be reopened by Head Office Finance, CFO or Admin (it then needs every approval again);
                                            a wrong or bounced payment is marked failed or reversed with a reason, and a new payment is recorded.
                                            <?php echo $fpRequest['RequestType'] === 'advance' ? 'Once an advance is settled and closed, its payments and settlement can no longer be changed. ' : ''; ?>Every correction appears in the timeline.
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="panel" id="fp-timeline-panel">
                            <div class="panel-hdr">
                                <h2>Timeline&nbsp;<span class="fw-300">all changes, oldest first</span></h2>
                                <div class="panel-toolbar fp-no-print">
                                    <?php if ($fpCanAudit) { ?>
                                        <a href="action/export_timeline.php?id=<?php echo (int) $fpRequest['ID']; ?>" class="btn btn-outline-secondary btn-sm mr-3"><i class="fa-solid fa-file-csv mr-1"></i>Export audit (CSV)</a>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <?php if (!$fpTimeline) { ?>
                                        <span class="text-muted">No recorded changes.</span>
                                    <?php } else { ?>
                                        <ul class="fp-timeline">
                                            <?php foreach ($fpTimeline as $entry) { ?>
                                                <li class="fp-tl-item fp-tl-<?php echo fp_h($fpTimelineClass[$entry['Action']] ?? 'light'); ?>">
                                                    <div class="fp-tl-head">
                                                        <span class="badge badge-<?php echo fp_h($fpTimelineClass[$entry['Action']] ?? 'light'); ?>"><?php echo fp_h($entry['Label']); ?></span>
                                                        <strong class="ml-1"><?php echo fp_h($entry['Actor']); ?></strong>
                                                        <span class="text-muted small"><?php echo $entry['UserName'] !== $entry['Actor'] ? '(' . fp_h($entry['UserName']) . ') ' : ''; ?><?php echo fp_h(fp_datetime($entry['When'])); ?></span>
                                                    </div>
                                                    <?php if ($entry['Note'] !== '') { ?>
                                                        <div class="fp-tl-note"><span class="text-muted"><?php echo fp_h($entry['NoteLabel']); ?>:</span> <?php echo fp_h($entry['Note']); ?></div>
                                                    <?php } ?>
                                                    <?php if ($entry['Details']) { ?>
                                                        <div class="small text-muted"><?php echo fp_h(implode(' | ', array_map(function ($detail) { return $detail[0] . ': ' . str_replace("\n", '; ', $detail[1]); }, $entry['Details']))); ?></div>
                                                    <?php } ?>
                                                    <?php if ($entry['Changes']) { ?>
                                                        <table class="table table-sm table-bordered fp-tl-changes mt-1 mb-0">
                                                            <thead class="thead-light"><tr><th style="width:18%">Field</th><th style="width:41%">Before</th><th>After</th></tr></thead>
                                                            <tbody>
                                                                <?php foreach ($entry['Changes'] as $change) { ?>
                                                                    <tr><td><?php echo fp_h($change[0]); ?></td><td class="fp-tl-before"><?php echo fp_h($change[1]); ?></td><td><?php echo fp_h($change[2]); ?></td></tr>
                                                                <?php } ?>
                                                            </tbody>
                                                        </table>
                                                    <?php } ?>
                                                    <?php if ($fpCanAudit) { ?>
                                                        <div class="small text-muted mt-1 fp-no-print">
                                                            Entry #<?php echo (int) $entry['ID']; ?> | IP <?php echo fp_h($entry['IPAddress'] ?: '-'); ?> | Device <?php echo fp_h(mb_strimwidth((string) $entry['Device'], 0, 90, '...') ?: '-'); ?>
                                                            <?php if ($entry['StoredBefore'] !== null || $entry['StoredAfter'] !== null) { ?>
                                                                <details class="d-inline ml-1"><summary class="d-inline text-primary" style="cursor:pointer">All recorded values</summary>
                                                                    <pre class="fp-tl-raw mt-1 mb-0">Before: <?php echo fp_h(json_encode($entry['StoredBefore'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>

After: <?php echo fp_h(json_encode($entry['StoredAfter'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
                                                                </details>
                                                            <?php } ?>
                                                        </div>
                                                    <?php } ?>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                        <p class="small text-muted mb-0">The timeline is append-only: entries are never edited or deleted.</p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php if ($fpRequest && $fpApproval['can_reopen']) { ?>
        <div class="modal fade" id="fp-reopen-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form class="modal-content" id="fp-reopen-form" onsubmit="return false;" data-id="<?php echo (int) $fpRequest['ID']; ?>" data-row-version="<?php echo (int) $fpRequest['RowVersion']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Reopen <?php echo fp_h($fpRequest['ExpenseNumber']); ?> for changes</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>The request goes back to the requester. After the changes it must pass every approval stage again. The earlier approvals stay in the history.</p>
                        <div class="form-group mb-0">
                            <label for="fp-reopen-reason">What must be changed? (required)</label>
                            <textarea class="form-control" id="fp-reopen-reason" name="Reason" rows="3" maxlength="<?php echo FP_APPROVAL_COMMENT_MAX; ?>"></textarea>
                            <div class="invalid-feedback" data-for="Reason"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning" id="fp-reopen-confirm">Reopen for changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php } ?>
    <?php if ($fpPaymentActions) {
        include('partials/payment_modals.php');
    } ?>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/modules/fp_common.js"></script>
    <script src="../js/modules/fp_approvals.js"></script>
    <?php if ($fpPaymentActions) { ?>
        <script src="../js/modules/fp_payments.js"></script>
    <?php } ?>
    <script>jQuery(function () { FP.activateNav(<?php echo json_encode($fpBack['group']); ?>, <?php echo json_encode($fpBack['key']); ?>); });</script>
</body>
</html>
