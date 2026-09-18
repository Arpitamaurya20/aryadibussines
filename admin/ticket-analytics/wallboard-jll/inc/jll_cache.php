<?php
declare(strict_types=1);

function jll_cache_dir(): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'techxpert_jll_wallboard';
}

function jll_cache_read(string $key, int $ttlSeconds): ?array
{
    $file = jll_cache_dir() . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_]/', '', $key) . '.json';
    if (!is_readable($file)) {
        return null;
    }
    $raw = @file_get_contents($file);
    $data = $raw ? json_decode($raw, true) : null;
    if (!is_array($data) || !isset($data['expires']) || (int)$data['expires'] <= time()) {
        return null;
    }
    return $data['payload'] ?? null;
}

function jll_cache_write(string $key, array $payload, int $ttlSeconds): void
{
    $dir = jll_cache_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_]/', '', $key) . '.json';
    @file_put_contents($file, json_encode([
        'expires' => time() + max(30, $ttlSeconds),
        'payload' => $payload,
    ]));
}

function jll_cached_id_floors(mysqli $conn): array
{
    $cached = jll_cache_read('id_floors', 120);
    if (is_array($cached)) {
        return $cached;
    }

    require_once __DIR__ . '/jll_queries.php';
    $floors = [
        'corp' => jll_table_id_floor($conn, 'corporate_tickets'),
        'ppm' => jll_table_id_floor($conn, 'ppm_tickets', 20000),
        'hist' => jll_table_id_floor($conn, 'corporate_ticket_status_history', 40000),
    ];
    jll_cache_write('id_floors', $floors, 120);
    return $floors;
}

function jll_cached_company_ids(mysqli $conn): array
{
    $cached = jll_cache_read('company_ids', 300);
    if (is_array($cached) && !empty($cached['ids'])) {
        return $cached;
    }

    $hqId = (int)JLL_CORPORATE_HQ_ID;
    require_once __DIR__ . '/jll_queries.php';
    $rows = jll_query_rows($conn, "
        SELECT ID, CompanyName
        FROM company
        WHERE CorporateName = '$hqId' AND IsActive = 1
        ORDER BY CompanyName
    ");
    $ids = [];
    $contracts = [];
    foreach ($rows as $r) {
        $id = (int)($r['ID'] ?? 0);
        if ($id > 0) {
            $ids[] = $id;
            $contracts[] = [
                'id' => $id,
                'name' => (string)($r['CompanyName'] ?? ''),
            ];
        }
    }
    $payload = ['ids' => $ids, 'contracts' => $contracts, 'count' => count($ids)];
    jll_cache_write('company_ids', $payload, 300);
    return $payload;
}
