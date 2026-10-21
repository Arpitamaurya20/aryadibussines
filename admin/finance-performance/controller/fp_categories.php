<?php
/**
 * Expense category master (branch_expense_categories).
 * Requires fp_access.php and fp_audit.php.
 */

const FP_CATEGORY_ENTITY = 'expense_category';

/**
 * Transaction tables that reference a category; each is checked only once it exists.
 * New tables must also declare FOREIGN KEY (CategoryID) REFERENCES branch_expense_categories (ID) ON DELETE RESTRICT.
 */
function fp_category_reference_tables()
{
    return array(
        array('table' => 'branch_expense_items', 'column' => 'CategoryID', 'label' => 'expense items'),
        array('table' => 'branch_expense_budgets', 'column' => 'CategoryID', 'label' => 'budgets'),
        array('table' => 'branch_expense_approval_policies', 'column' => 'CategoryID', 'label' => 'approval policies'),
    );
}

function fp_category_existing_reference_tables($conn)
{
    $refs = fp_category_reference_tables();
    $names = array_column($refs, 'table');
    $rows = fp_db_rows(
        $conn,
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (' . implode(',', array_fill(0, count($names), '?')) . ')',
        str_repeat('s', count($names)),
        $names
    );
    $existing = array_map('strtolower', array_column($rows, 'TABLE_NAME'));
    return array_values(array_filter($refs, function ($ref) use ($existing) {
        return in_array(strtolower($ref['table']), $existing, true);
    }));
}

/**
 * Usage per category: [categoryId => ['total' => n, 'detail' => [label => n]]].
 */
function fp_category_usage($conn, $categoryId = 0)
{
    $usage = array();
    foreach (fp_category_existing_reference_tables($conn) as $ref) {
        $column = '`' . $ref['column'] . '`';
        $sql = 'SELECT ' . $column . ' AS CategoryID, COUNT(*) AS Cnt FROM `' . $ref['table'] . '`'
            . ($categoryId > 0 ? ' WHERE ' . $column . ' = ' . (int) $categoryId : '')
            . ' GROUP BY ' . $column;
        foreach (fp_db_rows($conn, $sql) as $row) {
            $id = (int) $row['CategoryID'];
            if (!isset($usage[$id])) {
                $usage[$id] = array('total' => 0, 'detail' => array());
            }
            $usage[$id]['total'] += (int) $row['Cnt'];
            $usage[$id]['detail'][$ref['label']] = (int) $row['Cnt'];
        }
    }
    return $usage;
}

function fp_category_usage_text($detail)
{
    $parts = array();
    foreach ($detail as $label => $count) {
        $parts[] = $count . ' ' . $label;
    }
    return implode(', ', $parts);
}

function fp_category_audit_fields()
{
    return array(
        'CategoryCode', 'CategoryName', 'Description', 'ParentCategoryID', 'CompanyID',
        'ReceiptRequired', 'QuotationRequiredAbove', 'AdvanceAllowed', 'ReimbursementAllowed', 'RecurringAllowed', 'SortOrder', 'IsActive',
    );
}

function fp_category_snapshot($row)
{
    $snapshot = array();
    foreach (fp_category_audit_fields() as $field) {
        $value = $row[$field] ?? null;
        if ($field === 'ParentCategoryID') {
            $value = $value === null || (int) $value === 0 ? null : (int) $value;
        } elseif ($field === 'QuotationRequiredAbove') {
            $value = $value === null || $value === '' ? null : number_format((float) $value, 2, '.', '');
        } elseif (!in_array($field, array('CategoryCode', 'CategoryName', 'Description'), true)) {
            $value = (int) $value;
        }
        $snapshot[$field] = $value;
    }
    return $snapshot;
}

/**
 * $lock: '' | 'update' | 'share'
 */
function fp_category_get($conn, $id, $lock = '')
{
    $suffix = $lock === 'update' ? ' FOR UPDATE' : ($lock === 'share' ? ' LOCK IN SHARE MODE' : '');
    $rows = fp_db_rows($conn, 'SELECT * FROM branch_expense_categories WHERE ID = ?' . $suffix, 'i', array((int) $id));
    return $rows ? $rows[0] : null;
}

