<?php
$timelineLabel = ($ticket['timeline_type'] ?? '') === 'custom'
    ? 'Custom: ' . ($ticket['timeline_custom_at'] ?? 'Not set')
    : 'Immediate';
$budgetShow = isset($ticket['budget']) && $ticket['budget'] !== null && $ticket['budget'] !== ''
    ? htmlspecialchars($ticket['budget'])
    : '—';
?>
<div class="hc-section-title">Ticket overview</div>
<div class="hc-info-grid">
    <div class="hc-info-item">
        <div class="hc-info-label">Ticket code</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['ticket_code'] ?? ''); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Status</div>
        <div class="hc-info-value"><?= hc_status_badge($ticket['status'] ?? 'new'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Booking date</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['booking_date'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Booking time</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['booking_time'] ?? '—'); ?></div>
    </div>
</div>

<div class="hc-section-title">Contact &amp; location</div>
<div class="hc-info-grid">
    <div class="hc-info-item">
        <div class="hc-info-label">Name</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['name'] ?? ''); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Phone</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['phone'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Email</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['email'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">City</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['city'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">State</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['state_name'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Postal code</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['postal_code'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Location</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['location'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Landmark</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['landmark'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item full-width">
        <div class="hc-info-label">Address</div>
        <div class="hc-info-value"><?= nl2br(htmlspecialchars($ticket['address'] ?? '—')); ?></div>
    </div>
</div>

<div class="hc-section-title">Service request</div>
<div class="hc-info-grid">
    <div class="hc-info-item">
        <div class="hc-info-label">Timeline</div>
        <div class="hc-info-value"><?= htmlspecialchars($timelineLabel); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Budget</div>
        <div class="hc-info-value"><?= $budgetShow; ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Purpose</div>
        <div class="hc-info-value"><?= htmlspecialchars(ucfirst($ticket['purpose'] ?? '')); ?></div>
    </div>
    <div class="hc-info-item full-width">
        <div class="hc-info-label">Purpose contact</div>
        <div class="hc-info-value">
            <?= htmlspecialchars($ticket['purpose_name'] ?? ''); ?><br>
            <?= htmlspecialchars($ticket['purpose_phone'] ?? ''); ?><br>
            <?= nl2br(htmlspecialchars($ticket['purpose_address'] ?? '')); ?>
        </div>
    </div>
    <div class="hc-info-item full-width">
        <div class="hc-info-label">Description</div>
        <div class="hc-info-value"><?= nl2br(htmlspecialchars($ticket['description'] ?? '')); ?></div>
    </div>
</div>

<div class="hc-section-title">Audit</div>
<div class="hc-info-grid">
    <div class="hc-info-item">
        <div class="hc-info-label">Created by</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['created_by'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Created at</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['created_at'] ?? '—'); ?></div>
    </div>
    <div class="hc-info-item">
        <div class="hc-info-label">Last updated</div>
        <div class="hc-info-value"><?= htmlspecialchars($ticket['updated_at'] ?? '—'); ?></div>
    </div>
</div>
