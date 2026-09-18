<?php

function getEmployeeAssetCategories()
{
    return array(
        'Laptop',
        'Desktop',
        'Monitor',
        'Mobile Phone',
        'Tablet',
        'Technician Tools',
        'Safety Equipment',
        'Vehicle',
        'Access Card / ID',
        'Software License',
        'Headset / Accessories',
        'Other',
    );
}

function getEmployeeAssetHoldStatuses()
{
    return array(
        'Returned' => 'Returned from Employee',
        'Lost' => 'Lost / Missing',
        'Damaged' => 'Damaged',
        'Retired' => 'Retired / Written Off',
    );
}

function employeeAssetAssignedSqlCondition($alias = '')
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    return "({$prefix}AssetHoldStatus IS NULL OR {$prefix}AssetHoldStatus = '' OR {$prefix}AssetHoldStatus = 'Assigned')";
}

function employeeAssetTableHasHoldStatusColumn($conn)
{
    static $hasColumn = null;
    if ($hasColumn !== null) {
        return $hasColumn;
    }
    $result = mysqli_query($conn, "SHOW COLUMNS FROM employee_company_assets LIKE 'AssetHoldStatus'");
    $hasColumn = ($result && mysqli_num_rows($result) > 0);
    return $hasColumn;
}

function employeeAssetActiveAssignedSql($alias = '')
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    $sql = "{$prefix}IsActive = 1";
    return $sql;
}

function employeeAssetAssignedOnlySql($conn, $alias = '')
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    $sql = employeeAssetActiveAssignedSql($alias);
    if (employeeAssetTableHasHoldStatusColumn($conn)) {
        $sql .= ' AND ' . employeeAssetAssignedSqlCondition($alias);
    }
    return $sql;
}

function formatEmployeeAssetHoldStatusBadge($status)
{
    $status = (string) $status;
    $labels = array_merge(array('Assigned' => 'With Employee'), getEmployeeAssetHoldStatuses());
    $label = isset($labels[$status]) ? $labels[$status] : $status;
    if ($status === 'Assigned' || $status === '') {
        return '<span class="badge badge-primary">With Employee</span>';
    }
    if ($status === 'Returned') {
        return '<span class="badge badge-info">' . htmlspecialchars($label) . '</span>';
    }
    if ($status === 'Lost') {
        return '<span class="badge badge-danger">' . htmlspecialchars($label) . '</span>';
    }
    if ($status === 'Damaged') {
        return '<span class="badge badge-warning">' . htmlspecialchars($label) . '</span>';
    }
    return '<span class="badge badge-secondary">' . htmlspecialchars($label) . '</span>';
}

function formatEmployeeAssetAckBadge($ackStatus, $holdStatus = 'Assigned')
{
    if ($holdStatus !== 'Assigned' && $holdStatus !== '' && $holdStatus !== null) {
        return formatEmployeeAssetHoldStatusBadge($holdStatus);
    }
    if ($ackStatus === 'Pending') {
        return '<span class="badge badge-warning">Pending Acknowledgement</span>';
    }
    return '<span class="badge badge-success">Acknowledged</span>';
}

function hasEmployeeAssetAckAdminAccess($roles)
{
    if (isset($_SESSION['UserType'])) {
        $userType = (string) $_SESSION['UserType'];
        if (in_array($userType, array('Admin', 'Super Admin'), true)) {
            return true;
        }
    }
    if (!isset($roles['EmployeeRoles']) || !is_array($roles['EmployeeRoles'])) {
        return false;
    }
    $allowed = array('HR', 'Finance', 'Accounts', 'CFO', 'Super Admin', 'Admin');
    foreach ($roles['EmployeeRoles'] as $role) {
        if (in_array($role, $allowed, true)) {
            return true;
        }
    }
    return false;
}

function getEmployeeAssetAckShareSecret()
{
    $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'techxpert';
    return hash('sha256', 'employee_asset_ack_' . $host . '_techxpert_v1');
}

function createEmployeeAssetAckShareToken($employeeId, $ttlSeconds = 2592000)
{
    $employeeId = (int) $employeeId;
    $expiry = time() + max(3600, (int) $ttlSeconds);
    $payload = $employeeId . '.' . $expiry;
    $sig = hash_hmac('sha256', $payload, getEmployeeAssetAckShareSecret());
    return rtrim(strtr(base64_encode($payload . '.' . $sig), '+/', '-_'), '=');
}

