<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('hr_ticket_helpers.php');

header('Content-Type: application/json; charset=utf-8');
setTimeZone();

$data = hr_ticket_api_parse_input();
$conn = _connectodb();

$employeeId = isset($data['EmployeeID']) ? (int) $data['EmployeeID'] : 0;
$ticketId = hr_ticket_api_resolve_ticket_id($conn, $data);
if ($employeeId < 1 || $ticketId < 1) {
    hr_ticket_api_response(true, 'EmployeeID and HrTicketID are required.');
}

$hr = new Hrticket($conn);
$ticket = $hr->getTicketById($ticketId);
$access = hr_ticket_api_assert_ticket_access($conn, $ticket, $employeeId);

$attachmentRaw = $data['Attachment'] ?? $data['attachment'] ?? '';
if ($attachmentRaw === '' && isset($_FILES['attachment'])) {
    $uploadedBy = hr_ticket_api_created_by_label($conn, $employeeId);
    $result = $hr->uploadAttachment($ticketId, $_FILES, $uploadedBy, (int) ($data['CommentID'] ?? 0));
    if (!empty($result['error'])) {
        hr_ticket_api_response(true, $result['message'] ?? 'Upload failed.');
    }
    $row = _getTableDetails($conn, 'hr_ticket_attachment', ' WHERE id = ' . (int) ($result['attachment_id'] ?? 0));
    $fileName = is_array($row) ? ($row['file_name'] ?? '') : '';
    if (!empty($access['is_hr'])) {
        hr_ticket_notify_employees($conn, array((int) ($ticket['employee_id'] ?? 0)), 'HR Attachment — ' . ($ticket['ticket_code'] ?? ''), 'HR uploaded an attachment to your ticket.', array(
            'module' => 'hr_ticket', 'screen' => 'hr_ticket', 'ticket_id' => $ticketId, 'ticket_code' => $ticket['ticket_code'] ?? '',
        ), $uploadedBy);
    } else {
        $targets = (int) ($ticket['assigned_to'] ?? 0) > 0 ? array((int) $ticket['assigned_to']) : hr_ticket_hr_employee_ids_for_notification($conn);
        hr_ticket_notify_employees($conn, $targets, 'Attachment on HR Ticket — ' . ($ticket['ticket_code'] ?? ''), ($ticket['employee_name'] ?? 'Employee') . ' uploaded an attachment.', array(
            'module' => 'hr_ticket', 'screen' => 'hr_ticket_manage', 'ticket_id' => $ticketId, 'ticket_code' => $ticket['ticket_code'] ?? '',
        ), $uploadedBy);
    }
    echo json_encode(array(
        'error' => false,
        'message' => 'Attachment uploaded.',
        'attachment_id' => (int) ($result['attachment_id'] ?? 0),
        'download_url' => hr_ticket_api_attachment_url($fileName, hr_ticket_api_base_url()),
    ));
    exit;
}

if ($attachmentRaw === '') {
    hr_ticket_api_response(true, 'Attachment (base64) is required.');
}

$uploadedBy = hr_ticket_api_created_by_label($conn, $employeeId);
$result = hr_ticket_api_save_base64_attachment($conn, $ticketId, $attachmentRaw, (string) ($data['AttachmentName'] ?? 'attachment.jpg'), $uploadedBy, (int) ($data['CommentID'] ?? 0));

if (!empty($result['error'])) {
    hr_ticket_api_response(true, $result['message'] ?? 'Upload failed.');
}

if (!empty($access['is_hr'])) {
    hr_ticket_notify_employees($conn, array((int) ($ticket['employee_id'] ?? 0)), 'HR Attachment — ' . ($ticket['ticket_code'] ?? ''), 'HR uploaded an attachment to your ticket.', array(
        'module' => 'hr_ticket', 'screen' => 'hr_ticket', 'ticket_id' => $ticketId, 'ticket_code' => $ticket['ticket_code'] ?? '',
    ), $uploadedBy);
} else {
    $targets = (int) ($ticket['assigned_to'] ?? 0) > 0 ? array((int) $ticket['assigned_to']) : hr_ticket_hr_employee_ids_for_notification($conn);
    hr_ticket_notify_employees($conn, $targets, 'Attachment on HR Ticket — ' . ($ticket['ticket_code'] ?? ''), ($ticket['employee_name'] ?? 'Employee') . ' uploaded an attachment.', array(
        'module' => 'hr_ticket', 'screen' => 'hr_ticket_manage', 'ticket_id' => $ticketId, 'ticket_code' => $ticket['ticket_code'] ?? '',
    ), $uploadedBy);
}

echo json_encode($result);
