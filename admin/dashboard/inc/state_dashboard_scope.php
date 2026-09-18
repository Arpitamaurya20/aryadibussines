<?php
declare(strict_types=1);

/**
 * Dashboard scope: state managers see all branches in their state(s);
 * branch account managers see only branches where AccountBranchManager = employee.
 */
function manager_dashboard_resolve_scope(mysqli $conn, array $session): array
{
    $employeeId = isset($session['Roles']['EmployeeID']) ? (int)$session['Roles']['EmployeeID'] : 0;
    $empty = [
        'mode' => '',
        'employee_id' => $employeeId,
        'state_names' => [],
        'state_in_clause' => '',
        'branch_ids' => [],
        'branch_in_clause' => '',
        'branches' => [],
        'page_title' => '',
        'breadcrumb' => '',
    ];

    if ($employeeId <= 0) {
        return $empty;
    }

    if (!class_exists('State')) {
        require_once dirname(__DIR__, 2) . '/includes/autoloader.inc.php';
    }

    // State manager takes precedence when both roles exist.
    if (function_exists('userHasStateCorporateLeadAccess') && userHasStateCorporateLeadAccess($session)) {
        $stateObject = new State($conn);
        $stateNames = $stateObject->getAllowedStateNamesForEmployee($employeeId);
        return [
            'mode' => 'state',
            'employee_id' => $employeeId,
            'state_names' => $stateNames,
            'state_in_clause' => $stateObject->buildBranchStateInClause($stateNames),
            'branch_ids' => [],
            'branch_in_clause' => '',
            'branches' => [],
            'page_title' => 'My State Dashboard',
            'breadcrumb' => 'My State Dashboard',
            'hero_text' => 'Wallboard-style view for your states — tickets, quotations, branches & technicians.',
        ];
    }

    if (manager_dashboard_user_is_branch_account_manager($session)) {
        $branchObject = new Branch($conn);
        $mapped = $branchObject->getMappedAccountBranchesofAccountBranchManager($employeeId);
        $branchIds = [];
        $branches = [];
        foreach ($mapped as $row) {
            $id = (int)($row['ID'] ?? 0);
            if ($id > 0 && !in_array($id, $branchIds, true)) {
                $branchIds[] = $id;
                $branches[] = [
                    'id' => $id,
                    'name' => (string)($row['BranchSite'] ?? 'Branch'),
                    'state_name' => (string)($row['BranchState'] ?? ''),
                    'corporate_id' => (int)($row['CompanyID'] ?? 0),
                ];
            }
        }
        return [
            'mode' => 'branch',
            'employee_id' => $employeeId,
            'state_names' => [],
            'state_in_clause' => '',
            'branch_ids' => $branchIds,
            'branch_in_clause' => manager_dashboard_build_branch_in_clause($branchIds),
            'branches' => $branches,
            'page_title' => 'My Branch Dashboard',
            'breadcrumb' => 'My Branch Dashboard',
            'hero_text' => 'Wallboard-style view for your assigned branches — tickets, quotations & technicians.',
        ];
    }

    return $empty;
}

function manager_dashboard_user_is_branch_account_manager(array $session): bool
{
    if (function_exists('CheckRole') && CheckRole($session, 'Branch Account Manager')) {
        return true;
    }
    $employeeId = isset($session['Roles']['EmployeeID']) ? (int)$session['Roles']['EmployeeID'] : 0;
    if ($employeeId <= 0) {
        return false;
    }
    $conn = _connectodb();
    if (!$conn) {
        return false;
    }
    if (!class_exists('Branch')) {
        require_once dirname(__DIR__, 2) . '/includes/autoloader.inc.php';
    }
    $branchObject = new Branch($conn);
    return count($branchObject->getMappedAccountBranchesofAccountBranchManager($employeeId)) > 0;
}

function userHasBranchAccountManagerAccess(array $session): bool
{
    if (function_exists('userHasStateCorporateLeadAccess') && userHasStateCorporateLeadAccess($session)) {
        return false;
    }
    return manager_dashboard_user_is_branch_account_manager($session);
}

