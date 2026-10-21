<?php
/**
 * Shared start-up for the Finance & Performance cron jobs. Not a job itself (blocked by .htaccess).
 *
 * A job can be started in two ways:
 *   Command line:  php admin/finance-performance/cron/<job>.php <site host name>
 *   URL (cron-job.org or similar):  https://<site>/admin/finance-performance/cron/<job>.php?key=<cron key>
 *     (or send the key in an "X-Cron-Key" header instead of the URL).
 *
 * The URL only works when config/cron_secret.php exists and returns a key of at least 32 characters:
 *   <?php return 'long-random-key';
 * Without that file every web call gets "404 Not Found". The file is not committed to git.
 *
 * Jobs are safe to run again: drafts, reminders and escalations are created only once. Two runs of the
 * same job never overlap; the second one stops at once.
 */

const FP_CRON_KEY_MIN_LENGTH = 32;

if (PHP_SAPI === 'cli') {
    $_SERVER['HTTP_HOST'] = isset($argv[1]) && $argv[1] !== '' ? $argv[1] : 'localhost';
    $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
} else {
    fp_cron_check_web_key();
}

chdir(__DIR__);
require_once __DIR__ . '/../../controllers/common_controllers.php';
require_once __DIR__ . '/../controller/fp_access.php';
require_once __DIR__ . '/../controller/fp_audit.php';
require_once __DIR__ . '/../controller/fp_requests.php';
setTimeZone();

function fp_cron_web_stop($status, $text)
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo $text . "\n";
    exit;
}

function fp_cron_check_web_key()
{
    $file = __DIR__ . '/../config/cron_secret.php';
    $secret = is_file($file) ? include $file : '';
    if (!is_string($secret) || strlen($secret) < FP_CRON_KEY_MIN_LENGTH) {
        fp_cron_web_stop(404, 'Not Found');
    }
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', array('GET', 'POST', 'HEAD'), true)) {
        fp_cron_web_stop(405, 'Method Not Allowed');
    }
    $given = (string) ($_SERVER['HTTP_X_CRON_KEY'] ?? ($_GET['key'] ?? ''));
    if ($given === '' || !hash_equals($secret, $given)) {
        fp_cron_web_stop(403, 'Forbidden');
    }
    ignore_user_abort(true);
    set_time_limit(300);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
}

/**
 * Runs one job. $job names the lock; $work($conn) does the work and returns a one-line summary.
 * Exit code 1 (command line) or HTTP 500 (URL) when the job fails, so the cron service reports it.
 */
function fp_cron_run($job, $work)
{
    $web = PHP_SAPI !== 'cli';
    try {
        $conn = fp_db_connect();
        $lock = 'fp_cron_' . $job;
        $got = fp_db_rows($conn, 'SELECT GET_LOCK(?, 0) AS Got', 's', array($lock));
        if ((int) ($got[0]['Got'] ?? 0) !== 1) {
            echo date('Y-m-d H:i:s') . ' ' . $job . ': previous run still working, skipped.' . PHP_EOL;
            return;
        }
        try {
            echo date('Y-m-d H:i:s') . ' ' . $work($conn) . PHP_EOL;
        } finally {
            fp_db_rows($conn, 'SELECT RELEASE_LOCK(?) AS Released', 's', array($lock));
        }
    } catch (Throwable $e) {
        error_log('Finance & Performance cron ' . $job . ' failed: ' . $e->getMessage());
        if ($web) {
            http_response_code(500);
            echo date('Y-m-d H:i:s') . ' ' . $job . ' failed. See the PHP error log.' . PHP_EOL;
            return;
        }
        fwrite(STDERR, ucfirst($job) . ' run failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
