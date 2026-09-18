<?php
$historyRows = getAuditTicketStatusHistory($conn, $ticketDbId);
?>

<div class="p-1">
    <?php if (empty($historyRows)) { ?>
    <div class="alert alert-info mb-0">No status history recorded yet.</div>
    <?php } else { ?>
    <?php foreach ($historyRows as $history) {
        $employeeName = '';
        $assigneeId = (int) $history['AssignedTo'];
        if ($assigneeId > 0) {
            $employeeName = auditTicketEmployeeName($conn, $assigneeId);
        }
        $prevStatus = isset($history['PreviousStatus']) ? trim((string) $history['PreviousStatus']) : '';
        ?>
    <div class="at-history-item">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <div class="hist-status"><?php echo htmlspecialchars($history['Status']); ?></div>
                <?php if ($prevStatus !== '') { ?>
                <div class="small text-muted">Previous: <?php echo htmlspecialchars($prevStatus); ?></div>
                <?php } ?>
            </div>
            <div class="small text-muted text-right">
                <?php echo htmlspecialchars($history['CreatedDate'] . ' ' . $history['CreatedTime']); ?>
            </div>
        </div>
        <?php if ($employeeName !== '') { ?>
        <div class="mt-2"><i class="fal fa-user-hard-hat mr-1"></i> <?php echo htmlspecialchars($employeeName); ?></div>
        <?php } ?>
        <?php if (!empty($history['Remarks'])) { ?>
        <div class="mt-1 text-muted small"><?php echo nl2br(htmlspecialchars($history['Remarks'])); ?></div>
        <?php } ?>
        <div class="mt-1 small">By <?php echo htmlspecialchars($history['CreatedBy']); ?></div>
    </div>
    <?php } ?>
    <?php } ?>
</div>
