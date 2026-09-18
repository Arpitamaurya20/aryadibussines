<div class="table-responsive">
    <table class="table table-bordered table-sm at-checklist-table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Checkpoint</th>
                <th>Ideal</th>
                <th>Min</th>
                <th>Max</th>
                <th>Value</th>
                <th>OK / Not OK</th>
                <th>Remarks</th>
                <th>Compliance</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($checklistItems as $item) {
                $cp = $item['checklist'];
                $rowClass = '';
                if ($item['ok_status'] === 'Not OK') {
                    $rowClass = 'checkpoint-row-out';
                } elseif ($item['ok_status'] === 'OK') {
                    $rowClass = 'checkpoint-row-ok';
                } elseif ($item['compliance_status'] === 'Out of Range') {
                    $rowClass = 'checkpoint-row-out';
                } elseif ($item['compliance_status'] === 'In Range') {
                    $rowClass = 'checkpoint-row-ok';
                }
                ?>
            <tr class="<?php echo $rowClass; ?>">
                <td><?php echo $i++; ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($cp['CheckpointName']); ?></strong>
                    <?php if ((int) $cp['IsMandatory'] === 1) { ?><span class="badge badge-primary ml-1">Required</span><?php } ?>
                </td>
                <td class="ideal-val"><?php echo htmlspecialchars($cp['IdealValue'] ?: '-'); ?><?php echo $cp['Unit'] ? ' ' . htmlspecialchars($cp['Unit']) : ''; ?></td>
                <td><?php echo htmlspecialchars($cp['MinValue'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($cp['MaxValue'] ?: '-'); ?></td>
                <td><strong><?php echo $item['response_value'] !== '' ? htmlspecialchars($item['response_value']) : '<span class="text-muted">Pending</span>'; ?></strong></td>
                <td><?php echo auditTicketOkStatusBadge($item['ok_status']); ?></td>
                <td class="small"><?php echo $item['remarks'] !== '' ? nl2br(htmlspecialchars($item['remarks'])) : '<span class="text-muted">-</span>'; ?></td>
                <td><?php echo auditTicketComplianceBadge($item['compliance_status']); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