function fp_category_companies($conn)
{
    return fp_db_rows($conn, 'SELECT ID, CompanyName, IsActive FROM quote_company_details ORDER BY CompanyName');
}

function fp_category_list($conn)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT c.ID, c.CategoryCode, c.CategoryName, c.Description, c.ParentCategoryID, c.CompanyID,
                c.ReceiptRequired, c.QuotationRequiredAbove, c.AdvanceAllowed, c.ReimbursementAllowed, c.RecurringAllowed,
                c.SortOrder, c.IsActive, c.RowVersion, c.CreatedAt, c.UpdatedAt,
                p.CategoryCode AS ParentCode, p.CategoryName AS ParentName, p.IsActive AS ParentIsActive,
                (SELECT COUNT(*) FROM branch_expense_categories ch WHERE ch.ParentCategoryID = c.ID) AS ChildCount,
                (SELECT COUNT(*) FROM branch_expense_categories ch WHERE ch.ParentCategoryID = c.ID AND ch.IsActive = 1) AS ActiveChildCount,
                COALESCE(uu.UserName, cu.UserName) AS LastChangedBy
         FROM branch_expense_categories c
         LEFT JOIN branch_expense_categories p ON p.ID = c.ParentCategoryID
         LEFT JOIN users uu ON c.UpdatedByUserID > 0 AND uu.UserID = c.UpdatedByUserID
         LEFT JOIN users cu ON c.CreatedByUserID > 0 AND cu.UserID = c.CreatedByUserID
         ORDER BY COALESCE(p.SortOrder, c.SortOrder), COALESCE(c.ParentCategoryID, c.ID), c.ParentCategoryID IS NOT NULL, c.SortOrder, c.CategoryName'
    );

    $companies = array();
    foreach (fp_category_companies($conn) as $company) {
        $companies[(int) $company['ID']] = $company['CompanyName'];
    }
    $usage = fp_category_usage($conn);

    $list = array();
    foreach ($rows as $row) {
        $id = (int) $row['ID'];
        $companyId = (int) $row['CompanyID'];
        $used = $usage[$id] ?? array('total' => 0, 'detail' => array());
        $list[] = array(
            'ID'                   => $id,
            'CategoryCode'         => $row['CategoryCode'],
            'CategoryName'         => $row['CategoryName'],
            'Description'          => $row['Description'],
            'ParentCategoryID'     => $row['ParentCategoryID'] === null ? 0 : (int) $row['ParentCategoryID'],
            'ParentCode'           => (string) $row['ParentCode'],
            'ParentName'           => (string) $row['ParentName'],
            'ParentIsActive'       => $row['ParentIsActive'] === null ? null : (int) $row['ParentIsActive'],
            'CompanyID'            => $companyId,
            'CompanyName'          => $companyId === 0 ? 'All companies' : ($companies[$companyId] ?? 'Unknown company #' . $companyId),
            'ReceiptRequired'      => (int) $row['ReceiptRequired'],
            'QuotationRequiredAbove' => $row['QuotationRequiredAbove'],
            'AdvanceAllowed'       => (int) $row['AdvanceAllowed'],
            'ReimbursementAllowed' => (int) $row['ReimbursementAllowed'],
            'RecurringAllowed'     => (int) $row['RecurringAllowed'],
            'SortOrder'            => (int) $row['SortOrder'],
            'IsActive'             => (int) $row['IsActive'],
            'RowVersion'           => (int) $row['RowVersion'],
            'ChildCount'           => (int) $row['ChildCount'],
            'ActiveChildCount'     => (int) $row['ActiveChildCount'],
            'UsageCount'           => $used['total'],
            'UsageText'            => fp_category_usage_text($used['detail']),
            'CanDelete'            => (int) $row['ChildCount'] === 0 && $used['total'] === 0,
            'CodeLocked'           => $used['total'] > 0,
            'LastChangedAt'        => (string) ($row['UpdatedAt'] ?: $row['CreatedAt']),
            'LastChangedBy'        => (string) ($row['LastChangedBy'] ?: 'System'),
        );
    }
    return $list;
}

/* ------------------------------------------------------------------
   Create / edit
------------------------------------------------------------------ */

function fp_category_flag($source, $key)
{
    return fp_input_string($source, $key) === '1' ? 1 : 0;
}

