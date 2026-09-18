<?php
declare(strict_types=1);

const STATE_DASHBOARD_CACHE_TTL = 600;

function state_dashboard_cache_key(int $employeeId, array $filters): string
{
    return 'state_dash_v2_' . $employeeId . '_' . md5(json_encode($filters));
}

function state_dashboard_cache_path(string $key): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $key . '.json';
}

function state_dashboard_cached_snapshot(
    mysqli $conn,
    int $employeeId,
    array $filters,
    bool $forceRefresh = false
): array {
    $key = state_dashboard_cache_key($employeeId, $filters);
    $path = state_dashboard_cache_path($key);

    if (!$forceRefresh && is_readable($path)) {
        $raw = @file_get_contents($path);
        $wrapper = $raw ? json_decode($raw, true) : null;
        if (is_array($wrapper)
            && isset($wrapper['expires'], $wrapper['data'])
            && (int)$wrapper['expires'] > time()
            && is_array($wrapper['data'])
        ) {
            return $wrapper['data'];
        }
    }

    require_once __DIR__ . '/state_dashboard_queries.php';
    $data = state_dashboard_fetch_snapshot($conn, $filters);

    @file_put_contents($path, json_encode([
        'expires' => time() + STATE_DASHBOARD_CACHE_TTL,
        'data' => $data,
    ]));

    return $data;
}
