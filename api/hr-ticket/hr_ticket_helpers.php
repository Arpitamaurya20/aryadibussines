<?php
/**
 * Shared helpers for HR ticket mobile APIs.
 */

require_once dirname(__DIR__, 2) . '/admin/includes/autoloader.inc.php';
require_once dirname(__DIR__, 2) . '/admin/hr-tickets/controller/hr_tickets_controller.php';

function hr_ticket_api_parse_input()
{
    $dataRaw = file_get_contents('php://input');
    $data = json_decode($dataRaw, true);
    if (!is_array($data)) {
        $data = array();
    }
    if (!empty($_GET)) {
        $data = array_merge($_GET, $data);
    }
    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }
    return $data;
}

function hr_ticket_api_response($error, $message, $extra = array())
{
    header('Content-Type: application/json; charset=utf-8');
    $response = array_merge(array(
        'error' => (bool) $error,
        'message' => $message,
    ), $extra);
    echo json_encode($response);
    exit;
}

function hr_ticket_api_base_url()
{
    if (defined('FRONT_SITE_PATH')) {
        return rtrim(FRONT_SITE_PATH, '/');
    }
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if ($host === '') {
        return '';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $host;
}

function hr_ticket_api_get_roles($conn, $employeeId)
{
    require_once dirname(__DIR__, 2) . '/admin/authentication/auth_controller/authentication_controller.php';
    return getUserRole($conn, (int) $employeeId, 'None');
}

function hr_ticket_api_employee_has_hr_access($conn, $employeeId)
{
    $roles = hr_ticket_api_get_roles($conn, (int) $employeeId);
    return hasHrTicketManageAccess($roles);
}

function hr_ticket_api_resolve_ticket_id($conn, $data)
{
    $hr = new Hrticket($conn);
    $idKeys = array('HrTicketID', 'hr_ticket_id', 'id');
    foreach ($idKeys as $key) {
        if (isset($data[$key]) && (int) $data[$key] > 0) {
            $ticket = $hr->getTicketById((int) $data[$key]);
            if ($ticket) {
                return (int) $ticket['id'];
            }
        }
    }
    $codeKeys = array('TicketID', 'ticket_code', 'ticket_id');
    foreach ($codeKeys as $key) {
        if (!isset($data[$key])) {
            continue;
        }
        $val = trim((string) $data[$key]);
        if ($val === '') {
            continue;
        }
        $safe = mysqli_real_escape_string($conn, $val);
        $row = _getTableDetails($conn, 'hr_ticket', " WHERE ticket_code = '$safe' LIMIT 1");
        if (is_array($row) && !empty($row['id'])) {
            return (int) $row['id'];
        }
        if (ctype_digit($val)) {
            $ticket = $hr->getTicketById((int) $val);
            if ($ticket) {
                return (int) $ticket['id'];
            }
        }
    }
    return 0;
}

function hr_ticket_api_attachment_url($fileName, $baseUrl = '')
{
    $fileName = trim((string) $fileName);
    if ($fileName === '') {
        return '';
    }
    $path = '/admin/hr-tickets/download-attachment.php?f=' . rawurlencode($fileName);
    return $baseUrl !== '' ? rtrim($baseUrl, '/') . $path : $path;
}

function hr_ticket_api_format_attachment($row, $baseUrl = '')
{
    return array(
        'id' => (int) ($row['id'] ?? 0),
        'ticket_id' => (int) ($row['ticket_id'] ?? 0),
        'comment_id' => isset($row['comment_id']) ? (int) $row['comment_id'] : null,
        'file_name' => $row['file_name'] ?? '',
        'original_name' => $row['original_name'] ?? '',
        'mime_type' => $row['mime_type'] ?? '',
        'file_size' => isset($row['file_size']) ? (int) $row['file_size'] : 0,
        'uploaded_by' => $row['uploaded_by'] ?? '',
        'created_at' => $row['created_at'] ?? '',
        'download_url' => hr_ticket_api_attachment_url($row['file_name'] ?? '', $baseUrl),
    );
}

function hr_ticket_api_format_comment($row)
{
    return array(
        'id' => (int) ($row['id'] ?? 0),
        'ticket_id' => (int) ($row['ticket_id'] ?? 0),
        'author_employee_id' => isset($row['author_employee_id']) ? (int) $row['author_employee_id'] : null,
        'author_name' => $row['author_name'] ?? ($row['author_username'] ?? ''),
        'author_role' => $row['author_role'] ?? 'employee',
        'comment_text' => $row['comment_text'] ?? '',
        'is_internal' => !empty($row['is_internal']) ? 1 : 0,
        'created_at' => $row['created_at'] ?? '',
    );
}

function hr_ticket_api_format_history($row)
{
    return array(
        'id' => (int) ($row['id'] ?? 0),
        'action_type' => $row['action_type'] ?? '',
        'status' => $row['status'] ?? '',
        'assigned_from' => isset($row['assigned_from']) ? (int) $row['assigned_from'] : null,
        'assigned_from_name' => $row['assigned_from_name'] ?? '',
        'assigned_to' => isset($row['assigned_to']) ? (int) $row['assigned_to'] : null,
        'assigned_to_name' => $row['assigned_to_name'] ?? '',
        'summary' => $row['summary'] ?? '',
        'created_by' => $row['created_by'] ?? '',
        'created_at' => $row['created_at'] ?? '',
    );
}

function hr_ticket_api_format_ticket($conn, array $ticket, $includeDetail = false, $baseUrl = '', $requestEmployeeId = 0)
{
    $hr = new Hrticket($conn);
    $ticketId = (int) ($ticket['id'] ?? 0);
    $isHr = hr_ticket_api_employee_has_hr_access($conn, $requestEmployeeId);
    $isOwner = $requestEmployeeId > 0 && $requestEmployeeId === (int) ($ticket['employee_id'] ?? 0);
    $status = (string) ($ticket['status'] ?? 'open');
    $isClosed = $status === 'closed';

    $formatted = array(
        'id' => $ticketId,
        'ticket_id' => $ticket['ticket_code'] ?? '',
        'ticket_code' => $ticket['ticket_code'] ?? '',
        'employee_id' => (int) ($ticket['employee_id'] ?? 0),
        'employee_name' => $ticket['employee_name'] ?? '',
        'employee_phone' => $ticket['employee_phone'] ?? '',
        'employee_email' => $ticket['employee_email'] ?? '',
        'category' => $ticket['category'] ?? 'general',
        'category_label' => $hr->categoryLabel($ticket['category'] ?? 'general'),
        'subject' => $ticket['subject'] ?? '',
        'description' => $ticket['description'] ?? '',
        'priority' => $ticket['priority'] ?? 'normal',
        'status' => $status,
        'status_label' => $hr->statusLabel($status),
        'assigned_to' => isset($ticket['assigned_to']) ? (int) $ticket['assigned_to'] : null,
        'assigned_name' => $ticket['assigned_name'] ?? '',
        'payment_reference' => $ticket['payment_reference'] ?? '',
        'resolved_at' => $ticket['resolved_at'] ?? null,
        'closed_at' => $ticket['closed_at'] ?? null,
        'created_by' => $ticket['created_by'] ?? '',
        'created_at' => $ticket['created_at'] ?? '',
        'updated_at' => $ticket['updated_at'] ?? '',
        'portal_detail_url' => hr_ticket_api_base_url() . '/admin/hr-tickets/' . hr_ticket_detail_url($ticketId),
        'permissions' => array(
            'is_owner' => $isOwner,
            'is_hr' => $isHr,
            'can_manage' => $isHr,
            'can_reply' => !$isClosed && ($isOwner || $isHr),
            'can_close' => $isOwner && $status === 'resolved',
            'can_assign' => $isHr && !$isClosed,
        ),
    );

    if ($includeDetail) {
        $comments = $hr->getComments($ticketId, $isHr);
        $commentList = array();
        foreach ($comments as $c) {
            $commentList[] = hr_ticket_api_format_comment($c);
        }

        $attachments = $hr->getAttachments($ticketId);
        $attachList = array();
        foreach ($attachments as $a) {
            $attachList[] = hr_ticket_api_format_attachment($a, $baseUrl);
        }

        $history = $hr->getTicketHistory($ticketId);
        $historyList = array();
        foreach ($history as $h) {
            $historyList[] = hr_ticket_api_format_history($h);
        }

        $formatted['comments'] = $commentList;
        $formatted['attachments'] = $attachList;
        $formatted['history'] = $historyList;
        $formatted['meta'] = array(
            'categories' => array(
                array('value' => 'general', 'label' => 'General'),
                array('value' => 'payment_related', 'label' => 'Payment Related'),
                array('value' => 'benefits', 'label' => 'Benefits'),
            ),
            'statuses' => array(
                array('value' => 'open', 'label' => 'Open'),
                array('value' => 'in_progress', 'label' => 'In Progress'),
                array('value' => 'pending_employee_response', 'label' => 'Pending Employee Response'),
                array('value' => 'on_hold', 'label' => 'On Hold'),
                array('value' => 'resolved', 'label' => 'Resolved'),
                array('value' => 'closed', 'label' => 'Closed'),
            ),
            'priorities' => array(
                array('value' => 'low', 'label' => 'Low'),
                array('value' => 'normal', 'label' => 'Normal'),
                array('value' => 'high', 'label' => 'High'),
            ),
        );
    }

    return $formatted;
}

function hr_ticket_api_decode_base64_file($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return false;
    }
    if (strpos($raw, 'base64,') !== false) {
        $parts = explode('base64,', $raw, 2);
        $raw = isset($parts[1]) ? $parts[1] : '';
    }
    $decoded = base64_decode($raw, true);
    return $decoded !== false ? $decoded : false;
}