function fp_category_input($post)
{
    return array(
        'ID'                   => fp_input_int($post, 'ID'),
        'RowVersion'           => fp_input_int($post, 'RowVersion'),
        'CategoryCode'         => strtoupper(fp_input_string($post, 'CategoryCode')),
        'CategoryName'         => preg_replace('/\s+/u', ' ', fp_input_string($post, 'CategoryName')),
        'Description'          => fp_input_string($post, 'Description'),
        'ParentCategoryID'     => fp_input_int($post, 'ParentCategoryID'),
        'CompanyID'            => fp_input_int($post, 'CompanyID'),
        'ReceiptRequired'      => fp_category_flag($post, 'ReceiptRequired'),
        'AdvanceAllowed'       => fp_category_flag($post, 'AdvanceAllowed'),
        'ReimbursementAllowed' => fp_category_flag($post, 'ReimbursementAllowed'),
        'RecurringAllowed'     => fp_category_flag($post, 'RecurringAllowed'),
        'SortOrderRaw'         => fp_input_string($post, 'SortOrder'),
        'QuotationAboveRaw'    => str_replace(',', '', fp_input_string($post, 'QuotationRequiredAbove')),
    );
}

function fp_category_validate_fields(&$in)
{
    $errors = array();
    if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,29}$/', $in['CategoryCode'])) {
        $errors['CategoryCode'] = 'Use 2-30 characters: letters, numbers, hyphen or underscore, starting with a letter or number.';
    }
    $nameLength = mb_strlen($in['CategoryName']);
    if ($nameLength < 2 || $nameLength > 150) {
        $errors['CategoryName'] = 'Name must be 2-150 characters.';
    }
    if (mb_strlen($in['Description']) > 500) {
        $errors['Description'] = 'Description can be at most 500 characters.';
    }
    if ($in['SortOrderRaw'] === '') {
        $in['SortOrder'] = 0;
    } elseif (preg_match('/^\d{1,4}$/', $in['SortOrderRaw'])) {
        $in['SortOrder'] = (int) $in['SortOrderRaw'];
    } else {
        $errors['SortOrder'] = 'Sort order must be a whole number from 0 to 9999.';
    }
    if ($in['QuotationAboveRaw'] === '') {
        $in['QuotationRequiredAbove'] = null;
    } elseif (preg_match('/^\d{1,10}(\.\d{1,2})?$/', $in['QuotationAboveRaw']) && (float) $in['QuotationAboveRaw'] > 0) {
        $in['QuotationRequiredAbove'] = number_format((float) $in['QuotationAboveRaw'], 2, '.', '');
    } else {
        $errors['QuotationRequiredAbove'] = 'Enter an amount greater than zero (up to 2 decimals), or leave blank for no quotation rule.';
    }
    if ($in['CompanyID'] < 0) {
        $errors['CompanyID'] = 'Choose a valid company.';
    }
    if ($in['ParentCategoryID'] < 0 || ($in['ID'] > 0 && $in['ParentCategoryID'] === $in['ID'])) {
        $errors['ParentCategoryID'] = 'A category cannot be its own parent.';
    }
    if (!$in['AdvanceAllowed'] && !$in['ReimbursementAllowed']) {
        $errors['AdvanceAllowed'] = 'Allow advances, reimbursement or both; otherwise the category cannot be used.';
    }
    return $errors;
}

/**
 * Checks that need the database; runs inside the save transaction.
 */