function verifyEmployeeAssetAckShareToken($token)
{
    $token = trim((string) $token);
    if ($token === '') {
        return array('valid' => false, 'message' => 'Missing link token');
    }
    $raw = base64_decode(strtr($token, '-_', '+/'), true);
    if ($raw === false) {
        return array('valid' => false, 'message' => 'Invalid link');
    }
    $parts = explode('.', $raw);
    if (count($parts) !== 3) {
        return array('valid' => false, 'message' => 'Invalid link format');
    }
    $employeeId = (int) $parts[0];
    $expiry = (int) $parts[1];
    $sig = $parts[2];
    $payload = $parts[0] . '.' . $parts[1];
    $expected = hash_hmac('sha256', $payload, getEmployeeAssetAckShareSecret());
    if (!hash_equals($expected, $sig)) {
        return array('valid' => false, 'message' => 'Invalid or tampered link');
    }
    if (time() > $expiry) {
        return array('valid' => false, 'message' => 'This link has expired. Please contact HR/Admin for a new link.');
    }
    if ($employeeId <= 0) {
        return array('valid' => false, 'message' => 'Invalid employee reference');
    }
    return array(
        'valid' => true,
        'employee_id' => $employeeId,
        'expiry' => $expiry,
    );
}

function getEmployeeAssetAckBaseUrl()
{
    if (isset($_SERVER['HTTP_HOST'], $_SERVER['SCRIPT_NAME'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $script = str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']);
        if (preg_match('#^(.*)/admin/#', $script, $matches)) {
            return rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . $matches[1], '/');
        }
        if (preg_match('#^(.*)/employee-asset-ack-form\.php#', $script, $matches)) {
            return rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . $matches[1], '/');
        }
        if (preg_match('#^(.*)/api/#', $script, $matches)) {
            return rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . $matches[1], '/');
        }
    }
    if (defined('FRONT_SITE_PATH')) {
        return rtrim(FRONT_SITE_PATH, '/');
    }
    return '';
}

function buildEmployeeAssetAckPublicUrl($employeeId)
{
    $token = createEmployeeAssetAckShareToken($employeeId);
    $base = getEmployeeAssetAckBaseUrl();
    return $base . '/employee-asset-ack-form.php?token=' . urlencode($token);
}

function getEmployeeAssetAckEmployee($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return null;
    }
    $sql = "SELECT ID, Name, EmployeeNumber, Department, Designation, State, Email, ContactNumber
            FROM employees
            WHERE ID = $employeeId AND IsActive = 1
            LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return null;
    }
    return mysqli_fetch_assoc($result) ?: null;
}

function getEmployeeCompanyAssets($conn, $employeeId, $assignedOnly = true, $ackStatus = '')
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return array();
    }
    $where = "WHERE EmployeeID = $employeeId AND IsActive = 1";
    if ($assignedOnly) {
        if (employeeAssetTableHasHoldStatusColumn($conn)) {
            $where .= ' AND ' . employeeAssetAssignedSqlCondition();
        }
    }
    if ($ackStatus !== '') {
        $ackStatus = mysqli_real_escape_string($conn, $ackStatus);
        $where .= " AND AcknowledgementStatus = '$ackStatus'";
    }
    $sql = "SELECT * FROM employee_company_assets $where ORDER BY AllocationDate DESC, ID DESC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return array();
    }
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function getEmployeeCompanyAssetHistory($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return array();
    }
    $where = "WHERE EmployeeID = $employeeId AND IsActive = 1";
    if (employeeAssetTableHasHoldStatusColumn($conn)) {
        $where .= " AND NOT (" . employeeAssetAssignedSqlCondition() . ")";
    } else {
        return array();
    }
    $sql = "SELECT * FROM employee_company_assets $where ORDER BY HoldStatusDate DESC, ID DESC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return array();
    }
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function getPendingEmployeeCompanyAssets($conn, $employeeId)
{
    return getEmployeeCompanyAssets($conn, $employeeId, true, 'Pending');
}

