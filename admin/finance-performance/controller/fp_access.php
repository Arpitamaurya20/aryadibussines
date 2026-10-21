<?php
/**
 * Finance & Performance access layer.
 *
 * Identity and roles are always re-read from users / user_roles / employees.
 * $_SESSION['Roles'] is never trusted here because SessionCheck() can rebuild it from cookies.
 */

function fp_access_matrix()
{
    static $matrix = null;
    if ($matrix === null) {
        $matrix = require __DIR__ . '/../config/access_matrix.php';
    }
    return $matrix;
}

function fp_require_classes()
{
    $dir = dirname(__DIR__, 2) . '/classes/';
    foreach (array('Core' => 'core', 'State' => 'state', 'Branch' => 'branch', 'Employee' => 'employee') as $class => $file) {
        if (!class_exists($class)) {
            require_once $dir . $file . '.class.php';
        }
    }
}

function fp_empty_scope()
{
    return array(
        'all'          => false,
        'self'         => false,
        'employee_ids' => array(),
        'state_names'  => array(),
        'branch_ids'   => array(),
        'company_ids'  => array(),
        'office_ids'   => array(),
    );
}

function fp_normalize_state($name)
{
    return strtolower(trim((string) $name));
}

function fp_db_rows($conn, $sql, $types = '', $params = array())
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Finance & Performance query prepare failed: ' . $conn->error);
        return array();
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        error_log('Finance & Performance query failed: ' . $stmt->error);
        $stmt->close();
        return array();
    }
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : array();
    $stmt->close();
    return $rows;
}

/**
 * Connection for module endpoints: exceptions on errors and strict SQL mode on this session only.
 */
function fp_db_connect()
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = _connectodb();
    if (!$conn) {
        throw new RuntimeException('Database connection failed.');
    }
    $conn->set_charset('utf8mb4');
    $conn->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    return $conn;
}

function fp_memo($key, $producer)
{
    static $cache = array();
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = $producer();
    }
    return $cache[$key];
}

function fp_unique_ints($values)
{
    $ids = array();
    foreach ($values as $value) {
        $id = (int) $value;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

function fp_unique_names($values)
{
    $names = array();
    foreach ($values as $value) {
        $value = trim((string) $value);
        if ($value !== '') {
            $names[fp_normalize_state($value)] = $value;
        }
    }
    return array_values($names);
}

/* ------------------------------------------------------------------
   Existing ERP assignments
------------------------------------------------------------------ */

function fp_mapped_state_names($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return array();
    }
    return fp_memo('state:' . $employeeId, function () use ($conn, $employeeId) {
        fp_require_classes();
        $state = new State($conn);
        $names = array();
        foreach (array_merge($state->getStatesMapped_StateLead($employeeId), $state->getStatesMapped_StateHead($employeeId)) as $row) {
            $names[] = $row['StateName'] ?? '';
        }
        return fp_unique_names($names);
    });
}

function fp_mapped_region_state_names($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return array();
    }
    return fp_memo('region:' . $employeeId, function () use ($conn, $employeeId) {
        $rows = fp_db_rows(
            $conn,
            'SELECT s.StateName FROM state s
             INNER JOIN region r ON r.ID = s.RegionID
             WHERE s.IsActive = 1 AND r.IsActive = 1 AND (r.RegionHead = ? OR r.RegionCorporateHead = ?)',
            'ii',
            array($employeeId, $employeeId)
        );
        return fp_unique_names(array_column($rows, 'StateName'));
    });
}

function fp_mapped_branch_ids($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return array();
    }
    return fp_memo('branch:' . $employeeId, function () use ($conn, $employeeId) {
        fp_require_classes();
        $branch = new Branch($conn);
        return fp_unique_ints(array_column($branch->getMappedAccountBranchesofAccountBranchManager($employeeId), 'ID'));
    });
}

function fp_mapped_company_ids($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return array();
    }
    return fp_memo('company:' . $employeeId, function () use ($conn, $employeeId) {
        $rows = fp_db_rows($conn, 'SELECT ID FROM company WHERE IsActive = 1 AND AccountManager = ?', 'i', array($employeeId));
        return fp_unique_ints(array_column($rows, 'ID'));
    });
}