function fp_category_validate_relations($conn, $in, $existing)
{
    $errors = array();
    $id = (int) $in['ID'];

    $dupes = fp_db_rows($conn, 'SELECT ID FROM branch_expense_categories WHERE CategoryCode = ? AND ID <> ? LIMIT 1', 'si', array($in['CategoryCode'], $id));
    if ($dupes) {
        $errors['CategoryCode'] = 'Another category already uses this code.';
    }
    if ($existing && $existing['CategoryCode'] !== $in['CategoryCode']) {
        $usage = fp_category_usage($conn, $id);
        if (!empty($usage[$id]['total'])) {
            $errors['CategoryCode'] = 'The code cannot change because the category is used in ' . fp_category_usage_text($usage[$id]['detail']) . '.';
        }
    }

    $companyChanged = !$existing || (int) $existing['CompanyID'] !== $in['CompanyID'];
    if ($in['CompanyID'] > 0 && $companyChanged) {
        $company = fp_db_rows($conn, 'SELECT ID FROM quote_company_details WHERE ID = ? AND IsActive = 1', 'i', array($in['CompanyID']));
        if (!$company) {
            $errors['CompanyID'] = 'Choose an active company.';
        }
    }

    $parentId = $in['ParentCategoryID'];
    if ($parentId > 0) {
        $parent = fp_category_get($conn, $parentId, 'share');
        $parentChanged = !$existing || (int) $existing['ParentCategoryID'] !== $parentId;
        if (!$parent) {
            $errors['ParentCategoryID'] = 'The parent category no longer exists.';
        } elseif ($parent['ParentCategoryID'] !== null) {
            $errors['ParentCategoryID'] = 'Sub-categories can only be one level deep. Choose a top-level category as the parent.';
        } elseif ($parentChanged && (int) $parent['IsActive'] !== 1) {
            $errors['ParentCategoryID'] = 'The parent category is inactive.';
        } elseif ((int) $parent['CompanyID'] !== 0 && (int) $parent['CompanyID'] !== $in['CompanyID']) {
            $errors['CompanyID'] = 'A sub-category must use the same company as its parent category.';
        }
        if ($existing) {
            $children = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt FROM branch_expense_categories WHERE ParentCategoryID = ?', 'i', array($id));
            if ((int) $children[0]['Cnt'] > 0) {
                $errors['ParentCategoryID'] = 'This category has sub-categories, so it cannot be placed under another category.';
            }
        }
    }

    if ($existing && $in['CompanyID'] > 0) {
        $mismatch = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt FROM branch_expense_categories WHERE ParentCategoryID = ? AND CompanyID <> ?', 'ii', array($id, $in['CompanyID']));
        if ((int) $mismatch[0]['Cnt'] > 0) {
            $errors['CompanyID'] = 'Some sub-categories belong to a different company scope. Change them first.';
        }
    }

    $siblings = fp_db_rows(
        $conn,
        'SELECT ID FROM branch_expense_categories
         WHERE ParentCategoryID <=> ? AND CategoryName = ? AND ID <> ? AND (CompanyID = ? OR CompanyID = 0 OR ? = 0)
         LIMIT 1',
        'isiii',
        array($parentId > 0 ? $parentId : null, $in['CategoryName'], $id, $in['CompanyID'], $in['CompanyID'])
    );
    if ($siblings) {
        $errors['CategoryName'] = 'A category with this name already exists at the same level for an overlapping company scope.';
    }

    return $errors;
}

