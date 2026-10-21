<?php
if (!function_exists('fp_payment_modes')) {
    http_response_code(404);
    exit;
}
?>
<div class="modal fade" id="fp-payment-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form class="modal-content" id="fp-payment-form" onsubmit="return false;" novalidate>
            <div class="modal-header">
                <h5 class="modal-title">Record payment <span class="fw-300" id="fp-pay-title"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-3" id="fp-pay-summary"></div>
                <p class="small text-muted">Record a payment only after the money has actually been sent. The request becomes paid only through payment records.</p>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="fp-pay-amount">Amount (INR)</label>
                        <input type="text" inputmode="decimal" class="form-control" id="fp-pay-amount" name="Amount" maxlength="14" autocomplete="off">
                        <div class="invalid-feedback" data-for="Amount"></div>
                        <small class="form-text text-muted" id="fp-pay-amount-help"></small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="fp-pay-date">Payment date</label>
                        <input type="date" class="form-control" id="fp-pay-date" name="PaymentDate">
                        <div class="invalid-feedback" data-for="PaymentDate"></div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="fp-pay-mode">Mode</label>
                        <select class="form-control" id="fp-pay-mode" name="PaymentMode">
                            <option value="">Choose</option>
                            <?php foreach (fp_payment_modes() as $key => $label) { ?>
                                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                        <div class="invalid-feedback" data-for="PaymentMode"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="fp-pay-reference" id="fp-pay-reference-label">Transaction reference (UTR / UPI / cheque no.)</label>
                    <input type="text" class="form-control" id="fp-pay-reference" name="Reference" maxlength="100" autocomplete="off">
                    <div class="invalid-feedback" data-for="Reference"></div>
                </div>
                <div class="form-group">
                    <label class="d-block">Paid to</label>
                    <div id="fp-pay-payee-types"></div>
                    <div class="invalid-feedback d-block" data-for="PayeeType"></div>
                    <div class="small text-muted mt-1" id="fp-pay-employee-hint"></div>
                    <div class="mt-2 d-none" id="fp-pay-vendor-box">
                        <input type="text" class="form-control" id="fp-pay-vendor" name="PayeeName" maxlength="150" list="fp-pay-vendors" placeholder="Vendor name">
                        <datalist id="fp-pay-vendors"></datalist>
                        <div class="invalid-feedback" data-for="PayeeName"></div>
                        <div class="small text-muted mt-1" id="fp-pay-vendor-hint"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="fp-pay-remarks">Remarks (optional)</label>
                    <textarea class="form-control" id="fp-pay-remarks" name="Remarks" rows="2" maxlength="500"></textarea>
                    <div class="invalid-feedback" data-for="Remarks"></div>
                </div>
                <div class="alert alert-warning d-none mb-0" id="fp-pay-shared">
                    <div class="mb-2">This reference is already recorded on:</div>
                    <ul class="mb-2" id="fp-pay-shared-list"></ul>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="fp-pay-shared-confirm" name="ConfirmSharedReference" value="1">
                        <label class="custom-control-label" for="fp-pay-shared-confirm">Yes, one bank transfer paid all of these requests.</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="fp-pay-save">Save payment</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="fp-payment-status-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="fp-payment-status-form" onsubmit="return false;" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="fp-pstatus-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p id="fp-pstatus-text"></p>
                <div class="form-group">
                    <label for="fp-pstatus-reason">Reason (required)</label>
                    <textarea class="form-control" id="fp-pstatus-reason" name="Reason" rows="3" maxlength="<?php echo FP_PAYMENT_REASON_MAX; ?>"></textarea>
                    <div class="invalid-feedback" data-for="Reason"></div>
                </div>
                <div class="form-group mb-0">
                    <label for="fp-pstatus-reference">Bank return / reversal reference (optional)</label>
                    <input type="text" class="form-control" id="fp-pstatus-reference" name="StatusReference" maxlength="100" autocomplete="off">
                    <div class="invalid-feedback" data-for="StatusReference"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="fp-pstatus-save"></button>
            </div>
        </form>
    </div>
</div>