function fp_team_employee_ids($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return array();
    }
    return fp_memo('team:' . $employeeId, function () use ($conn, $employeeId) {
        fp_require_classes();
        $employee = new Employee($conn);
        $ids = array();
        foreach ($employee->GetSupervisedEmployees($employeeId) as $row) {
            if ((int) ($row['IsActive'] ?? 0) === 1) {
                $ids[] = $row['ID'];
            }
        }
        foreach ($employee->getMappedEmployeesofCityLead($employeeId) as $row) {
            $ids[] = $row['ID'];
        }
        return array_values(array_diff(fp_unique_ints($ids), array($employeeId)));
    });
}

function fp_table_exists($conn, $table)
{
    return fp_memo('table:' . $table, function () use ($conn, $table) {
        $rows = fp_db_rows($conn, 'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', 's', array($table));
        return count($rows) > 0;
    });
}

/**
 * Active offices from branch_expense_office_employees; $flag is 'IsManager' or 'CanRequest'.
 */
function fp_assigned_office_ids($conn, $employeeId, $flag)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0 || !in_array($flag, array('IsManager', 'CanRequest'), true) || !fp_table_exists($conn, 'branch_expense_office_employees')) {
        return array();
    }
    return fp_memo('office:' . $flag . ':' . $employeeId, function () use ($conn, $employeeId, $flag) {
        $rows = fp_db_rows(
            $conn,
            'SELECT oe.OfficeID FROM branch_expense_office_employees oe
             INNER JOIN branch_expense_offices o ON o.ID = oe.OfficeID
             WHERE oe.EmployeeID = ? AND oe.IsActive = 1 AND oe.' . $flag . ' = 1 AND o.IsActive = 1',
            'i',
            array($employeeId)
        );
        return fp_unique_ints(array_column($rows, 'OfficeID'));
    });
}

/**
 * Scope a logical role grants, built from the ERP assignment named by $scopeType.
 */
function fp_build_scope($conn, $scopeType, $employeeId)
{
    $scope = fp_empty_scope();
    switch ($scopeType) {
        case 'all':
            $scope['all'] = true;
            break;
        case 'self':
            $scope['self'] = $employeeId > 0;
            break;
        case 'team':
            $scope['employee_ids'] = fp_team_employee_ids($conn, $employeeId);
            break;
        case 'branch':
            $scope['branch_ids'] = $employeeId > 0 ? fp_mapped_branch_ids($conn, $employeeId) : array();
            break;
        case 'state':
            $scope['state_names'] = $employeeId > 0 ? fp_mapped_state_names($conn, $employeeId) : array();
            break;
        case 'region':
            $scope['state_names'] = $employeeId > 0 ? fp_mapped_region_state_names($conn, $employeeId) : array();
            break;
        case 'company':
            $scope['company_ids'] = $employeeId > 0 ? fp_mapped_company_ids($conn, $employeeId) : array();
            break;
        case 'office':
            $scope['office_ids'] = fp_assigned_office_ids($conn, $employeeId, 'IsManager');
            break;
    }
    return $scope;
}

function fp_merge_scope($a, $b)
{
    return array(
        'all'          => $a['all'] || $b['all'],
        'self'         => $a['self'] || $b['self'],
        'employee_ids' => fp_unique_ints(array_merge($a['employee_ids'], $b['employee_ids'])),
        'state_names'  => fp_unique_names(array_merge($a['state_names'], $b['state_names'])),
        'branch_ids'   => fp_unique_ints(array_merge($a['branch_ids'], $b['branch_ids'])),
        'company_ids'  => fp_unique_ints(array_merge($a['company_ids'], $b['company_ids'])),
        'office_ids'   => fp_unique_ints(array_merge($a['office_ids'], $b['office_ids'])),
    );
}

function fp_scope_has_any($scope)
{
    return $scope['all'] || $scope['self'] || fp_scope_beyond_self($scope);
}

function fp_scope_beyond_self($scope)
{
    return $scope['all'] || $scope['employee_ids'] || $scope['state_names'] || $scope['branch_ids'] || $scope['company_ids'] || !empty($scope['office_ids']);
}

/* ------------------------------------------------------------------
   Actor
------------------------------------------------------------------ */

function fp_denied_actor($reason, $username = '', $userType = '')
{
    return array(
        'ok'            => false,
        'reason'        => $reason,
        'user_id'       => 0,
        'username'      => $username,
        'user_type'     => $userType,
        'employee_id'   => 0,
        'employee_name' => '',
        'erp_roles'     => array(),
        'roles'         => array(),
        'grants'        => array(),
        'request_office_ids' => array(),
        'delegated_from' => array(),
    );
}

