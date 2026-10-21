<?php
/**
 * Escalates overdue approval stages and notifies the escalation approvers. Run every 30 minutes.
 *   Command line:  php admin/finance-performance/cron/process_escalations.php <site host name>
 *   URL:           https://<site>/admin/finance-performance/cron/process_escalations.php?key=<cron key>
 * See fp_cron.php for the cron key.
 */
require_once __DIR__ . '/fp_cron.php';

fp_cron_run('escalations', function ($conn) {
    return 'escalated stages: ' . fp_approval_process_escalations($conn, 500);
});