function hr_ticket_api_guess_extension_from_base64($raw)
{
    $raw = trim((string) $raw);
    if (preg_match('#^data:([^;]+);base64,#i', $raw, $m)) {
        $mime = strtolower($m[1]);
        $map = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        );
        if (isset($map[$mime])) {
            return $map[$mime];
        }
    }
    return 'jpg';
}

/**
 * Save base64 attachment for mobile API.
 * @return array{error:bool,message?:string,attachment_id?:int}
 */
function hr_ticket_api_save_base64_attachment($conn, $ticketId, $attachmentRaw, $originalName, $uploadedBy, $commentId = 0)
{
    $hr = new Hrticket($conn);
    $result = $hr->uploadBase64Attachment((int) $ticketId, $attachmentRaw, (string) $originalName, (string) $uploadedBy, (int) $commentId);
    if (!empty($result['error'])) {
        return $result;
    }
    $fileName = '';
    if (!empty($result['attachment_id'])) {
        $row = _getTableDetails($conn, 'hr_ticket_attachment', ' WHERE id = ' . (int) $result['attachment_id'] . ' LIMIT 1');
        if (is_array($row)) {
            $fileName = $row['file_name'] ?? '';
        }
    }
    $result['download_url'] = hr_ticket_api_attachment_url($fileName, hr_ticket_api_base_url());
    return $result;
}

function hr_ticket_api_created_by_label($conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId < 1) {
        return 'Mobile API';
    }
    $emp = _getTableDetails($conn, 'employees', " WHERE ID = $employeeId LIMIT 1");
    if (is_array($emp) && !empty($emp['Name'])) {
        return 'mobile:' . $emp['Name'];
    }
    return 'mobile:' . $employeeId;
}

function hr_ticket_api_assert_ticket_access($conn, $ticket, $employeeId)
{
    if (!is_array($ticket)) {
        hr_ticket_api_response(true, 'Ticket not found.');
    }
    $employeeId = (int) $employeeId;
    if ($employeeId < 1) {
        hr_ticket_api_response(true, 'EmployeeID is required.');
    }
    $isOwner = (int) ($ticket['employee_id'] ?? 0) === $employeeId;
    $isHr = hr_ticket_api_employee_has_hr_access($conn, $employeeId);
    if (!$isOwner && !$isHr) {
        hr_ticket_api_response(true, 'Access denied.');
    }
    return array('is_owner' => $isOwner, 'is_hr' => $isHr);
}