/**
 * Employees who delegated their approvals to $employeeId for today.
 */
function fp_delegators_of($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0 || !fp_table_exists($conn, 'branch_expense_approval_delegations')) {
        return array();
    }
    $rows = fp_db_rows(
        $conn,
        'SELECT DelegatorEmployeeID FROM branch_expense_approval_delegations
         WHERE DelegateEmployeeID = ? AND IsActive = 1 AND FromDate <= CURDATE() AND ToDate >= CURDATE()',
        'i',
        array($employeeId)
    );
    return fp_unique_ints(array_column($rows, 'DelegatorEmployeeID'));
}

/**
 * Mapping check used to grant a logical role when the ERP role string is missing.
 */
function fp_mapping_matches($conn, $mapping, $employeeId, $employee)
{
    if ($employeeId <= 0) {
        return false;
    }
    $matrix = fp_access_matrix();
    switch ($mapping) {
        case 'employee':
            if (empty($employee)) {
                return false;
            }
            return !($matrix['exclude_vendor_employees'] && (string) ($employee['Vendor'] ?? '0') === '1');
        case 'supervisor':
            return count(fp_team_employee_ids($conn, $employeeId)) > 0;
        case 'branch_account_manager':
            return count(fp_mapped_branch_ids($conn, $employeeId)) > 0;
        case 'state_head':
            return count(fp_mapped_state_names($conn, $employeeId)) > 0;
        case 'region_head':
            return count(fp_mapped_region_state_names($conn, $employeeId)) > 0;
        case 'account_manager':
            return count(fp_mapped_company_ids($conn, $employeeId)) > 0;
        case 'office_manager':
            return count(fp_assigned_office_ids($conn, $employeeId, 'IsManager')) > 0;
    }
    return false;
}

function fp_mapping_label($mapping)
{
    $labels = array(
        'employee'               => 'Active employee record',
        'supervisor'             => 'employees.Supervisor / citydata.CorporateLead',
        'branch_account_manager' => 'branch.AccountBranchManager',
        'state_head'             => 'state.StateCorporateHead / state.StateHead',
        'region_head'            => 'region.RegionHead / region.RegionCorporateHead',
        'account_manager'        => 'company.AccountManager',
        'office_manager'         => 'Branch office manager (Finance & Performance > Settings > Branch Offices)',
    );
    return $labels[$mapping] ?? '';
}

function fp_build_actor($conn, $username, $sessionUserType)
{
    $matrix = fp_access_matrix();

    $users = fp_db_rows($conn, 'SELECT UserID, UserName, UserType, EmployeeID FROM users WHERE UserName = ? AND IsActive = 1 LIMIT 1', 's', array($username));
    if (!$users) {
        return fp_denied_actor('user_not_found', $username);
    }
    $user = $users[0];
    $userType = (string) $user['UserType'];

    if ($sessionUserType !== '' && $sessionUserType !== $userType) {
        return fp_denied_actor('session_mismatch', $username, $userType);
    }
    if (in_array($userType, $matrix['blocked_user_types'], true)) {
        return fp_denied_actor('client_user', $username, $userType);
    }

    $employeeId = (int) $user['EmployeeID'];
    $employee = array();
    if ($employeeId > 0) {
        $rows = fp_db_rows($conn, 'SELECT ID, Name, Vendor, State, City FROM employees WHERE ID = ? AND IsActive = 1 LIMIT 1', 'i', array($employeeId));
        $employee = $rows ? $rows[0] : array();
        if (!$employee) {
            $employeeId = 0;
        }
    } else {
        $employeeId = 0;
    }

    if ($employeeId > 0) {
        $roleRows = fp_db_rows($conn, 'SELECT Role FROM user_roles WHERE EmployeeID = ? AND IsActive = 1', 'i', array($employeeId));
        $erpRoles = array_values(array_unique(array_map('strval', array_column($roleRows, 'Role'))));
    } else {
        $erpRoles = array($userType);
    }

    $actor = array(
        'ok'            => true,
        'reason'        => '',
        'user_id'       => (int) $user['UserID'],
        'username'      => (string) $user['UserName'],
        'user_type'     => $userType,
        'employee_id'   => $employeeId,
        'employee_name' => (string) ($employee['Name'] ?? ''),
        'erp_roles'     => $erpRoles,
        'roles'         => array(),
        'grants'        => array(),
        'request_office_ids' => fp_assigned_office_ids($conn, $employeeId, 'CanRequest'),
        'delegated_from' => fp_delegators_of($conn, $employeeId),
    );

    foreach ($matrix['roles'] as $key => $role) {
        $matchedBy = array();
        if (in_array($userType, $role['user_types'], true)) {
            $matchedBy[] = 'User type: ' . $userType;
        }
        foreach (array_intersect($role['erp_roles'], $erpRoles) as $erpRole) {
            $matchedBy[] = 'ERP role: ' . $erpRole;
        }
        if ($role['mapping'] !== '' && fp_mapping_matches($conn, $role['mapping'], $employeeId, $employee)) {
            $matchedBy[] = 'Mapping: ' . fp_mapping_label($role['mapping']);
        }
        if (!$matchedBy) {
            continue;
        }

        $scope = fp_build_scope($conn, $role['scope'], $employeeId);
        $actor['roles'][$key] = array(
            'label'      => $role['label'],
            'matched_by' => $matchedBy,
            'scope_type' => $role['scope'],
            'scope'      => $scope,
        );
        foreach ($role['permissions'] as $permission) {
            $actor['grants'][$permission] = fp_merge_scope($actor['grants'][$permission] ?? fp_empty_scope(), $scope);
        }
    }

    if ($matrix['financial_actions_require_employee'] && $employeeId <= 0) {
        unset($actor['grants']['approve'], $actor['grants']['pay']);
    }

    return $actor;
}

