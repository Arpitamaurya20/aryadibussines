<?php
/** @var Hrticket $hr */
/** @var int $ticketId */
$history = $hr->getTicketHistory($ticketId);
?>
<?php if (empty($history)) { ?>
<p class="text-muted">No history recorded yet.</p>
<?php } else { ?>
<div class="hr-timeline">
    <?php foreach ($history as $h) { ?>
    <div class="hr-timeline-item">
        <div class="hr-comment-item">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <?= hr_ticket_history_badge($h['action_type'] ?? ''); ?>
                <?php if (!empty($h['status'])) { echo hr_ticket_status_badge($h['status']); } ?>
            </div>
            <div class="font-weight-bold"><?= htmlspecialchars($h['summary'] ?? ''); ?></div>
            <div class="text-muted small mt-1">
                <?= htmlspecialchars($h['created_by'] ?? 'System'); ?>
                · <?= htmlspecialchars($h['created_at'] ?? ''); ?>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
<?php } ?>
