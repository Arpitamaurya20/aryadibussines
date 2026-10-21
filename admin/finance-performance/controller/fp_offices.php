<?php
/**
 * Branch office master (branch_expense_offices) and employee assignments (branch_expense_office_employees).
 * Requires fp_access.php and fp_audit.php.
 */

const FP_OFFICE_ENTITY = 'expense_office';
const FP_ASSIGNMENT_ENTITY = 'office_assignment';

function fp_office_snapshot($row)
{
    return array(
        'OfficeCode' => (string) $row['OfficeCode'],
        'OfficeName' => (string) $row['OfficeName'],
        'CompanyID'  => (int) $row['CompanyID'],
        'StateID'    => (int) $row['StateID'],
        'CityID'     => $row['CityID'] === null ? null : (int) $row['CityID'],
        'Address'    => (string) $row['Address'],
        'IsActive'   => (int) $row['IsActive'],
    );
}

function fp_office_get($conn, $id, $lock = '')
{
    $suffix = $lock === 'update' ? ' FOR UPDATE' : ($lock === 'share' ? ' LOCK IN SHARE MODE' : '');
    $rows = fp_db_rows($conn, 'SELECT * FROM branch_expense_offices WHERE ID = ?' . $suffix, 'i', array((int) $id));
    return $rows ? $rows[0] : null;
}

function fp_office_lookups($conn)
{
    return array(
        'companies' => fp_db_rows($conn, 'SELECT ID, CompanyName FROM quote_company_details WHERE IsActive = 1 ORDER BY CompanyName'),
        'states'    => fp_db_rows($conn, 'SELECT ID, StateName FROM state WHERE IsActive = 1 ORDER BY StateName'),
        'cities'    => fp_db_rows($conn, 'SELECT CityId AS ID, CityName, StateID FROM citydata WHERE status = 1 ORDER BY CityName'),
    );
}

function fp_office_list($conn)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT o.ID, o.OfficeCode, o.OfficeName, o.CompanyID, o.StateID, o.CityID, o.Address, o.IsActive, o.RowVersion,
                COALESCE(o.UpdatedAt, o.CreatedAt) AS LastChangedAt,
                q.CompanyName, s.StateName, c.CityName,
                (SELECT COUNT(*) FROM branch_expense_office_employees oe WHERE oe.OfficeID = o.ID AND oe.IsActive = 1 AND oe.CanRequest = 1) AS RequesterCount,
                (SELECT COUNT(*) FROM branch_expense_office_employees oe WHERE oe.OfficeID = o.ID AND oe.IsActive = 1 AND oe.IsManager = 1) AS ManagerCount,
                (SELECT COUNT(*) FROM branch_expense_requests r WHERE r.OfficeID = o.ID) AS RequestCount
         FROM branch_expense_offices o
         LEFT JOIN quote_company_details q ON q.ID = o.CompanyID
         LEFT JOIN state s ON s.ID = o.StateID
         LEFT JOIN citydata c ON c.CityId = o.CityID
         ORDER BY o.IsActive DESC, s.StateName, o.OfficeName'
    );
    foreach ($rows as &$row) {
        foreach (array('ID', 'CompanyID', 'StateID', 'IsActive', 'RowVersion', 'RequesterCount', 'ManagerCount', 'RequestCount') as $field) {
            $row[$field] = (int) $row[$field];
        }
        $row['CityID'] = $row['CityID'] === null ? 0 : (int) $row['CityID'];
        $row['CompanyName'] = (string) $row['CompanyName'];
        $row['StateName'] = (string) $row['StateName'];
        $row['CityName'] = (string) $row['CityName'];
        $row['ScopeLocked'] = $row['RequestCount'] > 0;
    }
    unset($row);
    return $rows;
}

