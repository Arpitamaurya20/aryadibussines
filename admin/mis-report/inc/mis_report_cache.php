<?php
declare(strict_types=1);

const MIS_REPORT_CACHE_TTL = 1800;
const MIS_REPORT_CACHE_WAIT_SEC = 90;

function mis_report_cache_dir(): string
{
    $dir = dirname(__DIR__) . '/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function mis_report_cache_key(string $section, int $employeeId, array $filters): string
{
    return 'mis_' . $section . '_' . $employeeId . '_' . md5(json_encode($filters));
}

function mis_report_cache_paths(string $key): array
{
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
    $dir = mis_report_cache_dir();
    return [
        'data' => $dir . DIRECTORY_SEPARATOR . $safe . '.json',
        'lock' => $dir . DIRECTORY_SEPARATOR . $safe . '.lock',
    ];
}

function mis_report_cache_read(string $path): ?array
{
    if (!is_readable($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    $wrapper = $raw ? json_decode($raw, true) : null;
    if (!is_array($wrapper)
        || !isset($wrapper['expires'], $wrapper['data'])
        || (int)$wrapper['expires'] <= time()
        || !is_array($wrapper['data'])
    ) {
        return null;
    }
    return $wrapper['data'];
}

function mis_report_cache_write(string $path, array $data): void
{
    @file_put_contents($path, json_encode([
        'expires' => time() + MIS_REPORT_CACHE_TTL,
        'generated_at' => date('Y-m-d H:i:s'),
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
}

function mis_report_cache_remember(
    string $section,
    int $employeeId,
    array $filters,
    callable $generator,
    bool $forceRefresh = false
): array {
    $key = mis_report_cache_key($section, $employeeId, $filters);
    $paths = mis_report_cache_paths($key);

    if (!$forceRefresh) {
        $hit = mis_report_cache_read($paths['data']);
        if ($hit !== null) {
            return $hit;
        }
    }

    $lockFp = @fopen($paths['lock'], 'c+');
    $hasLock = false;

    try {
        if ($lockFp) {
            $hasLock = flock($lockFp, LOCK_EX);
            if ($hasLock && !$forceRefresh) {
                $hit = mis_report_cache_read($paths['data']);
                if ($hit !== null) {
                    return $hit;
                }
            }
        }

        $data = $generator();
        mis_report_cache_write($paths['data'], $data);
        return $data;
    } finally {
        if ($lockFp) {
            if ($hasLock) {
                @flock($lockFp, LOCK_UN);
            }
            @fclose($lockFp);
        }
    }
}

function mis_report_cached_workforce(
    mysqli $conn,
    int $employeeId,
    array $filters,
    string $stateName,
    bool $forceRefresh = false
): array {
    require_once __DIR__ . '/mis_report_queries.php';

    return mis_report_cache_remember('workforce', $employeeId, $filters, static function () use ($conn, $filters, $stateName) {
        return mis_report_build_workforce_snapshot($conn, $filters, $stateName);
    }, $forceRefresh);
}

function mis_report_cached_tickets(
    mysqli $conn,
    int $employeeId,
    array $filters,
    string $stateName,
    bool $forceRefresh = false
): array {
    require_once __DIR__ . '/mis_report_queries.php';

    return mis_report_cache_remember('tickets', $employeeId, $filters, static function () use ($conn, $filters, $stateName, $employeeId) {
        return mis_report_build_ticket_snapshot($conn, $filters, $stateName, $employeeId);
    }, $forceRefresh);
}

function mis_report_merge_snapshots(array $workforce, array $tickets): array
{
    $merged = $tickets;
    $merged['workforce'] = $workforce['workforce'] ?? [];
    $merged['employees'] = $workforce['employees'] ?? [];
    $merged['selected_state'] = $workforce['selected_state'] ?? ($tickets['selected_state'] ?? '');
    if (!empty($workforce['generated_at'])) {
        $merged['generated_at'] = $workforce['generated_at'];
    }
    return $merged;
}
