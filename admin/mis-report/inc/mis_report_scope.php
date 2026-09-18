<?php
declare(strict_types=1);

require_once __DIR__ . '/mis_report_queries.php';

/**
 * MIS Report — admin selects a state first; data loads only after filter submit.
 */
function mis_report_user_has_access(array $session): bool
{
    if (!isset($session['Roles']['EmployeeRoles']) || !is_array($session['Roles']['EmployeeRoles'])) {
        return false;
    }
    $allowed = ['Super Admin', 'Admin', 'Sub Admin'];
    foreach ($session['Roles']['EmployeeRoles'] as $role) {
        if (in_array((string)$role, $allowed, true)) {
            return true;
        }
    }
    return false;
}

function mis_report_resolve_scope(mysqli $conn, array $session): array
{
    $employeeId = isset($session['Roles']['EmployeeID']) ? (int)$session['Roles']['EmployeeID'] : 0;

    return [
        'mode' => 'state',
        'employee_id' => $employeeId,
        'state_names' => mis_report_list_state_names($conn),
        'state_in_clause' => '',
        'branch_ids' => [],
        'branch_in_clause' => '',
        'branches' => [],
        'page_title' => 'MIS Report',
        'breadcrumb' => 'MIS Report',
        'hero_text' => 'Select a state, then load tickets, quotations, workforce & branch data for that state only.',
        'is_mis_report' => true,
    ];
}

function mis_report_enforce_page_access(mysqli $conn, array $session): array
{
    if (!mis_report_user_has_access($session)) {
        header('Location: ../authentication/login.php');
        exit;
    }

    return mis_report_resolve_scope($conn, $session);
}

function mis_report_render_scope_hero(?string $selectedState = null): void
{
    echo '<div class="smd-scope-summary" id="smd_scope_summary">';
    if ($selectedState) {
        echo '<span class="smd-scope-chip"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> ';
        echo htmlspecialchars($selectedState, ENT_QUOTES, 'UTF-8') . '</span>';
        echo '<p class="smd-scope-hint">Showing data for this state only. Change state and click Load report to refresh.</p>';
    } else {
        echo '<span class="smd-scope-chip smd-scope-chip--muted"><i class="fa-solid fa-filter" aria-hidden="true"></i> No state loaded yet</span>';
        echo '<p class="smd-scope-hint">Choose a state and period, then click <strong>Load report</strong>. Company and branch filters are optional.</p>';
    }
    echo '</div>';
}