function fp_office_save($conn, $actor, $post)
{
    $in = array(
        'ID'         => fp_input_int($post, 'ID'),
        'RowVersion' => fp_input_int($post, 'RowVersion'),
        'OfficeCode' => strtoupper(fp_input_string($post, 'OfficeCode')),
        'OfficeName' => preg_replace('/\s+/u', ' ', fp_input_string($post, 'OfficeName')),
        'CompanyID'  => fp_input_int($post, 'CompanyID'),
        'StateID'    => fp_input_int($post, 'StateID'),
        'CityID'     => fp_input_int($post, 'CityID'),
        'Address'    => fp_input_string($post, 'Address'),
    );
    $errors = array();
    if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,19}$/', $in['OfficeCode'])) {
        $errors['OfficeCode'] = 'Use 2-20 characters: letters, numbers, hyphen or underscore.';
    }
    $nameLength = mb_strlen($in['OfficeName']);
    if ($nameLength < 2 || $nameLength > 150) {
        $errors['OfficeName'] = 'Name must be 2-150 characters.';
    }
    if ($in['CompanyID'] <= 0) {
        $errors['CompanyID'] = 'Choose the legal entity.';
    }
    if ($in['StateID'] <= 0) {
        $errors['StateID'] = 'Choose the state.';
    }
    if (mb_strlen($in['Address']) > 500) {
        $errors['Address'] = 'Address can be at most 500 characters.';
    }
    if ($errors) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
    }

    try {
        return fp_transaction($conn, function () use ($conn, $actor, $in) {
            $existing = null;
            if ($in['ID'] > 0) {
                $existing = fp_office_get($conn, $in['ID'], 'update');
                if (!$existing) {
                    return fp_result(false, 404, 'This office no longer exists. Reload the page.');
                }
                if ((int) $existing['RowVersion'] !== $in['RowVersion']) {
                    return fp_result(false, 409, 'Someone else changed this office. Reload the page and try again.');
                }
            }

            $errors = array();
            if (fp_db_rows($conn, 'SELECT ID FROM branch_expense_offices WHERE OfficeCode = ? AND ID <> ?', 'si', array($in['OfficeCode'], $in['ID']))) {
                $errors['OfficeCode'] = 'Another office already uses this code.';
            }
            $companyChanged = !$existing || (int) $existing['CompanyID'] !== $in['CompanyID'];
            $stateChanged = !$existing || (int) $existing['StateID'] !== $in['StateID'];
            if ($companyChanged && !fp_db_rows($conn, 'SELECT ID FROM quote_company_details WHERE ID = ? AND IsActive = 1', 'i', array($in['CompanyID']))) {
                $errors['CompanyID'] = 'Choose an active legal entity.';
            }
            if ($stateChanged && !fp_db_rows($conn, 'SELECT ID FROM state WHERE ID = ? AND IsActive = 1', 'i', array($in['StateID']))) {
                $errors['StateID'] = 'Choose an active state.';
            }
            if ($in['CityID'] > 0 && !fp_db_rows($conn, 'SELECT CityId FROM citydata WHERE CityId = ? AND StateID = ?', 'ii', array($in['CityID'], $in['StateID']))) {
                $errors['CityID'] = 'The city does not belong to the selected state.';
            }
            if ($existing && ($companyChanged || $stateChanged)) {
                $used = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt FROM branch_expense_requests WHERE OfficeID = ?', 'i', array($in['ID']));
                if ((int) $used[0]['Cnt'] > 0) {
                    $errors[$companyChanged ? 'CompanyID' : 'StateID'] = 'Legal entity and state cannot change once requests exist for this office. Create a new office instead.';
                }
            }
            if ($errors) {
                return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
            }

            $cityId = $in['CityID'] > 0 ? $in['CityID'] : null;
            $now = date('Y-m-d H:i:s');
            $userId = (int) $actor['user_id'];
            $employeeId = (int) $actor['employee_id'];

            if (!$existing) {
                $stmt = $conn->prepare(
                    'INSERT INTO branch_expense_offices
                        (OfficeCode, OfficeName, CompanyID, StateID, CityID, Address, IsActive, RowVersion, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
                     VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?, ?, ?)'
                );
                $stmt->bind_param('ssiiisiis', $in['OfficeCode'], $in['OfficeName'], $in['CompanyID'], $in['StateID'], $cityId, $in['Address'], $userId, $employeeId, $now);
                $stmt->execute();
                $id = (int) $conn->insert_id;
                $stmt->close();
                fp_audit_log($conn, $actor, FP_OFFICE_ENTITY, $id, 'create', null, fp_office_snapshot(fp_office_get($conn, $id)));
                return fp_result(true, 200, 'Office created.', array('data' => array('ID' => $id)));
            }

            $old = fp_office_snapshot($existing);
            $new = array_merge($old, array(
                'OfficeCode' => $in['OfficeCode'], 'OfficeName' => $in['OfficeName'], 'CompanyID' => $in['CompanyID'],
                'StateID' => $in['StateID'], 'CityID' => $cityId, 'Address' => $in['Address'],
            ));
            if (!fp_audit_changed_fields($old, $new)) {
                return fp_result(true, 200, 'No changes to save.', array('data' => array('ID' => $in['ID'])));
            }
            $stmt = $conn->prepare(
                'UPDATE branch_expense_offices
                 SET OfficeCode = ?, OfficeName = ?, CompanyID = ?, StateID = ?, CityID = ?, Address = ?,
                     RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
                 WHERE ID = ? AND RowVersion = ?'
            );
            $stmt->bind_param('ssiiisiisii', $in['OfficeCode'], $in['OfficeName'], $in['CompanyID'], $in['StateID'], $cityId, $in['Address'], $userId, $employeeId, $now, $in['ID'], $in['RowVersion']);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected !== 1) {
                return fp_result(false, 409, 'Someone else changed this office. Reload the page and try again.');
            }
            fp_audit_log($conn, $actor, FP_OFFICE_ENTITY, $in['ID'], 'update', $old, $new);
            return fp_result(true, 200, 'Office updated.', array('data' => array('ID' => $in['ID'])));
        });
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1062) {
            return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => array('OfficeCode' => 'Another office already uses this code.')));
        }
        throw $e;
    }
}

