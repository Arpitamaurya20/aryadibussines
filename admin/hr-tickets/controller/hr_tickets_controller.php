<?php

require_once(__DIR__ . '/../../includes/autoloader.inc.php');
require_once(__DIR__ . '/../../controllers/portal_notification_controller.php');

function hr_ticket_encode_id($id)
{
	$id = (int) $id;
	if ($id < 1) {
		return '';
	}
	return rtrim(strtr(base64_encode((string) $id), '+/', '-_'), '=');
}

function hr_ticket_decode_id($token)
{
	$token = trim((string) $token);
	if ($token === '') {
		return 0;
	}
	$b64 = strtr($token, '-_', '+/');
	$pad = strlen($b64) % 4;
	if ($pad > 0) {
		$b64 .= str_repeat('=', 4 - $pad);
	}
	$decoded = base64_decode($b64, true);
	if ($decoded === false || !ctype_digit((string) $decoded)) {
		return 0;
	}
	return (int) $decoded;
}

function hr_ticket_my_list_url()
{
	return 'view-my-hr-tickets';
}

function hr_ticket_manage_list_url()
{
	return 'view-hr-tickets';
}

function hr_ticket_detail_url($ticketId)
{
	$token = hr_ticket_encode_id($ticketId);
	if ($token === '') {
		return hr_ticket_my_list_url();
	}
	return 'view-hr-ticket-detail?t=' . rawurlencode($token);
}

function hr_ticket_session_username()
{
	return $_SESSION['pb_username'] ?? '';
}

function hr_ticket_session_employee_id($session = null)
{
	if ($session === null) {
		$session = $_SESSION;
	}
	if (isset($session['Roles']['EmployeeID']) && (int) $session['Roles']['EmployeeID'] > 0) {
		return (int) $session['Roles']['EmployeeID'];
	}
	return 0;
}

function hasHrTicketManageAccess($roles)
{
	if (isset($_SESSION['UserType'])) {
		$userType = (string) $_SESSION['UserType'];
		if (in_array($userType, ['Admin', 'Super Admin'], true)) {
			return true;
		}
	}
	if (!isset($roles['EmployeeRoles']) || !is_array($roles['EmployeeRoles'])) {
		return false;
	}
	$allowed = ['HR', 'Super Admin', 'Admin'];
	foreach ($roles['EmployeeRoles'] as $role) {
		if (in_array($role, $allowed, true)) {
			return true;
		}
	}
	return false;
}

function hr_ticket_user_can_raise($session)
{
	return hr_ticket_session_employee_id($session) > 0;
}

function hr_ticket_user_can_view($session, $ticket)
{
	if (!is_array($ticket)) {
		return false;
	}
	$employeeId = hr_ticket_session_employee_id($session);
	if ($employeeId > 0 && (int) ($ticket['employee_id'] ?? 0) === $employeeId) {
		return true;
	}
	return hasHrTicketManageAccess($session['Roles'] ?? []);
}

function hr_ticket_user_can_manage($session, $ticket)
{
	if (!is_array($ticket)) {
		return false;
	}
	return hasHrTicketManageAccess($session['Roles'] ?? []);
}

function hr_ticket_hr_representative_list($conn)
{
	$rows = [];
	$sql = "SELECT DISTINCT e.ID, e.Name
		FROM user_roles ur
		INNER JOIN employees e ON e.ID = ur.EmployeeID
		WHERE e.IsActive = 1 AND ur.IsActive = 1 AND ur.Role IN ('HR', 'Admin', 'Super Admin')
		ORDER BY e.Name ASC";
	if ($res = mysqli_query($conn, $sql)) {
		while ($r = mysqli_fetch_assoc($res)) {
			$rows[] = $r;
		}
	}
	if (empty($rows)) {
		$rows = _getTableRecords($conn, 'employees', " WHERE IsActive = 1 ORDER BY Name ASC LIMIT 50");
	}
	return $rows;
}

function hr_ticket_hr_employee_ids_for_notification($conn)
{
	$ids = [];
	$sql = "SELECT DISTINCT ur.EmployeeID
		FROM user_roles ur
		INNER JOIN employees e ON e.ID = ur.EmployeeID
		WHERE e.IsActive = 1 AND ur.IsActive = 1 AND ur.Role IN ('HR', 'Admin', 'Super Admin')";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		while ($row = mysqli_fetch_assoc($result)) {
			$ids[] = (int) $row['EmployeeID'];
		}
	}
	return array_values(array_unique($ids));
}

function hr_ticket_status_options()
{
	return Hrticket::statusOptions();
}

function hr_ticket_category_options()
{
	return Hrticket::categoryOptions();
}

function hr_ticket_status_badge($status)
{
	$status = strtolower(trim((string) $status));
	$labels = [
		'open' => 'Open',
		'in_progress' => 'In Progress',
		'pending_employee_response' => 'Pending Employee Response',
		'on_hold' => 'On Hold',
		'resolved' => 'Resolved',
		'closed' => 'Closed',
	];
	$label = $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
	$slug = preg_replace('/[^a-z0-9_]/', '', str_replace('-', '_', $status));
	if ($slug === '') {
		$slug = 'open';
	}
	return '<span class="hr-badge hr-badge--' . htmlspecialchars($slug) . '">' . htmlspecialchars($label) . '</span>';
}

function hr_ticket_category_badge($category)
{
	$category = strtolower(trim((string) $category));
	$labels = [
		'general' => 'General',
		'payment_related' => 'Payment Related',
		'benefits' => 'Benefits',
	];
	$label = $labels[$category] ?? ucwords(str_replace('_', ' ', $category));
	return '<span class="hr-badge hr-badge--cat-' . htmlspecialchars($category) . '">' . htmlspecialchars($label) . '</span>';
}

