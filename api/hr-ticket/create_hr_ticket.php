<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('hr_ticket_helpers.php');

header('Content-Type: application/json; charset=utf-8');
setTimeZone();

$data = hr_ticket_api_parse_input();
$conn = _connectodb();

$employeeId = isset($data['EmployeeID']) ? (int) $data['EmployeeID'] : 0;
if ($employeeId < 1) {
    hr_ticket_api_response(true, 'EmployeeID is required.');
}

$subject = trim((string) ($data['Subject'] ?? $data['subject'] ?? ''));
$description = trim((string) ($data['Description'] ?? $data['description'] ?? ''));
$category = strtolower(trim((string) ($data['Category'] ?? $data['category'] ?? 'general')));
$priority = strtolower(trim((string) ($data['Priority'] ?? $data['priority'] ?? 'normal')));
$paymentRef = trim((string) ($data['PaymentReference'] ?? $data['payment_reference'] ?? ''));

$payload = array(
    'subject' => $subject,
    'description' => $description,
    'category' => $category,
    'priority' => $priority,
    'payment_reference' => $paymentRef,
);

$hr = new Hrticket($conn);
$createdBy = hr_ticket_api_created_by_label($conn, $employeeId);
$result = $hr->createTicket($payload, $employeeId, $createdBy);

if (!empty($result['error'])) {
    hr_ticket_api_response(true, $result['message'] ?? 'Could not create ticket.');
}

$ticketId = (int) ($result['id'] ?? 0);
hr_ticket_notify_on_created($conn, $ticketId, $createdBy);

$attachmentRaw = $data['Attachment'] ?? $data['attachment'] ?? '';
$attachmentName = trim((string) ($data['AttachmentName'] ?? $data['attachment_name'] ?? 'attachment.jpg'));
if ($attachmentRaw !== '' && $ticketId > 0) {
    $upload = hr_ticket_api_save_base64_attachment($conn, $ticketId, $attachmentRaw, $attachmentName, $createdBy);
    if (!empty($upload['error'])) {
        $result['attachment_warning'] = $upload['message'] ?? 'Attachment upload failed.';
    } else {
        $result['attachment_id'] = $upload['attachment_id'] ?? null;
        $result['attachment_url'] = $upload['download_url'] ?? '';
    }
}

$ticket = $hr->getTicketById($ticketId);
$baseUrl = hr_ticket_api_base_url();

echo json_encode(array(
    'error' => false,
    'message' => $result['message'] ?? 'HR ticket raised successfully.',
    'ticket_id' => $result['ticket_code'] ?? '',
    'HrTicketID' => $ticketId,
    'data' => $ticket ? hr_ticket_api_format_ticket($conn, $ticket, true, $baseUrl, $employeeId) : null,
));