function fp_category_save($conn, $actor, $post)
{
    $in = fp_category_input($post);
    $errors = fp_category_validate_fields($in);
    if ($errors) {
        return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
    }

    try {
        return fp_transaction($conn, function () use ($conn, $actor, $in) {
            $existing = null;
            if ($in['ID'] > 0) {
                $existing = fp_category_get($conn, $in['ID'], 'update');
                if (!$existing) {
                    return fp_result(false, 404, 'This category no longer exists. Reload the page.');
                }
                if ((int) $existing['RowVersion'] !== $in['RowVersion']) {
                    return fp_result(false, 409, 'Someone else changed this category. Reload the page and try again.');
                }
            }

            $errors = fp_category_validate_relations($conn, $in, $existing);
            if ($errors) {
                return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => $errors));
            }

            $parentId = $in['ParentCategoryID'] > 0 ? $in['ParentCategoryID'] : null;
            $now = date('Y-m-d H:i:s');
            $userId = (int) $actor['user_id'];
            $employeeId = (int) $actor['employee_id'];

            if ($existing === null) {
                $stmt = $conn->prepare(
                    'INSERT INTO branch_expense_categories
                        (CategoryCode, CategoryName, Description, ParentCategoryID, CompanyID,
                         ReceiptRequired, QuotationRequiredAbove, AdvanceAllowed, ReimbursementAllowed, RecurringAllowed, SortOrder,
                         IsActive, RowVersion, CreatedByUserID, CreatedByEmployeeID, CreatedAt)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'sssiiisiiiiiis',
                    $in['CategoryCode'], $in['CategoryName'], $in['Description'], $parentId, $in['CompanyID'],
                    $in['ReceiptRequired'], $in['QuotationRequiredAbove'], $in['AdvanceAllowed'], $in['ReimbursementAllowed'], $in['RecurringAllowed'], $in['SortOrder'],
                    $userId, $employeeId, $now
                );
                $stmt->execute();
                $id = (int) $conn->insert_id;
                $stmt->close();

                fp_audit_log($conn, $actor, FP_CATEGORY_ENTITY, $id, 'create', null, fp_category_snapshot(fp_category_get($conn, $id)));
                return fp_result(true, 200, 'Category created.', array('data' => array('ID' => $id)));
            }

            $old = fp_category_snapshot($existing);
            $new = $old;
            foreach (array('CategoryCode', 'CategoryName', 'Description', 'CompanyID', 'ReceiptRequired', 'QuotationRequiredAbove', 'AdvanceAllowed', 'ReimbursementAllowed', 'RecurringAllowed', 'SortOrder') as $field) {
                $new[$field] = $in[$field];
            }
            $new['ParentCategoryID'] = $parentId;
            if (!fp_audit_changed_fields($old, $new)) {
                return fp_result(true, 200, 'No changes to save.', array('data' => array('ID' => $in['ID'])));
            }

            $stmt = $conn->prepare(
                'UPDATE branch_expense_categories
                 SET CategoryCode = ?, CategoryName = ?, Description = ?, ParentCategoryID = ?, CompanyID = ?,
                     ReceiptRequired = ?, QuotationRequiredAbove = ?, AdvanceAllowed = ?, ReimbursementAllowed = ?, RecurringAllowed = ?, SortOrder = ?,
                     RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
                 WHERE ID = ? AND RowVersion = ?'
            );
            $stmt->bind_param(
                'sssiiisiiiiiisii',
                $in['CategoryCode'], $in['CategoryName'], $in['Description'], $parentId, $in['CompanyID'],
                $in['ReceiptRequired'], $in['QuotationRequiredAbove'], $in['AdvanceAllowed'], $in['ReimbursementAllowed'], $in['RecurringAllowed'], $in['SortOrder'],
                $userId, $employeeId, $now, $in['ID'], $in['RowVersion']
            );
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected !== 1) {
                return fp_result(false, 409, 'Someone else changed this category. Reload the page and try again.');
            }

            fp_audit_log($conn, $actor, FP_CATEGORY_ENTITY, $in['ID'], 'update', $old, $new);
            return fp_result(true, 200, 'Category updated.', array('data' => array('ID' => $in['ID'])));
        });
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1062) {
            return fp_result(false, 422, 'Please correct the highlighted fields.', array('errors' => array('CategoryCode' => 'Another category already uses this code.')));
        }
        throw $e;
    }
}

/* ------------------------------------------------------------------
   Activate / deactivate / delete
------------------------------------------------------------------ */

