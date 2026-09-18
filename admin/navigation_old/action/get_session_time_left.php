<?php
session_start();
if (!isset($_SESSION['session_start_time'])) {
    $_SESSION['session_start_time'] = time();
}
$lifetime = ini_get('session.gc_maxlifetime');
$elapsed = time() - $_SESSION['session_start_time'];
$remaining = $lifetime - $elapsed;
if ($remaining < 0) $remaining = 0;
$remaining_min = round($remaining / 60, 2);
// Return JSON
header('Content-Type: application/json');
echo json_encode([
    'remaining' => $remaining_min
]);