function hr_ticket_history_badge($actionType)
{
	$map = [
		'raised' => ['raised_hist', 'Raised'],
		'assigned' => ['assigned_hist', 'Assigned'],
		'reassigned' => ['reassigned_hist', 'Reassigned'],
		'status_changed' => ['status_changed_hist', 'Status changed'],
		'comment_added' => ['comment_hist', 'Comment'],
		'attachment_added' => ['attach_hist', 'Attachment'],
	];
	$actionType = strtolower(trim((string) $actionType));
	$pair = $map[$actionType] ?? ['comment_hist', ucwords(str_replace('_', ' ', $actionType))];
	return '<span class="hr-badge hr-badge--' . htmlspecialchars($pair[0]) . '">' . htmlspecialchars($pair[1]) . '</span>';
}

function hr_ticket_notify_employees($conn, array $employeeIds, $title, $body, array $payload, $createdBy)
{
	$employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
	if (empty($employeeIds)) {
		return;
	}
	foreach ($employeeIds as $empId) {
		if ($empId < 1) {
			continue;
		}
		pnc_publishNotification($conn, [
			'target' => 'employee',
			'employee_id' => $empId,
			'title' => $title,
			'body' => $body,
			'payload' => $payload,
			'send_push' => true,
			'save_portal' => true,
			'created_by' => $createdBy,
		]);
	}
}

function hr_ticket_notify_on_created($conn, $ticketId, $createdBy)
{
	$hr = new Hrticket($conn);
	$ticket = $hr->getTicketById((int) $ticketId);
	if (!$ticket) {
		return;
	}
	$code = $ticket['ticket_code'] ?? '';
	$category = $hr->categoryLabel($ticket['category'] ?? 'general');
	$subject = $ticket['subject'] ?? '';
	$employeeName = $ticket['employee_name'] ?? 'Employee';

	$hrIds = hr_ticket_hr_employee_ids_for_notification($conn);
	$title = 'New HR Ticket — ' . $code;
	$body = $employeeName . ' raised a ' . $category . ' ticket: ' . $subject;
	$payload = [
		'module' => 'hr_ticket',
		'screen' => 'hr_ticket_manage',
		'ticket_id' => (int) $ticketId,
		'ticket_code' => $code,
	];
	hr_ticket_notify_employees($conn, $hrIds, $title, $body, $payload, $createdBy);
}

function hr_ticket_notify_on_update($conn, $ticket, $oldStatus, $newStatus, $oldAssigned, $newAssigned, $updatedBy)
{
	if (!is_array($ticket)) {
		return;
	}
	$hr = new Hrticket($conn);
	$code = $ticket['ticket_code'] ?? '';
	$ticketId = (int) ($ticket['id'] ?? 0);
	$employeeId = (int) ($ticket['employee_id'] ?? 0);
	$payload = [
		'module' => 'hr_ticket',
		'screen' => 'hr_ticket',
		'ticket_id' => $ticketId,
		'ticket_code' => $code,
	];

	if ($oldStatus !== $newStatus) {
		$title = 'HR Ticket Update — ' . $code;
		$body = 'Status changed to ' . $hr->statusLabel($newStatus) . '.';
		hr_ticket_notify_employees($conn, [$employeeId], $title, $body, $payload, $updatedBy);
	}

	if ($newAssigned > 0 && $newAssigned !== $oldAssigned) {
		$toName = $ticket['employee_name'] ?? 'Employee';
		$title = 'HR Ticket Assigned — ' . $code;
		$body = 'You have been assigned ticket ' . $code . ' from ' . $toName . '.';
		hr_ticket_notify_employees($conn, [$newAssigned], $title, $body, array_merge($payload, ['screen' => 'hr_ticket_manage']), $updatedBy);
	}
}

function hr_ticket_notify_on_comment($conn, $ticket, $authorRole, $commentPreview, $authorUsername)
{
	if (!is_array($ticket)) {
		return;
	}
	$code = $ticket['ticket_code'] ?? '';
	$ticketId = (int) ($ticket['id'] ?? 0);
	$employeeId = (int) ($ticket['employee_id'] ?? 0);
	$assignedTo = (int) ($ticket['assigned_to'] ?? 0);
	$payload = [
		'module' => 'hr_ticket',
		'ticket_id' => $ticketId,
		'ticket_code' => $code,
	];

	if ($authorRole === 'hr') {
		$title = 'HR Response — ' . $code;
		$body = 'HR replied on your ticket: ' . $commentPreview;
		$payload['screen'] = 'hr_ticket';
		hr_ticket_notify_employees($conn, [$employeeId], $title, $body, $payload, $authorUsername);
		return;
	}

	$title = 'Employee Reply — ' . $code;
	$body = ($ticket['employee_name'] ?? 'Employee') . ' replied: ' . $commentPreview;
	$payload['screen'] = 'hr_ticket_manage';
	$targets = $assignedTo > 0 ? [$assignedTo] : hr_ticket_hr_employee_ids_for_notification($conn);
	hr_ticket_notify_employees($conn, $targets, $title, $body, $payload, $authorUsername);
}

function hr_ticket_attachment_url($fileName)
{
	return 'download-attachment?f=' . rawurlencode((string) $fileName);
}

function hr_ticket_require_employee_access()
{
	if (!hr_ticket_user_can_raise($_SESSION)) {
		header('Location: ../authentication/login.php');
		exit;
	}
}

function hr_ticket_require_manage_access()
{
	if (!hasHrTicketManageAccess($_SESSION['Roles'] ?? [])) {
		header('Location: ' . hr_ticket_my_list_url());
		exit;
	}
}
