<div class="modal fade hr-modal" id="hr_raise_ticket_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white"><i class="fal fa-headset mr-2"></i>Raise HR Ticket</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="hr_raise_ticket_form" novalidate>
                <div class="modal-body">
                    <div class="hr-payment-note">
                        <strong>Payment-related queries:</strong> Use category <em>Payment Related</em> and include salary month / reference. HR tickets are mandatory for payment concerns.
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="category" id="hr_ticket_category" class="form-control" required>
                                <option value="general">General</option>
                                <option value="payment_related">Payment Related</option>
                                <option value="benefits">Benefits</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Priority</label>
                            <select name="priority" id="hr_ticket_priority" class="form-control">
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-12 form-group" id="hr_payment_ref_wrap" style="display:none;">
                            <label>Payment reference / salary month <span class="text-danger">*</span></label>
                            <input type="text" name="payment_reference" id="hr_payment_reference" class="form-control" maxlength="128" placeholder="e.g. March 2026 salary, payslip ref">
                        </div>
                        <div class="col-12 form-group">
                            <label>Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" maxlength="255" required placeholder="Brief summary of your concern">
                        </div>
                        <div class="col-12 form-group">
                            <label>Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="5" required placeholder="Describe your request or concern in detail"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="hr_raise_ticket_btn"><i class="fal fa-paper-plane mr-1"></i> Submit ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