function fp_office_set_status($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $status = fp_input_string($post, 'IsActive');
    $reason = fp_input_string($post, 'Reason');
    if ($id <= 0 || !in_array($status, array('0', '1'), true)) {
        return fp_result(false, 422, 'Invalid request.');
    }
    $activate = $status === '1';
    if ((!$activate && mb_strlen($reason) < 5) || mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'Please give a reason for deactivating (5-500 characters).', array('errors' => array('Reason' => 'Required.')));
    }

    return fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $activate, $reason) {
        $existing = fp_office_get($conn, $id, 'update');
        if (!$existing) {
            return fp_result(false, 404, 'This office no longer exists. Reload the page.');
        }
        if ((int) $existing['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'Someone else changed this office. Reload the page and try again.');
        }
        $isActive = $activate ? 1 : 0;
        if ((int) $existing['IsActive'] === $isActive) {
            return fp_result(false, 422, 'The office is already ' . ($activate ? 'active' : 'inactive') . '.');
        }
        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare(
            'UPDATE branch_expense_offices SET IsActive = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND RowVersion = ?'
        );
        $stmt->bind_param('iiisii', $isActive, $userId, $employeeId, $now, $id, $rowVersion);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected !== 1) {
            return fp_result(false, 409, 'Someone else changed this office. Reload the page and try again.');
        }
        $old = fp_office_snapshot($existing);
        $new = array_merge($old, array('IsActive' => $isActive));
        fp_audit_log($conn, $actor, FP_OFFICE_ENTITY, $id, $activate ? 'activate' : 'deactivate', $old, $new, $reason);
        return fp_result(true, 200, $activate ? 'Office activated.' : 'Office deactivated. Its employees can no longer raise requests against it.');
    });
}

/* ------------------------------------------------------------------
   Employee assignments
------------------------------------------------------------------ */

function fp_office_assignments($conn, $officeId)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT oe.ID, oe.EmployeeID, oe.CanRequest, oe.IsManager, oe.IsActive, oe.RowVersion,
                e.Name AS EmployeeName, e.EmployeeNumber, e.Designation, e.IsActive AS EmployeeIsActive,
                (SELECT COUNT(*) FROM branch_expense_office_employees x
                  INNER JOIN branch_expense_offices xo ON xo.ID = x.OfficeID AND xo.IsActive = 1
                  WHERE x.EmployeeID = oe.EmployeeID AND x.IsActive = 1 AND x.CanRequest = 1) AS RequestOfficeCount
         FROM branch_expense_office_employees oe
         LEFT JOIN employees e ON e.ID = oe.EmployeeID
         WHERE oe.OfficeID = ?
         ORDER BY oe.IsActive DESC, oe.IsManager DESC, e.Name',
        'i',
        array((int) $officeId)
    );
    foreach ($rows as &$row) {
        foreach (array('ID', 'EmployeeID', 'CanRequest', 'IsManager', 'IsActive', 'RowVersion', 'EmployeeIsActive', 'RequestOfficeCount') as $field) {
            $row[$field] = (int) $row[$field];
        }
        $row['EmployeeName'] = (string) $row['EmployeeName'];
        $row['EmployeeNumber'] = (string) $row['EmployeeNumber'];
        $row['Designation'] = (string) $row['Designation'];
    }
    unset($row);
    return $rows;
}

function fp_employee_search($conn, $term)
{
    $term = mb_substr(trim($term), 0, 50);
    if (mb_strlen($term) < 2) {
        return array();
    }
    $like = '%' . addcslashes($term, '%_\\') . '%';
    $vendorFilter = fp_access_matrix()['exclude_vendor_employees'] ? " AND Vendor <> '1'" : '';
    return fp_db_rows(
        $conn,
        'SELECT ID, Name, EmployeeNumber, Designation, State, City FROM employees
         WHERE IsActive = 1' . $vendorFilter . ' AND (Name LIKE ? OR EmployeeNumber LIKE ?)
         ORDER BY Name LIMIT 20',
        'ss',
        array($like, $like)
    );
}

