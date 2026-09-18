<?php
$history_rows = $hc->getTicketHistory((int) ($ticket['id'] ?? 0));
?>
<?php if (empty($history_rows)) { ?>
    <div class="hc-empty-state">
        <i class="fal fa-history"></i>
        <p class="mb-0">No activity recorded yet. Actions will appear here when the ticket is raised or updated.</p>
    </div>
<?php } else { ?>
    <div class="hc-timeline">
        <?php foreach ($history_rows as $h) {
            $statusShow = !empty($h['status'])
                ? ucwords(str_replace('_', ' ', $h['status']))
                : '';
            ?>
        <div class="hc-timeline-item">
            <div class="hc-timeline-card">
                <div class="hc-timeline-head">
                    <?= hc_history_badge($h['action_type'] ?? ''); ?>
                    <?php if ($statusShow !== '') { ?>
                        <?= hc_status_badge($h['status']); ?>
                    <?php } ?>
                </div>
                <div class="hc-timeline-summary"><?= htmlspecialchars($h['summary'] ?? ''); ?></div>
                <?php if (!empty($h['assigned_from_name']) || !empty($h['assigned_to_name'])) { ?>
                    <div class="hc-timeline-meta mb-1">
                        <i class="fal fa-exchange mr-1"></i>
                        <?php if (!empty($h['assigned_from_name'])) { ?>
                            <?= htmlspecialchars($h['assigned_from_name']); ?>
                        <?php } ?>
                        <?php if (!empty($h['assigned_to_name'])) { ?>
                            <span class="mx-1">→</span><?= htmlspecialchars($h['assigned_to_name']); ?>
                        <?php } ?>
                    </div>
                <?php } ?>
                <?php if (!empty($h['due_date'])) { ?>
                    <div class="hc-timeline-meta"><i class="fal fa-calendar mr-1"></i>Due: <?= htmlspecialchars($h['due_date']); ?></div>
                <?php } ?>
                <?php if (trim($h['remarks'] ?? '') !== '') { ?>
                    <div class="hc-timeline-meta"><i class="fal fa-comment mr-1"></i><?= nl2br(htmlspecialchars($h['remarks'])); ?></div>
                <?php } ?>
                <div class="hc-timeline-meta mt-2">
                    <i class="fal fa-user mr-1"></i><?= htmlspecialchars($h['created_by'] ?? 'System'); ?>
                    <span class="mx-1">·</span>
                    <i class="fal fa-clock mr-1"></i><?= htmlspecialchars($h['created_at'] ?? ''); ?>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
<?php } ?>
