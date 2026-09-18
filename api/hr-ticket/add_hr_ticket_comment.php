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

$commentText = trim((string) ($data['CommentText'] ?? $data['comment_text'] ?? $data['Comment'] ?? ''));
if ($commentText === '') {
    hr_ticket_api_response(true, 'CommentText is required.');
}

$hr = new Hrticket($conn);
$ticket = $hr->getTicketById($ticketId);
$access = hr_ticket_api_assert_ticket_access($conn, $ticket, $employeeId);

$authorRole = !empty($access['is_hr']) ? 'hr' : 'employee';
$isInternal = !empty($data['IsInternal']) && (int) $data['IsInternal'] === 1;
$createdBy = hr_ticket_api_created_by_label($conn, $employeeId);

$result = $hr->addComment($ticketId, array(
    'comment_text' => $commentText,
    'is_internal' => $isInternal,
), $employeeId, $createdBy, $authorRole);

if (!empty($result['error'])) {
    hr_ticket_api_response(true, $result['message'] ?? 'Could not add comment.');
}

$preview = substr(preg_replace('/\s+/', ' ', $commentText), 0, 120);
$updatedTicket = $result['ticket'] ?? $hr->getTicketById($ticketId);
hr_ticket_notify_on_comment($conn, $updatedTicket, $authorRole, $preview, $createdBy);

$commentId = (int) ($result['comment_id'] ?? 0);
$attachmentRaw = $data['Attachment'] ?? $data['attachment'] ?? '';
$attachmentName = trim((string) ($data['AttachmentName'] ?? $data['attachment_name'] ?? 'attachment.jpg'));
$attachExtra = array();
if ($attachmentRaw !== '' && $commentId > 0) {
    $upload = hr_ticket_api_save_base64_attachment($conn, $ticketId, $attachmentRaw, $attachmentName, $createdBy, $commentId);
    if (empty($upload['error'])) {
        $attachExtra['attachment'] = $upload;
    }
}

echo json_encode(array_merge(array(
    'error' => false,
    'message' => $result['message'] ?? 'Comment added.',
    'comment_id' => $commentId,
    'HrTicketID' => $ticketId,
), $attachExtra));