/**
 * $useCache is for sidebar rendering only; endpoints must pass false so access changes apply immediately.
 */
function fp_load_actor($conn, $useCache = false)
{
    static $requestCache = array();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $username = isset($_SESSION['pb_username']) ? trim((string) $_SESSION['pb_username']) : '';
    $sessionUserType = isset($_SESSION['UserType']) ? (string) $_SESSION['UserType'] : '';
    if ($username === '') {
        return fp_denied_actor('not_logged_in');
    }

    $cacheKey = $username . '|' . $sessionUserType;
    if (isset($requestCache[$cacheKey])) {
        return $requestCache[$cacheKey];
    }

    if ($useCache && isset($_SESSION['fp_actor_cache'])) {
        $cached = $_SESSION['fp_actor_cache'];
        if (($cached['key'] ?? '') === $cacheKey && (time() - (int) ($cached['at'] ?? 0)) < 300) {
            return $requestCache[$cacheKey] = $cached['actor'];
        }
    }

    $actor = fp_build_actor($conn, $username, $sessionUserType);
    $_SESSION['fp_actor_cache'] = array('key' => $cacheKey, 'at' => time(), 'actor' => $actor);
    return $requestCache[$cacheKey] = $actor;
}

/* ------------------------------------------------------------------
   Permission checks
------------------------------------------------------------------ */

function fp_can($actor, $permission)
{
    return !empty($actor['ok']) && isset($actor['grants'][$permission]) && fp_scope_has_any($actor['grants'][$permission]);
}

/**
 * Global master data (categories, policies) may only be changed by roles whose grant covers every record.
 */
function fp_can_globally($actor, $permission)
{
    return fp_can($actor, $permission) && !empty($actor['grants'][$permission]['all']);
}

/**
 * Employee who may raise expense requests: an active office assignment with CanRequest,
 * or a create grant over all records. Returns the office IDs allowed, or true for every active office.
 */
function fp_requester_offices($actor)
{
    if (!fp_can($actor, 'create') || (int) $actor['employee_id'] <= 0) {
        return array();
    }
    if (!empty($actor['grants']['create']['all'])) {
        return true;
    }
    return $actor['request_office_ids'] ?? array();
}

function fp_is_requester($actor)
{
    $offices = fp_requester_offices($actor);
    return $offices === true || count($offices) > 0;
}

/**
 * $record keys: employee_id, created_by_employee_id, state_name, branch_id, company_id, office_id.
 */
function fp_record_in_scope($actor, $permission, $record)
{
    if (!fp_can($actor, $permission)) {
        return false;
    }
    return fp_scope_covers_record($actor, $actor['grants'][$permission], $record);
}

/**
 * Whether one scope (a permission grant, or a single role's scope) covers $record.
 */
