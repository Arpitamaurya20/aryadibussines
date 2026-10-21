<?php
/**
 * Creates the draft direct expenses of recurring rules whose bills are coming due, and sends the
 * "due soon" and "overdue" reminders for direct expenses. Drafts are never submitted, approved or paid here.
 * Run once a day (e.g. 07:00).
 *   Command line:  php admin/finance-performance/cron/process_recurring.php <site host name>
 *   URL:           https://<site>/admin/finance-performance/cron/process_recurring.php?key=<cron key>
 * See fp_cron.php for the cron key.
 */
require_once __DIR__ . '/fp_cron.php';

fp_cron_run('recurring', function ($conn) {
    $today = date('Y-m-d');
    list($created, $blocked) = fp_recurring_process_due($conn, $today);
    $reminders = fp_direct_process_reminders($conn, $today);
    return 'recurring drafts created: ' . $created . ', rules needing attention: ' . $blocked . ', reminders: ' . $reminders;
});
