<?php
/** @var array $ticket */
/** @var array $hr_reps */
/** @var bool $canManage */
$isClosed = (string) ($ticket['status'] ?? '') === 'closed';
?>
<?php if ($canManage && !$isClosed) { ?>
<form id="hr_assignment_form">
    <input type="hidden" name="ticket_id" value="<?= (int) ($ticket['id'] ?? 0); ?>">
    <div class="row">
        <div class="col-md-6 form-group">
            <label class="font-weight-bold">Assign to HR representative</label>
            <select name="assigned_to" id="hr_assigned_to" class="select2 form-control w-100">
                <option value="">— Unassigned —</option>
                <?php foreach ($hr_reps as $rep) {
                    $rid = (int) ($rep['ID'] ?? 0);
                    if ($rid < 1) continue;
                    $sel = (int) ($ticket['assigned_to'] ?? 0) === $rid ? ' selected' : '';
                    ?>
                <option value="<?= $rid; ?>"<?= $sel; ?>><?= htmlspecialchars($rep['Name'] ?? ''); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-6 form-group">
            <label class="font-weight-bold">Status</label>
            <select name="status" id="hr_ticket_status" class="form-control">
                <?php
                $statusLabels = [
                    'open' => 'Open',
                    'in_progress' => 'In Progress',
                    'pending_employee_response' => 'Pending Employee Response',
                    'on_hold' => 'On Hold',
                    'resolved' => 'Resolved',
                    'closed' => 'Closed',
                ];
                foreach (hr_ticket_status_options() as $st) {
                    $sel = (string) ($ticket['status'] ?? '') === $st ? ' selected' : '';
                    $label = $statusLabels[$st] ?? ucwords(str_replace('_', ' ', $st));
                    ?>
                <option value="<?= htmlspecialchars($st); ?>"<?= $sel; ?>><?= htmlspecialchars($label); ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary" id="hr_assignment_save_btn">
        <i class="fal fa-save mr-1"></i> Update assignment &amp; status
    </button>
</form>
<hr>
<?php } ?>

<div class="hr-info-grid">
    <div class="hr-info-item">
        <div class="hr-info-label">Current status</div>
        <div class="hr-info-value"><?= hr_ticket_status_badge($ticket['status'] ?? ''); ?></div>
    </div>
    <div class="hr-info-item">
        <div class="hr-info-label">Assigned HR</div>
        <div class="hr-info-value"><?= htmlspecialchars($ticket['assigned_name'] ?? '—'); ?></div>
    </div>
    <?php if (!empty($ticket['resolved_at'])) { ?>
    <div class="hr-info-item">
        <div class="hr-info-label">Resolved at</div>
        <div class="hr-info-value"><?= htmlspecialchars($ticket['resolved_at']); ?></div>
    </div>
    <?php } ?>
    <?php if (!empty($ticket['closed_at'])) { ?>
    <div class="hr-info-item">
        <div class="hr-info-label">Closed at</div>
        <div class="hr-info-value"><?= htmlspecialchars($ticket['closed_at']); ?></div>
    </div>
    <?php } ?>
</div>

<p class="text-muted small mt-3 mb-0">
    Workflow: <strong>Open</strong> → <strong>In Progress</strong> (on assign) →
    <strong>Pending Employee Response</strong> / <strong>On Hold</strong> → <strong>Resolved</strong> → <strong>Closed</strong>.
</p>