function fp_scope_covers_record($actor, $scope, $record)
{
    if ($scope['all']) {
        return true;
    }

    $employeeId = (int) ($record['employee_id'] ?? 0);
    if ($employeeId > 0) {
        if ($scope['self'] && $employeeId === (int) $actor['employee_id']) {
            return true;
        }
        if (in_array($employeeId, $scope['employee_ids'], true)) {
            return true;
        }
    }

    $stateName = fp_normalize_state($record['state_name'] ?? '');
    if ($stateName !== '' && in_array($stateName, array_map('fp_normalize_state', $scope['state_names']), true)) {
        return true;
    }

    $branchId = (int) ($record['branch_id'] ?? 0);
    if ($branchId > 0 && in_array($branchId, $scope['branch_ids'], true)) {
        return true;
    }

    $officeId = (int) ($record['office_id'] ?? 0);
    if ($officeId > 0 && in_array($officeId, $scope['office_ids'] ?? array(), true)) {
        return true;
    }

    $companyId = (int) ($record['company_id'] ?? 0);
    return $companyId > 0 && in_array($companyId, $scope['company_ids'], true);
}

/**
 * Record-level decision including the self-approval rule for approve/pay.
 * $policy['allow_self_approval'] overrides the matrix default.
 */
function fp_can_act_on_record($actor, $permission, $record, $policy = array())
{
    if (!fp_record_in_scope($actor, $permission, $record)) {
        return array('allowed' => false, 'reason' => 'This record is outside your access scope.');
    }

    if (in_array($permission, array('approve', 'pay'), true)) {
        $matrix = fp_access_matrix();
        $allowSelf = array_key_exists('allow_self_approval', $policy) ? (bool) $policy['allow_self_approval'] : (bool) $matrix['allow_self_approval'];
        $me = (int) $actor['employee_id'];
        $isOwn = $me > 0 && ((int) ($record['employee_id'] ?? 0) === $me || (int) ($record['created_by_employee_id'] ?? 0) === $me);
        if ($isOwn && !$allowSelf) {
            return array('allowed' => false, 'reason' => 'You cannot ' . $permission . ' your own request.');
        }
    }

    return array('allowed' => true, 'reason' => '');
}

/**
 * SQL filter for list queries. $columns values are trusted column expressions from code, never user input.
 * Keys: employee_id, state_name, branch_id, company_id (client company), office_id.
 */
function fp_scope_where($conn, $actor, $permission, $columns)
{
    if (!fp_can($actor, $permission)) {
        return '1=0';
    }
    $scope = $actor['grants'][$permission];
    if ($scope['all']) {
        return '1=1';
    }

    $parts = array();
    if (!empty($columns['employee_id'])) {
        $employeeIds = $scope['employee_ids'];
        if ($scope['self'] && (int) $actor['employee_id'] > 0) {
            $employeeIds[] = (int) $actor['employee_id'];
        }
        $employeeIds = fp_unique_ints($employeeIds);
        if ($employeeIds) {
            $parts[] = $columns['employee_id'] . ' IN (' . implode(',', $employeeIds) . ')';
        }
    }
    if (!empty($columns['state_name']) && $scope['state_names']) {
        $quoted = array();
        foreach ($scope['state_names'] as $name) {
            $quoted[] = "'" . mysqli_real_escape_string($conn, fp_normalize_state($name)) . "'";
        }
        $parts[] = 'TRIM(LOWER(' . $columns['state_name'] . ')) IN (' . implode(',', $quoted) . ')';
    }
    if (!empty($columns['branch_id']) && $scope['branch_ids']) {
        $parts[] = $columns['branch_id'] . ' IN (' . implode(',', $scope['branch_ids']) . ')';
    }
    if (!empty($columns['company_id']) && $scope['company_ids']) {
        $parts[] = $columns['company_id'] . ' IN (' . implode(',', $scope['company_ids']) . ')';
    }
    if (!empty($columns['office_id']) && !empty($scope['office_ids'])) {
        $parts[] = $columns['office_id'] . ' IN (' . implode(',', $scope['office_ids']) . ')';
    }

    return $parts ? '(' . implode(' OR ', $parts) . ')' : '1=0';
}

/* ------------------------------------------------------------------
   Request guards
------------------------------------------------------------------ */

function fp_json_response($payload, $status = 200)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function fp_result($ok, $status, $message, $extra = array())
{
    return array_merge(array('ok' => $ok, 'status' => $status, 'message' => $message), $extra);
}

