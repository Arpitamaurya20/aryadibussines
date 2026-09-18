<?php
$assignedTo = (int) ($ticket['assigned_to'] ?? 0);
$currentStatus = $ticket['status'] ?? 'new';
$statusOptions = hc_ticket_status_options();
?>
<div class="hc-assign-grid">
    <div class="hc-assign-card">
        <div class="hc-assign-icon"><i class="fal fa-user-hard-hat"></i></div>
        <div class="hc-assign-label">Employee assigned</div>
        <div class="hc-assign-value">
            <?php if ($assignedTo < 1) { ?>
                Not assigned
            <?php } else { ?>
                <?= htmlspecialchars(strtoupper($ticket['assigned_name'] ?? '')); ?>
            <?php } ?>
        </div>
        <?php if ($assignedTo > 0 && !empty($ticket['assigned_phone'])) { ?>
            <div class="hc-assign-sub"><i class="fal fa-phone mr-1"></i><?= htmlspecialchars($ticket['assigned_phone']); ?></div>
        <?php } ?>
    </div>
    <div class="hc-assign-card">
        <div class="hc-assign-icon"><i class="fal fa-flag"></i></div>
        <div class="hc-assign-label">Current status</div>
        <div class="hc-assign-value"><?= hc_status_badge($currentStatus); ?></div>
    </div>
    <div class="hc-assign-card">
        <div class="hc-assign-icon"><i class="fal fa-calendar-alt"></i></div>
        <div class="hc-assign-label">Ticket due date</div>
        <div class="hc-assign-value"><?= !empty($ticket['due_date']) ? htmlspecialchars($ticket['due_date']) : 'Not set'; ?></div>
    </div>
    <div class="hc-assign-card">
        <div class="hc-assign-icon"><i class="fal fa-comment-alt-lines"></i></div>
        <div class="hc-assign-label">Remarks</div>
        <div class="hc-assign-value" style="font-size:0.85rem;font-weight:500;">
            <?php if (trim($ticket['remarks'] ?? '') === '') { ?>
                Not set
            <?php } else { ?>
                <?= nl2br(htmlspecialchars($ticket['remarks'])); ?>
            <?php } ?>
        </div>
    </div>
</div>
<div class="hc-assign-actions">
    <button type="button" class="btn hc-btn-accent" onclick="hcOpenAssignmentModal(); return false;">
        <i class="fal fa-edit mr-1"></i> Edit assignment
    </button>
</div>

<div class="modal fade hc-modal" id="hc_edit_assignment_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white"><i class="fal fa-user-cog mr-2"></i>Edit Assignment &amp; Status</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="hc_assignment_form">
                    <input type="hidden" name="ticket_id" value="<?= (int) ($ticket['id'] ?? 0); ?>">
                    <div class="hc-form-section">
                        <div class="hc-form-section-title"><i class="fal fa-sliders-h"></i> Assignment controls</div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="form-label">Assign technician <span class="text-danger">*</span></label>
                                <select class="select2 form-control w-100" id="hc_assign_employee" name="assigned_to">
                                    <option value="">Search technician</option>
                                    <?php foreach ($technician_list as $emp) {
                                        $eid = (int) ($emp['ID'] ?? 0);
                                        if ($eid < 1) {
                                            continue;
                                        }
                                        $sel = ($assignedTo === $eid) ? ' selected' : '';
                                        $label = trim($emp['Name'] ?? '');
                                        if (!empty($emp['ContactNumber'])) {
                                            $label .= ' — ' . $emp['ContactNumber'];
                                        }
                                        ?>
                                    <option value="<?= $eid; ?>"<?= $sel; ?>><?= htmlspecialchars($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="form-label">Change status</label>
                                <input type="hidden" name="is_reassign" id="hc_is_reassign" value="0">
                                <select name="status" id="hc_ticket_status" class="form-control select2 w-100">
                                    <?php if ($assignedTo > 0) { ?>
                                    <option value="__REASSIGN__">Reassign only (keep status)</option>
                                    <?php } ?>
                                    <?php foreach ($statusOptions as $st) {
                                        $sel = ($currentStatus === $st) ? ' selected' : '';
                                        $label = ucwords(str_replace('_', ' ', $st));
                                        ?>
                                    <option value="<?= htmlspecialchars($st); ?>"<?= $sel; ?>><?= htmlspecialchars($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="form-label">Ticket due date</label>
                                <input type="text" class="form-control" name="due_date" id="hc_due_date"
                                    value="<?= htmlspecialchars($ticket['due_date'] ?? ''); ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div class="col-12 form-group mb-0">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" id="hc_assignment_remarks" class="form-control" rows="3" placeholder="Notes for technician or internal team"><?= htmlspecialchars($ticket['remarks'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-primary px-5" id="hc_assignment_save_btn"
                            onclick="hcSaveAssignment(); return false;">
                            <span class="hc-btn-text"><i class="fal fa-check mr-1"></i> Save &amp; change</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