function employeeHasPendingAssetAcknowledgement($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return false;
    }
    $sql = "SELECT COUNT(*) AS cnt
            FROM employee_company_assets
            WHERE EmployeeID = $employeeId
              AND IsActive = 1
              AND AcknowledgementStatus = 'Pending'";
    if (employeeAssetTableHasHoldStatusColumn($conn)) {
        $sql .= ' AND ' . employeeAssetAssignedSqlCondition();
    }
    $sql .= " LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return false;
    }
    $row = mysqli_fetch_assoc($result);
    return ((int) ($row['cnt'] ?? 0)) > 0;
}

function buildEmployeeAssetAckListFilterSql($conn, $filters)
{
    $where = " WHERE e.IsActive = 1 AND e.Vendor = 0 ";
    if (!empty($filters['employee_id']) && (int) $filters['employee_id'] > 0) {
        $where .= ' AND e.ID = ' . (int) $filters['employee_id'];
    }
    if (!empty($filters['status']) && $filters['status'] !== '-1') {
        $status = mysqli_real_escape_string($conn, $filters['status']);
        if ($status === 'Pending') {
            $where .= " AND EXISTS (
                SELECT 1 FROM employee_company_assets a
                WHERE a.EmployeeID = e.ID AND a.IsActive = 1 AND a.AcknowledgementStatus = 'Pending'";
            if (employeeAssetTableHasHoldStatusColumn($conn)) {
                $where .= ' AND ' . employeeAssetAssignedSqlCondition('a');
            }
            $where .= ")";
        } elseif ($status === 'Acknowledged') {
            $where .= " AND EXISTS (
                SELECT 1 FROM employee_company_assets a
                WHERE a.EmployeeID = e.ID AND a.IsActive = 1";
            if (employeeAssetTableHasHoldStatusColumn($conn)) {
                $where .= ' AND ' . employeeAssetAssignedSqlCondition('a');
            }
            $where .= ") AND NOT EXISTS (
                SELECT 1 FROM employee_company_assets a2
                WHERE a2.EmployeeID = e.ID AND a2.IsActive = 1 AND a2.AcknowledgementStatus = 'Pending'";
            if (employeeAssetTableHasHoldStatusColumn($conn)) {
                $where .= ' AND ' . employeeAssetAssignedSqlCondition('a2');
            }
            $where .= ")";
        } elseif ($status === 'NoAssets') {
            $where .= " AND NOT EXISTS (
                SELECT 1 FROM employee_company_assets a
                WHERE a.EmployeeID = e.ID AND a.IsActive = 1";
            if (employeeAssetTableHasHoldStatusColumn($conn)) {
                $where .= ' AND ' . employeeAssetAssignedSqlCondition('a');
            }
            $where .= ")";
        } elseif ($status === 'HasAssets') {
            $where .= " AND EXISTS (
                SELECT 1 FROM employee_company_assets a
                WHERE a.EmployeeID = e.ID AND a.IsActive = 1";
            if (employeeAssetTableHasHoldStatusColumn($conn)) {
                $where .= ' AND ' . employeeAssetAssignedSqlCondition('a');
            }
            $where .= ")";
        }
    }
    if (!empty($filters['search'])) {
        $search = mysqli_real_escape_string($conn, trim($filters['search']));
        $where .= " AND (e.Name LIKE '%$search%' OR e.EmployeeNumber LIKE '%$search%' OR e.Department LIKE '%$search%' OR e.Designation LIKE '%$search%')";
    }
    return $where;
}

function getEmployeeAssetAckSummaryCounts($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    $assignedCondition = employeeAssetTableHasHoldStatusColumn($conn)
        ? employeeAssetAssignedSqlCondition()
        : '1=1';
    $sql = "SELECT
                SUM(CASE WHEN IsActive = 1 AND ($assignedCondition) THEN 1 ELSE 0 END) AS total_assets,
                SUM(CASE WHEN IsActive = 1 AND ($assignedCondition) AND AcknowledgementStatus = 'Pending' THEN 1 ELSE 0 END) AS pending_assets,
                SUM(CASE WHEN IsActive = 1 AND ($assignedCondition) AND AcknowledgementStatus = 'Acknowledged' THEN 1 ELSE 0 END) AS acknowledged_assets,
                SUM(CASE WHEN IsActive = 1 AND NOT ($assignedCondition) THEN 1 ELSE 0 END) AS history_assets
            FROM employee_company_assets
            WHERE EmployeeID = $employeeId";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return array('total_assets' => 0, 'pending_assets' => 0, 'acknowledged_assets' => 0, 'history_assets' => 0);
    }
    $row = mysqli_fetch_assoc($result);
    return array(
        'total_assets' => (int) ($row['total_assets'] ?? 0),
        'pending_assets' => (int) ($row['pending_assets'] ?? 0),
        'acknowledged_assets' => (int) ($row['acknowledged_assets'] ?? 0),
        'history_assets' => (int) ($row['history_assets'] ?? 0),
    );
}