/**
 * Sends a result built by fp_result() as the standard { error, message, errors?, data? } JSON body.
 */
function fp_respond($result)
{
    $payload = array('error' => !$result['ok'], 'message' => $result['message']);
    foreach (array('errors', 'data') as $key) {
        if (isset($result[$key])) {
            $payload[$key] = $result[$key];
        }
    }
    fp_json_response($payload, $result['status']);
}

/**
 * Runs $work in a transaction; commits only when it returns an ok result.
 */
function fp_transaction($conn, $work)
{
    $conn->begin_transaction();
    try {
        $result = $work();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
    if (!empty($result['ok'])) {
        $conn->commit();
    } else {
        $conn->rollback();
    }
    return $result;
}

function fp_input_string($source, $key)
{
    return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : '';
}

function fp_input_int($source, $key)
{
    $value = fp_input_string($source, $key);
    return preg_match('/^-?\d{1,10}$/', $value) ? (int) $value : 0;
}

function fp_csrf_token()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (empty($_SESSION['fp_csrf_token'])) {
        $_SESSION['fp_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['fp_csrf_token'];
}

function fp_csrf_valid()
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(fp_csrf_token(), $sent);
}

/**
 * Page guard: call before any output. Pages live directly under admin/finance-performance/.
 */
function fp_require_page($conn, $permission, $global = false)
{
    $actor = fp_load_actor($conn);
    if ($actor['reason'] === 'not_logged_in') {
        header('Location: ../authentication/login.php');
        exit;
    }
    if (!($global ? fp_can_globally($actor, $permission) : fp_can($actor, $permission))) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }
    return $actor;
}

/**
 * Page guard for pages listed in the 'menu' registry; uses the same rule as the sidebar.
 */
function fp_require_menu_page($conn, $menuKey)
{
    $actor = fp_load_actor($conn);
    if ($actor['reason'] === 'not_logged_in') {
        header('Location: ../authentication/login.php');
        exit;
    }
    $menu = fp_access_matrix()['menu'];
    if (!isset($menu[$menuKey]) || !fp_menu_item_visible($actor, $menu[$menuKey])) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }
    return $actor;
}

/**
 * Endpoint guard driven by the 'actions' registry; unknown actions are denied.
 */
function fp_require_action($conn, $actionKey)
{
    $matrix = fp_access_matrix();
    if (!isset($matrix['actions'][$actionKey])) {
        fp_json_response(array('error' => true, 'message' => 'Unknown action.'), 403);
    }
    $action = $matrix['actions'][$actionKey];

    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $action['method']) {
        fp_json_response(array('error' => true, 'message' => 'Method not allowed.'), 405);
    }

    $actor = fp_load_actor($conn);
    if (!$actor['ok']) {
        $status = $actor['reason'] === 'not_logged_in' ? 401 : 403;
        fp_json_response(array('error' => true, 'message' => 'Access denied.'), $status);
    }
    if ($action['method'] === 'POST' && !fp_csrf_valid()) {
        fp_json_response(array('error' => true, 'message' => 'Your session token has expired. Please reload the page.'), 419);
    }
    $global = !empty($action['global']);
    if (!($global ? fp_can_globally($actor, $action['permission']) : fp_can($actor, $action['permission']))) {
        fp_json_response(array('error' => true, 'message' => 'You do not have permission for this action.'), 403);
    }
    if (!empty($action['requires_employee']) && (int) $actor['employee_id'] <= 0) {
        fp_json_response(array('error' => true, 'message' => 'This action needs a login linked to an active employee record.'), 403);
    }
    return $actor;
}

/**
 * Endpoint runner: strict connection, registry guard, then $handler($conn, $actor) returning an fp_result().
 */
function fp_run_action($actionKey, $handler)
{
    try {
        $conn = fp_db_connect();
        $actor = fp_require_action($conn, $actionKey);
        fp_respond($handler($conn, $actor));
    } catch (Throwable $e) {
        error_log('Finance & Performance ' . $actionKey . ': ' . $e->getMessage());
        fp_json_response(array('error' => true, 'message' => 'Technical problem. Please try again.'), 500);
    }
}

/* ------------------------------------------------------------------
   Presentation helpers
------------------------------------------------------------------ */

/**
 * 'audience': '' (permission only) | 'requester' | 'requester_or_scoped' (requester, or scope beyond own records)
 * | 'approver' (approve permission, or acting as a delegate today).
 */
