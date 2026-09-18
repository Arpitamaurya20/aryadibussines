<?php
declare(strict_types=1);

require_once __DIR__ . '/type_dashboard_queries.php';

function ta_executive_dashboard_catalog(): array
{
    return [
        [
            'type' => 'R&M',
            'title' => 'R&M',
            'description' => 'Repair & maintenance corporate tickets',
            'page' => 'rm-dashboard.php',
            'accent' => '#2563eb',
        ],
        [
            'type' => 'PPM',
            'title' => 'PPM',
            'description' => 'Planned preventive maintenance tickets',
            'page' => 'ppm-dashboard.php',
            'accent' => '#14b8a6',
        ],
        [
            'type' => 'Projects',
            'title' => 'Projects',
            'description' => 'Project-type corporate tickets',
            'page' => 'projects-dashboard.php',
            'accent' => '#8b5cf6',
        ],
        [
            'type' => 'Supply',
            'title' => 'Supply',
            'description' => 'Supply / material corporate tickets',
            'page' => 'supply-dashboard.php',
            'accent' => '#f97316',
        ],
        [
            'type' => 'AMC Breakdown',
            'title' => 'AMC Breakdown',
            'description' => 'AMC breakdown corporate tickets',
            'page' => 'amc-dashboard.php',
            'accent' => '#ef4444',
        ],
    ];
}

function ta_executive_dashboard_by_type(string $type): ?array
{
    $type = ta_normalize_ticket_type($type);
    foreach (ta_executive_dashboard_catalog() as $item) {
        if ($item['type'] === $type) {
            return $item;
        }
    }
    return null;
}
