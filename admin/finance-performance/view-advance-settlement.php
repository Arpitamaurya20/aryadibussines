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
$fpPage = fp_settlement_page($fpDb, $fpActor, $fpId);
if (!$fpPage) {
    http_response_code(404);
}
$fpRequest = $fpPage ? $fpPage['request'] : null;
$fpSum = $fpPage ? $fpPage['summary'] : null;
$fpClaimStatuses = fp_settlement_statuses();
$fpClaimClass = array('draft' => 'secondary', 'submitted' => 'info', 'branch_reviewed' => 'primary', 'returned' => 'warning', 'verified' => 'success', 'cancelled' => 'dark');
$fpDecisions = fp_settlement_line_decisions();
$fpDecisionClass = array('pending' => 'light', 'accepted' => 'success', 'partial' => 'warning', 'rejected' => 'danger');
$fpTxnTypes = fp_settlement_txn_types();
$fpTxnClass = array('completed' => 'success', 'failed' => 'danger', 'reversed' => 'warning');
$fpAccountHint = $fpPage && $fpPage['can_finance'] ? fp_payment_employee_account_hint($fpDb, $fpRequest['EmployeeID']) : '';
$fpHasTxnActions = $fpPage && $fpPage['can_finance'];

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
    <title><?php echo $fpRequest ? fp_h($fpRequest['ExpenseNumber']) . ' settlement' : 'Advance not found'; ?> - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        .fp-dl dt { font-weight: 600; color: #666; font-size: 12px; }
        .fp-dl dd { margin-bottom: 10px; }
        .fp-lines th, .fp-lines td { font-size: 12.5px; vertical-align: top; }
        .fp-lines .form-control { font-size: 12px; padding: 3px 6px; height: 28px; }
        .fp-figure .small { color: #6c757d; }
        .fp-check { font-size: 11.5px; }
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
                        <li class="breadcrumb-item"><a href="view-settlements.php">Settlements</a></li>
                        <li class="breadcrumb-item active"><?php echo $fpRequest ? fp_h($fpRequest['ExpenseNumber']) : 'Not found'; ?></li>
                    </ol>

                    <?php if (!$fpPage) { ?>
                        <div class="alert alert-warning">This advance was not found, is not an approved cash advance, or you do not have access to it.</div>
                        <a href="view-settlements.php" class="btn btn-secondary">Back to Settlements</a>
                    <?php } else { ?>
                    <div id="fp-settlement-page" data-request-id="<?php echo (int) $fpRequest['ID']; ?>" data-row-version="<?php echo (int) $fpRequest['RowVersion']; ?>"
                         data-number="<?php echo fp_h($fpRequest['ExpenseNumber']); ?>" data-balance="<?php echo (int) $fpSum['balance']; ?>"
                         data-first-paid="<?php echo fp_h($fpSum['first_paid']); ?>" data-today="<?php echo fp_h(date('Y-m-d')); ?>"
                         data-reference-optional="<?php echo fp_h(implode(',', fp_payment_reference_optional_modes())); ?>">

                        <div class="panel">
                            <div class="panel-hdr">
                                <h2>Advance <?php echo fp_h($fpRequest['ExpenseNumber']); ?>&nbsp;<span class="fw-300">settlement</span></h2>
                                <div class="panel-toolbar">
                                    <span class="badge badge-<?php echo $fpRequest['SettlementStatus'] === 'settled' ? 'success' : 'secondary'; ?> mr-2"><?php echo fp_h(fp_settlement_request_statuses()[$fpRequest['SettlementStatus']] ?? $fpRequest['SettlementStatus']); ?></span>
                                    <?php if ($fpPage['overdue']) { ?><span class="badge badge-danger mr-3">Overdue</span><?php } ?>
                                </div>
                            </div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <dl class="row fp-dl mb-0">
                                        <div class="col-md-3"><dt>Advance holder</dt><dd><?php echo fp_h($fpRequest['EmployeeName']); ?></dd></div>
                                        <div class="col-md-3"><dt>Branch office</dt><dd><?php echo fp_h($fpRequest['OfficeName'] . ' (' . $fpRequest['OfficeCode'] . ')'); ?></dd></div>
                                        <div class="col-md-3"><dt>Approved amount</dt><dd><?php echo fp_h(fp_money($fpRequest['TotalAmount'])); ?></dd></div>
                                        <div class="col-md-3"><dt>First paid on</dt><dd><?php echo fp_h(fp_date($fpSum['first_paid'])); ?><?php echo $fpSum['first_paid'] !== '' && $fpRequest['SettlementStatus'] !== 'settled' ? ' <span class="text-muted">(' . (int) $fpSum['days'] . ' days ago; settle within ' . (int) fp_settlement_due_days() . ')</span>' : ''; ?></dd></div>
                                        <div class="col-12"><dt>Purpose</dt><dd><?php echo nl2br(fp_h($fpRequest['Purpose'])); ?></dd></div>
                                    </dl>
                                    <div class="row text-center border rounded py-2 mx-0 mb-2 fp-figure">
                                        <div class="col-6 col-md"><div class="small">Advance paid out</div><strong><?php echo fp_h(fp_payment_money($fpSum['paid'])); ?></strong></div>
                                        <div class="col-6 col-md"><div class="small">Expenses accepted</div><strong><?php echo fp_h(fp_payment_money($fpSum['accepted'])); ?></strong></div>
                                        <div class="col-6 col-md"><div class="small">Cash returned by employee</div><strong><?php echo fp_h(fp_payment_money($fpSum['refund'])); ?></strong></div>
                                        <div class="col-6 col-md"><div class="small">Extra paid to employee</div><strong><?php echo fp_h(fp_payment_money($fpSum['additional'])); ?></strong></div>
                                        <div class="col-12 col-md"><div class="small">Position</div><strong class="<?php echo $fpSum['balance'] === 0 ? 'text-success' : 'text-danger'; ?>"><?php echo fp_h(fp_settlement_balance_text($fpSum['balance'])); ?></strong></div>
                                    </div>
                                    <p class="small text-muted mb-2">
                                        Position = paid out + extra paid - expenses accepted - cash returned. The advance itself is not an expense; only the expenses Finance accepts count against budgets.
                                        The advance is closed only when every claim is decided and the position is zero.
                                    </p>
                                    <?php if ($fpPage['is_finance'] && $fpRequest['SettlementStatus'] !== 'settled' && !$fpPage['can_close'] && $fpPage['close_block']) { ?>
                                        <div class="small text-muted mb-2"><i class="fa-solid fa-circle-info mr-1"></i>Cannot close yet: <?php echo fp_h($fpPage['close_block'][1]); ?></div>
                                    <?php } ?>
                                    <div class="d-flex flex-wrap">
                                        <a href="view-expense-request.php?id=<?php echo (int) $fpRequest['ID']; ?>" class="btn btn-sm btn-outline-secondary mr-2 mb-1"><i class="fa-solid fa-file-lines mr-1"></i>Advance request and timeline</a>
                                        <?php if ($fpPage['can_start']) { ?>
                                            <a href="view-settlement-form.php?request=<?php echo (int) $fpRequest['ID']; ?>" class="btn btn-sm btn-primary mr-2 mb-1"><i class="fa-solid fa-plus mr-1"></i>Start a settlement</a>
                                        <?php } ?>
                                        <?php if ($fpPage['can_finance'] && $fpSum['balance'] > 0) { ?>
                                            <button type="button" class="btn btn-sm btn-success mr-2 mb-1 fp-record-txn" data-type="refund"><i class="fa-solid fa-arrow-down mr-1"></i>Record cash returned</button>
                                        <?php } ?>
                                        <?php if ($fpPage['can_finance'] && $fpSum['balance'] < 0) { ?>
                                            <button type="button" class="btn btn-sm btn-success mr-2 mb-1 fp-record-txn" data-type="additional"><i class="fa-solid fa-arrow-up mr-1"></i>Record extra payment</button>
                                        <?php } ?>
                                        <?php if ($fpPage['can_close']) { ?>
                                            <button type="button" class="btn btn-sm btn-dark mb-1" id="fp-close-advance"><i class="fa-solid fa-lock mr-1"></i>Close advance</button>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!$fpPage['claims']) { ?>
                            <div class="alert alert-light border">
                                No settlement claims yet.
                                <?php echo $fpPage['is_holder'] ? 'Use "Start a settlement" to list what you spent from this advance and attach the bills.' : 'The advance holder has not submitted a settlement.'; ?>
                            </div>
                        <?php } ?>

                        <?php foreach ($fpPage['claims'] as $entry) {
                            $claim = $entry['claim'];
                            $verifying = $entry['can_verify'];
                            $showDecision = $claim['Status'] === 'verified';
                        ?>
                            <div class="panel fp-claim" data-id="<?php echo (int) $claim['ID']; ?>" data-row-version="<?php echo (int) $claim['RowVersion']; ?>" data-number="<?php echo fp_h($claim['SettlementNumber']); ?>">
                                <div class="panel-hdr">
                                    <h2><?php echo fp_h($claim['SettlementNumber']); ?>&nbsp;<span class="fw-300">claimed <?php echo fp_h(fp_money($claim['ClaimedAmount'])); ?><?php echo $showDecision ? ', accepted ' . fp_h(fp_money($claim['AcceptedAmount'])) : ''; ?></span></h2>
                                    <div class="panel-toolbar">
                                        <span class="badge badge-<?php echo fp_h($fpClaimClass[$claim['Status']] ?? 'light'); ?> mr-3"><?php echo fp_h($fpClaimStatuses[$claim['Status']] ?? $claim['Status']); ?></span>
                                    </div>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="small text-muted mb-2">
                                            Created <?php echo fp_h(fp_datetime($claim['CreatedAt'])); ?>
                                            <?php echo $claim['SubmittedAt'] ? ' | Submitted ' . fp_h(fp_datetime($claim['SubmittedAt'])) : ''; ?>
                                            <?php echo $claim['ReviewedAt'] && $claim['ReviewedByName'] !== '' ? ' | Branch review: ' . fp_h($claim['ReviewedByName']) . ', ' . fp_h(fp_datetime($claim['ReviewedAt'])) : ''; ?>
                                            <?php echo $claim['VerifiedAt'] ? ' | Verified: ' . fp_h($claim['VerifiedByName']) . ', ' . fp_h(fp_datetime($claim['VerifiedAt'])) : ''; ?>
                                            <?php echo $claim['CancelledAt'] ? ' | Cancelled ' . fp_h(fp_datetime($claim['CancelledAt'])) : ''; ?>
                                        </div>
                                        <?php if ($claim['Notes'] !== '') { ?><p class="mb-2"><strong>Notes:</strong> <?php echo nl2br(fp_h($claim['Notes'])); ?></p><?php } ?>
                                        <?php if ($claim['Status'] === 'returned' && $claim['ReturnReason'] !== '') { ?>
                                            <div class="alert alert-warning py-2"><strong>Returned<?php echo $claim['ReturnedByName'] !== '' ? ' by ' . fp_h($claim['ReturnedByName']) : ''; ?>:</strong> <?php echo fp_h($claim['ReturnReason']); ?></div>
                                        <?php } ?>
                                        <?php if ($claim['ReviewComments'] !== '') { ?><p class="small mb-1"><strong>Branch comment:</strong> <?php echo fp_h($claim['ReviewComments']); ?></p><?php } ?>
                                        <?php if ($claim['VerifyComments'] !== '') { ?><p class="small mb-1"><strong>Finance comment:</strong> <?php echo fp_h($claim['VerifyComments']); ?></p><?php } ?>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-2 fp-lines">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th><th>Category</th><th>Description</th><th>Date</th><th>Vendor / invoice</th>
                                                        <th class="text-right">Amount</th>
                                                        <?php if ($showDecision || $verifying) { ?><th>Decision</th><th class="text-right">Accepted</th><th>Reason</th><?php } ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($entry['lines'] as $index => $line) {
                                                        $warnings = $entry['checks'][(int) $line['ID']] ?? array();
                                                    ?>
                                                        <tr data-line-id="<?php echo (int) $line['ID']; ?>" data-amount="<?php echo fp_h($line['Amount']); ?>">
                                                            <td><?php echo (int) $line['LineNo']; ?></td>
                                                            <td><?php echo fp_h(fp_settlement_line_category($line)); ?></td>
                                                            <td>
                                                                <?php echo fp_h($line['Description']); ?>
                                                                <?php foreach ($warnings as $warning) { ?><div class="text-danger fp-check"><i class="fa-solid fa-triangle-exclamation mr-1"></i><?php echo fp_h($warning); ?></div><?php } ?>
                                                            </td>
                                                            <td class="text-nowrap"><?php echo fp_h(fp_date($line['ExpenseDate'])); ?></td>
                                                            <td>
                                                                <?php echo fp_h($line['VendorName'] !== '' ? $line['VendorName'] : '-'); ?>
                                                                <?php echo $line['VendorGSTIN'] !== '' ? '<div class="small text-muted">GSTIN ' . fp_h($line['VendorGSTIN']) . '</div>' : ''; ?>
                                                                <?php echo $line['InvoiceNumber'] !== '' ? '<div class="small text-muted">Inv. ' . fp_h($line['InvoiceNumber']) . ($line['InvoiceDate'] ? ', ' . fp_h(fp_date($line['InvoiceDate'])) : '') . '</div>' : ''; ?>
                                                            </td>
                                                            <td class="text-right text-nowrap"><?php echo fp_h(fp_money($line['Amount'])); ?><?php echo (float) $line['TaxAmount'] > 0 ? '<div class="small text-muted">tax ' . fp_h(fp_money($line['TaxAmount'])) . '</div>' : ''; ?></td>
                                                            <?php if ($verifying) { ?>
                                                                <td style="min-width:150px">
                                                                    <select class="form-control fp-line-decision" name="lines.<?php echo (int) $index; ?>.Decision">
                                                                        <option value="">Choose...</option>
                                                                        <option value="accepted">Accept in full</option>
                                                                        <option value="partial">Accept part</option>
                                                                        <option value="rejected">Reject</option>
                                                                    </select>
                                                                </td>
                                                                <td style="min-width:110px"><input type="text" inputmode="decimal" class="form-control text-right fp-line-accepted" name="lines.<?php echo (int) $index; ?>.AcceptedAmount" disabled></td>
                                                                <td style="min-width:200px"><input type="text" class="form-control fp-line-reason" name="lines.<?php echo (int) $index; ?>.Reason" maxlength="500" placeholder="Needed when not accepted in full" disabled></td>
                                                            <?php } elseif ($showDecision) { ?>
                                                                <td><span class="badge badge-<?php echo fp_h($fpDecisionClass[$line['Decision']] ?? 'light'); ?>"><?php echo fp_h($fpDecisions[$line['Decision']] ?? $line['Decision']); ?></span></td>
                                                                <td class="text-right text-nowrap"><?php echo fp_h(fp_money($line['AcceptedAmount'])); ?></td>
                                                                <td class="small"><?php echo fp_h($line['DecisionReason']); ?></td>
                                                            <?php } ?>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <?php if ($entry['see_docs']) { ?>
                                            <div class="mb-2">
                                                <strong class="small">Bills and receipts:</strong>
                                                <?php if (!$entry['documents']) { ?><span class="small text-muted">none attached</span><?php } ?>
                                                <ul class="small mb-0 pl-3">
                                                    <?php foreach ($entry['documents'] as $doc) { $url = 'action/download_settlement_document.php?ID=' . (int) $doc['ID']; ?>
                                                        <li>
                                                            <?php echo $doc['LineNo'] ? 'Line ' . (int) $doc['LineNo'] : 'Whole settlement'; ?> - <?php echo fp_h(fp_document_type_label($doc['DocumentType'])); ?>:
                                                            <a href="<?php echo fp_h($url); ?>" target="_blank" rel="noopener"><?php echo fp_h($doc['OriginalName']); ?></a>
                                                            <a href="<?php echo fp_h($url); ?>&amp;download=1" title="Download"><i class="fa-solid fa-download"></i></a>
                                                            <?php echo $doc['Notes'] !== '' ? '<span class="text-muted">(' . fp_h($doc['Notes']) . ')</span>' : ''; ?>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                                <?php if ($entry['removed']) { ?>
                                                    <div class="small text-muted mt-1">Removed documents (kept for audit):
                                                        <?php foreach ($entry['removed'] as $doc) { ?>
                                                            <a href="action/download_settlement_document.php?ID=<?php echo (int) $doc['ID']; ?>" target="_blank" rel="noopener" class="text-muted"><s><?php echo fp_h($doc['OriginalName']); ?></s></a>
                                                        <?php } ?>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>

                                        <?php if ($entry['can_edit']) { ?>
                                            <a href="view-settlement-form.php?id=<?php echo (int) $claim['ID']; ?>" class="btn btn-sm btn-primary mr-2"><i class="fa-solid fa-pen mr-1"></i><?php echo $claim['Status'] === 'returned' ? 'Correct and resubmit' : 'Continue editing'; ?></a>
                                            <button type="button" class="btn btn-sm btn-outline-danger fp-cancel-claim">Cancel settlement</button>
                                        <?php } ?>

                                        <?php if ($entry['can_review']) { ?>
                                            <form class="fp-review-form border-top pt-2 mt-2" onsubmit="return false;" novalidate>
                                                <label class="mb-1"><strong>Branch review</strong> - check that the expenses are real and for branch work, and that the bills match.</label>
                                                <textarea class="form-control mb-1" name="Comments" rows="2" maxlength="1000" placeholder="Comment (required when returning)"></textarea>
                                                <div class="invalid-feedback" data-for="Comments"></div>
                                                <button type="button" class="btn btn-sm btn-success mr-2 fp-review" data-decision="confirm"><i class="fa-solid fa-check mr-1"></i>Confirm and send to Finance</button>
                                                <button type="button" class="btn btn-sm btn-outline-warning fp-review" data-decision="return"><i class="fa-solid fa-rotate-left mr-1"></i>Return to employee</button>
                                            </form>
                                        <?php } ?>

                                        <?php if ($verifying) { ?>
                                            <form class="fp-verify-form border-top pt-2 mt-2" onsubmit="return false;" novalidate>
                                                <div class="alert alert-danger py-2 fp-verify-errors" style="display:none"></div>
                                                <label class="mb-1"><strong>Finance verification</strong> - decide every line above. Accepted amounts count as expenses; the rest must be returned by the employee.</label>
                                                <div class="mb-1">Accepted in this claim: <strong class="fp-verify-total">INR 0.00</strong> of <?php echo fp_h(fp_money($claim['ClaimedAmount'])); ?></div>
                                                <textarea class="form-control mb-1" name="Comments" rows="2" maxlength="1000" placeholder="Comment (required when returning)"></textarea>
                                                <div class="invalid-feedback" data-for="Comments"></div>
                                                <button type="button" class="btn btn-sm btn-outline-success mr-2 fp-accept-all">Accept all lines in full</button>
                                                <button type="button" class="btn btn-sm btn-success mr-2 fp-verify" data-decision="verify"><i class="fa-solid fa-check-double mr-1"></i>Verify</button>
                                                <button type="button" class="btn btn-sm btn-outline-warning fp-verify" data-decision="return"><i class="fa-solid fa-rotate-left mr-1"></i>Return to employee</button>
                                            </form>
                                        <?php } elseif ($claim['Status'] === 'branch_reviewed' && $fpPage['is_finance'] && !$fpPage['is_holder']) { ?>
                                            <div class="small text-muted border-top pt-2 mt-2"><?php echo fp_h(fp_settlement_verify_block($fpActor, $fpRequest, $claim)[1] ?? ''); ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <div class="panel">
                            <div class="panel-hdr"><h2>Cash returned and extra payments</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <?php if (!$fpPage['transactions']) { ?>
                                        <p class="text-muted mb-0">None recorded. Finance records cash the employee returns, or the extra amount paid when accepted expenses are more than the advance.</p>
                                    <?php } else { ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="thead-light"><tr><th>Entry no.</th><th>Type</th><th>Date</th><th class="text-right">Amount</th><th>Mode / reference</th><th>Status</th><th>Recorded</th><?php echo $fpHasTxnActions ? '<th></th>' : ''; ?></tr></thead>
                                                <tbody>
                                                    <?php foreach ($fpPage['transactions'] as $txn) { ?>
                                                        <tr>
                                                            <td class="text-nowrap"><?php echo fp_h($txn['TxnNumber']); ?></td>
                                                            <td><?php echo fp_h($fpTxnTypes[$txn['TxnType']] ?? $txn['TxnType']); ?><?php echo $txn['Remarks'] !== '' ? '<div class="small text-muted">' . fp_h($txn['Remarks']) . '</div>' : ''; ?></td>
                                                            <td class="text-nowrap"><?php echo fp_h(fp_date($txn['TxnDate'])); ?></td>
                                                            <td class="text-right text-nowrap"><?php echo $txn['Status'] === 'completed' ? fp_h(fp_money($txn['Amount'])) : '<s>' . fp_h(fp_money($txn['Amount'])) . '</s>'; ?></td>
                                                            <td><?php echo fp_h(fp_payment_modes()[$txn['TxnMode']] ?? $txn['TxnMode']); ?><div class="small text-muted"><?php echo fp_h($txn['Reference'] !== '' ? $txn['Reference'] : '-'); ?><?php echo (int) $txn['SharedReference'] === 1 ? ' <span class="badge badge-light">shared transfer</span>' : ''; ?><?php echo $fpPage['is_finance'] && $txn['AccountHint'] !== '' ? '<br>' . fp_h($txn['AccountHint']) : ''; ?></div></td>
                                                            <td>
                                                                <span class="badge badge-<?php echo fp_h($fpTxnClass[$txn['Status']] ?? 'light'); ?>"><?php echo fp_h(fp_payment_statuses()[$txn['Status']] ?? $txn['Status']); ?></span>
                                                                <?php if ($txn['Status'] !== 'completed') { ?>
                                                                    <div class="small text-muted"><?php echo fp_h($txn['StatusReason']); ?><?php echo $txn['StatusReference'] !== '' ? ' (ref. ' . fp_h($txn['StatusReference']) . ')' : ''; ?></div>
                                                                    <div class="small text-muted"><?php echo fp_h(fp_datetime($txn['StatusChangedAt'])); ?><?php echo $txn['StatusChangedByName'] !== '' ? ', ' . fp_h($txn['StatusChangedByName']) : ''; ?></div>
                                                                <?php } ?>
                                                            </td>
                                                            <td class="text-nowrap small"><?php echo fp_h(fp_datetime($txn['RecordedAt'])); ?><div class="text-muted"><?php echo fp_h($txn['RecordedByName']); ?></div></td>
                                                            <?php if ($fpHasTxnActions) { ?>
                                                                <td class="text-nowrap">
                                                                    <?php if ($txn['Status'] === 'completed') { ?>
                                                                        <button type="button" class="btn btn-xs btn-outline-danger fp-txn-status" data-outcome="failed" data-id="<?php echo (int) $txn['ID']; ?>" data-number="<?php echo fp_h($txn['TxnNumber']); ?>">Failed</button>
                                                                        <button type="button" class="btn btn-xs btn-outline-warning fp-txn-status" data-outcome="reversed" data-id="<?php echo (int) $txn['ID']; ?>" data-number="<?php echo fp_h($txn['TxnNumber']); ?>">Reverse</button>
                                                                    <?php } ?>
                                                                </td>
                                                            <?php } ?>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                    <div class="alert alert-secondary small mt-3 mb-0">
                                        Verified claims and recorded entries cannot be edited or deleted. A wrong entry is marked failed or reversed with a reason and a new one is recorded.
                                        After the advance is closed nothing can be changed. Every step appears in the advance request's timeline.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a href="view-settlements.php" class="btn btn-secondary mb-4">Back to Settlements</a>
                    </div>

                    <?php if ($fpHasTxnActions) { ?>
                        <div class="modal fade" id="fp-txn-modal" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form class="modal-content" id="fp-txn-form" novalidate onsubmit="return false;">
                                    <div class="modal-header modal_header">
                                        <h5 class="modal-title" id="fp-txn-title">Record</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="small mb-2" id="fp-txn-help"></p>
                                        <?php if ($fpAccountHint !== '') { ?><p class="small text-muted mb-2" id="fp-txn-account">Employee bank account on file: <?php echo fp_h($fpAccountHint); ?></p><?php } ?>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="fp-txn-amount">Amount (INR) <span class="text-danger">*</span></label>
                                                <input type="text" inputmode="decimal" class="form-control text-right" name="Amount" id="fp-txn-amount">
                                                <div class="invalid-feedback" data-for="Amount"></div>
                                                <small class="form-text text-muted" id="fp-txn-amount-help"></small>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="fp-txn-date">Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="TxnDate" id="fp-txn-date">
                                                <div class="invalid-feedback" data-for="TxnDate"></div>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="fp-txn-mode">Mode <span class="text-danger">*</span></label>
                                                <select class="form-control" name="TxnMode" id="fp-txn-mode">
                                                    <option value="">Choose...</option>
                                                    <?php foreach (fp_payment_modes() as $key => $label) { ?>
                                                        <option value="<?php echo fp_h($key); ?>"><?php echo fp_h($label); ?></option>
                                                    <?php } ?>
                                                </select>
                                                <div class="invalid-feedback" data-for="TxnMode"></div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="fp-txn-reference" id="fp-txn-reference-label">Transaction reference</label>
                                                <input type="text" class="form-control" name="Reference" id="fp-txn-reference" maxlength="100">
                                                <div class="invalid-feedback" data-for="Reference"></div>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning small d-none" id="fp-txn-shared">
                                            This reference is already used on another advance. Tick below only if one transfer really covered several advances.
                                            <div class="custom-control custom-checkbox mt-1">
                                                <input type="checkbox" class="custom-control-input" id="fp-txn-shared-confirm">
                                                <label class="custom-control-label" for="fp-txn-shared-confirm">Yes, this is one shared transfer</label>
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="fp-txn-remarks">Remarks</label>
                                            <textarea class="form-control" name="Remarks" id="fp-txn-remarks" rows="2" maxlength="500"></textarea>
                                            <div class="invalid-feedback" data-for="Remarks"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success" id="fp-txn-save">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="modal fade" id="fp-txn-status-modal" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form class="modal-content" id="fp-txn-status-form" novalidate onsubmit="return false;">
                                    <div class="modal-header modal_header">
                                        <h5 class="modal-title" id="fp-tstatus-title">Change entry</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="small mb-2" id="fp-tstatus-text"></p>
                                        <div class="form-group">
                                            <label for="fp-tstatus-reason">Reason <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="Reason" id="fp-tstatus-reason" rows="2" maxlength="500"></textarea>
                                            <div class="invalid-feedback" data-for="Reason"></div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="fp-tstatus-reference">Bank return reference</label>
                                            <input type="text" class="form-control" name="StatusReference" id="fp-tstatus-reference" maxlength="100">
                                            <div class="invalid-feedback" data-for="StatusReference"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger" id="fp-tstatus-save">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="modal fade" id="fp-reason-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="fp-reason-title">Confirm</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p id="fp-reason-text" class="mb-2"></p>
                                    <label for="fp-reason-input" id="fp-reason-label">Reason</label>
                                    <textarea class="form-control" id="fp-reason-input" rows="3" maxlength="500"></textarea>
                                    <div class="invalid-feedback">Please give a reason (at least 5 characters).</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Back</button>
                                    <button type="button" class="btn btn-danger" id="fp-reason-confirm">Confirm</button>
                                </div>
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
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/modules/fp_common.js"></script>
    <?php if ($fpPage) { ?><script src="../js/modules/fp_advance_settlement.js"></script><?php } ?>
</body>
</html>
