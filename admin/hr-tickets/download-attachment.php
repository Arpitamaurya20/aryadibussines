<?php
session_start();

require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
include('../controller/hr_tickets_controller.php');

$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);

$file = isset($_GET['f']) ? basename((string) $_GET['f']) : '';
if ($file === '' || preg_match('/[^a-zA-Z0-9._-]/', $file)) {
	http_response_code(404);
	exit('File not found.');
}

$conn = _connectodb();
$safe = mysqli_real_escape_string($conn, $file);
$row = _getTableDetails($conn, 'hr_ticket_attachment', " WHERE file_name = '$safe' LIMIT 1");
if (!$row) {
	http_response_code(404);
	exit('File not found.');
}

$ticketId = (int) ($row['ticket_id'] ?? 0);
$hr = new Hrticket($conn);
$ticket = $hr->getTicketById($ticketId);
if (!$ticket || !hr_ticket_user_can_view($_SESSION, $ticket)) {
	http_response_code(403);
	exit('Access denied.');
}

$path = __DIR__ . '/uploads/' . $file;
if (!is_file($path)) {
	http_response_code(404);
	exit('File not found.');
}

$mime = $row['mime_type'] ?? 'application/octet-stream';
$original = $row['original_name'] ?? $file;

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . str_replace('"', '', $original) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
