<?php
/**
 * Finance & Performance audit trail (branch_expense_audit_log). Insert-only.
 * Call inside the same transaction as the change so the audit row commits or rolls back with it.
 */

function fp_audit_changed_fields($old, $new)
{
    $changed = array();
    foreach ($new as $field => $value) {
        $before = is_array($old) && array_key_exists($field, $old) ? $old[$field] : null;
        if (is_array($before) || is_array($value)) {
            if (json_encode($before) !== json_encode($value)) {
                $changed[] = $field;
            }
            continue;
        }
        if ((string) $before !== (string) $value || ($before === null) !== ($value === null)) {
            $changed[] = $field;
        }
    }
    return $changed;
}

function fp_audit_log($conn, $actor, $entityType, $entityId, $action, $old = null, $new = null, $remarks = '')
{
    $changed = ($old !== null && $new !== null) ? implode(',', fp_audit_changed_fields($old, $new)) : '';
    $oldJson = $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE);
    $newJson = $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE);
    $remarks = mb_substr(trim((string) $remarks), 0, 500);
    $userId = (int) ($actor['user_id'] ?? 0);
    $employeeId = (int) ($actor['employee_id'] ?? 0);
    $userName = mb_substr((string) ($actor['username'] ?? ''), 0, 100);
    $ip = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $agent = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $now = date('Y-m-d H:i:s');
    $entityId = (int) $entityId;
    $changed = mb_substr($changed, 0, 500);

    $stmt = $conn->prepare(
        'INSERT INTO branch_expense_audit_log
            (EntityType, EntityID, Action, OldValues, NewValues, ChangedFields, Remarks, ActorUserID, ActorEmployeeID, ActorUserName, IPAddress, UserAgent, CreatedAt)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sisssssiissss', $entityType, $entityId, $action, $oldJson, $newJson, $changed, $remarks, $userId, $employeeId, $userName, $ip, $agent, $now);
    $stmt->execute();
    $stmt->close();
}

function fp_audit_history($conn, $entityType, $entityId, $limit = 200)
{
    $rows = fp_db_rows(
        $conn,
        'SELECT ID, Action, OldValues, NewValues, ChangedFields, Remarks, ActorUserName, CreatedAt
         FROM branch_expense_audit_log
         WHERE EntityType = ? AND EntityID = ?
         ORDER BY ID DESC
         LIMIT ?',
        'sii',
        array($entityType, (int) $entityId, (int) $limit)
    );
    foreach ($rows as &$row) {
        $row['OldValues'] = $row['OldValues'] === null ? null : json_decode($row['OldValues'], true);
        $row['NewValues'] = $row['NewValues'] === null ? null : json_decode($row['NewValues'], true);
        $row['ChangedFields'] = $row['ChangedFields'] === '' ? array() : explode(',', $row['ChangedFields']);
    }
    unset($row);
    return $rows;
}