function saveEmployeeCompanyAssets($conn, $employeeId, $assets, $source = 'Admin', $createdByEmployeeId = null)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0 || !is_array($assets) || empty($assets)) {
        return array('error' => true, 'message' => 'Employee and at least one asset are required.');
    }
    $employee = getEmployeeAssetAckEmployee($conn, $employeeId);
    if (!$employee) {
        return array('error' => true, 'message' => 'Employee not found.');
    }

    $saved = 0;
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $source = mysqli_real_escape_string($conn, $source);
    $createdBy = $createdByEmployeeId !== null ? (int) $createdByEmployeeId : 'NULL';

    foreach ($assets as $asset) {
        if (!is_array($asset)) {
            continue;
        }
        $category = trim((string) ($asset['category'] ?? 'Other'));
        $description = trim((string) ($asset['description'] ?? ''));
        $serial = trim((string) ($asset['serial'] ?? ''));
        $allocationDate = trim((string) ($asset['allocation_date'] ?? ''));
        $remarks = trim((string) ($asset['remarks'] ?? ''));

        if ($description === '' || $allocationDate === '') {
            continue;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $allocationDate)) {
            continue;
        }

        $category = mysqli_real_escape_string($conn, $category !== '' ? $category : 'Other');
        $description = mysqli_real_escape_string($conn, $description);
        $serial = mysqli_real_escape_string($conn, $serial);
        $allocationDate = mysqli_real_escape_string($conn, $allocationDate);
        $remarks = mysqli_real_escape_string($conn, $remarks);

        if (employeeAssetTableHasHoldStatusColumn($conn)) {
            $sql = "INSERT INTO employee_company_assets
                (EmployeeID, AssetCategory, AssetDescription, AssetSerialNumber, AllocationDate,
                 AcknowledgementStatus, AssetHoldStatus, Source, CreatedByEmployeeID, Remarks, IsActive, CreatedDate, CreatedTime)
                VALUES
                ($employeeId, '$category', '$description', '$serial', '$allocationDate',
                 'Pending', 'Assigned', '$source', $createdBy, '$remarks', 1, '$date', '$time')";
        } else {
            $sql = "INSERT INTO employee_company_assets
                (EmployeeID, AssetCategory, AssetDescription, AssetSerialNumber, AllocationDate,
                 AcknowledgementStatus, Source, CreatedByEmployeeID, Remarks, IsActive, CreatedDate, CreatedTime)
                VALUES
                ($employeeId, '$category', '$description', '$serial', '$allocationDate',
                 'Pending', '$source', $createdBy, '$remarks', 1, '$date', '$time')";
        }
        if (mysqli_query($conn, $sql)) {
            $saved++;
        }
    }

    if ($saved === 0) {
        return array('error' => true, 'message' => 'No valid asset rows were saved. Check required fields.');
    }
    return array('error' => false, 'message' => $saved . ' asset(s) saved successfully.', 'saved' => $saved);
}