function manager_dashboard_build_branch_in_clause(array $branchIds): string
{
    $ids = [];
    foreach ($branchIds as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    return $ids ? implode(', ', $ids) : '';
}

function state_dashboard_branch_join_on(array $filters, string $branchAlias, string $ticketBranchCol): string
{
    $mode = $filters['scope_mode'] ?? 'state';
    $on = "$branchAlias.ID = $ticketBranchCol";
    if ($mode === 'branch' && ($filters['branch_in_clause'] ?? '') !== '') {
        $on .= " AND $branchAlias.ID IN ({$filters['branch_in_clause']})";
    } elseif (($filters['state_in_clause'] ?? '') !== '') {
        $on .= " AND $branchAlias.BranchState IN ({$filters['state_in_clause']})";
    } else {
        $on .= ' AND 1=0';
    }
    return "INNER JOIN branch $branchAlias ON $on";
}

function state_dashboard_scope_is_valid(array $filters): bool
{
    if (($filters['scope_mode'] ?? 'state') === 'branch') {
        return ($filters['branch_in_clause'] ?? '') !== '';
    }
    return ($filters['state_in_clause'] ?? '') !== '';
}

function state_dashboard_scope_branch_where(array $filters, string $alias = 'b'): string
{
    $parts = ["$alias.IsActive = 1"];
    if (($filters['scope_mode'] ?? 'state') === 'branch' && ($filters['branch_in_clause'] ?? '') !== '') {
        $parts[] = "$alias.ID IN ({$filters['branch_in_clause']})";
    } elseif (($filters['state_in_clause'] ?? '') !== '') {
        $parts[] = "$alias.BranchState IN ({$filters['state_in_clause']})";
    } else {
        $parts[] = '1=0';
    }
    if (($filters['branch_id'] ?? 0) > 0) {
        $parts[] = $alias . '.ID = ' . (int)$filters['branch_id'];
    }
    if (($filters['corporate_id'] ?? 0) > 0) {
        $parts[] = $alias . '.CompanyID = ' . (int)$filters['corporate_id'];
    }
    return implode(' AND ', $parts);
}

/**
 * Clean hero scope line — avoids rendering dozens of branch badges.
 */
function manager_dashboard_render_scope_hero(array $scope, array $filterOptions): void
{
    $mode = $scope['mode'] ?? 'state';
    echo '<div class="smd-scope-summary" id="smd_scope_summary">';

    if ($mode === 'branch') {
        $branchCount = count($scope['branches'] ?? []);
        $companyCount = count($filterOptions['companies'] ?? []);
        echo '<span class="smd-scope-chip"><i class="fa-solid fa-building" aria-hidden="true"></i> ';
        echo (int)$branchCount . ' branch' . ($branchCount === 1 ? '' : 'es') . ' in your scope</span>';
        if ($companyCount > 0) {
            echo '<span class="smd-scope-chip smd-scope-chip--muted"><i class="fa-solid fa-briefcase" aria-hidden="true"></i> ';
            echo (int)$companyCount . ' ' . ($companyCount === 1 ? 'company' : 'companies') . '</span>';
        }
        echo '<p class="smd-scope-hint">Use the filters below to drill down by company or branch.</p>';
    } else {
        $stateNames = $scope['state_names'] ?? [];
        $branchCount = count($filterOptions['branches'] ?? []);
        $stateCount = count($stateNames);
        if ($stateCount > 0 && $stateCount <= 4) {
            foreach ($stateNames as $stateName) {
                echo '<span class="smd-scope-chip">' . htmlspecialchars((string)$stateName, ENT_QUOTES, 'UTF-8') . '</span>';
            }
        } elseif ($stateCount > 4) {
            echo '<span class="smd-scope-chip"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> ';
            echo $stateCount . ' states in your scope</span>';
        }
        if ($branchCount > 0) {
            echo '<span class="smd-scope-chip smd-scope-chip--muted"><i class="fa-solid fa-building" aria-hidden="true"></i> ';
            echo (int)$branchCount . ' branch' . ($branchCount === 1 ? '' : 'es') . '</span>';
        }
    }

    echo '</div>';
}

/**
 * Resolve scope and redirect if this page does not match the employee's access.
 * State manager access always wins when both state and branch apply.
 *
 * @return array Resolved scope (script exits on redirect).
 */
function manager_dashboard_enforce_page_scope(mysqli $conn, array $session, string $expectedMode): array
{
    $scope = manager_dashboard_resolve_scope($conn, $session);
    $mode = $scope['mode'] ?? '';

    if ($mode === 'state' && $expectedMode === 'branch') {
        header('Location: state_manager_dashboard.php');
        exit;
    }
    if ($mode === 'branch' && $expectedMode === 'state') {
        header('Location: branch_manager_dashboard.php');
        exit;
    }
    if ($mode !== $expectedMode) {
        header('Location: ../login/login.php');
        exit;
    }

    return $scope;
}

/**
 * Default landing page: state dashboard if state access, else branch dashboard.
 */
function manager_dashboard_redirect_to_default(mysqli $conn, array $session): void
{
    $scope = manager_dashboard_resolve_scope($conn, $session);
    if (($scope['mode'] ?? '') === 'state') {
        header('Location: state_manager_dashboard.php');
        exit;
    }
    if (($scope['mode'] ?? '') === 'branch') {
        header('Location: branch_manager_dashboard.php');
        exit;
    }
    header('Location: ../login/login.php');
    exit;
}
