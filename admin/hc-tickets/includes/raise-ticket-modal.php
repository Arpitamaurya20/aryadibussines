<div class="modal fade hc-modal" id="hc_raise_ticket_modal" tabindex="-1" role="dialog" aria-labelledby="hcRaiseTicketLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white" id="hcRaiseTicketLabel">
                    <i class="fal fa-ticket-alt mr-2"></i>Raise Home Care Ticket
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="hc_raise_ticket_form" novalidate>
                <div class="modal-body">
                    <p class="hc-required-note mb-0">Fields marked with <span class="text-danger">*</span> are required.</p>

                    <div class="hc-form-section">
                        <div class="hc-form-section-title"><i class="fal fa-user"></i> Who is asking</div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" maxlength="255" required placeholder="Full name">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" maxlength="32" placeholder="Contact number">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" maxlength="255" placeholder="email@example.com">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" maxlength="128" placeholder="City">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">State</label>
                                <select name="state_id" id="hc_state_id" class="select2 form-control w-100">
                                    <option value="">Search &amp; select state</option>
                                    <?php if (!empty($state_list)) {
                                        foreach ($state_list as $stateRow) {
                                            $sid = (int) ($stateRow['ID'] ?? 0);
                                            if ($sid < 1) {
                                                continue;
                                            }
                                            ?>
                                    <option value="<?= $sid; ?>"><?= htmlspecialchars($stateRow['StateName'] ?? ''); ?></option>
                                    <?php }
                                    } ?>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Postal code</label>
                                <input type="text" name="postal_code" class="form-control" maxlength="16" placeholder="PIN / ZIP">
                            </div>
                            <div class="col-12 form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Street address"></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" maxlength="255" placeholder="Area / locality">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Landmark</label>
                                <input type="text" name="landmark" class="form-control" maxlength="255" placeholder="Nearby landmark">
                            </div>
                        </div>
                    </div>

                    <div class="hc-form-section">
                        <div class="hc-form-section-title"><i class="fal fa-clock"></i> Timeline &amp; budget</div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label">Timeline <span class="text-danger">*</span></label>
                                <select name="timeline_type" id="hc_timeline_type" class="form-control" required>
                                    <option value="immediate">Immediate</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group" id="hc_timeline_custom_wrap" style="display:none;">
                                <label class="form-label">Custom date &amp; time <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="timeline_custom_at" id="hc_timeline_custom_at" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Budget</label>
                                <input type="number" name="budget" class="form-control" min="0" step="0.01" placeholder="Optional amount">
                            </div>
                        </div>
                    </div>

                    <div class="hc-form-section">
                        <div class="hc-form-section-title"><i class="fal fa-briefcase"></i> Purpose</div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label">Purpose <span class="text-danger">*</span></label>
                                <select name="purpose" id="hc_purpose" class="form-control" required>
                                    <option value="">Select purpose</option>
                                    <option value="rent">Rent</option>
                                    <option value="self">Self</option>
                                </select>
                            </div>
                        </div>
                        <div id="hc_purpose_contact_wrap" style="display:none;">
                            <p class="small text-muted mb-2" id="hc_purpose_hint"></p>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="form-label">Contact name <span class="text-danger">*</span></label>
                                    <input type="text" name="purpose_name" id="hc_purpose_name" class="form-control" maxlength="255">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="form-label">Contact phone <span class="text-danger">*</span></label>
                                    <input type="text" name="purpose_phone" id="hc_purpose_phone" class="form-control" maxlength="32">
                                </div>
                                <div class="col-12 form-group mb-0">
                                    <label class="form-label">Contact address <span class="text-danger">*</span></label>
                                    <textarea name="purpose_address" id="hc_purpose_address" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hc-form-section mb-0">
                        <div class="hc-form-section-title"><i class="fal fa-align-left"></i> Service description</div>
                        <div class="form-group mb-0">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="4" required placeholder="Describe the service need in detail"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default hc-btn-ghost" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="hc_raise_ticket_btn">
                        <span class="hc-btn-text"><i class="fal fa-paper-plane mr-1"></i> Raise ticket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