function acknowledgeEmployeeCompanyAssets($conn, $employeeId, $assetIds, $source = 'EmployeePortal', $ip = '')
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0 || !is_array($assetIds) || empty($assetIds)) {
        return array('error' => true, 'message' => 'Select at least one asset to acknowledge.');
    }

    $ids = array();
    foreach ($assetIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    if (empty($ids)) {
        return array('error' => true, 'message' => 'Invalid asset selection.');
    }

    $idList = implode(',', $ids);
    $now = date('Y-m-d H:i:s');
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $ip = mysqli_real_escape_string($conn, $ip);
    $source = mysqli_real_escape_string($conn, $source);

    $sql = "UPDATE employee_company_assets
            SET AcknowledgementStatus = 'Acknowledged',
                AcknowledgedDate = '$now',
                AcknowledgedIP = '$ip',
                Source = '$source',
                UpdatedDate = '$date',
                UpdatedTime = '$time'
            WHERE ID IN ($idList)
              AND EmployeeID = $employeeId
              AND IsActive = 1
              AND AcknowledgementStatus = 'Pending'";
    if (employeeAssetTableHasHoldStatusColumn($conn)) {
        $sql .= ' AND ' . employeeAssetAssignedSqlCondition();
    }
    if (!mysqli_query($conn, $sql)) {
        return array('error' => true, 'message' => 'Failed to acknowledge assets.');
    }
    $updated = mysqli_affected_rows($conn);
    if ($updated <= 0) {
        return array('error' => true, 'message' => 'No pending assets were updated.');
    }
    return array('error' => false, 'message' => $updated . ' asset(s) acknowledged successfully.', 'updated' => $updated);
}

function updateEmployeeCompanyAssetHoldStatus($conn, $assetId, $employeeId, $holdStatus, $remarks = '', $updatedBy = '')
{
    $assetId = (int) $assetId;
    $employeeId = (int) $employeeId;
    if ($assetId <= 0) {
        return array('error' => true, 'message' => 'Invalid asset.');
    }
    if (!employeeAssetTableHasHoldStatusColumn($conn)) {
        return array('error' => true, 'message' => 'Asset status tracking is not enabled. Please run the latest SQL migration.');
    }
    $allowed = getEmployeeAssetHoldStatuses();
    if (!isset($allowed[$holdStatus])) {
        return array('error' => true, 'message' => 'Invalid asset status selected.');
    }
    $holdStatus = mysqli_real_escape_string($conn, $holdStatus);
    $remarks = mysqli_real_escape_string($conn, trim((string) $remarks));
    $updatedBy = mysqli_real_escape_string($conn, trim((string) $updatedBy));
    $now = date('Y-m-d H:i:s');
    $date = date('Y-m-d');
    $time = date('H:i:s');

    $where = "ID = $assetId AND EmployeeID = $employeeId AND IsActive = 1 AND " . employeeAssetAssignedSqlCondition();
    $sql = "UPDATE employee_company_assets
            SET AssetHoldStatus = '$holdStatus',
                HoldStatusDate = '$now',
                HoldStatusRemarks = '$remarks',
                HoldStatusUpdatedBy = '$updatedBy',
                UpdatedDate = '$date',
                UpdatedTime = '$time'
            WHERE $where";
    if (!mysqli_query($conn, $sql) || mysqli_affected_rows($conn) <= 0) {
        return array('error' => true, 'message' => 'Asset status could not be updated.');
    }
    $label = $allowed[$holdStatus];
    return array('error' => false, 'message' => 'Asset marked as: ' . $label . '.');
}

function softDeleteEmployeeCompanyAsset($conn, $assetId, $employeeId = 0)
{
    $assetId = (int) $assetId;
    $employeeId = (int) $employeeId;
    if ($assetId <= 0) {
        return array('error' => true, 'message' => 'Invalid asset.');
    }
    $where = "ID = $assetId";
    if ($employeeId > 0) {
        $where .= " AND EmployeeID = $employeeId";
    }
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $sql = "UPDATE employee_company_assets
            SET IsActive = 0, UpdatedDate = '$date', UpdatedTime = '$time'
            WHERE $where AND IsActive = 1";
    if (!mysqli_query($conn, $sql) || mysqli_affected_rows($conn) <= 0) {
        return array('error' => true, 'message' => 'Asset could not be deleted.');
    }
    return array('error' => false, 'message' => 'Asset removed successfully.');
}

function formatEmployeeAssetAckStatusBadge($pending, $total)
{
    if ($total <= 0) {
        return '<span class="badge badge-secondary">No Assets</span>';
    }
    if ($pending > 0) {
        return '<span class="badge badge-warning">Pending (' . (int) $pending . ')</span>';
    }
    return '<span class="badge badge-success">Acknowledged</span>';
}

function sendEmployeeAssetAckJson($payload)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}