function fp_category_set_status($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $status = fp_input_string($post, 'IsActive');
    $reason = fp_input_string($post, 'Reason');

    if ($id <= 0 || !in_array($status, array('0', '1'), true)) {
        return fp_result(false, 422, 'Invalid request.');
    }
    $activate = $status === '1';
    if (!$activate && mb_strlen($reason) < 5) {
        return fp_result(false, 422, 'Please give a reason for deactivating (at least 5 characters).', array('errors' => array('Reason' => 'Required.')));
    }
    if (mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'The reason can be at most 500 characters.', array('errors' => array('Reason' => 'Too long.')));
    }

    return fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $activate, $reason) {
        $existing = fp_category_get($conn, $id, 'update');
        if (!$existing) {
            return fp_result(false, 404, 'This category no longer exists. Reload the page.');
        }
        if ((int) $existing['RowVersion'] !== $rowVersion) {
            return fp_result(false, 409, 'Someone else changed this category. Reload the page and try again.');
        }
        if ((int) $existing['IsActive'] === ($activate ? 1 : 0)) {
            return fp_result(false, 422, 'The category is already ' . ($activate ? 'active' : 'inactive') . '.');
        }

        if ($activate && $existing['ParentCategoryID'] !== null) {
            $parent = fp_category_get($conn, (int) $existing['ParentCategoryID'], 'share');
            if (!$parent || (int) $parent['IsActive'] !== 1) {
                return fp_result(false, 422, 'Activate the parent category first.');
            }
        }
        if (!$activate) {
            $children = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt FROM branch_expense_categories WHERE ParentCategoryID = ? AND IsActive = 1', 'i', array($id));
            if ((int) $children[0]['Cnt'] > 0) {
                return fp_result(false, 422, 'Deactivate its ' . (int) $children[0]['Cnt'] . ' active sub-categor' . ((int) $children[0]['Cnt'] === 1 ? 'y' : 'ies') . ' first.');
            }
        }

        $isActive = $activate ? 1 : 0;
        $userId = (int) $actor['user_id'];
        $employeeId = (int) $actor['employee_id'];
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare(
            'UPDATE branch_expense_categories
             SET IsActive = ?, RowVersion = RowVersion + 1, UpdatedByUserID = ?, UpdatedByEmployeeID = ?, UpdatedAt = ?
             WHERE ID = ? AND RowVersion = ?'
        );
        $stmt->bind_param('iiisii', $isActive, $userId, $employeeId, $now, $id, $rowVersion);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected !== 1) {
            return fp_result(false, 409, 'Someone else changed this category. Reload the page and try again.');
        }

        $old = fp_category_snapshot($existing);
        $new = $old;
        $new['IsActive'] = $isActive;
        fp_audit_log($conn, $actor, FP_CATEGORY_ENTITY, $id, $activate ? 'activate' : 'deactivate', $old, $new, $reason);
        return fp_result(true, 200, $activate ? 'Category activated.' : 'Category deactivated. It stays on existing records but cannot be chosen for new ones.');
    });
}

function fp_category_delete($conn, $actor, $post)
{
    $id = fp_input_int($post, 'ID');
    $rowVersion = fp_input_int($post, 'RowVersion');
    $reason = fp_input_string($post, 'Reason');

    if ($id <= 0) {
        return fp_result(false, 422, 'Invalid request.');
    }
    if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
        return fp_result(false, 422, 'Please give a reason for deleting (5-500 characters).', array('errors' => array('Reason' => 'Required.')));
    }

    try {
        return fp_transaction($conn, function () use ($conn, $actor, $id, $rowVersion, $reason) {
            $existing = fp_category_get($conn, $id, 'update');
            if (!$existing) {
                return fp_result(false, 404, 'This category no longer exists. Reload the page.');
            }
            if ((int) $existing['RowVersion'] !== $rowVersion) {
                return fp_result(false, 409, 'Someone else changed this category. Reload the page and try again.');
            }

            $children = fp_db_rows($conn, 'SELECT COUNT(*) AS Cnt FROM branch_expense_categories WHERE ParentCategoryID = ?', 'i', array($id));
            if ((int) $children[0]['Cnt'] > 0) {
                return fp_result(false, 422, 'This category has sub-categories. Delete or move them first, or deactivate this category instead.');
            }
            $usage = fp_category_usage($conn, $id);
            if (!empty($usage[$id]['total'])) {
                return fp_result(false, 422, 'This category is used in ' . fp_category_usage_text($usage[$id]['detail']) . ', so it cannot be deleted. Deactivate it instead.');
            }

            $stmt = $conn->prepare('DELETE FROM branch_expense_categories WHERE ID = ? AND RowVersion = ?');
            $stmt->bind_param('ii', $id, $rowVersion);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected !== 1) {
                return fp_result(false, 409, 'Someone else changed this category. Reload the page and try again.');
            }

            fp_audit_log($conn, $actor, FP_CATEGORY_ENTITY, $id, 'delete', fp_category_snapshot($existing), null, $reason);
            return fp_result(true, 200, 'Category deleted.');
        });
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1451) {
            return fp_result(false, 422, 'This category is referenced by other records, so it cannot be deleted. Deactivate it instead.');
        }
        throw $e;
    }
}

function fp_category_history($conn, $id)
{
    $category = fp_category_get($conn, $id);
    if (!$category) {
        return fp_result(false, 404, 'This category no longer exists.');
    }
    return fp_result(true, 200, 'OK', array('data' => array(
        'category' => array('ID' => (int) $category['ID'], 'CategoryCode' => $category['CategoryCode'], 'CategoryName' => $category['CategoryName']),
        'history'  => fp_audit_history($conn, FP_CATEGORY_ENTITY, $id),
    )));
}