function fp_menu_item_visible($actor, $item)
{
    $permission = $item['permission'];
    $allowed = empty($item['global']) ? fp_can($actor, $permission) : fp_can_globally($actor, $permission);
    if (!$allowed) {
        return false;
    }
    if (!empty($item['beyond_self']) && !fp_scope_beyond_self($actor['grants'][$permission])) {
        return false;
    }
    switch ($item['audience'] ?? '') {
        case 'requester':
            return fp_is_requester($actor);
        case 'requester_or_scoped':
            return fp_is_requester($actor) || fp_scope_beyond_self($actor['grants'][$permission]);
        case 'approver':
            return (int) $actor['employee_id'] > 0 && (fp_can($actor, 'approve') || !empty($actor['delegated_from']));
    }
    return true;
}

/**
 * Sidebar tree: top-level entries are either 'item' or 'group' (with 'children').
 * A group takes the position of its first visible entry.
 */
function fp_menu_items($actor)
{
    $nodes = array();
    if (empty($actor['ok'])) {
        return $nodes;
    }
    $matrix = fp_access_matrix();
    $moduleDir = dirname(__DIR__) . '/';
    $groupIndex = array();
    foreach ($matrix['menu'] as $key => $item) {
        if (!is_file($moduleDir . $item['file']) || !fp_menu_item_visible($actor, $item)) {
            continue;
        }
        $node = array(
            'type'  => 'item',
            'key'   => $key,
            'label' => $item['label'],
            'icon'  => $item['icon'],
            'href'  => '../finance-performance/' . $item['file'],
        );
        $groupKey = $item['group'] ?? '';
        if ($groupKey === '' || !isset($matrix['menu_groups'][$groupKey])) {
            $nodes[] = $node;
            continue;
        }
        if (!isset($groupIndex[$groupKey])) {
            $groupIndex[$groupKey] = count($nodes);
            $nodes[] = array(
                'type'     => 'group',
                'key'      => $groupKey,
                'label'    => $matrix['menu_groups'][$groupKey]['label'],
                'icon'     => $matrix['menu_groups'][$groupKey]['icon'],
                'children' => array(),
            );
        }
        $nodes[$groupIndex[$groupKey]]['children'][] = $node;
    }
    return $nodes;
}

function fp_scope_summary($scope)
{
    if ($scope['all']) {
        return 'All companies, states, branches and employees';
    }
    $parts = array();
    if ($scope['self']) {
        $parts[] = 'Own records';
    }
    if ($scope['employee_ids']) {
        $parts[] = count($scope['employee_ids']) . ' team member(s)';
    }
    if ($scope['state_names']) {
        $parts[] = 'States: ' . implode(', ', $scope['state_names']);
    }
    if ($scope['branch_ids']) {
        $parts[] = count($scope['branch_ids']) . ' client branch(es)';
    }
    if ($scope['company_ids']) {
        $parts[] = count($scope['company_ids']) . ' client company(ies)';
    }
    if (!empty($scope['office_ids'])) {
        $parts[] = count($scope['office_ids']) . ' managed branch office(s)';
    }
    return $parts ? implode('; ', $parts) : 'No records mapped';
}

function fp_actor_public_view($actor)
{
    $permissions = array();
    foreach (fp_access_matrix()['permissions'] as $permission => $label) {
        $scope = $actor['grants'][$permission] ?? fp_empty_scope();
        $permissions[$permission] = array(
            'allowed' => fp_can($actor, $permission),
            'scope'   => array(
                'all'           => $scope['all'],
                'self'          => $scope['self'],
                'team_count'    => count($scope['employee_ids']),
                'states'        => $scope['state_names'],
                'branch_count'  => count($scope['branch_ids']),
                'company_count' => count($scope['company_ids']),
                'office_count'  => count($scope['office_ids'] ?? array()),
                'summary'       => fp_scope_summary($scope),
            ),
        );
    }
    $roles = array();
    foreach ($actor['roles'] as $key => $role) {
        $roles[] = array('key' => $key, 'label' => $role['label'], 'matched_by' => $role['matched_by'], 'scope' => fp_scope_summary($role['scope']));
    }
    return array(
        'username'    => $actor['username'],
        'user_type'   => $actor['user_type'],
        'employee_id' => $actor['employee_id'],
        'request_office_count' => count($actor['request_office_ids'] ?? array()),
        'roles'       => $roles,
        'permissions' => $permissions,
    );
}
