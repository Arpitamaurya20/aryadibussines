<?php
/** @var array $ticket */
/** @var Hrticket $hr */
?>
<div class="hr-info-grid">
    <div class="hr-info-item">
        <div class="hr-info-label">Ticket code</div>
        <div class="hr-info-value"><?= htmlspecialchars($ticket['ticket_code'] ?? ''); ?></div>
    </div>
    <div class="hr-info-item">
        <div class="hr-info-label">Category</div>
        <div class="hr-info-value"><?= hr_ticket_category_badge($ticket['category'] ?? ''); ?></div>
    </div>
    <div class="hr-info-item">
        <div class="hr-info-label">Status</div>
        <div class="hr-info-value"><?= hr_ticket_status_badge($ticket['status'] ?? ''); ?></div>
    </div>
    <div class="hr-info-item">
        <div class="hr-info-label">Priority</div>
        <div class="hr-info-value"><?= htmlspecialchars(ucfirst($ticket['priority'] ?? 'normal')); ?></div>
    </div>
    <div class="hr-info-item">
        <div class="hr-info-label">Employee</div>
        <div class="hr-info-value"><?= htmlspecialchars($ticket['employee_name'] ?? ''); ?></div>
    </div>
    <?php if (!empty($ticket['assigned_name'])) { ?>
    <div class="hr-info-item">
        <div class="hr-info-label">Assigned HR</div>
        <div class="hr-info-value"><?= htmlspecialchars($ticket['assigned_name']); ?></div>
    </div>
    <?php } ?>
    <?php if (!empty($ticket['payment_reference'])) { ?>
    <div class="hr-info-item">
        <div class="hr-info-label">Payment reference</div>
        <div class="hr-info-value"><?= htmlspecialchars($ticket['payment_reference']); ?></div>
    </div>
    <?php } ?>
    <div class="hr-info-item">
        <div class="hr-info-label">Created</div>
        <div class="hr-info-value"><?= htmlspecialchars($ticket['created_at'] ?? ''); ?></div>
    </div>
    <div class="hr-info-item full-width">
        <div class="hr-info-label">Subject</div>
        <div class="hr-info-value"><?= htmlspecialchars($ticket['subject'] ?? ''); ?></div>
    </div>
    <div class="hr-info-item full-width">
        <div class="hr-info-label">Description</div>
        <div class="hr-info-value"><?= nl2br(htmlspecialchars($ticket['description'] ?? '')); ?></div>
    </div>
</div>

<?php
$attachments = $hr->getAttachments((int) ($ticket['id'] ?? 0));
if (!empty($attachments)) {
    ?>
<h6 class="mt-4 mb-2 font-weight-bold">Attachments</h6>
<ul class="hr-attach-list">
    <?php foreach ($attachments as $att) { ?>
    <li>
        <a href="<?= htmlspecialchars(hr_ticket_attachment_url($att['file_name'] ?? '')); ?>" target="_blank" rel="noopener">
            <i class="fal fa-paperclip mr-1"></i><?= htmlspecialchars($att['original_name'] ?? ''); ?>
        </a>
        <span class="text-muted small ml-2"><?= htmlspecialchars($att['created_at'] ?? ''); ?></span>
    </li>
    <?php } ?>
</ul>
<?php } ?>
