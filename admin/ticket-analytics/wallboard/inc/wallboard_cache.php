<?php
declare(strict_types=1);

/** Cache expensive MAX(ID) lookups for 2 minutes. */
function wallboard_cached_id_floors(mysqli $conn): array
{
    $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'techxpert_wallboard_id_floors.json';
    if (is_readable($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        $data = $raw ? json_decode($raw, true) : null;
        if (is_array($data) && isset($data['expires']) && (int)$data['expires'] > time()) {
            return $data['floors'];
        }
    }

    $floors = [
        'corp' => wallboard_table_id_floor($conn, 'corporate_tickets'),
        'ppm' => wallboard_table_id_floor($conn, 'ppm_tickets', 20000),
        'hist' => wallboard_table_id_floor($conn, 'corporate_ticket_status_history', 40000),
    ];

    @file_put_contents($cacheFile, json_encode([
        'expires' => time() + 120,
        'floors' => $floors,
    ]));

    return $floors;
}