function fp_office_assignment_save($conn, $actor, $post)
{
    $officeId = fp_input_int($post, 'OfficeID');
    $employeeId = fp_input_int($post, 'EmployeeID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $canRequest = fp_input_string($post, 'CanRequest') === '1' ? 1 : 0;
    $isManager = fp_input_string($post, 'IsManager') === '1' ? 1 : 0;
    $isActive = fp_input_string($post, 'IsActive') === '0' ? 0 : 1;
    $reason = fp_input_string($post, 'Reason');

    if ($officeId <= 0 || $employeeId <= 0) {
        return fp_result(false, 422, 'Choose an office and an employee.');
    }
    if ($isActive && !$canRequest && !$isManager) {
        return fp_result(false, 422, 'Tick "Can raise requests", "Branch manager" or both.');
    }
    if (mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'The reason can be at most 500 characters.');
    }

    try {
        return fp_transaction($conn, function () use ($conn, $actor, $officeId, $employeeId, $rowVersion, $canRequest, $isManager, $isActive, $reason) {
            $office = fp_office_get($conn, $officeId, 'share');
            if (!$office) {
                return fp_result(false, 404, 'This office no longer exists. Reload the page.');
            }
            $existingRows = fp_db_rows($conn, 'SELECT * FROM branch_expense_office_employees WHERE OfficeID = ? AND EmployeeID = ? FOR UPDATE', 'ii', array($officeId, $employeeId));
            $existing = $existingRows ? $existingRows[0] : null;

            if ($isActive) {
                $vendorFilter = fp_access_matrix()['exclude_vendor_employees'] ? " AND Vendor <> '1'" : '';
                if (!fp_db_rows($conn, 'SELECT ID FROM employees WHERE ID = ? AND IsActive = 1' . $vendorFilter, 'i', array($employeeId))) {
                    return fp_result(false, 422, 'Only active, non-vendor employees can be assigned.');
                }
                if (!$existing && (int) $office['IsActive'] !== 1) {
                    return fp_result(false, 422, 'Activate the office before assigning employees.');
                }
            }

            $now = date('Y-m-d H:i:s');
            $userId = (int) $actor['user_id'];
            $new = array('OfficeID' => $officeId, 'EmployeeID' => $employeeId, 'CanRequest' => $canRequest, 'IsManager' => $isManager, 'IsActive' => $isActive);

            if (!$existing) {
                if (!$isActive) {
                    return fp_result(false, 422, 'This employee is not assigned to the office.');
                }
                $stmt = $conn->prepare(
                    'INSERT INTO branch_expense_office_employees (OfficeID, EmployeeID, CanRequest, IsManager, IsActive, RowVersion, CreatedByUserID, CreatedAt)
                     VALUES (?, ?, ?, ?, 1, 1, ?, ?)'
                );
                $stmt->bind_param('iiiiis', $officeId, $employeeId, $canRequest, $isManager, $userId, $now);
                $stmt->execute();
                $id = (int) $conn->insert_id;
                $stmt->close();
                fp_audit_log($conn, $actor, FP_ASSIGNMENT_ENTITY, $id, 'create', null, $new, $reason);
                return fp_result(true, 200, 'Employee assigned to the office.');
            }

            if ($rowVersion > 0 && (int) $existing['RowVersion'] !== $rowVersion) {
                return fp_result(false, 409, 'Someone else changed this assignment. Reload and try again.');
            }
            $old = array('OfficeID' => $officeId, 'EmployeeID' => $employeeId, 'CanRequest' => (int) $existing['CanRequest'], 'IsManager' => (int) $existing['IsManager'], 'IsActive' => (int) $existing['IsActive']);
            if (!fp_audit_changed_fields($old, $new)) {
                return fp_result(true, 200, 'No changes to save.');
            }
            if (!$isActive && mb_strlen($reason) < 5) {
                return fp_result(false, 422, 'Please give a reason for removing the assignment (at least 5 characters).', array('errors' => array('Reason' => 'Required.')));
            }
            $id = (int) $existing['ID'];
            $currentVersion = (int) $existing['RowVersion'];
            $stmt = $conn->prepare(
                'UPDATE branch_expense_office_employees
                 SET CanRequest = ?, IsManager = ?, IsActive = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedAt = ?
                 WHERE ID = ? AND RowVersion = ?'
            );
            $stmt->bind_param('iiiisii', $canRequest, $isManager, $isActive, $userId, $now, $id, $currentVersion);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected !== 1) {
                return fp_result(false, 409, 'Someone else changed this assignment. Reload and try again.');
            }
            $action = !$isActive ? 'deactivate' : ((int) $existing['IsActive'] === 0 ? 'activate' : 'update');
            fp_audit_log($conn, $actor, FP_ASSIGNMENT_ENTITY, $id, $action, $old, $new, $reason);
            return fp_result(true, 200, $isActive ? 'Assignment saved.' : 'Assignment removed.');
        });
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1062) {
            return fp_result(false, 409, 'This employee was just assigned by someone else. Reload and try again.');
        }
        throw $e;
    }
}
